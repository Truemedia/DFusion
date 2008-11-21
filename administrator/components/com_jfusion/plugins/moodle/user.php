<?php

/**
* @package JFusion_Moodle
* @version 1.0.8-008
* @author Henk Wevers
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

/** NOTE 1
 * We can map the sitepolicy system on the block field. The sitepolicy system in Moodle works as follows:
 * If, in the moodle table "config" the record "sitepolicy" is not empty but contains an URL to a page
 * The field "policyagreed" in the usertable is activated and should contain a 1 if policy is agreed
 * With moodle as master this can be used to block a user to an integration as long as policy is not agreed
 * If you use Moodle as slave, You should use the policy agreed page in Moodle to contain an explanation why
 * the user is blocked.
 * NOTE 2: When creating a new userrecord in Moodle, the fields language and characterset information are mandatory.
 * In this version I have set them to GB. In the next version this will be configurable in the plugin editing screen
  */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the Abstract User Class
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.curl.php');
/**
* @package JFusion_Moodle
*/
class JFusionUser_moodle extends JFusionUser{

    function &getUser($username){
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $update_block = $params->get('update_block');
        $username = $this->filterUsername($username);
        if ($params->get('allow_email_login')){
           if(strpos($username, '@')) {
              $identifier = 'email';
           } else {
             $identifier = 'username';
           }
        } else {
           $identifier = 'username';
        }
        $query = 'SELECT id as userid, username, firstname as name, lastname, email, password, NULL as password_salt, confirmed as activation,policyagreed as block  FROM #__user WHERE '.$identifier.' = ' . $db->Quote($username);
        $db->setQuery($query);
        $result = $db->loadObject();
      if ($result) {
          // contruct full name
          $result->name = trim($result->name.' '.$result->lastname);
          // reverse activation, moodle: require activated = 0, confirmed = 1
          $result->activation = !$result->activation;
      // get the policy agreed stuff
          $query = 'SELECT value FROM #__config WHERE  name = sitepolicy';
           $db->setQuery($query);
          $sitepolicy = $db->loadObject();
      if ($sitepolicy->value){
        $result->block = !$result->block;
      }  else {
              $result->block = 0;
      }
      }
      return $result;
     }
    function updateUser($userinfo,$overwrite){
      // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $update_activation = $params->get('update_activation');
        $update_email = $params->get('update_email');
        $update_block = $params->get('update_block');
        $update_activation = $params->get('update_activation');

        $status = array();
        $status['debug'] = array();

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

            if (!empty($userinfo->password_clear)) {
                if (!($params->get('passwordsaltmain'))) {
                   $existingpassword = md5($userinfo->password_clear.$params->get('passwordsaltmain'));
              	} else {
                  	$existingpassword = md5($userinfo->password_clear);
              	}
              	if ($existingpassword != $existinguser->password) {
	              	$this->updatePassword($userinfo, $existinguser, $status);
              	} else {
                  	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' .substr($existingpassword,0,6) . '********';
              	}
            } else {
            	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': No password_clear available';
            }

      //check the activation status
          if ($existinguser->activation != $userinfo->activation) {
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
                  $status['debug'][] = JText::_('SKIPPED_ACTIVATION_UPDATE') . ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
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
           return $status;
        }
     }
    function getJname(){
      return 'moodle';
    }

