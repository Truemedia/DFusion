<?php
/**
 * @package JFusion
 * @subpackage Controller
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

jimport('joomla.application.component.controller');

/**
 * JFusion Component Controller
 * @package JFusion
 */

class JFusionControllerFrontEnd extends JController
{
	/**
	 * Displays the integrated software inside Joomla without a frame
	 */
	function displayplugin()
	{
		//find out if there is an itemID with the view variable
		$menuitemid = JRequest::getVar('Itemid');
		//we do not want the frontpage menuitem as it will cause a 500 error in some cases
		$jPluginParam = NULL;
		if ($menuitemid && $menuitemid!=1) {
			$db =& JFactory::getDBO();
			$query = 'SELECT params from #__menu WHERE id = ' . $menuitemid;
			$menu_data = $db->loadResult();
			$db->setQuery($query);
			$params = $db->loadResult();
			$menu_param = new JParameter($params, '');
			$jview = $menu_param->get('visual_integration');
			//load custom plugin parameter
			$jPluginParam = new JParameter('');
			$jPluginParam->loadArray(unserialize(base64_decode($menu_param->get('JFusionPluginParam'))));
			$jname = $jPluginParam->get('jfusionplugin');
		} elseif ($menuitemid==1) {
			//if menuitemid is set to frontpage, unset it
			JRequest::setVar('Itemid','');
		} else {
			$jview = JRequest::getVar('view');
			$jname = JRequest::getVar('jname');
		}

		if ($jview) {
			//check to see if the plugin is configured properly
			$db =& JFactory::getDBO();
			$query = 'SELECT status from #__jfusion WHERE name = ' . $db->quote($jname);
			$db->setQuery($query );

			if ($db->loadResult() != 1) {
				//die gracefully as the plugin is not configured properly
				echo JText::_('ERROR_PLUGIN_CONFIG');
				$result = false;
				return $result;
			}
		} else {
			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
			//make one final attempt to get a default view from the joomla_int plugin
			$params = JFusionFactory::getParams('joomla_int');
			$default_plugin = $params->get('default_plugin');
			$jview = $params->get('link_mode');
			if(empty($jview) || empty($default_plugin)){
				echo JText::_('NO_VIEW_SELECTED');
				$result = false;
				return $result;
			}
		}

		//load the view
		$view = &$this->getView($jview, 'html');

		//parse required variables and render output
		if ($jview == 'wrapper') {
			if ($menuitemid){
				//get the wrapper params from the menu item
				$db =& JFactory::getDBO();
				$query = 'SELECT params from #__menu WHERE id = ' . $menuitemid;
				$menu_data = $db->loadResult();
				$db->setQuery($query);
				$params = $db->loadResult();
				$menu_param = new JParameter($params, '');
			} else {
				//fetch the general wrapper settings from joomla_int
				$menu_param  = JFusionFactory::getParams('joomla_int');
			}

			//parse the url
			$wrap = urldecode(JRequest::getVar('wrap', '', 'get'));
			$wrap = base64_decode(str_replace("_slash_","/",$wrap));
			$params2 = JFusionFactory::getParams($jname);
			$source_url = $params2->get('source_url');

			//check for trailing slash
			if (substr($source_url, -1) == '/') {
				$url = $source_url . $wrap;
			} else {
				$url = $source_url . '/'. $wrap;
			}

			//set params
			$view->assignRef('url', $url);
			$view->assignRef('params', $menu_param);
		}

		//render the view
		$view->assignRef('jname', $jname);
		$view->assignRef('jPluginParam', $jPluginParam);
		$view->addTemplatePath(JPATH_COMPONENT . DS . 'view'.DS.strtolower($jview).DS.'tmpl');
		$view->setLayout('default');
		$view->display();
	}
}
