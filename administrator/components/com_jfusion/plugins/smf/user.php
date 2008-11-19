<?php

/**
* @package JFusion_SMF
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');



/**
* JFusion plugin class for SMF 1.1.4
* @package JFusion_SMF
*/
class JFusionUser_smf extends JFusionUser{

    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $update_block = $params->get('update_block');
        $update_activation = $params->get('update_activation');
        $update_email = $params->get('update_email');

        $status = array();

        //find out if the user already exists
        $existinguser = $this->getUser($userinfo->username);

        if (!empty($existinguser)) {
            //a matching user has been found
            if ($existinguser->email != $userinfo->email) {
            	if ($update_email || $overwrite) {
                	$this->updateEmail($userinfo, $existinguser, $status);
            	} else {
            		//return a debug to inform we skiped this step
            		$status['debug'][] = JText::_('SKIPPED_EMAIL_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
            	}
            }

            if (!empty($userinfo->password_clear)) {
                //we can update the password
                $this->updatePassword($userinfo, &$existinguser, &$status);
            }

            //check the blocked status
            if ($existinguser->block != $userinfo->block) {
            	if ($update_block || $overwrite) {
	                if ($userinfo->block) {
    	                //block the user
        	            $this->blockUser($userinfo, $existinguser, $status);
            	    } else {
                	    //unblock the user
                    	$this->unblockUser($userinfo, $existinguser, $status);
                	}
            	} else {
            		//return a debug to inform we skiped this step
            		$status['debug'][] = JText::_('SKIPPED_BLOCK_UPDATE') . ': ' . $existinguser->block . ' -> ' . $userinfo->block;
            	}
            }

            //check the activation status
            if (empty($existinguser->activation) != empty($userinfo->activation)) {
            	if ($update_activation || $overwrite) {
	                if ($userinfo->activation) {
    	                //inactiva the user
        	            $this->inactivateUser($userinfo, $existinguser, $status);
            	    } else {
                	    //activate the user
	                    $this->activateUser($userinfo, $existinguser, $status);
    	            }
            	} else {
            		//return a debug to inform we skiped this step
            		$status['debug'][] = JText::_('SKIPPED_EMAIL_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
            	}
            }

            $status['userinfo'] = $existinguser;
            if (empty($status['error'])) {
                $status['action'] = 'updated';
            }
            return $status;

        } else {
            //we need to create a new user
            $this->createUser($userinfo, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            $status['userinfo'] = $this->getUser($userinfo->username);

            return $status;

        }
    }

    function &getUser($username)
    {
        // initialise some objects
        $params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT ID_MEMBER as userid, memberName as username, realName as name, emailAddress as email, passwd as password, passwordSalt as password_salt, validation_code, is_activated FROM #__members WHERE memberName=' . $db->Quote($username) ;
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT ID_BAN_GROUP, expire_time FROM #__ban_groups WHERE name= ' . $db->quote($result->username);
            $db->setQuery($query);
            $expire_time = $db->loadObject();
            if ($expire_time) {
            	if ($expire_time->expire_time == '' || $expire_time->expire_time > time() ){
                	$result->block = 1;
            	} else {
                	$result->block = 0;
            	}
            } else {
                $result->block = 0;
            }

            if ($result->is_activated == 1){
				$result->activation = '';
            } else {
				$result->activation = $result->validation_code;
            }
        }

        return $result;

    }


    function getJname()
    {
        return 'smf';
    }

    function deleteUser($username)
    {
        //TODO: create a function that deletes a user
    }

    function destroySession($userinfo, $options)
    {
        $params = JFusionFactory::getParams($this->getJname());
        $cookiename = $params->get('cookie_name');
        setcookie($cookiename, serialize(array(0, '', 0)), time() - 3600,  '/', '', 0);

    }

    function createSession($userinfo, $options)
    {



        //check to see if the smf_api.php file exists
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');
        if (substr($source_path, -1) == DS) {
            $api_file = $source_path .'smf_api.php';
        } else {
            $api_file = $source_path .DS.'smf_api.php';
        }

        if (file_exists($api_file)) {
            require_once($api_file);
            $username = $userinfo->username;
            $password = $userinfo->password_clear;

            if (isset($options['remember'])) {
                $cookie_length = $options['remember'] ? 31536000 : 3600;
            } else {
                $cookie_length = 3600;
            }

            smf_setLoginCookie($cookie_length,$username,$password,false);
            smf_loadSession();
            smf_authenticateUser();

            $status['debug'] = 'Created SMF session using the smf_api.php';
            $status['error'] = false;
            return $status;
        } else {
            $status['error'] = 'Dual login is not available for this plugin, as the smf_api.php file was not found at:'. $api_file . 'Please download the smf_api.php file and put it in your smf home directory:   http://www.simplemachines.org/community/index.php?action=dlattach;topic=42867.0;attach=9158';
            return $status;

        }
    }

    function filterUsername($username)
    {
        //no username filtering implemented yet
        return $username;
    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        $existinguser->password = sha1(strtolower($userinfo->username) . $userinfo->password_clear);
        $existinguser->password_salt = substr(md5(rand()), 0, 4);
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET passwd = ' . $db->quote($existinguser->password). ', passwordSalt = ' . $db->quote($existinguser->password_salt). ' WHERE ID_MEMBER  = ' . $existinguser->userid;
        $db = JFusionFactory::getDatabase($this->getJname());
        $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
        }
    }

