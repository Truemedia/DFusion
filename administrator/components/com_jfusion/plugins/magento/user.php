<?php

/**
* @package JFusion_magento
* @version 1.0.8-001
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.curl.php');

/**
* @package JFusion_myplugin
*/
class JFusionUser_magento extends JFusionUser{


    /* Magento does not have usernames. The user is identified by an 'identity_id' that is found through
     *  the users e-mail address.
     *  To make it even more difficult for us, there is no simple tablecontaining all userdata, but
     *  the userdata is arranged in tables for different variable types.
     *  User attributes are identified by fixed attribute ID's in these tables
	 *
     *  The usertables are:
     *  customer_entity
     *  customer_address_entity
     *  customer_address_entity_datetime
     *  customer_address_entity_decimal
     *  customer_address_entity_int
     *  customer_address_entity_text
     *  customer_address_entity_varchar
     *  customer_entity_datetime
     *  customer_entity_decimal
     *  customer_entity_int
     *  customer_entity_text
     *  customer_entity_varchar
	 *
     *  The attribute ID's are:
     *  3  Store view
     *  4  Firstname
     *  5  Lastname
     *  8  Password hash
	 *
     */

    function &getUser($username){
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());
        $username = $this->filterUsername($username);

        // Get the user id from Magento's  user table.
        $query = "SELECT entity_id FROM #__customer_entity WHERE email =" . $db->Quote($username );
        $db->setQuery($query);
        $entity = (int) $db->loadResult();

        // check if we have found the user, if not return failure
        if (!$entity) {
            return false;
        }

        // Now get the data we need into the user. As explained, data is in seperate tables.
        // The query returns an array where the data is placed indexed by attribute ID
        $db->setQuery("SELECT attribute_id, value FROM #__customer_entity_varchar WHERE entity_id = ".$entity);
        $myInfo = $db->loadObjectList("attribute_id");

        $instance = new JUser();
        $instance->set('userid',$entity);
        $instance->set('name', $myInfo[4]->value . ' ' . $myInfo[5]->value);   // full name
        $instance->set('username', $username);                                 // username == e-mail
        $instance->set('email',$username);                                     // username == e-mail
		// $instance->set('activation', ???); //TODO
        $hashArr = explode(':', $myInfo[8]->value);
        $instance->set('password',$hashArr[0]);
        // note: The password is either salted or not.
        // the hash is either md5($psw) or
        // md5($salt.$psw):$salt
        $instance->set('password_salt',$hashArr[1]);
		// $instance->set('registerdate',???);
		// $instance->set('lastvisitdate',???);
        $instance->set('block',0);
		// $instance->set('password_clear', $password_clear ); // ?
		// $instance->set('password',$this->getEncryptedPassword($user));

		return $instance;
    }

    function updateUser($userinfo) {  //TODO!!
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

    function getJname(){
        return 'magento';
    }

    function destroySession($userinfo, $options){
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $post_url = $source_url.$params->get('logout_url');
        $status = JFusionCurl::RemoteLogout($post_url);
        return $status;
    }


    function createSession($userinfo, $options){
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
        $status=JFusionCurl::RemoteLogin($post_url,$formid,$userinfo->username,$userinfo->password_clear,true,true);
        return $status;
    }

    function filterUsername($username){
      //no username filtering implemented yet
      return $username;
    }
}