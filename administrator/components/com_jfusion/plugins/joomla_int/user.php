<?php

/**
* @package JFusion_Joomla_Int
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the Abstract User Class
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
jimport('joomla.user.helper');
/**
* @package JFusion_Joomla_Int
*/


// HJW removed all indirect ampersands in function calls, cheked the functiondeclarations to have these &'s in place.
// These generated a bunch of deprecation warnings
class JFusionUser_joomla_int extends JFusionUser{

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

        //prevent the JFusion user plugin from launching JFusion updateUser() again
        global $JFusionActive;
        $JFusionActive = true;

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

			if (isset($userinfo->password_clear)){
			    //check if the password needs to be updated
	    	    $model = JFusionFactory::getAuth($this->getJname());
        		$testcrypt = $model->generateEncryptedPassword($userinfo);
            	if ($testcrypt != $userinfo->password) {
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
            $status['userinfo'] = $this->getUser($userinfo->username);
            return $status;
        }
    }

    function deleteUsername($username)
    {
        //get the database ready
        $db = & JFactory::getDBO();

        $query = 'SELECT id FROM #__jfusion_users WHERE username='.$db->Quote($username);
        $db->setQuery($query );
        $userid = $db->loadResult();

        if ($userid) {
            //this user was created by JFusion and we need to delete them from the joomla user and jfusion lookup table
            $user =& JUser::getInstance($userid);
            $user->delete();
            $db->Execute('DELETE FROM #__jfusion_users_plugin WHERE id='.$userid);
            $db->Execute('DELETE FROM #__jfusion_users WHERE id='.$userid);
            return true;
        } else {
            //this user was NOT create by JFusion. Therefore we need to delete it in the Joomla user table only
            $query = 'SELECT id from #__users WHERE username = ' . $db->quote($username);
            $db->setQuery($query);
            $userid = $db->loadResult();
            if ($userid) {
                //delete it from the Joomla usertable
                $user =& JUser::getInstance($userid);
                $user->delete();
                return true;
            } else {
                //could not find user and return an error
                JError::raiseWarning(0, JText::_('ERROR_DELETE') . $username);
                return '';
            }
        }
    }


    function &getUser($identifier)
    {
        //get database object
        $db =& JFactory::getDBO();
        $params = JFusionFactory::getParams($this->getJname());

        //decide what can be used as a login credential
		$login_identifier = $params->get('login_identifier');
        if ($login_identifier == 1){
            $identifier_type = 'b.username';
            $identifier = $this->filterUsername($identifier);
        } elseif ($login_identifier == 3){
           if(strpos($identifier, '@')) {
               $identifier_type = 'b.email';
           } else {
               $identifier_type = 'b.username';
    	       $identifier = $this->filterUsername($identifier);
           }
        } else {
            $identifier_type = 'b.email';
        }

        //first check the JFusion user table
        $db->setQuery('SELECT a.id as userid, a.activation, b.username, a.name, a.password, a.email, a.block FROM #__users as a INNER JOIN #__jfusion_users as b ON a.id = b.id WHERE '. $identifier_type . '=' . $db->quote($identifier));
        $result = $db->loadObject();

        if (!$result) {
			//check directly in the joomla user table
            $db->setQuery('SELECT b.id as userid, b.activation, b.username, b.name, b.password, b.email, b.block FROM #__users as b WHERE '. $identifier_type . '=' .$db->quote($identifier));
            $result = $db->loadObject();

            if($result){
	            //Delete old user data in the lookup table
        	    $query = 'DELETE FROM #__jfusion_users WHERE id =' . $result->userid . ' OR username =' . $db->quote($result->username);
            	$db->setQuery($query);
            	if (!$db->query()) {
                	JError::raiseWarning(0,$db->stderr());
            	}

	            $query = 'DELETE FROM #__jfusion_users_plugin WHERE id =' . $result->userid;
    	        $db->setQuery($query);
        	    if (!$db->query()) {
            	    JError::raiseWarning(0,$db->stderr());
            	}

	            //create a new entry in the lookup table
    	        $query = 'INSERT INTO #__jfusion_users (id, username) VALUES (' . $result->userid . ', ' . $db->quote($identifier) . ')';
        	    $db->setQuery($query);
            	if (!$db->query()) {
                	JError::raiseWarning(0,$db->stderr());
            	}
            }
        }

        if ($result) {
            //split up the password if it contains a salt
            $parts = explode(':', $result->password );
            if (isset($parts[1])) {
                $result->password_salt = $parts[1];
                $result->password = $parts[0];
            }

            //unset the activation status if not blocked
            if($result->block == 0){
              $result->activation = '';
            }
        }
        return $result;
    }


