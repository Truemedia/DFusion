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
class JElementForumListDiscussionbot extends JElement
{
    var $_name = "ForumListDiscussionbot";

    function fetchElement($name, $value, &$node, $control_name)
    {
     	$db = & JFactory::getDBO();
        $query = 'SELECT params FROM #__plugins  WHERE element = \'jfusion\' and folder = \'content\'';
        $db->setQuery( $query );
        $params = $db->loadResult();      	
		$jPluginParam = new JParameter($params);		
		$jname = $jPluginParam->get('jname',false);
		$output = "";
		
		if($jname!==false) { 
			if (JFusionFunction::validPlugin($jname)) {
	            $JFusionPlugin = JFusionFactory::getForum($jname);
	            if (method_exists($JFusionPlugin,'getForumList')){
		            $forumlist = $JFusionPlugin->getForumList();
	    	        if (!empty($forumlist)) {
	    	        	$selectedValue = $jPluginParam->get($name);
	        	        $output .= JHTML::_('select.genericlist', $forumlist, $control_name.'['.$name.'][]', 'class="inputbox"',
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
