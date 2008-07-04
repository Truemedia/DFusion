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
class JElementForumlist extends JElement
{
    var $_name = "forumlist";

    function fetchElement($name, $value, &$node, $control_name)
    {
        //find out which JFusion plugin is used in the activity module
        $db = & JFactory::getDBO();
        $query = 'SELECT params FROM #__modules  WHERE module = \'mod_jfusion_activity\'';
        $db->setQuery( $query );
        $params = $db->loadResult();
        $parametersInstance = new JParameter($params, '' );

        $jname = $parametersInstance->get('JFusionPlugins');

        if ($jname) {

            $JFusionPlugin = JFusionFactory::getPlugin($jname);
            $forumlist = $JFusionPlugin->getForumList();

            if (!empty($forumlist)) {
                return JHTML::_('select.genericlist', $forumlist, $control_name.'['.$name.'][]', 'multiple size="6" class="inputbox"',
                'id', 'name', $value);
            } else {
                return JText::_('NO_LIST');
            }
        } else {
            return JText::_('NO_PLUGIN_SELECT');
        }
    }
}