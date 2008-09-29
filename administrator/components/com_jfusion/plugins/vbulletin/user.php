<?php

/**
* @package JFusion_vBulletin
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
* JFusion plugin class for vBulletin 3.6.8
* @package JFusion_vBulletin
*/
class JFusionUser_vbulletin extends JFusionUser{

    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);
        if ($userlookup->email == $userinfo->email) {

			//update the password if we have access to it
			if($userinfo->password_clear){
        		jimport('joomla.user.helper');
        		$password_salt = JUserHelper::genRandomPassword(3);
				$password = md5(md5($userinfo->password_clear).$password_salt);
                $query = 'UPDATE #__user SET password = ' . $db->quote($password). ', salt = ' . $db->quote($password_salt). ' WHERE userid  = ' . $userlookup->userid;
            	$db->setQuery($query );
            	if (!$db->Query()) {
	                $status['error'] = 'Could not update the vbulletin password: ' . $db->stderr();
	            }
    	    	$status['debug'] = 'User already exists, password was updated to:' . $password . 'with salt: ' .$password_salt;
            } else {
   	    	    $status['debug'] = 'User already exists, password was not updated';
            }

            //emails match up
            $status['userinfo'] = $userlookup;
            $status['error'] = false;

            return $status;
        } else if ($userlookup) {

                //we need to update the email
   	    		$query = 'UPDATE #__user SET email ='.$db->quote($userinfo->email) .' WHERE userid =' . $userlookup->userid;
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

            //found out what usergroup should be used
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('usergroup');

            //lookup the name of the usergroup
	        $db = JFusionFactory::getDatabase($this->getJname());
    	    $query = 'SELECT group_name from #__groups WHERE group_id = ' . $usergroup_id;
        	$db->setQuery($query );
        	$usergroupname = $db->loadResult();

            //prepare the variables
            $user = new stdClass;
			$user->userid = NULL;
			$user->usergroupid = $usergroup;
			$user->displaygroupid = $usergroup;
			$user->usertitle = $usergroupname;
			$user->username = $userinfo->username;

            if($userinfo->password_clear){
        		jimport('joomla.user.helper');
        		$user->salt = JUserHelper::genRandomPassword(3);
				$user->password = md5(md5($userinfo->password_clear).$user->salt);
            } else {
				$user->salt = $userinfo->password_salt;
				$user->password = $userinfo->password;
            }

			$user->email = $userinfo->email;
			$user->passworddate = date("Y/m/d");
			$user->joindate = time();

            //now append the new user data
            if (!$db->insertObject('#__user', $user, 'userid' )) {
                //return the error
                $status['error'] = 'Error while creating the user: ' . $db->stderr();
                return $status;
            }

	        //prepare the variables
    	    $userfield = new stdClass;
			$userfield->userid = $user->userid;

            if (!$db->insertObject('#__userfield', $userfield )) {
                //return the error
                $status['error'] = 'Error while creating the userfield: ' . $db->stderr();
                return $status;
            }

            //return the good news
            $status['debug'] = 'Created new user with userid:' . $user->userid;
            $status['error'] = false;
            $status['userinfo'] = $this->getUser($userinfo->username);
            return $status;

        }
    }

    function &getUser($username)
    {
        // Get user info from database
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT userid, username, username as name, email, password, salt as password_salt FROM #__user WHERE username=' . $db->Quote($username);
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT userid FROM #__userban WHERE userid='. $result->userid;
            $db->setQuery($query);
            if ($db->loadObject()) {
                $result->block = 1;
            } else {
                $result->block = 0;
            }
            return $result;

        } else {
            return false;
        }
    }


    function getJname()
    {
        return 'vbulletin';
    }

    function getTablename()
    {
        return 'user';
    }


    function deleteUser($username)
    {
	    //TODO: create a function that deletes a user
    }

    function destroySession($userinfo, $options)
    {
        // Get the parameters
        $params = JFusionFactory::getParams($this->getJname());

        //Clear the vBulletin Cookie
        $vbCookiePrefix = $params->get('cookie_prefix','bb');
        $vbCookieDomain = $params->get('cookie_domain');
        $vbCookiePath = $params->get('cookie_path');

        setcookie($vbCookiePrefix.'userid', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain );
        setcookie($vbCookiePrefix.'password', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain );
        setcookie($vbCookiePrefix.'styleid', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain );
        setcookie($vbCookiePrefix.'sessionhash', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain );
    }

    function createSession($userinfo, $options)
    {
        // Get a database object, and prepare some basic data
        $status = array();
        $status['debug'] = '';
        $userid = $userinfo->userid;

        if ($userid && !empty($userid) && ($userid > 0)) {
            $params = JFusionFactory::getParams($this->getJname());
            $vbCookiePrefix = $params->get('cookie_prefix');
            $vbCookieDomain = $params->get('cookie_domain');
            $vbCookiePath = $params->get('cookie_path');
            $vbLicense = $params->get('source_license','');

            $bypost = 1;

            if (isset($options['remember'])) {
                $lifetime = time() + 365*24*60*60;
                setcookie('usercookie[username]', $userinfo->username, $lifetime, $vbCookiePath, $vbCookieDomain );
                setcookie('usercookie[password]', $userinfo->password, $lifetime, $vbCookiePath, $vbCookieDomain );
                setcookie($vbCookiePrefix.'userid', $userinfo->userid, $lifetime, $vbCookiePath, $vbCookieDomain );
                setcookie($vbCookiePrefix.'password', md5($userinfo->password . $vbLicense ), $lifetime, $vbCookiePath, $vbCookieDomain );
                setcookie('userid', $userinfo->userid, $lifetime, $vbCookiePath, $vbCookieDomain );

            } else {
                setcookie($vbCookiePrefix.'userid', $userinfo->userid, time() + 43200, $vbCookiePath, $vbCookieDomain );
                setcookie($vbCookiePrefix.'password', md5($userinfo->password . $vbLicense ), time() + 43200, $vbCookiePath, $vbCookieDomain );
                setcookie('userid', $userinfo->userid, time() + 43200, $vbCookiePath, $vbCookieDomain );
            }
            $status['error'] = false;
       		$status['debug'] .= JText::_('CREATED') . ' ' . JText::_('SESSION') . ': ' .JText::_('USERID') . '=' . $userinfo->userid . ', ' . JText::_('SESSIONID') . '=' . $session_key . ', ' . JText::_('COOKIE_PATH') . '=' . $vbCookiePath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $vbCookieDomain;
            $status['debug'] = 'created session';
            return $status;
        } else {
            //could not find a valid userid
            $status['error'] = JText::_('INVALID_USERID');
            return $status;
        }
    }

    function filterUsername($username)
    {
        //no username filtering implemented yet
        return $username;

    }
}

