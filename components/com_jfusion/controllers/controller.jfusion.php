<?php
/**
* @package JFusion
* @subpackage Controller
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Load the JFusion framework
*/
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
            $jview =  $menu_param->get('visual_integration');
        } else {
            $jview = JRequest::getVar('view');
        }

        if ($jview) {
            JRequest::setVar('view', $jview);
            parent::display();
        } else {
            echo JText::_('NO_VIEW_SELECTED');
            return false;
        }
    }
}

