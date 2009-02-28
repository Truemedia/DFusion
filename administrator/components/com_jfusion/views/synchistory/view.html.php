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

class jfusionViewsynchistory extends JView {

    function display($tpl = null)
    {
        //prepare the toolbar
        $bar =& new JToolBar('My Toolbar' );
        $bar->appendButton('Standard', 'delete', 'Delete Record', 'deletehistory', false, false );
        $bar->appendButton('Standard', 'forward', 'Resolve Error', 'resolveerror', false, false );
        $toolbar = $bar->render();

        //get the all usersync data
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion_sync ORDER BY time_end DESC';
        $db->setQuery($query );
        $rows = $db->loadObjectList();

        $this->assignRef('rows', $rows);
      	$this->assignRef('toolbar', $toolbar);
        parent::display($tpl);
    }
}