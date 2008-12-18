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
        //get the all usersync data
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion_sync';
        $db->setQuery($query );
        $rows = $db->loadObjectList();

        if ($rows) {
            //print out results to user
            $this->assignRef('rows', $rows);
            parent::display($tpl);
        } else {
            JError::raiseWarning(500, JText::_('NO_USERSYNC_DATA'));
        }
    }

}
?>