    function getJname()
    {
        return 'joomla_int';
    }

    function filterUsername($username)
    {
		//check to see if additional username filtering need to be applied
        $params = JFusionFactory::getParams($this->getJname());
        $added_filter = $params->get('username_filter');
		if ($added_filter && $added_filter != 'joomla_int') {
	        $JFusionPlugin = JFusionFactory::getUser($added_filter);
	        $username = $JFusionPlugin->filterUsername($username);
		}

        return $username;
    }

    function createSession($userinfo, $options)
    {

        //initalise some objects
        $acl =& JFactory::getACL();
        $instance =& JUser::getInstance($userinfo->userid);
        $grp = $acl->getAroGroup($userinfo->userid);

        //Authorise the user based on the group information
        if (!isset($options['group'])) {
            $options['group'] = 'USERS';
        }

        if (!$acl->is_group_child_of($grp->name, $options['group'])) {
            //report back error
            $status['error'] = 'You do not have access to this page! Your usergroup is:' . $grp->name . '. As a minimum you should be a member of:' . $options['group'];
            return $status;
        }

        //Mark the user as logged in
        $instance->set('guest', 0);
        $instance->set('aid', 1);

        // Fudge Authors, Editors, Publishers and Super Administrators into the special access group
        if ($acl->is_group_child_of($grp->name, 'Registered') ||
        $acl->is_group_child_of($grp->name, 'Public Backend')) {
            $instance->set('aid', 2);
        }

        //Set the usertype based on the ACL group name
        $instance->set('usertype', $grp->name);

        // Register the needed session variables
        $session =& JFactory::getSession();
        $session->set('user', $instance);

        // Get the session object
        $table = & JTable::getInstance('session');
        $table->load($session->getId() );

        $table->guest = $instance->get('guest');
        $table->username = $instance->get('username');
        $table->userid = intval($instance->get('id'));
        $table->usertype = $instance->get('usertype');
        $table->gid = intval($instance->get('gid'));

        $table->update();

        // Hit the user last visit field
        $instance->setLastVisit();
        if (!$instance->save()) {
            $status['error'] = $instance->getError();
            return $status;
        } else {
            $status['error'] = false;
            $status['debug'] = 'Joomla session created';
            return $status;
        }
    }

