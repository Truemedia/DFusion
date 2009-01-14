<?php
/**
 * @package JFusion
 * @subpackage Models
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
				$result = false;
                return $result;
				break;
		}

		// Was the package unpacked?
		if (!$package) {
			$this->setState('message', JText::_('NO_PACKAGE_FOUND') );
            $result = false;
            return $result;
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
		$this->setState('message', $installer->parent->message);
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

	function copy($jname, $new_jname, $update = false) {
		$db =& JFactory::getDBO();
		$db->setQuery('SELECT id FROM #__jfusion WHERE name ='. $db->Quote($jname));
		$myId = $db->loadResult();
		if (!$myId) {
			;// error!! plugin not installed (hack attempt?)
		}

		$installer = new JfusionPluginInstaller($this);
		// Install the package
		if (!$installer->copy($jname, $new_jname, $update)) {
			// There was an error installing the package
			$msg = 'JFusion ' . JText::_('PLUGIN') . ' ' . JText::_('COPY') . ' ' . JText::_('FAILED');
			$result = false;
		} else {
			// Package installed sucessfully
			$msg = 'JFusion ' . JText::_('PLUGIN') . ' ' . JText::_('COPY') . ' ' . JText::_('SUCCESS');
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
            $result = false;
            return $result;
		}

		// Get the extension manifest object
		$manifest = $this->_getManifest($dir);
		if(is_null($manifest)) {
			$this->parent->abort(JText::_('INSTALL_NOT_VALID_PLUGIN'));
            $result = false;
            return $result;
		}

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
            	$result = false;
            	return $result;
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
            $result = false;
            return $result;
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
            $result = false;
            return $result;
		}
		$id = $db->loadResult();

		// Was there a module already installed with the same name?
		if ($id) {
			if (!$this->parent->getOverwrite()) {
				// Install failed, roll back changes
				$this->parent->abort(JText::_('PLUGIN').' '.JText::_('Install').': '.JText::_('PLUGIN').' "'.$name.'" '.JText::_('ALREADY_EXISTS'));
	            $result = false;
    	        return $result;
			} else {
				//update the plugin files in the database
				$plugin_files = $this->backup($name);
				$db->setQuery("UPDATE #__jfusion SET plugin_files = '$plugin_files' WHERE id = $id");
				$db->Query();
			}
		} else {
			//get some more details
			$dual_login = & $this->manifest->getElementByPath('dual_login');
			$slave = & $this->manifest->getElementByPath('slave');
			$activity = & $this->manifest->getElementByPath('activity');

            //prepare the variables
            $plugin_entry = new stdClass;
            $plugin_entry->id = NULL;
            $plugin_entry->name = $name;
			$plugin_entry->dual_login = JFilterInput::clean($dual_login->data(), 'integer');
			$plugin_entry->slave = JFilterInput::clean($slave->data(), 'integer');
			$plugin_entry->activity = JFilterInput::clean($activity->data(), 'integer');
			$plugin_entry->plugin_files = $this->backup($name);

            //now append the new plugin data
			if (!$db->insertObject('#__jfusion', $plugin_entry, 'id' )){
		        // Install failed, roll back changes
		        $this->parent->abort(JText::_('PLUGIN').' '.JText::_('INSTALL').' '.JText::_('ERROR').': ' . $db->stderr());
		        return false;
			}
			$this->parent->pushStep(array ('type' => 'plugin', 'id' => $plugin_entry->id));
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
            $result = false;
            return $result;
		}

		//check to see if this is updating a plugin that has been copied
		$query = "SELECT name FROM #__jfusion WHERE original_name = {$db->Quote($name)}";
		$db->setQuery($query);
		$copiedPlugins = $db->loadResultArray();

		foreach($copiedPlugins as $plugin){
			//update the copied version with the new files
			$this->copy($name,$plugin->jname,true);
		}

        $result = true;
        return $result;
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
            $result = false;
            return $result;
		}
		// remove files
		if (!JFolder::delete($dir)) {
			$this->parent->abort(JText::_('UNINSTALL_ERROR_DELETE'));
            $result = false;
            return $result;
		}

		$db =& JFactory::getDBO();

		// delete raw
		$db->setQuery('DELETE FROM #__jfusion WHERE name = '. $db->Quote($jname));
		if(!$db->Query()) {
			$this->parent->abort(JText::_('Owned!'));
		}

	}

	/**
	 * handles copying JFusion plugins
     * @param string $jname name of the JFusion plugin used
     * @param string $new_jname name of the copied plugin
     * @param boolean $update mark if we updating a copied plugin
	 * @return boolean
	 */
	function copy($jname, $new_jname, $update = false) {

		$dir = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname;
		$new_dir = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $new_jname;

		if (!$jname || !JFolder::exists($dir)) {
			$this->parent->abort(JText::_('COPY_ERROR_PATH'));
            $result = false;
            return $result;
		}

		//copy the files
		if (!JFolder::copy($dir, $new_dir, null, $update)) {
			$this->parent->abort(JText::_('COPY_ERROR'));
            $result = false;
            return $result;
		}

		// Define our preg arrays
		$regex		= array();
		$replace	= array();

		//change the classname
		$regex[]	= '#JFusion(Auth|User|Forum|Public|Admin)_' . $jname .'#ms';
		$replace[]	= 'JFusion$1_' . $new_jname;

		//change the jname function
		$regex[]	= '#return \'' . $jname .'\';#ms';
		$replace[]	= 'return \'' . $new_jname .'\';';

		//update the XML name tag
		$regex[]	= '#<name>' . $jname .'</name>#ms';
		$replace[]	= '<name>' . $new_jname .'</name>';


		//define which files need parsing
		$parse_files = array($new_dir . DS . 'auth.php', $new_dir . DS . 'admin.php', $new_dir . DS . 'user.php', $new_dir . DS . 'jfusion.xml', $new_dir . DS . 'forum.php', $new_dir . DS . 'public.php');

		foreach ($parse_files as $parse_file) {
			if(file_exists($parse_file)){
				$file_data = JFile::read($parse_file);
				$file_data = preg_replace($regex, $replace, $file_data);
        		JFile::write($parse_file, $file_data);
			}
		}

		$db =& JFactory::getDBO();
		if($update) {
			//update the copied plugin files
			$plugin_files = $this->backup($new_jname);
			$query = "UPDATE #__jfusion SET plugin_files = '$plugin_files' WHERE jname = '$new_jname'";
			$db->setQuery($query);
			$db->Query();
		} else {
			//add the new entry in the JFusion plugin table
			$db->setQuery('SELECT * FROM #__jfusion WHERE name = '.$db->Quote($jname));
			$plugin_entry = $db->loadObject();
			$plugin_entry->name = $new_jname;
			$plugin_entry->id = NULL;
			$plugin_entry->plugin_files = $this->backup($new_jname);

			//only change the original name if this is not a copy itself
			if(empty($plugin_entry->original_name)) {
				$plugin_entry->original_name = $jname;
			}

	        if (!$db->insertObject('#__jfusion', $plugin_entry, 'id' )) {
	            //return the error
	            $this->parent->abort('Error while creating the plugin: ' . $db->stderr());
	        }
		}

        $result = true;
        return $result;
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

	/**
	 * handles JFusion plugin backups
     * @param string $jname name of the JFusion plugin used
	 * @return backup zip file data or location
	 */
	function backup($jname)
	{
		$config =& JFactory::getConfig();
		$tmpDir =& $config->getValue('config.tmp_path');

		//compress the files
		$filename = $tmpDir.DS."$jname.zip";
		//retrieve a list of files within the plugin directory
		$files = JFolder::files(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$jname,null,false,true);

		//compress the plugin
		JArchive::create($filename, $files, 'zip');

		//now get the contents of the compressed file to return
		$data = addslashes(file_get_contents($filename));

		return $data;
	}
}