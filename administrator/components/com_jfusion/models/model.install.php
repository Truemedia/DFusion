<?php
/**
 * @package JFusion
 * @subpackage Models
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 /**
 * Require the Joomla Installer model
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_installer'.DS.'models'.DS.'install.php');

/**
 * Class to manage plugin install in JFusion
 * @package JFusion
 */
class JFusionModelInstaller extends InstallerModelInstall {

	/** @var object JTable object */
	var $_table = null;

	/** @var object JTable object */
	var $_url = null;

	/**
	 * Overridden constructor
	 * @access	protected
	 */
	function __construct()
	{
		parent::__construct();

	}

	/**
	 * Replaces original Install() method.
	 * @return true|false Result of the JFusion plugin install
	 */
	function install() {
		global $mainframe;

		$this->setState('action', 'install');

		switch(JRequest::getWord('installtype'))
		{
			case 'folder':
				$package = $this->_getPackageFromFolder();
				break;

			case 'upload':
				$package = $this->_getPackageFromUpload();
				break;

			case 'url':
				$package = $this->_getPackageFromUrl();
				break;

			default:
				$this->setState('message', JText::_('NO_INSTALL_TYPE'));
				return false;
				break;
		}

		// Was the package unpacked?
		if (!$package) {
			$this->setState('message', JText::_('NO_PACKAGE_FOUND') );
			return false;
		}

		// custom installer
		$installer = new JfusionPluginInstaller($this);
		// Install the package
		if (!$installer->install($package['dir'])) {
			// There was an error installing the package
			$msg = 'JFusion ' . JText::_('PLUGIN') . ' ' . JText::_('INSTALL') . ' ' . JText::_('FAILED');
			$result = false;
		} else {
			// Package installed sucessfully
			$msg = 'JFusion ' . JText::_('PLUGIN') . ' ' . JText::_('INSTALL') . ' ' . JText::_('SUCCESS');
			$result = true;
		}
		// Set some model state values
		$mainframe->enqueueMessage($msg);
		$this->setState('name', $installer->get('name'));
		$this->setState('result', $result);
		$this->setState('message', $installer->message);
		$this->setState('extension.message', $installer->get('extension.message'));

		// Cleanup the install files
		if (!is_file($package['packagefile'])) {
			$config =& JFactory::getConfig();
			$package['packagefile'] = $config->getValue('config.tmp_path').DS.$package['packagefile'];
		}

		JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		return $result;


	}

/**
 * Installer class for JFusion plugins
 * @param string $jname name of the JFusion plugin used
 */
	function uninstall($jname) {
		$db =& JFactory::getDBO();
		$db->setQuery('SELECT id FROM #__jfusion WHERE name ='. $db->Quote($jname));
		$myId = $db->loadResult();
		if (!$myId) {
			;// error!! plugin not installed (hack attempt?)
		}

		$installer = new JfusionPluginInstaller($this);
		// Install the package
		if (!$installer->uninstall($jname)) {
			// There was an error installing the package
			$msg = 'JFusion ' . JText::_('PLUGIN') . ' ' . JText::_('UNINSTALL') . ' ' . JText::_('FAILED');
			$result = false;
		} else {
			// Package installed sucessfully
			$msg = 'JFusion ' . JText::_('PLUGIN') . ' ' . JText::_('UNINSTALL') . ' ' . JText::_('SUCCESS');
			$result = true;
		}

		return $result;

	}
}

/**
 * Installer class for JFusion plugins
 * @package JFusion
 */
class JFusionPluginInstaller extends JObject {

	function __construct(&$parent) {

		$this->parent =& JInstaller::getInstance();
		$this->parent->setOverwrite(true);
	}