    function destroySession($userinfo, $options)
    {

    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        $userinfo->password_salt  = JUserHelper::genRandomPassword(32);
        $userinfo->password = JUserHelper::getCryptedPassword($userinfo->password_clear, $userinfo->password_salt);
        $new_password = $userinfo->password . ':' . $userinfo->password_salt;
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET password =' . $db->quote($new_password) . ' WHERE id =' . $existinguser->userid;
        $db->setQuery($query);

        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        } else {
          $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
        }
    }

    function updateUsername($userinfo, &$existinguser, &$status)
    {
        //generate the filtered integration username
        $username_clean = $this->filterUsername($userinfo->username);

        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET username =' . $db->quote($username_clean) . 'WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            //update failed, return error
            $status['error'] .= 'Error while updating the username: ' . $db->stderr();
        } else {
            $status['debug'] .= ' Updated the username to : ' . $username_clean;
        }

    }

    function updateEmail($userinfo, &$existinguser, &$status)
    {
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET email ='.$db->quote($userinfo->email) .' WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('PASSWORD_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    function blockUser($userinfo, &$existinguser, &$status)
    {
        //block the user
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET block = 1 WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        }
    }

    function unblockUser($userinfo, &$existinguser, &$status)
    {
        //unblock the user
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET block = 0 WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        $db->query();
        if (!$db->query()) {
            $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        }
    }

    function activateUser($userinfo, &$existinguser, &$status)
    {
        //unblock the user
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET block = 0, activation = \'\' WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function inactivateUser($userinfo, &$existinguser, &$status)
    {
        //unblock the user
        $db =& JFactory::getDBO();
        $query = 'UPDATE #__users SET block = 1, activation = '.$db->quote($userinfo->activation) .' WHERE id =' . $existinguser->userid;
        $db->setQuery($query);
        $db->query();
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
          $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function createUser($userinfo, $overwrite, &$status)
    {
        //load the database
        $db =& JFactory::getDBO();

        //joomla does not allow duplicate email addresses, check to see if the email is unique
        $query = 'SELECT id as userid, username, email from #__users WHERE email ='.$db->quote($userinfo->email);
        $db->setQuery($query);
        $existinguser = $db->loadObject();
        if (empty($existinguser)) {

          //apply username filtering
          $username_clean = $this->filterUsername($userinfo->username);

          //define which characters which Joomla forbids in usernames
          $trans = array('&#60;' => '_', '&lt;' => '_', '&#62;' => '_', '&gt;' => '_', '&#34;' => '_', '&quot;' => '_', '&#39;' => '_', '&#37;' => '_', '&#59;' => '_', '&#40;' => '_', '&#41;' => '_', '&amp;' => '_', '&#38;' => '_', '<' => '_', '>' => '_', '"' => '_', '\'' => '_', '%' => '_', ';' => '_', '(' => '_', ')' => '_', '&' => '_');
          //remove forbidden characters for the username
          $username_clean = strtr($username_clean, $trans);

          //make sure the username is at least 3 characters long
          while (strlen($username_clean) < 3) {
              $username_clean .= '_';
          }

          //now we need to make sure the username is unique in Joomla
          $db->setQuery('SELECT id FROM #__users WHERE username='.$db->Quote($username_clean));
          while ($db->loadResult()) {
              $username_clean .= '_';
              $db->setQuery('SELECT id FROM #__users WHERE username='.$db->Quote($username_clean));
          }

          //check for conlicting email addresses
          $db->setQuery('SELECT a.id as userid, a.username, a.name, a.password, a.email, a.block, a.activation FROM #__users as a WHERE a.email='.$db->Quote($userinfo->email)) ;
          $existinguser = $db->loadObject();

          if ($existinguser) {
              if ($overwrite) {
            $this->updateUsername($userinfo, $existinguser, $status);
              } else {
                  $status['error'] = JText::_('EMAIL_CONFLICT') . '. UserID:' . $existinguser->userid . ' JFusionPlugin:' . $this->getJname();
              }
        $status['userinfo'] = $existinguser;
        return;
            } else {

            //also store the salt if present
            if ($userinfo->password_salt) {
                $password = $userinfo->password . ':' . $userinfo->password_salt;
            } else {
                $password = $userinfo->password;
            }

            //now we can create the new Joomla user
            $instance = new JUser();
            $instance->set('name'         , $userinfo->name );
            $instance->set('username'     , $username_clean );
            $instance->set('password'     , $password);
            $instance->set('email'        , $userinfo->email );
            $instance->set('block'        , $userinfo->block );
            $instance->set('activation'   , $userinfo->activation );
            $instance->set('sendEmail '   , 1 );

            //find out what usergroup the new user should have
            $params = JFusionFactory::getParams($this->getJname());
            $gid = $params->get('usergroup', 18);
            $query = 'SELECT name FROM #__core_acl_aro_groups WHERE id = ' . $gid;
            $db->setQuery($query);
            $usergroup = $db->loadResult();

            $instance->set('usertype'     , $usergroup );
            $instance->set('gid'          , $gid );


            // save the user
            if (!$instance->save(false)) {
                //report the error
                $status = array();
                $status['error'] = $instance->getError() . 'plugin_username:' . $plugin_username . 'username:'. $username_clean . ' email:' . $email;
                return $status;
            } else {

                //check to see if the user exists now
                $joomla_user = $this->getUser($userinfo->username);

                if ($joomla_user) {
                    //report back success
                    $status['userinfo'] = $joomla_user;
                    return;
                } else {
                    $status['error'] = JText::_('COULD_NOT_CREATE_USER');
                    return;
                }
            }
          }
        } else {
          //Joomla does not allow duplicate emails report error

        }
    }
}