<?php
/**
* @package JFusion
* @subpackage Elements
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
* Require the Jfusion plugin factory
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');


/**
* Defines the forum select list for JFusion forum plugins
* @package JFusion
*/
class JElementForumlist extends JElement
{
    var $_name = "forumlist";

    function fetchElement($name, $value, &$node, $control_name)
    {
		//Query current selected Module Id
		$id     = JRequest::getVar( 'id', 0, 'method', 'int' );
		$cid    = JRequest::getVar( 'cid', array( $id ), 'method', 'array' );
		JArrayHelper::toInteger($cid, array(0));

        //find out which JFusion plugin is used in the activity module
        $db = & JFactory::getDBO();
        $query = 'SELECT params FROM #__modules  WHERE module = \'mod_jfusion_activity\' and id = '.$db->Quote($cid[0]);

        $db->setQuery( $query );
        $params = $db->loadResult();
        $parametersInstance = new JParameter($params, '' );

		//load custom plugin parameter
		$jPluginParam = new JParameter('');
		$jPluginParamRaw = unserialize(base64_decode($parametersInstance->get('JFusionPluginParam')));
		$output = "";
		
		$jname = $jPluginParamRaw['jfusionplugin'];
		if(!empty($jname)) {
			if (JFusionFunction::validPlugin($jname)) {
				$output .= "<b>".$jname . "</b><br />\n";
	            $JFusionPlugin = JFusionFactory::getForum($jname);
	            if (method_exists($JFusionPlugin,'getForumList')){
		            $forumlist = $JFusionPlugin->getForumList();
	    	        if (!empty($forumlist)) {
	    	        	$selectedValue = $parametersInstance->get($name.'_'.$jname);
	        	        $output .= JHTML::_('select.genericlist', $forumlist, $control_name.'['.$name.'_'.$jname.'][]', 'multiple size="6" class="inputbox"',
	            	    'id', 'name', $selectedValue);
		            } else {
	    	            $output .= $jname . ': ' . JText::_('NO_LIST');
	        	    }
				} else {
	    	        $output .= $jname . ': ' . JText::_('NO_LIST');
				}
				$output .= "<br />\n";
	        } else {
	            $output .= $jname . ": " . JText::_('NO_VALID_PLUGIN') . "<br />";
	        }
		} else {
			$output .= JText::_('NO_PLUGIN_SELECT');
		}
		
		return $output;
    }
}