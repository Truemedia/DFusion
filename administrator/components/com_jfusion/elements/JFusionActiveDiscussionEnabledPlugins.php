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
* Defines the plugins that are discussion bot capable
* @package JFusion
*/
    class JElementJFusionActiveDiscussionEnabledPlugins extends JElement
    {
        var $_name = "JFusionActiveDiscussionEnabledPlugins";

        function fetchElement($name, $value, &$node, $control_name)
        {
        	JPlugin::loadLanguage( 'plg_content_jfusion' );
            $db = & JFactory::getDBO();
            $query = 'SELECT name as id, name as name from #__jfusion WHERE status = 1 AND discussion = 1';
            $db->setQuery($query);
            $rows = $db->loadObjectList();

            if (!empty($rows)) {
                return JHTML::_('select.genericlist', $rows, $control_name.'['.$name.'][]', 'size="1" class="inputbox"',
                'id', 'name', $value);
        	} else {
                return JText::_('NO_DISCUSSION_ENABLED_PLUGINS');
        	}
    	}
	}



