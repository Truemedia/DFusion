<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC' ) or die('Restricted access' );
/**
* Load the JFusion framework
*/
jimport('joomla.application.component.view');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');


/**
* Renders the main admin screen that shows the configuration overview of all integrations
* @package JFusion
*/

class jfusionViewWrapper extends JView {

    function display($tpl = null)
    {

        $db =& JFactory::getDBO();

        //get the forum url
        $wrap = urldecode(JRequest::getVar('wrap', '', 'get'));
        $jname = urldecode(JRequest::getVar('jname', '', 'get'));

        if(!$jname) {
        	//no plugin defined, therefore get the default plugin
			$menuitemid = JRequest::getInt( 'Itemid' );
	    	$query = 'SELECT params from #__menu WHERE id = ' . $menuitemid;
    		$menu_data = $db->loadResult();
    	    $db->setQuery($query);
    	    $params = $db->loadResult();
            $menu_param = new JParameter($params, '');
            $jname =  $menu_param->get('JFusionPlugin');
            if (!jname){
            	//die gracefully as no plugin name was defined
				echo JText::_('ERROR_NO_PLUGIN');
                return false;
            }
        } else {
            	//fetch the general wrapper settings from joomla_int
        		$menu_param  = JFusionFactory::getParams('joomla_int');
        }

        //check to see if the plugin is configured properly
        $db =& JFactory::getDBO();
    	$query = 'SELECT status from #__jfusion WHERE name = ' . $db->quote($jname);
    	$db->setQuery($query );

    	if ($db->loadResult() != 3) {
            	//die gracefully as the plugin is not configured properly
				echo JText::_('ERROR_PLUGIN_CONFIG');
                return false;
    	}

		/**
		* load the JFusion framework
		*/
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');


        //get the URL to the forum
        $params2 = JFusionFactory::getParams($jname);
        $source_url = $params2->get('source_url');

        //check to see if url starts with http
		if (substr($source_url, 0, 7) != 'http://' && substr($source_url, 0, 8) != 'https://') {
            $source_url = 'http://' . $source_url;
        }

        //check for trailing slash
        if (substr($source_url, -1) == '/') {
            $url = $source_url . $wrap;
        } else {
            $url = $source_url . '/'. $wrap;
        }
        ;

        //print out results to user
        $this->assignRef('url', $url);
        $this->assignRef('params', $menu_param);
        $this->assignRef('jname', $jname);
        parent::display($tpl);
    }

}





