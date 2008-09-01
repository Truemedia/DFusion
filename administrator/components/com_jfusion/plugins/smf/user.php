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
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);
        if ($userlookup->email == $userinfo->email) {
            //emails match up

			//update the password if we have access to it
			if($userinfo->password_clear){
            	$password = sha1(strtolower($userinfo->username) . $userinfo->password_clear);
            	$password_salt = substr(md5(rand()), 0, 4);
                $query = 'UPDATE #__members SET passwd = ' . $db->quote($password). ', passwordSalt ' . $db->quote($password_salt). ' WHERE ID_MEMBER  = ' . $userlookup->userid;
            	$db->setQuery($query );
            	if (!$db->Query()) {
	                $status['error'] = 'Could not update the SMF password: ' . $db->stderr();
	            }
            }

            $status['userinfo'] = $userlookup;
            $status['error'] = false;
            $status['debug'] = JText::_('USER_EXISTS');
            return $status;
        } elseif ($userlookup) {

            //emails do not match up
            if ($overwrite) {
                //we need to update the email
				$db =& JFactory::getDBO();
   	    		$query = 'UPDATE #__users SET emailAddress ='.$db->quote($userinfo->email) .' WHERE ID_MEMBER =' . $userlookup->userid;
       			$db->setQuery($query);
				if(!$db->query()) {
					//update failed, return error
	            	$status['userinfo'] = $userlookup;
    	        	$status['error'] = 'Error while updating the user email: ' . $db->stderr();
        	    	return $status;
        		} else {
	            	$status['userinfo'] = $userinfo;
    	        	$status['error'] = false;
   	        		$status['debug'] = ' Update the email address from: ' . $userlookup->email . ' to:' . $userinfo->email;
        	    	return $status;
        		}


            } else {
				//overwite disabled return an error
            	$status['userinfo'] = $userlookup;
            	$status['error'] = JText::_('EMAIL_CONFLICT');
            	return $status;
            }

        } else {

            //we need to create a new SMF user

            //check to see if the smf_api.php file exists
            $params = JFusionFactory::getParams($this->getJname());
            $source_path = $params->get('source_path');

            //prepare the user variables
            $user = new stdClass;
            $user->ID_MEMBER = NULL;
            $user->memberName = $userinfo->username;
            $user->realName = $userinfo->name;
            $user->emailAddress = $userinfo->email;

            if($userinfo->password_clear){
            	$user->passwd = sha1(strtolower($userinfo->username) . $userinfo->password_clear);
            	$user->passwordSalt = substr(md5(rand()), 0, 4);
            } else {
            	$user->passwd = $userinfo->password;
            	$user->passwordSalt = $userinfo->password_salt;
            }

            $user->posts = 0 ;
            $user->dateRegistered = time();
            $user->is_activated = 1;
            $user->personalText = '';
            $user->pm_email_notify = 1;
            $user->ID_THEME = 0;
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
                    $status['error'] = 'Error while updating the user stats: ' . $db->stderr();
                    return $status;
                }

                $query = 'REPLACE INTO #__settings (variable, value) VALUES (\'latestMember\', ' . $user->ID_MEMBER . '), (\'latestRealName\', ' . $db->quote($userinfo->username) . ')';
                $db->setQuery($query);
                if (!$db->query()) {
                    //return the error
                    $status['error'] = 'Error while updating the user stats: ' . $db->stderr();
                    return $status;
                }

                //return the good news
                $status['debug'] = 'Created new user with userid:' . $user->ID_MEMBER;
                $status['error'] = false;
                $status['userinfo'] = $this->getUser($userinfo->username);
                return $status;
            }
        }
    }

    function &getUser($username)
    {
        // initialise some objects
        $params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT ID_MEMBER as userid, memberName as username, realName as name, emailAddress as email, passwd as password, passwordSalt as password_salt FROM #__members WHERE memberName=' . $db->Quote($username) ;
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT ID_MEMBER FROM #__ban_items WHERE ID_MEMBER= ' . $result->userid;
            $db->setQuery($query);
            if ($db->loadObject()) {
                $result->block = 1;
            } else {
                $result->block = 0;
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
        $cookiename = $params->get('cookiename');

        //check to see if we can find a SMF session
        if (isset($_COOKIE[$cookiename])) {
            $db = JFusionFactory::getDatabase($this->getJname());
            $query = 'DELETE FROM #__sessions WHERE session_id = ' . $db->Quote($_COOKIE[$cookiename]) . ' LIMIT 1';
            $db->setQuery($query );
            if ($db->Query()) {
                $status['debug'] = 'Destroyed the SMF session using the smf_api.php';
                $status['error'] = false;
                return $status;
            } else {
                $status['error'] = 'Could not destroy session: ' . $db->stderr();
                return $status;
            }
        } else {
            $status['error'] = 'Could not find a SMF session cookie and therefore could not logout. Please set your cookies to .yourdomain.com in SMF in order for Joomla to logout SMF';
            return $status;
        }
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
            $cookie_length = 3600 + $options['remember']*31536000;
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
}

