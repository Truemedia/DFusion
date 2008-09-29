<?php

/**
* @package JFusion_MyBB
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
* JFusion plugin class for myBB
* @package JFusion_MyBB
*/
class JFusionUser_mybb extends JFusionUser{

    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);

        if ($userlookup) {
            //a matching user has been found
            if ($userlookup->email == $userinfo->email) {
                //emails match up
                if ($userinfo->password_clear) {
                    //we can update the password
                    jimport('joomla.user.helper');
                    if (!$userinfo->password_salt) {
                        $userinfo->password_salt = JUserHelper::genRandomPassword(6);
                    }
                    $userlookup->password = md5(md5($userinfo->password_salt).md5($userinfo->password_clear));
                    $userlookup->password_salt = $userinfo->password_salt;

                    $query = 'UPDATE #__users SET password =' . $db->quote($userlookup->password) . ', salt = '.$db->quote($userlookup->password_salt). ' WHERE uid =' . $userlookup->userid;
                    $db->setQuery($query);
                    if (!$db->query()) {
                        //return the error
                        $status['error'] = 'Error while creating the user: ' . $db->stderr();
                        return $status;
                    }
                    $status['userinfo'] = $userlookup;
                    $status['error'] = false;
                    $status['debug'] = 'User already exists, password was updated to:' . $userlookup->password;
                    return $status;
                } else {
                    //no clear password available, just report back
                    $status['userinfo'] = $userlookup;
                    $status['error'] = false;
                    $status['debug'] = 'User already exists, password was not updated.';
                    return $status;

                }

            } else {
                    //we need to update the email
                    $query = 'UPDATE #__users SET email ='.$db->quote($userinfo->email) .' WHERE uid =' . $userlookup->userid;
                    $db->setQuery($query);
                    if (!$db->query()) {
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
            }
        } else {
            //we need to create a new user

            //found out what usergroup should be used
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('usergroup');
            $username_clean = $this->filterUsername($userinfo->username);

            //prepare the variables
            $user = new stdClass;
            $user->uid = NULL;
            $user->username = $username_clean;
            $user->email = $userinfo->email;

            if ($userinfo->password_clear) {
                //we can update the password
                jimport('joomla.user.helper');
                $user->salt = JUserHelper::genRandomPassword(6);
                $user->password = md5(md5($user->salt).md5($userinfo->password_clear));
                $user->loginkey  = JUserHelper::genRandomPassword(50);
            } else {
                $user->password = $userinfo->password;
                $user->salt = $userinfo->password_salt;
                $user->loginkey  = JUserHelper::genRandomPassword(50);
            }

            $user->usergroup = $usergroup;

            //now append the new user data
            if (!$db->insertObject('#__users', $user, 'uid' )) {
                //return the error
                $status['error'] = 'Error while creating the user: ' . $db->stderr();
                return $status;
            } else {
                //return the good news
                $status['debug'] = 'Created new user with userid:' . $user->id;
                $status['error'] = false;
                $status['userinfo'] = $this->getUser($username_clean);
                $status['action'] = 'created';
                return $status;

            }
        }
    }

    function &getUser($username)
    {
        // Get user info from database
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT uid as userid, username, username as name, email, usergroup, password, salt as password_salt FROM #__users WHERE username=' . $db->Quote($username);
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {

            //Check to see if they are banned
            $query = 'SELECT isbannedgroup FROM #__usergroups WHERE gid=' . $result->usergroup;
            $db->setQuery($query );
            $banCheck = $db->loadObject();

            if ($result->usergroup == 5 || ($banCheck && $banCheck->isbannedgroup == "yes")) {
                $result->block = 1;
            } else {
                $result->block = 0;
            }
        }

        return $result;

    }


    function getJname()
    {
        return 'mybb';
    }

    function deleteUser($username)
    {
        //TODO: create a function that deletes a user
    }

    function destroySession($userinfo, $options)
    {
        $params = JFusionFactory::getParams($this->getJname());
        $cookiedomain = $params->get('cookie_domain');
        $cookiepath = $params->get('cookie_path', '/');

        //Set cookie values
        $expires = mktime(12,0,0,1, 1, 1990);

        if (!$cookiepath) {
            $cookiepath = "/";
        }

        // Clearing Forum Cookies
        $remove_cookies = array('mybb', 'mybbuser', 'mybbadmin');
        if ($cookiedomain) {
            foreach($remove_cookies as $name){
                @setcookie($name, '', $expires, $cookiepath, $cookiedomain);
            }
        } else {
            foreach($remove_cookies as $name){
                @setcookie($name, '', $expires, $cookiepath);
            }
        }
        $status['error'] = false;
        return $status;
    }


    function createSession($userinfo,$options)
    {
        //get cookiedomain, cookiepath (theIggs solution)
        $params = JFusionFactory::getParams($this->getJname());
        $cookiedomain = $params->get('cookie_domain','');
        $cookiepath = $params->get('cookie_path','/');
        //get myBB uid, loginkey
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT uid, loginkey FROM #__users WHERE username=' .$db->quote($userinfo->username) ;
        $db->setQuery($query );
        $user = $db->loadObject();

        // Set cookie values
        $name='mybbuser';
        $value=$user->uid.'_'.$user->loginkey;
        $expires = null;
        $remember='no';
        $httponly=true;

        // Creating Forum Cookies
        //adopted from myBB function  in inc/functions.php
        if (!$cookiepath) {
            $cookiepath = "/";
        }
        if ($expires == -1) {
            $expires = 0;
        } else if ($expires == "" || $expires == null) {
            if ($remember == "no") {
                $expires = 0;
            } else {
                $expires = time() + (60*60*24*365);
                // Make the cookie expire in a years time
            }
        } else {
            $expires = time() + intval($expires);
        }

        $cookiepath = str_replace(array("\n","\r"), "", $cookiepath);
        $cookiedomain = str_replace(array("\n","\r"), "", $cookiedomain);

        // Versions of PHP prior to 5.2 do not support HttpOnly cookies and IE is buggy when specifying a blank domain so set the cookie manually
        $cookie = "Set-Cookie: {$name}=".urlencode($value);
        if ($expires > 0) {
            $cookie .= "; expires=".gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires);
        }
        if (!empty($cookiepath)) {
            $cookie .= "; path={$cookiepath}";
        }
        if (!empty($cookiedomain)) {
            $cookie .= "; domain={$cookiedomain}";
        }
        if ($httponly == true) {
            $cookie .= "; HttpOnly";
        }
        header($cookie, false);

        $status = array();
        $status['debug'] = JText::_('NAME') . '=' . $name . ', ' . JText::_('VALUE') . '=' . $value . ', ' . JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain;
        return $status;


    }

    function filterUsername($username)
    {
        //TODO: no username filtering implemented yet
        return $username;
    }



}

