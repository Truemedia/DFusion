<?php

/**
* @package JFusion
* @subpackage Models
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Class for general JFusion functions
* @package JFusion
*/

class JFusionUsersync{


    function saveSyncdata($syncdata)
    {
        //serialize the $syncdata to allow storage in a SQL field
        $serialized = base64_encode(serialize($syncdata));

        //find out if the syncid already exists
        $db =& JFactory::getDBO();
        $query = 'SELECT syncid FROM #__jfusion_sync WHERE syncid =' . $db->Quote($syncid);
        $db->setQuery($query);
        if ($db->loadResult()) {
            //run an update statement
            $db =& JFactory::getDBO();
            $query = 'UPDATE #__jfusion_sync SET syncdata = ' . $db->quote($serialized) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
            $db->setQuery($query );
            $db->query();
        } else {
            //run an insert statement
            $db =& JFactory::getDBO();
            $query = 'INSERT INTO #__jfusion_sync (syncdata, syncid) VALUES (' . $db->quote($serialized) .', ' . $db->Quote($syncdata['syncid']) . ')';
            $db->setQuery($query );
            $db->query();
        }
    }

    function getSyncdata($syncid)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT syncdata FROM #__jfusion_sync WHERE syncid =' . $db->Quote($syncid);
        $db->setQuery($query);
        $serialized = $db->loadResult();
        $syncdata = unserialize(base64_decode($serialized));

        return $syncdata;
    }

    function SyncStep1($syncdata)
    {

        //setup some variables
        $MasterPlugin = JFusionFactory::getPlugin($syncdata['master']);
        $sync_errors = array();

        //we should start with the import of slave users into the master
        foreach($syncdata['slave_data'] as $slave_sync) {
            //update the database every x users
            $update_count = 10;
            $count = 0;

            //get a list of users
            $slave_sync['jname'] = $jname;
            if($jname){
            $SlavePlugin = & JFusionFactory::getPlugin($jname);
            $userlist = $SlavePlugin->getUserList();

            //perform the actual sync
            foreach($userlist as $user) {
                $userinfo = $SlavePlugin->getUser($user->username);
                $status = $MasterPlugin->updateUser($userinfo);
                if ($status['error']) {
                    $sync_error = array();
                    $sync_error['master']['username'] = $status['userinfo']->username;
                    $sync_error['master']['email'] = $status['userinfo']->email;
                    $sync_error['slave']['username'] = $userinfo->username;
                    $sync_error['slave']['email'] = $userinfo->email;
                    //save the error for later
                    $syncdata['slave_data'][$jname]['errors'][] = $sync_error;

                    //update the counters
                    $syncdata['slave_data'][$jname]['error'] += 1;
                    $syncdata['slave_data'][$jname]['total'] -= 1;
                } else {
                    if ($status['action'] == 'created') {
                        $syncdata['slave_data'][$jname]['created'] += 1;
                    } else {
                        $syncdata['slave_data'][$jname]['updated'] += 1;
                    }
                    $syncdata['slave_data'][$jname]['total'] -= 1;
                }

                //update the database
                ++$count;
                if ($count > $update_count) {
                    //save the syncdata
                    $count = 0;
                    JFusionUsersync::saveSyncdata($syncdata);
                }
            }
            }
            //end of sync
        }
    }


}