    function updateUsername($userinfo, &$existinguser, &$status)
    {

    }

    function updateEmail($userinfo, &$existinguser, &$status)
    {
        //we need to update the email
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET emailAddress ='.$db->quote($userinfo->email) .' WHERE ID_MEMBER =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('PASSWORD_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    function blockUser($userinfo, &$existinguser, &$status)
    {

            $db = JFusionFactory::getDatabase($this->getJname());
            $ban = new stdClass;
            $ban->ID_BAN_GROUP = NULL;
            $ban->name = $existinguser->username;
            $ban->ban_time = time();
            $ban->expire_time = NULL;
            $ban->cannot_access = 1;
            $ban->cannot_register = 0;
            $ban->cannot_post = 0;
            $ban->cannot_login = 0;
            $ban->reason = 'You have been banned from this software. Please contact your site admin for more details';

            //now append the new user data
            if (!$db->insertObject('#__ban_groups', $ban, 'ID_BAN_GROUP' )) {
         	   $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
	        } else {
		        $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        	}
    }

    function unblockUser($userinfo, &$existinguser, &$status)
    {
        	$db = JFusionFactory::getDatabase($this->getJname());
            $query = 'DELETE FROM #__ban_groups WHERE name = ' . $db->quote($existinguser->username);
            $db->setQuery($query);
		    if (!$db->query()) {
        	    $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        	} else {
	        	$status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        	}
    }

    function activateUser($userinfo, &$existinguser, &$status)
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET is_activated = 1, validation_code = \'\' WHERE ID_MEMBER  = ' . $existinguser->userid;
        $db = JFusionFactory::getDatabase($this->getJname());
        $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function inactivateUser($userinfo, &$existinguser, &$status)
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__members SET is_activated = 0, validation_code = '.$db->Quote($userinfo->activation).' WHERE ID_MEMBER  = ' . $existinguser->userid;
        $db = JFusionFactory::getDatabase($this->getJname());
        $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function createUser($userinfo, &$status)
    {
        //we need to create a new SMF user
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');

        //prepare the user variables
        $user = new stdClass;
        $user->ID_MEMBER = NULL;
        $user->memberName = $userinfo->username;
        $user->realName = $userinfo->name;
        $user->emailAddress = $userinfo->email;

        if (isset($userinfo->password_clear)) {
            $user->passwd = sha1(strtolower($userinfo->username) . $userinfo->password_clear);
            $user->passwordSalt = substr(md5(rand()), 0, 4);
        } else {
            $user->passwd = $userinfo->password;

            if (!isset($userinfo->password_salt)) {
                $user->passwordSalt = substr(md5(rand()), 0, 4);
            } else {
                $user->passwordSalt = $userinfo->password_salt;
            }

        }

        $user->posts = 0 ;
        $user->dateRegistered = time();

        if ($userinfo->activation){
        	$user->is_activated = 0;
        	$user->validation_code = $userinfo->activation;
        } else {
        	$user->is_activated = 1;
        	$user->validation_code = '';
        }


        $user->personalText = '';
        $user->pm_email_notify = 1;
        $user->ID_THEME = 0;
        $user->ID_GROUP = $params->get('usergroup', 4);
        $user->ID_POST_GROUP = $params->get('usergroup', 4);

        //now append the new user data
        if (!$db->insertObject('#__members', $user, 'ID_MEMBER' )) {
            //return the error
            $status['error'] = 'Error while creating the user: ' . $db->stderr();
            return $status;
        } else {
            //update the stats
            $query = 'UPDATE #__settings SET value = value + 1 	WHERE variable = \'totalMembers\' ';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            $query = 'REPLACE INTO #__settings (variable, value) VALUES (\'latestMember\', ' . $user->ID_MEMBER . '), (\'latestRealName\', ' . $db->quote($userinfo->username) . ')';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //return the good news
            $status['debug'][] = JText::_('USER_CREATION');
            $status['userinfo'] = $this->getUser($userinfo->username);
            return $status;
        }
    }
    function deleteUsername($username)
    {
    }
}
