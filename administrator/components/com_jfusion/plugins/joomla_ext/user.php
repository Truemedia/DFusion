<?php
/**
 * @package JFusion_Joomla_Ext
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');

/**
 * JFusion plugin class for an external Joomla database
 * @package JFusion_Joomla_Ext
 */
class JFusionUser_joomla_ext extends JFusionUser
{

    function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = & JFactory::getDBO();
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
            //a matching user has been found
            if ($existinguser->email != $userinfo->email) {
              if ($update_email || $overwrite) {
                  $this->updateEmail($userinfo, $existinguser, $status);
              } else {
                //return a email conflict
                $status['error'][] = JText::_('EMAIL') . ' ' . JText::_('CONFLICT').  ': ' . $existinguser->email . ' -> ' . $userinfo->email;
                $status['userinfo'] = $existinguser;
                return $status;
              }
            }

			if (isset($userinfo->password_clear)){
				// add password_clear to existinguser for the Joomla helper routines
				$existinguser->password_clear=$userinfo->password_clear;
			    //check if the password needs to be updated
	    	    $model = JFusionFactory::getAuth($this->getJname());
        		$testcrypt = $model->generateEncryptedPassword($existinguser);
            	if ($testcrypt != $existinguser->password) {
                	$this->updatePassword($userinfo, $existinguser, $status);
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
                  } else {
                      //unblock the user
                      $this->unblockUser($userinfo, $existinguser, $status);
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
                  } else {
                      //activate the user
                      $this->activateUser($userinfo, $existinguser, $status);
                  }
              } else {
                //return a debug to inform we skiped this step
                $status['debug'][] = JText::_('SKIPPED_EMAIL_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
              }
            }

            $status['userinfo'] = $existinguser;
            if (empty($status['error'])) {
                $status['action'] = 'updated';
            }
            return $status;

        } else {

            $this->createUser($userinfo, $overwrite, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            return $status;
        }
    }


	function &getUser($username)
    {
        // Get a database object
		$db = JFusionFactory::getDatabase($this->getJname());
        $db->setQuery('SELECT a.id as userid, a.username, a.name, a.password, a.email, a.block, a.registerDate as registerdate, lastvisitDate as lastvisitdate FROM #__users as a WHERE a.username=' . $db->quote($username));
        $result = $db->loadObject();

        if ($result) {
            //split up the password if it contains a salt
            $parts = explode(':', $result->password );
        	if($parts[1]) {
        		$result->password_salt = $parts[1];
        		$result->password = $parts[0];
        	}

        	//do an extra check to prevent inactive accounts to login
        	if ($result->activation) {
        		$result->block = 1;
        	}

            return $result;
        } else {
            return false;
        }


		return $result;
    }

    function getJname()
    {
		return 'joomla_ext';
    }

    function deleteUser($username)
    {
        //get the database ready
        $db = JFusionFactory::getDatabase($this->getJname());

        //delete user from the Joomla usertable
  		$query = 'DELETE FROM #__users WHERE username = ' . $db->quote($username);
   		$db->setQuery($query);
       	$db->query();
    }



    function destroySession($userinfo, $options)
    {
            $status['error'] = 'Dual login is not available for this plugin';
            return $status;
    }

    function createSession($userinfo, $options)
    {
            $status['error'] = 'Dual login is not available for this plugin';
            return $status;
    }

	function filterUsername($username) {
	    //no username filtering implemented yet
	    return $username;
    }

 }
