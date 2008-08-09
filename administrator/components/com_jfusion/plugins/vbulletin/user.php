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

    function updateUser($userinfo)
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
                $query = 'UPDATE #__user SET password = ' . $db->quote($password). ', salt ' . $db->quote($password_salt). ' WHERE userid  = ' . $userlookup->userid;
            	$db->setQuery($query );
            	if (!$db->Query()) {
	                $status['error'] = 'Could not update the SMF password: ' . $db->stderr();
	            }
            }

            //emails match up
            $status['userinfo'] = $userlookup;
            $status['error'] = false;
            $status['debug'] = JText::_('USER_EXISTS');
            return $status;
        } else if ($userlookup) {
            //emails match up
            $status['userinfo'] = $userlookup;
            $status['error'] = JText::_('EMAIL_CONFLICT');
            return $status;
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
			$user->joindate = date("Y/m/d");

            //now append the new user data
            if (!$db->insertObject('#__user', $user, 'userid' )) {
                //return the error
                $status['error'] = 'Error while creating the user: ' . $db->stderr();
                return $status;
            } else {
                //return the good news
                $status['debug'] = 'Created new user with userid:' . $user->id;
                $status['error'] = false;
                $status['userinfo'] = $this->getUser($username_clean);
                return $status;
            }
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
                setcookie('usercookie[username]', $userinfo->username, $lifetime, "/" );
                setcookie('usercookie[password]', $userinfo->password, $lifetime, "/" );
                setcookie($vbCookiePrefix.'userid', $userinfo->userid, $lifetime, $vbCookiePath, $vbCookieDomain );
                setcookie($vbCookiePrefix.'password', md5($userinfo->password . $vbLicense ), $lifetime, $vbCookiePath, $vbCookieDomain );
                setcookie('userid', $userinfo->userid, $lifetime, "/" );

            } else {
                setcookie($vbCookiePrefix.'userid', $userinfo->userid, time() + 43200, $vbCookiePath, $vbCookieDomain );
                setcookie($vbCookiePrefix.'password', md5($userinfo->password . $vbLicense ), time() + 43200, $vbCookiePath, $vbCookieDomain );
                setcookie('userid', $userinfo->userid, time() + 43200, "/" );
            }
            $status['error'] = false;
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