    function destroySession($userinfo, $options){
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $post_url = $source_url.$params->get('logout_url');
       $cookiedomain = $params->get('cookie_domain');
      $cookiepath = $params->get('cookie_path');
        $status = JFusionCurl::RemoteLogout($post_url,$cookiedomain,$cookiepath);
        return $status;
     }
    function createSession($userinfo, $options){
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $cookiedomain = $params->get('cookie_domain');
        $cookiepath = $params->get('cookie_path');
        $cookieexpires = $params->get('cookie_expires');
        $post_url = $source_url.$params->get('login_url');
        $formid = $params->get('loginform_id');
        $override = $params->get('override');
      $hidden = false;
      $buttons = true;
      $integrationtype = 1;
      $relpath=true;
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
        $status=JFusionCurl::RemoteLogin($post_url,$formid,$userinfo->username,$userinfo->password_clear,
              $integrationtype,$relpath,$hidden,$buttons,$override,$cookiedomain,$cookiepath,$cookieexpires);
        return $status;
    }
    function filterUsername($username){
      //no username filtering implemented yet
      return $username;
    }
    function updatePassword($userinfo, $existinguser, &$status){
     $params = JFusionFactory::getParams('moodle');
    if ($params->get('passwordsaltmain')){
      $existinguser->password = md5($userinfo->password_clear.$params->get('passwordsaltmain'));
     } else {
      $existinguser->password = md5($userinfo->password_clear);
    }
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__user SET password =' . $db->quote($existinguser->password) . ' WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        } else {
          $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
        }
  }

    function updateUsername($userinfo, &$existinguser, &$status){
    }

    function updateEmail($userinfo, &$existinguser, &$status){  //TODO ? check for duplicates, or leave it atdb error
        //we need to update the email
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__user SET email =' . $db->quote($existinguser->email) . ' WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    function blockUser($userinfo, &$existinguser, &$status){
    $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT value FROM #__config WHERE  name = sitepolicy';
    $db->setQuery($query);
    $sitepolicy = $db->loadObject();
    if ($sitepolicy->value){
          $query = 'UPDATE #__user SET policyagreed = false WHERE id =' . $existinguser->userid;
      $db->setQuery($query);
          if (!$db->query()) {
              $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
          } else {
            $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
          }
    } else {
             $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . JText::_('BLOCK_UPDATE_SITEPOLICY_NOT_SET');
    }
    }

    function unblockUser($userinfo, &$existinguser, &$status){
    $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT value FROM #__config WHERE  name = sitepolicy';
    $db->setQuery($query);
    $sitepolicy = $db->loadObject();
    if ($sitepolicy->value){
          $query = 'UPDATE #__user SET policyagreed = true WHERE id =' . $existinguser->userid;
      $db->setQuery($query);
          if (!$db->query()) {
              $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
          } else {
            $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
          }
    } else {
             $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . JText::_('BLOCK_UPDATE_SITEPOLICY_NOT_SET');
    }
    }

    function activateUser($userinfo, &$existinguser, &$status){
        //activate the user
        $db = JFusionFactory::getDatabase($this->getJname());
         $query = 'UPDATE #__user SET confirmed = true WHERE id =' . $existinguser->userid;
    $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function inactivateUser($userinfo, &$existinguser, &$status){
        $db = JFusionFactory::getDatabase($this->getJname());
         $query = 'UPDATE #__user SET confirmed = false WHERE id =' . $existinguser->userid;
    $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function createUser($userinfo, &$status){
        //found out what usergroup should be used
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup = $params->get('usergroup');
        //prepare the variables
        $user = new stdClass;
        $user->id = NULL;
        $user->username = $userinfo->username;
       $parts = explode(' ', $userinfo->name);
    $user->firstname = $parts[0];
    if ($parts[(count($parts)-1)]){
      for ($i=1;$i< (count($parts)); $i++) {
             $lastname= $lastname.' '.$parts[$i];
         }
    }
    $user->lastname = $lastname;
    $user->email = strtolower($userinfo->email);
        if (isset($userinfo->password_clear)) {
       $params = JFusionFactory::getParams('moodle');
      if ($params->get('passwordsaltmain')){
        $user->password = md5($userinfo->password_clear.$params->get('passwordsaltmain'));
       } else {
        $user->password = md5($userinfo->password_clear);
      }
         } else {
            $user->password = $userinfo->password;
        }
    if ($userinfo->activation) {
      $user->confirmed = 0;
    } else {
      $user->confirmed = 1;
      }
    $user->policyagreed = !$userinfo->block; // just write, true doesn't harm'
    // standard moodle stuff
    $user->auth='manual';
    $user->mnethostid = 1;
    $user->timemodified = time();

        //now append the new user data
        if (!$db->insertObject('#__user', $user, 'id' )) {
            //return the error
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
            return;
        }

        // get new ID
        $userid = $db->insertid();

        // have to set user preferences
        $user_1     = new stdClass;
        $user_1->id    = NULL;
        $user_1->userid  = $userid;
        $user_1->name   = 'auth_forcepasswordchange';
        $user_1->value  = 0;
         if (!$db->insertObject('#__user_preferences', $user_1, 'id' )) {
            //return the error
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
            return;
        }

        $user_1->id    = NULL;
        $user_1->userid  = $userid;
        $user_1->name   = 'email_bounce_count';
        $user_1->value  = 1;
         if (!$db->insertObject('#__user_preferences', $user_1, 'id' )) {
            //return the error
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
            return;
        }

        $user_1->id    = NULL;
        $user_1->userid  = $userid;
        $user_1->name   = 'email_send_count';
        $user_1->value  = 1;
         if (!$db->insertObject('#__user_preferences', $user_1, 'id' )) {
            //return the error
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
            return;
        }
        //return the good news
        $status['debug'][] = JText::_('USER_CREATION');
    }

    function deleteUsername($username)
    {
    }
}