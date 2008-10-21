<?php

/**
* @package JFusion_Moodle
* @version 1.0.8
* @author Henk Wevers
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/


  /* reminder for jFusion abstract user definition
     * gets the userinfo from the JFusion integrated software. Definition of object:
     * $userinfo->userid
     * $userinfo->name
     * $userinfo->username
     * $userinfo->email
     * $userinfo->password (encrypted password)
     * $userinfo->password_salt (salt used to encrypt password)
     * $userinfo->block (0 if allowed to access site, 1 if user access is blocked)
     * $userinfo->registerdate
     * $userinfo->lastvisitdate
     * @param string $username username
     * @return object userinfo Object containing the user information
     */



// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the Abstract User Class
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
require_once('loginhelper.php');
//require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'loginhelper.php');
/**
* @package JFusion_Moodle
*/
class JFusionUser_moodle extends JFusionUser{



    function &getUser($username)
    {
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());
        $username = $this->filterUsername($username);
        $query = 'SELECT id as userid, username, firstname as name, lastname, email, password, NULL as password_salt, confirmed as activation FROM #__user '. 'WHERE username=' . $db->Quote($username);
        $db->setQuery($query);
        $result = $db->loadObject();
        // contruct full name
        $result->name = trim($result->name.' '.$result->lastname);
        // reverse activation, moodle: require activated = 0, confirmed = 1
        $result->activation = !$result->activation;
        return $result;
     }

    function updateUser($userinfo)  //TODO!!
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

    function getJname()
    {
        return 'moodle';
    }

    function destroySession($userinfo, $options)
    {
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $post_url = $source_url.$params->get('logout_url');
        $cookies = array();
        $cookie  = array();
        $status['error'] = '';
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $cookies_to_set = array();
        $cookies_to_set_index = 0;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_URL,$post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_REFERER, "");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'read_header');
        $remotedata = curl_exec($ch);
        curl_close($ch);
        #we have to set the cookies now
        setmycookies($cookies_to_set);
        return $status;
    }


    function createSession($userinfo, $options)
    {
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $post_url = $source_url.$params->get('login_url');
        $formid = $params->get('loginform_id');
        $cookies = array();
        $cookie  = array();
        $status['error'] = '';
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $cookiearr = array();
        $cookies_to_set = array();
        $cookies_to_set_index = 0;
        $status=RemoteLogin($post_url,$formid,$userinfo->username,$userinfo->password_clear);
        return $status;
    }

    function filterUsername($username)
    {
      //no username filtering implemented yet
      return $username;
    }
}