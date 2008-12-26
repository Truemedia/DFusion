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
        $menuitemid = JRequest::getInt('Itemid' );
        if ($menuitemid) {
            $db =& JFactory::getDBO();
            $query = 'SELECT params from #__menu WHERE id = ' . $menuitemid;
            $menu_data = $db->loadResult();
            $db->setQuery($query);
            $params = $db->loadResult();
            $menu_param = new JParameter($params, '');
            $jview = $menu_param->get('visual_integration');
            $jname = $menu_param->get('JFusionPlugin');
        } else {
            $jview = JRequest::getVar('view');
            $jname = JRequest::getVar('jname');
        }

        if ($jview) {

            //check to see if the plugin is configured properly
            $db =& JFactory::getDBO();
            $query = 'SELECT status from #__jfusion WHERE name = ' . $db->quote($jname);
            $db->setQuery($query );

            if ($db->loadResult() != 3) {
                //die gracefully as the plugin is not configured properly
                echo JText::_('ERROR_PLUGIN_CONFIG');
	            $result = false;
    	        return $result;
            } else {

                $view = &$this->getView($jview, 'html');
                $view->assignRef('jname', $jname);
                $view->addTemplatePath(JPATH_COMPONENT . DS . 'view'.DS.strtolower($jview).DS.'tmpl');
                $view->setLayout('default');
                $view->display();
            }

        } else {
        	//make one final attempt to get a default view from the joomla_int plugin
			$params = JFusionFactory::getParams('joomla_int');
			$default_plugin = $params->get('default_plugin');
			$link_mode = $params->get('link_mode');
			if(empty($link_mode) || empty($default_plugin)){
	            echo JText::_('NO_VIEW_SELECTED');
    	        $result = false;
        	    return $result;
			} else {
                $view = &$this->getView($jview, 'html');
                $view->assignRef('jname', $default_plugin);
                $view->addTemplatePath(JPATH_COMPONENT . DS . 'view'.DS.strtolower($link_mode).DS.'tmpl');
                $view->setLayout('default');
                $view->display();
			}
        }
    }
}

