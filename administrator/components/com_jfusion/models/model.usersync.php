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

        $db =& JFactory::getDBO();
        $query = 'INSERT INTO #__jfusion_sync (syncdata, syncid, time_start, action) VALUES (' . $db->quote($serialized) .', ' . $db->Quote($syncdata['syncid']) . ', ' . $db->quote(time()). ', ' . $db->Quote($syncdata['action']) . ')';
        $db->setQuery($query );
        $db->query();

    }

    function updateSyncdata($syncdata)
    {
        //serialize the $syncdata to allow storage in a SQL field
        $serialized = base64_encode(serialize($syncdata));

        //find out if the syncid already exists
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__jfusion_sync SET syncdata = ' . $db->quote($serialized) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
        $db->setQuery($query );
        $db->query();
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

    function SyncError($syncid, $syncerror)
    {
		$syncdata = JFusionUsersync::getSyncdata($syncid);
		foreach ($syncerror as $error) {

			if ($error['action'] == 'master') {
		        $JFusionPlugin = JFusionFactory::getUser($error['master_jname']);
		        $JFusionPlugin->deleteUser(html_entity_decode($error['master_username']));
				echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'error.png" width="32" height="32">' . JText::_('DELETED'). ' ' . $error['master_jname'] . ' ' . JText::_('USER') . $error['master_username'] . '<br/>';
			} elseif ($error['action'] == 'slave') {
		        $JFusionPlugin = JFusionFactory::getUser($error['slave_jname']);
		        $JFusionPlugin->deleteUser(html_entity_decode($error['slave_username']));
				echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'error.png" width="32" height="32">' . JText::_('DELETED'). ' ' . $error['slave_jname'] . ' ' . JText::_('USER') . $error['slave_username'] . '<br/>';
			}
		}
    }

    function SyncMaster($syncdata, $plugin_offset, $user_offset)
    {
        //setup some variables
        $MasterPlugin = JFusionFactory::getUser($syncdata['master']);
        $sync_errors = array();

        //we should start with the import of slave users into the master
        if ($syncdata['slave_data']) {
        	for ($i=$plugin_offset; $i<count($syncdata['slave_data']); $i++) {
        		$syncdata['plugin_offset'] = $i;

                //get a list of users
                $jname = $syncdata['slave_data'][$i]['jname'];
                if ($jname) {

					//output which plugin is worked on
					echo '<h2>'. $jname . '</h2><br/>';

                    $SlavePlugin = & JFusionFactory::getPlugin($jname);
                    $SlaveUser = & JFusionFactory::getUser($jname);
                    $userlist = $SlavePlugin->getUserList();

                    //perform the actual sync
        			for ($j=$user_offset; $j<count($userlist); $j++) {
        				$syncdata['user_offset'] = $j;
                        $userinfo = $SlaveUser->getUser($userlist[$j]->username);
                        $status = $MasterPlugin->updateUser($userinfo);
                        if ($status['error']) {

                        	//output results
							echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'error.png" width="32" height="32">' . JText::_('CONFLICT'). ' ' . $syncdata['master'] . ' ' . $userlist[$j]->username . ' / ' . $userlist[$j]->email . '.  ' . $jname . ' ' . $status['userinfo']->username . ' / ' . $status['userinfo']->email . '<br/>';

                            $sync_error = array();
                            $sync_error['master']['username'] = $status['userinfo']->username;
                            $sync_error['master']['email'] = $status['userinfo']->email;
                            $sync_error['master']['jname'] = $syncdata['master'];
                            $sync_error['slave']['username'] = $userinfo->username;
                            $sync_error['slave']['email'] = $userinfo->email;
                            $sync_error['slave']['jname'] = $jname;
                            //save the error for later
                            $syncdata['errors'][] = $sync_error;

                            //update the counters
                            $syncdata['slave_data'][$i]['error'] += 1;
                            $syncdata['slave_data'][$i]['total'] -= 1;
                        } else {
                            if ($status['action'] == 'created') {
								echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'created.png">' . JText::_('USERNAME') . ':' . $userlist[$j]->username . ',  ' . JText::_('CREATED') . '<br/>';
                                $syncdata['slave_data'][$i]['created'] += 1;
                            } else {
								echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'updated.png">' . JText::_('USERNAME') . ':' . $userlist[$j]->username . ',  ' . JText::_('UPDATED') . '<br/>';
                                $syncdata['slave_data'][$i]['updated'] += 1;
                            }
                            $syncdata['slave_data'][$i]['total'] -= 1;
                        }

						//add the offsets in order for the resume function to work


                        //update the database
                        JFusionUsersync::updateSyncdata($syncdata);
        			}

                }
            }
            //end of sync, save the final data
            $syncdata['completed'] = 'true';
            JFusionUsersync::updateSyncdata($syncdata);

            //update the finish time
      		$db =& JFactory::getDBO();
       		$query = 'UPDATE #__jfusion_sync SET time_end = ' . $db->quote(time()) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
       		$db->setQuery($query );
       		$db->query();
        }
    }

    function SyncSlave($syncdata, $plugin_offset, $user_offset)
    {

        //setup some variables
        $MasterUser = JFusionFactory::getUser($syncdata['master']);
        $MasterPlugin = JFusionFactory::getPlugin($syncdata['master']);
        $sync_errors = array();

        //we should start with the import of slave users into the master
        if ($syncdata['slave_data']) {
        	for ($i=$plugin_offset; $i<count($syncdata['slave_data']); $i++) {
        		$syncdata['plugin_offset'] = $i;

                //get a list of users
                $jname = $syncdata['slave_data'][$i]['jname'];
                if ($jname) {

					//output which plugin is worked on
					echo '<h2>'. $jname . '</h2><br/>';

                    $SlavePlugin = & JFusionFactory::getPlugin($jname);
                    $SlaveUser = & JFusionFactory::getUser($jname);
                    $userlist = $MasterPlugin->getUserList();

                    //perform the actual sync
        			for ($j=$user_offset; $j<count($userlist); $j++) {
        				$syncdata['user_offset'] = $j;
                        $userinfo = $MasterUser->getUser($userlist[$j]->username);
                        $status = $SlaveUser->updateUser($userinfo);
                        if ($status['error']) {

							echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'error.png" width="32" height="32">' . JText::_('CONFLICT'). ' ' . $syncdata['master'] . ' ' . $userlist[$j]->username . ' / ' . $userlist[$j]->email . '.  ' . $jname . ' ' . $status['userinfo']->username . ' / ' . $status['userinfo']->email . '<br/>';
                            $sync_error = array();
                            $sync_error['master']['username'] = $status['userinfo']->username;
                            $sync_error['master']['email'] = $status['userinfo']->email;
                            $sync_error['master']['jname'] = $syncdata['master'];
                            $sync_error['slave']['username'] = $userinfo->username;
                            $sync_error['slave']['email'] = $userinfo->email;
                            $sync_error['slave']['jname'] = $jname;
                            //save the error for later
                            $syncdata['errors'][] = $sync_error;

                            //update the counters
                            $syncdata['slave_data'][$i]['error'] += 1;
                            $syncdata['slave_data'][$i]['total'] -= 1;
                        } else {
                            if ($status['action'] == 'created') {
								echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'created.png">' . JText::_('USERNAME') . ':' . $userlist[$j]->username . ',  ' . JText::_('CREATED') . '<br/>';
                                $syncdata['slave_data'][$i]['created'] += 1;
                            } else {
								echo '<img src="components'.DS.'com_jfusion'.DS.'images'.DS.'updated.png">' . JText::_('USERNAME') . ':' . $userlist[$j]->username . ',  ' . JText::_('UPDATED') . '<br/>';
                                $syncdata['slave_data'][$i]['updated'] += 1;
                            }
                            $syncdata['slave_data'][$i]['total'] -= 1;
                        }

                        //update the database
                        JFusionUsersync::updateSyncdata($syncdata);
                    }
                }
            }
            //end of sync, save the final data
            $syncdata['completed'] = 'true';
            JFusionUsersync::updateSyncdata($syncdata);

            //update the finish time
      		$db =& JFactory::getDBO();
       		$query = 'UPDATE #__jfusion_sync SET time_end = ' . $db->quote(time()) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
       		$db->setQuery($query );
       		$db->query();
        }
    }
}
