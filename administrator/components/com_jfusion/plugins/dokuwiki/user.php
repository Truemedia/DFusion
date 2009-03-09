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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

require_once( dirname(__FILE__).'/dokuwiki.php');

/**
 * JFusion plugin class for punBB
 * @package JFusion_punBB
 */
class JFusionUser_dokuwiki extends JFusionUser {
    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        //$db = & JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $share = Dokuwiki::getInstance();

        $userinfo->username = strtolower($userinfo->username);

        $update_block = $params->get('update_block');
        $update_activation = $params->get('update_activation');
        $update_email = $params->get('update_email');

        $status = array();
        $status['debug'] = array();
        $status['error'] = array();

		//check to see if a valid $userinfo object was passed on
		if(!is_object($userinfo)) {
			$status['error'][] = JText::_('NO_USER_DATA_FOUND');
			return $status;
		}
        //find out if the user already exists
        $existinguser = $this->getUser($userinfo->username);

        if (!empty($existinguser)) {
            //a matching user has been found
			$status['debug'][] = JText::_('USER_DATA_FOUND');
            if ($existinguser->email != $userinfo->email) {
			  $status['debug'][] = JText::_('EMAIL_CONFLICT');
              if ($update_email || $overwrite) {
			      $status['debug'][] = JText::_('EMAIL_CONFLICT_OVERWITE_ENABLED');
                  $changes['mail'] = $userinfo->email;
                  $status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
              } else {
                //return a email conflict
			    $status['debug'][] = JText::_('EMAIL_CONFLICT_OVERWITE_DISABLED');
                $status['error'][] = JText::_('EMAIL') . ' ' . JText::_('CONFLICT').  ': ' . $existinguser->email . ' -> ' . $userinfo->email;
                $status['userinfo'] = $existinguser;
                return $status;
              }
            }
			if ( isset($userinfo->password_clear) && strlen($userinfo->password_clear) ) {
				// add password_clear to existinguser for the Joomla helper routines
				$existinguser->password_clear = $userinfo->password_clear;
                $changes['pass'] = $userinfo->password_clear;
                /*
			    //check if the password needs to be updated
	    	    $model = JFusionFactory::getAuth($this->getJname());
        		$testcrypt = $model->generateEncryptedPassword($existinguser);
            	if ($testcrypt != $existinguser->password) {
                    $existinguser->password = $changes['pass'] = $testcrypt;
                    $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
            	} else {
                	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ':' .  JText::_('PASSWORD_VALID');
            	}
                */
        	} else {
            	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' . JText::_('PASSWORD_UNAVAILABLE');
        	}

            if (!$share->auth->modifyUser($userinfo->username,$changes)) {
                $status['error'][] = 'ERROR: Updating '.$userinfo->username;
            }

            $status['userinfo'] = $existinguser;
            if (empty($status['error'])) {
                $status['action'] = 'updated';
            }
            return $status;
        } else {
			$status['debug'][] = JText::_('NO_USER_FOUND_CREATING_ONE');
            $this->createUser($userinfo, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            return $status;
        }
    }


    function &getUser($username)
    {
        $share = Dokuwiki::getInstance();
        $raw_user = $share->getUserList($username);

        if (is_array($raw_user )){
            $user = new stdClass;
            $user->userid = $raw_user['username'];
            $user->username = $raw_user['username'];
            $user->password = $raw_user['pass'];
            $user->email = $raw_user['mail'];
            return $user;
        }
        return false;
    }


    function getJname()
    {
        return 'dokuwiki';
    }

    function deleteUser($userinfo)
    {
//  		$user[$username] = $username;
//        $share = Dokuwiki::getInstance();
//        $d = $share->auth->deleteUsers($user);
	    //TODO: create a function that deletes a user
    }

    function destroySession($userinfo, $options){
		return JFusionJplugin::destroySession($userinfo, $options,$this->getJname());
     }

    function createSession($userinfo, $options){
		return JFusionJplugin::createSession($userinfo, $options,$this->getJname());
    }

	function filterUsername($username) {
    	//no username filtering implemented yet
    	return $username;
    }

    function createUser($userinfo, &$status)
    {
        $share = Dokuwiki::getInstance();

        if (isset($userinfo->password_clear)) {
            $pass = $userinfo->password_clear;
        } else {
            $pass = $userinfo->password;
        }
        //now append the new user data
        if (!$share->auth->createUser($userinfo->username,$pass,$userinfo->name,$userinfo->email)) {
            //return the error
            $status['error'] = JText::_('USER_CREATION_ERROR');
            return $status;
        } else {
            //return the good news
            $status['debug'][] = JText::_('USER_CREATION');
            $status['userinfo'] = $this->getUser($userinfo->username);
            return $status;
        }
    }
}

