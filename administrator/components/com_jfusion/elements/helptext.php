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
* Defines the forum select list for JFusion forum plugins
* @package JFusion
*/
class JElementhelptext extends JElement
{
    var $_name = "helptext";

    function fetchElement($name, $value, &$node, $control_name)
    {
        //find out which JFusion plugin is used in the activity module
        return JText::_($value);
    }
}