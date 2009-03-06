<?php

/**
 * @package JFusion
 * @subpackage Models
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Abstract interface for all JFusion plugin implementations.
* @package JFusion
*/
class JFusionUser{

    /**
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
    function &getUser($username)
    {
        return 0;
    }

    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
    }

     /**
     * Function that automatically logs out the user from the integrated software
     * $result['error'] (contains any error messages)
     * $result['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the userinfo
     * @param array $options Array with the login options, such as remember_me
     * @return array result Array containing the result of the session destroy
     */
    function destroySession($userinfo, $options)
    {
    }

     /**
     * Function that automatically logs in the user from the integrated software
     * $result['error'] (contains any error messages)
     * $result['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the userinfo
     * @param array $options Array with the login options, such as remember_me     *
     * @return array result Array containing the result of the session creation
     */
    function createSession($userinfo, $options)
    {
    }


     /**
     * Function that filters the username according to the JFusion plugin
     * @param string $username Username as it was entered by the user
     * @return string filtered username that should be used for lookups
     */
    function filterUsername($username)
    {
        return $username;
    }


     /**
     * Updates or creates a user for the integrated software. This allows JFusion to have external softwares as slave for user management
     * $result['error'] (contains any error messages)
     * $result['userinfo'] (contains the userinfo object of the integrated software user)
     * @param object $suserinfo Object containing the userinfo
     * @return array result Array containing the result of the user update
     */
    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = & JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $update_block = $params->get('update_block');
        $update_activation = $params->get('update_activation');
        $update_email = $params->get('update_email');

        $status = array();
        $status['debug'] = array();
        $status['error'] = array();

		//check to see if a valid $userinfo object was passed on
		if(!is_object($userinfo)){
			$status['error'][] = JText::_('NO_USER_DATA_FOUND');
			return $status;
		}

        //find out if the user already exists
        $existinguser = $this->getUser($userinfo->username);

        if (!empty($existinguser)) {
        	$changed = false;
            //a matching user has been found
			$status['debug'][] = JText::_('USER_DATA_FOUND');
            if ($existinguser->email != $userinfo->email) {
			  $status['debug'][] = JText::_('EMAIL_CONFLICT');
              if ($update_email || $overwrite) {
			      $status['debug'][] = JText::_('EMAIL_CONFLICT_OVERWITE_ENABLED');
                  $this->updateEmail($userinfo, $existinguser, $status);
                  $changed = true;
              } else {
                //return a email conflict
			    $status['debug'][] = JText::_('EMAIL_CONFLICT_OVERWITE_DISABLED');
                $status['error'][] = JText::_('EMAIL') . ' ' . JText::_('CONFLICT').  ': ' . $existinguser->email . ' -> ' . $userinfo->email;
                $status['userinfo'] = $existinguser;
                return $status;
              }
            }

			if (!empty($userinfo->password_clear) && strlen($userinfo->password_clear) != 32){
				// add password_clear to existinguser for the Joomla helper routines
				$existinguser->password_clear=$userinfo->password_clear;
			    //check if the password needs to be updated
	    	    $model = JFusionFactory::getAuth($this->getJname());
        		$testcrypt = $model->generateEncryptedPassword($existinguser);
            	if ($testcrypt != $existinguser->password) {
                	$this->updatePassword($userinfo, $existinguser, $status);
                	$changed = true;
            	} else {
                	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ':' .  JText::_('PASSWORD_VALID');
            	}
        	} else {
            	$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' . JText::_('PASSWORD_UNAVAILABLE');
        	}

            //check the blocked status
            if ($existinguser->block != $userinfo->block) {
              if ($update_block || $overwrite) {
                  if ($userinfo->block) {
                      //block the user
                      $this->blockUser($userinfo, $existinguser, $status);
                      $changed = true;
                  } else {
                      //unblock the user
                      $this->unblockUser($userinfo, $existinguser, $status);
                      $changed = true;
                  }
              } else {
                //return a debug to inform we skiped this step
                $status['debug'][] = JText::_('SKIPPED_BLOCK_UPDATE') . ': ' . $existinguser->block . ' -> ' . $userinfo->block;
              }
            }

            //check the activation status
            if ($existinguser->activation != $userinfo->activation) {
              if ($update_activation || $overwrite) {
                  if ($userinfo->activation) {
                      //inactiva the user
                      $this->inactivateUser($userinfo, $existinguser, $status);
                      $changed = true;
                  } else {
                      //activate the user
                      $this->activateUser($userinfo, $existinguser, $status);
                      $changed = true;
                  }
              } else {
                //return a debug to inform we skiped this step
                $status['debug'][] = JText::_('SKIPPED_ACTIVATION_UPDATE') . ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
              }
            }

            $status['userinfo'] = $existinguser;
            if (empty($status['error'])) {
            	if($changed == true){
                	$status['action'] = 'updated';
            	} else {
            		$status['action'] = 'unchanged';
            	}
            } else {
            	$status['action'] = 'error';
            }
            return $status;

        } else {
			$status['debug'][] = JText::_('NO_USER_FOUND_CREATING_ONE');
            $this->createUser($userinfo, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            } else {
            	$status['action'] = 'error';
            }
            return $status;
        }
    }

     /**
     * Function that updates the user password
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function updatePassword($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that updates the username
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function updateUsername($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that updates the user email address
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function updateEmail($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that updates the blocks the user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function blockUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that unblocks the user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function unblockUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that activates the users account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function activateUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that inactivates the users account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function inactivateUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that creates a new user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param array status Array containing the errors and result of the function
     */
    function createUser($userinfo, &$status)
    {
    }

     /**
     * Function that deletes a user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the existing userinfo
     * @return array status Array containing the errors and result of the function
     */
    function deleteUser($userinfo)
    {
    	//setup status array to hold debug info and errors
        $status = array();
        $status['debug'] = array();
        $status['error'] = array();

        $status['error'][] = JText::_('DELETE_FUNCTION_MISSING');

        return $status;
    }

}



