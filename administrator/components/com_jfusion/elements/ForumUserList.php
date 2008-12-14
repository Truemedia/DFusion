<?php
/**
* @package JFusion
* @subpackage Elements
* @version 1.0.7
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
class JElementForumUserList extends JElement
{
    var $_name = "ForumUserList";

    function fetchElement($name, $value, &$node, $control_name)
    {
        global $jname;
		if ($jname){
        	if (JFusionFunction::validPlugin($jname)) {
            	$JFusionPlugin = JFusionFactory::getPlugin($jname);
            	$users = $JFusionPlugin->getUserList();

            	if (!empty($users)) {
                	return JHTML::_('select.genericlist', $users, $control_name.'['.$name.']', '',
                	'id', 'name', $value);
            	} else {
                	return '';
            	}
        	} else {
                return JText::_('SAVE_CONFIG_FIRST');
        	}
        } else {
            return 'Programming error: You must define global $jname before the JParam object can be rendered';
        }
    }
}