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
* Defines the forum select list for JFusion forum plugins
* @package JFusion
*/
    class JElementJFusionPlugins extends JElement
    {
        var $_name = "JFusionActivePlugins";

        function fetchElement($name, $value, &$node, $control_name)
        {
            $db = & JFactory::getDBO();
            $query = 'SELECT name as id, name as name from #__jfusion WHERE status = 3';
            $db->setQuery($query );
            $rows = $db->loadObjectList();

            if (!empty($rows)) {
                return JHTML::_('select.genericlist', $rows, $control_name.'['.$name.'][]', 'size="1" class="inputbox"',
                'id', 'name', $value);
        	} else {
                return JText::_('NO_VALID_PLUGINS');
        	}
    	}
	}



