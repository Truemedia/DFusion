<?php

/**
* @package JFusion_SMF
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion User Class for SMF 1.1.x
 * For detailed descriptions on these functions please check the model.abstractuser.php
 * @package JFusion_SMF
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

class JFusionUser_smf extends JFusionUser {

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

    function destroySession($userinfo, $options){
		$status = JFusionJplugin::destroySession($userinfo, $options,$this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
		setcookie($params->get('cookie_name'), '',0,$params->get('cookie_path'),$params->get('cookie_domain'),$params->get('secure'),$params->get('httponly'));
		return $status;
     }

    function createSession($userinfo, $options){
		$status = JFusionJplugin::createSession($userinfo, $options,$this->getJname());
		return $status;
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
	        $status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
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
        $user->ID_GROUP = $params->get('usergroup', 0);
        $user->ID_POST_GROUP = $params->get('userpostgroup', 4);

        //now append the new user data
        if (!$db->insertObject('#__members', $user, 'ID_MEMBER' )) {
            //return the error
            $status['error'] = JText::_('USER_CREATION_ERROR'). ': ' . $db->stderr();
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
}
