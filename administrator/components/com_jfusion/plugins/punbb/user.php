<?php

/**
 * @package JFusion_punBB
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
 * JFusion plugin class for punBB
 * @package JFusion_punBB
 */
class JFusionUser_punbb extends JFusionUser{

    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);
      	if ($userlookup->email == $userinfo->email) {
        	//emails match up
            $status['userinfo'] = $userlookup;
        	$status['error'] = false;
        	$status['debug'] = JText::_('USER_EXISTS');
            return $status;
	    } elseif ($userlookup) {
        	//emails match up
            $status['userinfo'] = $userlookup;
        	$status['error'] = JText::_('EMAIL_CONFLICT');
            return $status;
	    } else {
            $status['userinfo'] = $userlookup;
        	$status['error'] = JText::_('UNABLE_CREATE_USER');
            return $status;
	    }
    }


 function &getUser($username)
    {
        // Get user info from database
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT id as userid,username,password,email FROM #__users WHERE username=' . $db->Quote($username);
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
             //Check to see if they are banned
            $query = 'SELECT username FROM #__bans WHERE username=' . $db->Quote($username);
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
        return 'punbb';
    }

    function deleteUser($username)
    {
	    //TODO: create a function that deletes a user
    }

    function destroySession($userinfo, $session)
    {
        // Get the parameters
        $params = JFusionFactory::getParams($this->getJname());

        //Clear the PunBB Cookie
        $punCookie = $params->get('cookie_name');

        setcookie($punCookie, " ", time() - 1800, "/" );
        $status['error'] = false;
        return $status;

    }

    function createSession($userinfo, $options)
    {
        //Now set-up the PunBB cookie for single sign-in
        // Get a database object, and prepare some basic data
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());

        $punCookiePrefix = $params->get('cookie_prefix');
		$punCookieName = $params->get('cookie_name');
        $punCookieDomain = $params->get('cookie_domain');
        $punCookiePath = $params->get('cookie_path');
        $punCookieSecure = $params->get('cookie_secure');
        $punCookieSeed = $params->get('cookie_seed');
        $punCookieContent = serialize(array($userinfo->userid, md5($punCookieSeed.$userinfo->password)));

        $bypost = 1;

        //TODO: This should determine if the user is logged on from a cookie
        //		$vbUserCookie = trim(mosGetParam($_POST, 'cookieuser', '' ) );

		setcookie($punCookiePrefix.$punCookieName, $punCookieContent, time() + 43200, $punCookiePath, $punCookieDomain, $punCookieSecure);

        //TODO: Validate how we pick up the "Rember Me thing"
        if (isset($options['remember'])) {
            $lifetime = time() + 365*24*60*60;
            setcookie("usercookie[username]", $userinfo->username, $lifetime, "/" );
            setcookie("usercookie[password]", $userinfo->password, $lifetime, "/" );
            setcookie($punCookiePrefix.$punCookieName, $punCookieContent, $lifetime, $punCookiePath, $punCookieDomain, $punCookieSecure);
            setcookie("userid", $userinfo->userid, $lifetime, "/" );
        }
        $status['error'] = false;
   		$status['debug'] = JText::_('USERID') . '=' . $userid . ', ' . JText::_('PASSWORD') . '=' . $userinfo->password . ', ' . JText::_('COOKIE_PATH') . '=' . $punCookiePath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $punCookieDomain;
        return $status;
    }


	function filterUsername($username) {
    	//no username filtering implemented yet
    	return $username;
    }
}

