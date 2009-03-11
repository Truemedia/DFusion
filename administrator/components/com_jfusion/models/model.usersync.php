<?php

/**
* @package JFusion
* @subpackage Models
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );


/**
* Prevent time-outs
*/

@set_time_limit(0);
@ini_set('memory_limit', '256M');
@ini_set('upload_max_filesize', '128M');
@ini_set('post_max_size', '256M');
@ini_set('max_input_time', '7200');
@ini_set('max_execution_time', '0');
@ini_set('expect.timeout', '7200');
@ini_set('default_socket_timeout', '7200');


/**
* Class for general JFusion functions
* @package JFusion
*/

class JFusionUsersync{

    function saveErrorData($syncid, $syncdata)
    {
        //serialize the $syncdata to allow storage in a file
        $serialized = serialize($syncdata);
		//set file path
		$file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'usersync'.DS.$syncid;
		jimport('joomla.filesystem.file');
		JFile::write($file, $serialized);
    }

    function getErrorData($syncid)
    {
		//set file path
		$file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'usersync'.DS.$syncid;
		jimport('joomla.filesystem.file');
		$serialized = JFile::read($file);
        $syncdata = unserialize($serialized);
		return $syncdata;
    }

    function saveSyncdata($syncdata)
    {
        //serialize the $syncdata to allow storage in a SQL field
        $serialized = base64_encode(serialize($syncdata));

        $db =& JFactory::getDBO();
        $query = 'INSERT INTO #__jfusion_sync (syncdata, syncid, time_start, action) VALUES (' . $db->Quote($serialized) .', ' . $db->Quote($syncdata['syncid']) . ', ' . $db->Quote(time()). ', ' . $db->Quote($syncdata['action']) . ')';
        $db->setQuery($query );
        $db->query();

    }

    function updateSyncdata($syncdata)
    {
        //serialize the $syncdata to allow storage in a SQL field
        $serialized = base64_encode(serialize($syncdata));

        //find out if the syncid already exists
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__jfusion_sync SET syncdata = ' . $db->Quote($serialized) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
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

        //get the errors and append it
        $syncdata['errors'] = JFusionUsersync::getErrorData($syncid);
        
        return $syncdata;
    }