	/**
	 * handles JFusion plugin installation
     * @param string $dir install path
	 * @return boolean
	 */
	function install($dir=null) {
		// Get a database connector object
		$db =& JFactory::getDBO();

		if ($dir && JFolder::exists($dir)) {
			$this->parent->setPath('source', $dir);

		} else {
			$this->parent->abort(JText::_('INSTALL_INVALID_PATH'));
			return false;
		}

		// Get the extension manifest object
		$manifest = $this->_getManifest($dir);
		$this->manifest =& $manifest->document;
		//var_dump($this->manifest);

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Manifest Document Setup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// Set the extensions name
		$name =& $this->manifest->getElementByPath('name');
		$name = JFilterInput::clean($name->data(), 'string');
		$this->set('name', $name);


		// Get the component description
		$description = & $this->manifest->getElementByPath('description');
		if (is_a($description, 'JSimpleXMLElement')) {
			$this->parent->set('message', $description->data());
		} else {
			$this->parent->set('message', '' );
		}
		$myDesc = JFilterInput::clean($description->data(), 'string');

		// installation path
		$this->parent->setPath('extension_root', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $name);

		// get files to copy
		$element =& $this->manifest->getElementByPath('files');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children())) {
			$files =& $element->children();
			foreach ($files as $file) {
				if ($file->attributes($type)) {
					$pname = $file->attributes($type);
					break;
				}
			}
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Filesystem Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */
		// If the plugin directory does not exist, lets create it
		$created = false;
		if (!file_exists($this->parent->getPath('extension_root'))) {
			if (!$created = JFolder::create($this->parent->getPath('extension_root'))) {
				$this->parent->abort(JText::_('PLUGIN').' '.JText::_('INSTALL').': '.JText::_('INSTALL_FAILED_DIRECTORY').': "'.$this->parent->getPath('extension_root').'"');
				return false;
			}
		}

		/*
		 * If we created the plugin directory and will want to remove it if we
		 * have to roll back the installation, lets add it to the installation
		 * step stack
		 */
		if ($created) {
			$this->parent->pushStep(array ('type' => 'folder', 'path' => $this->parent->getPath('extension_root')));
		}

		// Copy all necessary files
		if ($this->parent->parseFiles($element, -1) === false) {
			// Install failed, roll back changes
			$this->parent->abort();
			return false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Database Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */
		$db->setQuery('SELECT id FROM #__jfusion WHERE name = '.$db->Quote($name));
		if (!$db->Query()) {
			// Install failed, roll back changes
			$this->parent->abort(JText::_('PLUGIN').' '.JText::_('INSTALL').' '.JText::_('ERROR').': '.$db->stderr(true));
			return false;
		}
		$id = $db->loadResult();

		// Was there a module already installed with the same name?
		if ($id) {

			if (!$this->parent->getOverwrite())
			{
				// Install failed, roll back changes
				$this->parent->abort(JText::_('PLUGIN').' '.JText::_('Install').': '.JText::_('PLUGIN').' "'.$pname.'" '.JText::_('ALREADY_EXISTS'));
				return false;
			}

		} else {

			//get some more details
		$version = & $this->manifest->getElementByPath('version');
		$creationDate = & $this->manifest->getElementByPath('creationdate');
		$author = & $this->manifest->getElementByPath('author');
		$support = & $this->manifest->getElementByPath('authorurl');
		$dual_login = & $this->manifest->getElementByPath('dual_login');
		$slave = & $this->manifest->getElementByPath('slave');

            //prepare the variables
            $plugin_entry = new stdClass;
            $plugin_entry->id = NULL;
            $plugin_entry->name = $name;
			$plugin_entry->description = $myDesc;
			$plugin_entry->version = JFilterInput::clean($version->data(), 'string');
			$plugin_entry->date = JFilterInput::clean($creationDate->data(), 'string');
			$plugin_entry->author = JFilterInput::clean($author->data(), 'string');
			$plugin_entry->support = JFilterInput::clean($support->data(), 'string');
			$plugin_entry->dual_login = JFilterInput::clean($dual_login->data(), 'integer');
			$plugin_entry->slave = JFilterInput::clean($slave->data(), 'integer');


            //now append the new plugin data
			if (!$db->insertObject('#__jfusion', $plugin_entry, 'id' )) echo 'OWNED'. $db->getQuery();
			$this->parent->pushStep(array ('type' => 'plugin', 'id' => $row->id));
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Finalization and Cleanup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// Lastly, we will copy the manifest file to its appropriate place.
		if (!$this->parent->copyManifest(-1)) {
			// Install failed, rollback changes
			$this->parent->abort(JText::_('PLUGIN').' '.JText::_('INSTALL').': '.JText::_('INSTALL_ERROR_FILE'));
			return false;
		}
		return true;
	}

	/**
	 * handles JFusion plugin un-installation
     * @param string $jname name of the JFusion plugin used
	 * @return boolean
	 */
	function uninstall($jname) {
		$dir = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname;

		if (!$jname || !JFolder::exists($dir)) {
			$this->parent->abort(JText::_('UNINSTALL_ERROR_PATH'));
			return false;
		}
		// remove files
		if (!JFolder::delete($dir)) {
			$this->parent->abort(JText::_('UNINSTALL_ERROR_DELETE'));
			return false;
		}

		$db =& JFactory::getDBO();

		// delete raw
		$db->setQuery('DELETE FROM #__jfusion WHERE name = '. $db->Quote($jname));
		if(!$db->Query()) {
			$this->parent->abort(JText::_('Owned!'));
		}

	}

	/**
	 * load manifest file with installation information
	 * @return simpleXML object (or null)
	 * @param $dir string - Directory
	 */
	function _getManifest($dir) {
		// Initialize variables
		$null	= null;
		$xml	=& JFactory::getXMLParser('Simple');

		// TODO: DISCUSS if we should allow flexible naming for installation file
		$this->parent->setPath('manifest', $dir. DS .'jfusion.xml');
		// If we cannot load the xml file return null
		if (!$xml->loadFile($dir.DS.'jfusion.xml')) {
			// Free up xml parser memory and return null
			unset ($xml);
			return $null;
		}

		/*
		 * Check for a valid XML root tag.
		 * @todo: Remove backwards compatability in a future version
		 * Should be 'install', but for backward compatability we will accept 'mosinstall'.
		 */
		$root =& $xml->document;
		if (!is_object($root) || ($root->name() != 'install' && $root->name() != 'mosinstall')) {
			// Free up xml parser memory and return null
			unset ($xml);
			return $null;
		}

		// Valid manifest file return the object
		return $xml;


	}


}
