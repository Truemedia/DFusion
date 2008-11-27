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

            //load the language
            $this->loadLanguage('com_jfusion', JPATH_BASE);
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
        function onLoginUser($user, &$options)
        {
			//prevent any output by the plugins (this could prevent cookies from being passed to the header)
			ob_start();

            jimport('joomla.user.helper');
			global $JFusionActive;
			$JFusionActive = true;

            //get the JFusion master
            $master = JFusionFunction::getMaster();
            $JFusionMaster = JFusionFactory::getUser($master->name);
            $userinfo = $JFusionMaster->getUser($user['username']);

            //apply the cleartext password to the user object
            $userinfo->password_clear = $user['password'];

            $MasterUser = $JFusionMaster->updateUser($userinfo,0);
            if ($MasterUser['error']) {
               	JFusionFunction::raiseWarning($master->name . ' ' .JText::_('USER') . ' ' . JText::_('UPDATE'), $MasterUser['error'],1);
	        }

            // See if the user has been blocked or is not activated
            if (!empty($userinfo->block) || !empty($userinfo->activation)) {

         		//make sure the block is also applied in slave softwares
	            $slaves = JFusionFunction::getSlaves();
    	        foreach($slaves as $slave) {
        	        $JFusionSlave = JFusionFactory::getUser($slave->name);
            	    $SlaveUser = $JFusionSlave->updateUser($userinfo,0);
                	if ($SlaveUser['error']) {
                    	JFunctionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['error'],1);
	                }
    	        }

                if (!empty($userinfo->block)) {
                    JError::raiseWarning('500', JText::_('FUSION_BLOCKED_USER'));
					//hide the default Joomla login failure message
					JError::setErrorHandling(E_WARNING, 'ignore');
                    ob_end_clean();
                    $success = false;
                    return $success;
                } else {
                    JError::raiseWarning('500', JText::_('FUSION_INACTIVE_USER'));
					//hide the default Joomla login failure message
					JError::setErrorHandling(E_WARNING, 'ignore');
                    ob_end_clean();
                    $success = false;
                    return $success;                }
            }

			//setup the master session
            $MasterSession = $JFusionMaster->createSession($userinfo, $options);
            if ($MasterSession['error']) {
            	//report the error back
                JFunctionFunction::raiseWarning($master->name .' ' .JText::_('SESSION').' ' .JText::_('CREATE'), $MasterSession['error'],1);
                if ($master->name == 'joomla_int'){
                  	//we can not tolerate Joomla session failures
	                ob_end_clean();
					//hide the default Joomla login failure message
					JError::setErrorHandling(E_WARNING, 'ignore');
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
                    //no Joomla user could be created, fatal error
                    JFusionFunction::raiseWarning('joomla_int: '.' ' .JText::_('USER')  .' ' .JText::_('UPDATE'), $JoomlaUser['error'],1);
					//hide the default Joomla login failure message
					JError::setErrorHandling(E_WARNING, 'ignore');
                    ob_end_clean();
                    $success = false;
                    return $success;
                }

                //create a Joomla session
                $JoomlaSession = $JFusionJoomla->createSession($JoomlaUser['userinfo'], $options);
                if ($JoomlaSession['error']) {
                    //no Joomla session could be created -> deny login
                    JFusionFunction::raiseWarning('joomla_int ' .' ' .JText::_('SESSION') .' ' .JText::_('CREATE'), $JoomlaSession ['error'],1);
					//hide the default Joomla login failure message
					JError::setErrorHandling(E_WARNING, 'ignore');
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
                   	JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('USER') .' ' .JText::_('UPDATE') , $SlaveUser['error'],1);
                } else {
                    //apply the cleartext password to the user object
                    $SlaveUser['userinfo']->password_clear = $user['password'];

                    JFusionFunction::updateLookup($SlaveUser['userinfo'], $slave->name, $JoomlaUser['userinfo']->userid);

                    if (!isset($options['group']) && $slave->dual_login == 1) {
                        $SlaveSession = $JFusionSlave->createSession($SlaveUser['userinfo'], $options);
                        if ($SlaveSession['error']) {
                            JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('SESSION') .' ' .JText::_('CREATE'), $SlaveSession['error'],1);
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
                            JFunction::raiseWarning($master->name .' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'), $MasterSession['error']);
                        }
                    } else {
                        JFusionFunction::raiseWarning($master->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'),1);
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
                                JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'),$SlaveSession['error'],1);
                            }
                        } else {
                            JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'),1);
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

                //convert the user array into a user object
                $JoomlaUser = new stdClass();
                foreach ($user as $key => $value) {
                	$JoomlaUser->$key = $value;
                }

	            //check to see if we need to update the master
    	        $master = JFusionFunction::getMaster();
    	        if($master->name != 'joomla_int'){
		            $JFusionMaster = JFusionFactory::getUser($master->name);
		            $MasterUser = $JFusionMaster->updateUser($JoomlaUser);
                    if ($MasterUser['error']) {
               			JFusionFunction::raiseWarning($master->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $MasterUser['error'],1);
                    }
    	        }

                //update the user details in any JFusion slaves
            	$slaves = JFusionFunction::getPlugins();
            	foreach($slaves as $slave) {
                	$JFusionSlave = JFusionFactory::getUser($slave->name);
                	$SlaveUser = $JFusionSlave->updateUser($JoomlaUser);
                    if ($SlaveUser['error']) {
               			JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['error'],1);
                    }
            	}
            }
            //stop output buffer
            ob_end_clean();
        }
    }