    function SyncError($syncid, $syncerror)
    {
		//Load debug library
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');

?><div style="border: 0pt none ; margin: 0pt; padding: 0pt 5px; width: 800px;">
<?php
		$syncdata = JFusionUsersync::getSyncdata($syncid);
		foreach ($syncerror as $id => $error) {
			if ($error['action'] == '1') {
				//update the first entity
				echo '<h2>' . $syncdata['errors'][$id]['user']['jname'] . ' ' . JText::_('USER')  .' ' .JText::_('UPDATE').'</h2>';
		        $JFusionPlugin = JFusionFactory::getUser($syncdata['errors'][$id]['user']['jname']);
			    debug::show($syncdata['errors'][$id]['conflict']['userinfo'], $syncdata['errors'][$id]['conflict']['jname']. ' ' . JText::_('USER'). ' ' . JText::_('INFORMATION'),1);

				$status = $JFusionPlugin->updateUser($syncdata['errors'][$id]['conflict']['userinfo'],1);
				if ($status['error']) {
			        debug::show($status['error'], $syncdata['errors'][$id]['user']['jname'].' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('ERROR'), 0);
			        debug::show($status['debug'], $syncdata['errors'][$id]['user']['jname'].' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);
				} else {
			        debug::show($status['debug'], $syncdata['errors'][$id]['user']['jname'].' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);
			        JFusionFunction::updateLookup($syncdata['errors'][$id]['user']['userinfo'], 0, $syncdata['errors'][$id]['user']['jname']);
				}

			} elseif ($error['action'] == '2') {
				//update the second entity (conflicting plugin)
				echo '<h2>' . $syncdata['errors'][$id]['conflict']['jname'] . ' ' . JText::_('USER')  .' ' .JText::_('UPDATE').'</h2>';
		        $JFusionPlugin = JFusionFactory::getUser($syncdata['errors'][$id]['conflict']['jname']);
			    debug::show($syncdata['errors'][$id]['user']['userinfo'], $syncdata['errors'][$id]['user']['jname']. ' ' . JText::_('USER'). ' ' . JText::_('INFORMATION'),1);

				$status = $JFusionPlugin->updateUser($syncdata['errors'][$id]['user']['userinfo'],1);
				if ($status['error']) {
			        debug::show($status['error'], $syncdata['errors'][$id]['conflict']['jname'].' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('ERROR'), 0);
			        debug::show($status['debug'], $syncdata['errors'][$id]['conflict']['jname'].' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);
				} else {
			        debug::show($status['debug'], $syncdata['errors'][$id]['conflict']['jname'].' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);
			        JFusionFunction::updateLookup($syncdata['errors'][$id]['user']['userinfo'], 0, $syncdata['errors'][$id]['user']['jname']);
				}

			} elseif ($error['action'] == '3') {
				//delete the first entity

				//NOT IMPLEMENTED YET

				//prevent Joomla from deleting all the slaves via the user plugin if it is set as master
				global $JFusionActive;
				$JFusionActive = 1;

		        $JFusionPlugin = JFusionFactory::getUser($error['user_jname']);
		        $userinfo = $JFusionPlugin->getUser(html_entity_decode($error['user_username']));
		        $status = $JFusionPlugin->deleteUser($userinfo);
				if($status['error']) {
					//delete error
					echo '<img src="components/com_jfusion/images/error.png" width="32" height="32">' . JText::_('ERROR'). ' ' . JText::_('DELETING'). ' ' . $error['user_jname'] . ' ' . JText::_('USER') . ' ' . $error['user_username'] . '<br/>';
				} else {
					//delete success
					echo '<img src="components/com_jfusion/images/updated.png" width="32" height="32">' . JText::_('SUCCESS'). ' ' . JText::_('DELETING'). ' ' . $error['user_jname'] . ' ' . JText::_('USER') . ' ' . $error['user_username'] . '<br/>';
					JFusionFunction::updateLookup($userinfo, 0, $error['conflict_jname'],true);
				}
			} elseif ($error['action'] == '4') {
				//delete the second entity (conflicting plugin)

				//NOT IMPLEMENTED YET

				//prevent Joomla from deleting all the slaves via the user plugin if it is set as master
				global $JFusionActive;
				$JFusionActive = 1;

		        $JFusionPlugin = JFusionFactory::getUser($error['conflict_jname']);
		      	$userinfo = $JFusionPlugin->getUser(html_entity_decode($error['conflict_username']));
		        $status = $JFusionPlugin->deleteUser($userinfo);
				if($status['error']) {
					//delete error
					echo '<img src="components/com_jfusion/images/error.png" width="32" height="32">' . JText::_('ERROR'). ' ' . JText::_('DELETING'). ' ' . $error['conflict_jname'] . ' ' . JText::_('USER') . ' ' . $error['conflict_username'] . '<br/>';
				} else {
					//delete success
					echo '<img src="components/com_jfusion/images/updated.png" width="32" height="32">' . JText::_('SUCCESS'). ' ' . JText::_('DELETING'). ' ' . $error['conflict_jname'] . ' ' . JText::_('USER') . ' ' . $error['conflict_username'] . '<br/>';
					JFusionFunction::updateLookup($userinfo, 0, $error['conflict_jname'],true);
				}
			}
		}
		echo '</div>';
		echo "<h2>".JText::_('CONFLICT_RESOLUTION_COMPLETE')."</h2>";
    }

    function SyncExecute($syncdata, $action, $plugin_offset, $user_offset)
    {
        //setup some variables
        $MasterPlugin = JFusionFactory::getAdmin($syncdata['master']);
        $MasterUser = JFusionFactory::getUser($syncdata['master']);
        $sync_errors_array = array();

        //only store syncdata every 20 users for better performance
        $store_interval = 20;
        $user_count = 0;

        //we should start with the import of slave users into the master
        if ($syncdata['slave_data']) {
        	for ($i=$plugin_offset; $i<count($syncdata['slave_data']); $i++) {
        		$syncdata['plugin_offset'] = $i;

                //get a list of users
                $jname = $syncdata['slave_data'][$i]['jname'];
                if ($jname) {

					//output which plugin is worked on
					echo '<h2>'. $jname . '</h2><br/>';

                    $SlavePlugin = & JFusionFactory::getAdmin($jname);
                    $SlaveUser = & JFusionFactory::getUser($jname);
					if($action =='master'){
                    	$userlist = $SlavePlugin->getUserList();
                    	$action_name = $jname;
                    	$action_reverse_name = $syncdata['master'];
					} else {
                    	$userlist = $MasterPlugin->getUserList();
                    	$action_name = $syncdata['master'];
                    	$action_reverse_name = $jname;
					}

                    //perform the actual sync
        			for ($j=$user_offset; $j<count($userlist); $j++) {
        				$syncdata['user_offset'] = $j;
						if($action =='master'){
	                        $userinfo = $SlaveUser->getUser($userlist[$j]->username);
    	                    $status = $MasterUser->updateUser($userinfo,0);
						} else {
	                        $userinfo = $MasterUser->getUser($userlist[$j]->username);
    	                    $status = $SlaveUser->updateUser($userinfo,0);
						}

                        if ($status['error']) {

                        	//output results
							echo '<img src="components/com_jfusion/images/error.png" width="32" height="32">' . JText::_('CONFLICT'). ' ' . $syncdata['master'] . ' ' . $userlist[$j]->username . ' / ' . $userlist[$j]->email . '.  ' . $jname . ' ' . $status['userinfo']->username . ' / ' . $status['userinfo']->email . '<br/>';

                            $sync_error = array();
                            $sync_error['conflict']['userinfo'] = $status['userinfo'];
                            $sync_error['conflict']['error'] = $status['error'];
                            $sync_error['conflict']['debug'] = $status['debug'];
                            $sync_error['conflict']['jname'] = $action_reverse_name;
                            $sync_error['user']['jname'] = $action_name;
                            $sync_error['user']['userinfo'] = $userinfo;
                            $sync_error['user']['userlist'] = $userlist[$j];

                            //save the error for later
                            $sync_error_array[] = $sync_error;                           
                            $syncdata['slave_data'][$i]['error'] += 1;

                        } else {
                            //update the lookup table
                            if($action=="master") {
    	                      	JFusionFunction::updateLookup($userinfo,0,$jname);
                            } else {
								JFusionFunction::updateLookup($SlaveUser->getUser($userlist[$j]->username),0,$jname);
                            }
                        }

                        //update the counters
                        $syncdata['slave_data'][$i][$status['action']] += 1;
                        $syncdata['slave_data'][$i]['total'] -= 1;

                        //update the database
                        if ($user_count > $store_interval){
                        	JFusionUsersync::updateSyncdata($syncdata);
                        	JFusionUsersync::saveErrorData($syncdata['syncid'],$sync_error_array);
                        	$user_count = 0;
                        } else {
                        	$user_count = $user_count + 1;
                        }
        			}
                }
            }
            //end of sync, save the final data
            $syncdata['completed'] = 'true';
            JFusionUsersync::updateSyncdata($syncdata);
           	JFusionUsersync::saveErrorData($syncdata['syncid'],$sync_error_array);

            //update the finish time
      		$db =& JFactory::getDBO();
       		$query = 'UPDATE #__jfusion_sync SET time_end = ' . $db->Quote(time()) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
       		$db->setQuery($query );
       		$db->query();

       		echo '<h2>' . JText::_('USERSYNC') . ' ' . JText::_('COMPLETED') . '</h2><br/>';

			//show error resolve options if there are any errors
			if (!empty($sync_error_array)){
				echo '<h2><a href="index.php?option=com_jfusion&task=syncerror&syncid=' . $syncdata['syncid'] . '">' . JText::_('SYNC_CONFLICT') . '</a></h2>';
			}
        }
    }
}
