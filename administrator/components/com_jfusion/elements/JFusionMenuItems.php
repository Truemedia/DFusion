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
* Defines a select list for jfusion menu items
* @package JFusion
*/
class JElementJFusionMenuItems extends JElement
{
    var $_name = "JFusionMenuItems";

    function fetchElement($name, $value, &$node, $control_name)
    {
    	JPlugin::loadLanguage( 'com_jfusion' );
    	
        $db = & JFactory::getDBO();
        $query = 'SELECT id, name FROM #__menu WHERE type = \'component\' AND link LIKE \'%com_jfusion%\' ORDER BY name';
        $db->setQuery( $query );
        $rows = $db->loadObjectList();
        
        if(!empty($rows)) {
        	$row = new stdClass();
        	$row->id = '';
        	$row->name = '';
        	array_unshift($rows,$row);  
			return JHTML::_('select.genericlist', $rows, $control_name.'['.$name.']', '','id', 'name', $value);
        } else {
        	$return = "<input type=hidden id = '{$control_name}{$name}' name='{$control_name}[{$name}]' value=''>" . JText::_('NO_MENU_ITEMS');
			return $return; 
        }
    }
}
?>