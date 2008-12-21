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

class jfusionViewsyncoptions extends JView {

    function display($tpl = null)
    {
        //find out what the JFusion master and slaves are
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE master = 1 and status = 3';
        $db->setQuery($query );
        $master = $db->loadObject();

        $query = 'SELECT * from #__jfusion WHERE slave = 1 and status = 3';
        $db->setQuery($query );
        $slaves = $db->loadObjectList();

        //only run the usersync if master and slaves exist
        if ($master && $slaves) {
        	//generate a user sync sessionid
        	jimport('joomla.user.helper');
        	$syncid = JUserHelper::genRandomPassword(10);

            //get the master data
            $JFusionPlugin = JFusionFactory::getAdmin($master->name);
            $master_data['total'] = $JFusionPlugin->getUserCount();
            $master_data['jname'] = $master->name;

            //get the slave data
            foreach($slaves as $slave) {
                $JFusionSlave = JFusionFactory::getAdmin($slave->name);
                $slave_data[$slave->name]['total'] = $JFusionSlave->getUserCount();
                $slave_data[$slave->name]['jname'] = $slave->name;
                unset($JFusionSlave);
            }

            //serialise the data for storage in the usersync table
            $slave_serial = serialize($slave_data);
            $master_serial = serialize($master_data);

            //print out results to user
            $this->assignRef('master_data', $master_data);
            $this->assignRef('slave_data', $slave_data);
            $this->assignRef('syncid', $syncid);
            parent::display($tpl);

        } else {
            JError::raiseWarning(500, JText::_('SYNC_NOCONFIG'));
        }
    }
}

