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

    function updateUser($userinfo)
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
        $query = 'SELECT uid as userid, username, email, usergroup, password, salt as password_salt FROM #__users WHERE username=' . $db->Quote($username);
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {

           //Check to see if they are banned
            $query = 'SELECT isbannedgroup FROM #__usergroups WHERE gid=' . $result->usergroup;
			$db->setQuery( $query );
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

    	if(!$cookiepath) {
         	$cookiepath = "/";
      	}

   		// Clearing Forum Cookies
   		$remove_cookies = array('mybb', 'mybbuser', 'mybbadmin');
      	if($cookiedomain) {
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
      	if(!$cookiepath){
        	$cookiepath = "/";
      	}
      	if($expires == -1){
   			$expires = 0;
   		} else if($expires == "" || $expires == null){
   			if($remember == "no"){
   				$expires = 0;
   			} else {
   				$expires = time() + (60*60*24*365); // Make the cookie expire in a years time
	   		}
   		} else {
   			$expires = time() + intval($expires);
   		}

   		$cookiepath = str_replace(array("\n","\r"), "", $cookiepath);
   		$cookiedomain = str_replace(array("\n","\r"), "", $cookiedomain);

   		// Versions of PHP prior to 5.2 do not support HttpOnly cookies and IE is buggy when specifying a blank domain so set the cookie manually
   		$cookie = "Set-Cookie: {$name}=".urlencode($value);
   		if($expires > 0){
   			$cookie .= "; expires=".gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires);
   		}
   		if(!empty($cookiepath)){
   			$cookie .= "; path={$cookiepath}";
   		}
   		if(!empty($cookiedomain)){
   			$cookie .= "; domain={$cookiedomain}";
   		}
   		if($httponly == true){
   			$cookie .= "; HttpOnly";
   		}
   		header($cookie, false);

    	$status = array();
   		$status['debug'] .= JText::_('NAME') . '=' . $name . ', ' . JText::_('VALUE') . '=' . $value . ', ' . JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain;
		return $status;


   	}

    function filterUsername($username) {
    	//TODO: no username filtering implemented yet
    	return $username;
    }



}

