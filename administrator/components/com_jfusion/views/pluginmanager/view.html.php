<?php
/**
* @package JFusion
* @subpackage Views
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
* Renders the main admin screen that shows the configuration overview of all integrations
* @package JFusion
*/

class jfusionViewpluginmanager extends JView {

    function display($tpl = null)
    {
        $bar =& new JToolBar('My Toolbar' );
        $bar->appendButton('Standard', 'delete', JText::_('REMOVE'), 'uninstall_plugin', false, false );
        $bar->appendButton('Standard', 'copy', JText::_('COPY'), 'copy_plugin', false, false );
        $toolbar = $bar->render();

        //get the data about the JFusion plugins except for joomla_int
        $db = & JFactory::getDBO();
        $query = "SELECT id, name from #__jfusion WHERE name != 'joomla_int'";
        $db->setQuery($query );
        $rows = $db->loadObjectList();

        if ($rows) {
            //print out results to user
            $this->assignRef('rows', $rows);
            $this->assignRef('toolbar', $toolbar);
            parent::display($tpl);
        } else {
            JError::raiseWarning(500, JText::_('NO_JFUSION_TABLE'));
        }
    }
}