<?php
    /**
* @package JFusion
* @subpackage Plugin_User
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
    jimport('joomla.plugin.plugin');
    require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
    require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');


    /**
* JFusion User class
* @package JFusion
*/
    class plgUserJfusion extends JPlugin
    {
        /**
* Constructor
*
* For php4 compatability we must not use the __constructor as a constructor for plugins
* because func_get_args ( void ) returns a copy of all passed arguments NOT references.
* This causes problems with cross-referencing necessary for the observer design pattern.
*
* @param object $subject The object to observe
* @param array $config An array that holds the plugin configuration
* @since 1.5
*/
        function plgUserJfusion(& $subject, $config)
        {
            parent::__construct($subject, $config);
        }

        /**
* Remove all sessions for the user name
*
* Method is called after user data is deleted from the database
*
* @param array holds the user data
* @param boolean true if user was succesfully stored in the database
* @param string message
*/
        function onAfterDeleteUser($user, $succes, $msg)
        {
            if (!$succes) {
                return false;
            }

            $db =& JFactory::getDBO();
            $db->setQuery('DELETE FROM #__session WHERE userid = '.$db->Quote($user['id']));
            $db->Query();

            return true;
        }

        /**
* This method should handle any login logic and report back to the subject
*
* @access public
* @param array holds the user data
* @param array array holding options (remember, autoregister, group)
* @return boolean True on success
* @since 1.5
*/
        function onLoginUser($user, $options = array())
        {
			//prevent any output by the plugins (this could prevent cookies from being passed to the header)
			ob_start();

            jimport('joomla.user.helper');
			global $JFusionActive;
			$JFusionActive = true;

            //filter the username
            $JFusionJoomla = JFusionFactory::getUser('joomla_int');
            $username_clean = $JFusionJoomla->filterUsername($user['username']);

            //get the JFusion master
            $master = JFusionFunction::getMaster();
            $JFusionMaster = JFusionFactory::getUser($master->name);
            $userinfo = $JFusionMaster->getUser($username_clean);

            //apply the cleartext password to the user object
            $userinfo->password_clear = $user['password'];

            $MasterUser = $JFusionMaster->updateUser($userinfo,0);
            if ($MasterUser['error']) {
               	JError::raiseWarning('500', $master->name . ': '. print_r($MasterUser['error']));
	        }



            // See if the user has been blocked or is not activated
            if (!empty($userinfo->block) || !empty($userinfo->activation)) {

         		//make sure the block is also applied in slave softwares
	            $slaves = JFusionFunction::getSlaves();
    	        foreach($slaves as $slave) {
        	        $JFusionSlave = JFusionFactory::getUser($slave->name);
            	    $SlaveUser = $JFusionSlave->updateUser($userinfo,0);
                	if ($SlaveUser['error']) {
                    	JError::raiseWarning('500', $slave->name . ': '. print_r($SlaveUser['error']));
	                }
    	        }

                if (!empty($userinfo->block)) {
                    JError::raiseWarning('500', JText::_('FUSION_BLOCKED_USER'));
                    ob_end_clean();
                    $success = false;
                    return $success;
                } else {
                    JError::raiseWarning('500', JText::_('FUSION_INACTIVE_USER'));
                    ob_end_clean();
                    $success = false;
                    return $success;                }
            }

			//setup the master session
            $MasterSession = $JFusionMaster->createSession($userinfo, $options);
            if ($MasterSession['error']) {
            	//report the error back
                JError::raiseWarning('500', $master->name . ': '. print_r($MasterSession['error']));
                if ($master->name == 'joomla_int'){
                  	//we can not tolerate Joomla session failures
	                ob_end_clean();
                    $success = false;
                    return $success;
                }
            }

            //check to see if we need to setup a Joomla session
            if ($master->name != 'joomla_int'){
                //setup the Joomla user
                $JFusionJoomla = JFusionFactory::getUser('joomla_int');
                $JoomlaUser = $JFusionJoomla->updateUser($userinfo,0);
                if ($JoomlaUser['error']) {
                    //no Joomla user could be created
                    JError::raiseWarning('500', 'joomla_int: '. print_r($JoomlaUser['error']));
                    ob_end_clean();
                    $success = false;
                    return $success;
                }

                //create a Joomla session
                $JoomlaSession = $JFusionJoomla->createSession($JoomlaUser['userinfo'], $options);
                if ($JoomlaSession['error']) {
                    //no Joomla session could be created -> deny login
                    JError::raiseWarning('500', 'joomla_int: '. $JoomlaSession ['error']);
                    ob_end_clean();
                    $success = false;
                    return $success;
                }
            } else {
            	//joomla already setup, we can copy its details from the master
            	$JFusionJoomla = $JFusionMaster;
            	$JoomlaUser = array( 'userinfo' => $userinfo, 'error' => '');
            }

            if ($master->name != 'joomla_int') {
                JFusionFunction::updateLookup($userinfo, $master->name, $JoomlaUser['userinfo']->userid);
            }


            //setup the other slave JFusion plugins
            $slaves = JFusionFunction::getPlugins();
            foreach($slaves as $slave) {
                $JFusionSlave = JFusionFactory::getUser($slave->name);
                $SlaveUser = $JFusionSlave->updateUser($userinfo,0);
                if ($SlaveUser['error']) {
                    JError::raiseWarning('500', $slave->name . ': '. print_r($SlaveUser['error']));
                } else {

                    //apply the cleartext password to the user object
                    $SlaveUser['userinfo']->password_clear = $user['password'];

                    JFusionFunction::updateLookup($SlaveUser['userinfo'], $slave->name, $JoomlaUser['userinfo']->userid);

                    if (!isset($options['group']) && $slave->dual_login == 1) {
                        $SlaveSession = $JFusionSlave->createSession($SlaveUser['userinfo'], $options);
                        if ($SlaveSession['error']) {
                            JError::raiseWarning('500', $slave->name . ': ' . $SlaveSession['error']);
                        }
                    }
                }
            }
			ob_end_clean();
            return true;
        }


        /**
* This method should handle any logout logic and report back to the subject
*
* @access public
* @param array holds the user data
* @param array array holding options (client, ...)
* @return object True on success
* @since 1.5
*/
        function onLogoutUser($user, $options = array())
        {
			global $JFusionActive;
			$JFusionActive = true;
            $my =& JFactory::getUser();

			//prevent any output by the plugins (this could prevent cookies from being passed to the header)
			ob_start();

            //logout from the JFusion plugins if done through frontend
            if ($options['clientid'][0] != 1) {

                //get the JFusion master
                $master = JFusionFunction::getMaster();
                if ($master->name && $master->name != 'joomla_int') {
                    $JFusionMaster = JFusionFactory::getUser($master->name);
                    $userlookup = JFusionFunction::lookupUser($master->name, $my->get('id'));
                    $MasterUser = $JFusionMaster->getUser($userlookup->username);
                    //check if a user was found
                    if ($MasterUser) {
                        $MasterSession = $JFusionMaster->destroySession($MasterUser, $options);
                        if ($MasterSession['error']) {
                            JError::raiseWarning('500', $master->name . ': ' . $MasterSession['error']);
                        }
                    } else {
                        JError::raiseWarning('500', $master->name . ': ' . JText::_('COULD_NOT_FIND_USER'));
                    }

                }

                $slaves = JFusionFunction::getPlugins();
                foreach($slaves as $slave) {
                    //check if sessions are enabled
                    if ($slave->dual_login == 1) {
                        $JFusionSlave = JFusionFactory::getUser($slave->name);
                        $userlookup = JFusionFunction::lookupUser($slave->name, $my->get('id'));
                        $SlaveUser = $JFusionSlave->getUser($userlookup->username);
                        //check if a user was found
                        if ($SlaveUser) {
                            $SlaveSession = $JFusionSlave->destroySession($SlaveUser, $options);
                            if ($SlaveSession['error']) {
                                JError::raiseWarning('500', $slave->name . ': ' . $SlaveSession['error']);
                            }
                        } else {
                            JError::raiseWarning('500', $slave->name . ': ' . JText::_('COULD_NOT_FIND_USER'));
                        }
                    }
                }
            }

            //destroy the Joomla session
            $table = & JTable::getInstance('session');
            $table->destroy($user['id'], $options['clientid']);

            $my =& JFactory::getUser();
            if ($my->get('id') == $user['id']) {
                // Hit the user last visit field
                $my->setLastVisit();

                // Destroy the php session for this user
                $session =& JFactory::getSession();
                $session->destroy();
            }
			ob_end_clean();
            return true;
        }

        function onAfterStoreUser($user, $isnew, $succes, $msg)
        {

			//prevent any output by the plugins (this could prevent cookies from being passed to the header)
			ob_start();

            global $JFusionActive;

            if (!$JFusionActive) {
                //A change has been made to a user without JFusion knowing about it

                /**
				* Load the JFusion framework
				*/
                require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
                require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

                //update the JFusion plugins with the new user
                $joomla_int = JFusionFactory::getUser('joomla_int');
                $joomla_user = $joomla_int->getUser($user['username']);

                //setup the other slave JFusion plugins
                $plugins = JFusionFunction::getPlugins();
                foreach($plugins as $plugin) {
                    $JFusionPlugin = JFusionFactory::getUser($plugin->name);
                    $plugin_user = $JFusionPlugin->updateUser($joomla_user,0);
                    if ($plugin_user['error']) {
                        JError::raiseWarning('500', $plugin->name . ': '. print_r($plugin_user['error']));
                    }
                }
            }
            //stop output buffer
            ob_end_clean();
        }
    }


