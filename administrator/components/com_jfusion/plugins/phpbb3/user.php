<?php

/**
* @package JFusion_phpBB3
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

/**
* @package JFusion_phpBB3
*/
class JFusionUser_phpbb3 extends JFusionUser{

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

                //this is a proper login attemp, update last visit field
                $query = 'UPDATE #__users SET user_lastvisit = ' . time() . ' WHERE user_id = ' . $existinguser->userid;
		        $db->setQuery($query);
        		$db->query();
		        if (!$db->query()) {
        		    $status['error'][] = JText::_('LASTVISIT_UPDATE') . ' ' . JText::_('ERROR') . $db->stderr();
		        } else {
	    		    $status['debug'][] = JText::_('LASTVISIT_UPDATE'). ' ' . JText::_('SUCCES');
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

		//temp comment out of filtering to test UTF8 functions
        //$username = $this->filterUsername($username);

        $query = 'SELECT user_id as userid, username as name, username_clean as username, user_email as email, user_password as password, NULL as password_salt, user_actkey as activation, user_lastvisit as lastvisit FROM #__users '.
        'WHERE username_clean=' . $db->Quote($username);
        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT ban_userid FROM #__banlist WHERE ban_userid =' . $result->userid;
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
        return 'phpbb3';
    }

    function destroySession($userinfo, $options)
    {
        $userid = 0;
        $sessionid = 0;
        $status = array();
        $status['debug'] = '';

        //get the cookie parameters
        $params = JFusionFactory::getParams($this->getJname());
        $phpbb_cookie_name = $params->get('cookie_prefix');
        $phpbb_cookie_path = $params->get('cookie_path');

        //baltie cookie domain fix
        $phpbb_cookie_domain = $params->get('cookie_domain');
        if ($phpbb_cookie_domain == 'localhost' || $phpbb_cookie_domain == '127.0.0.1') {
            $phpbb_cookie_domain = '';
        }

        if (isset($_COOKIE[$phpbb_cookie_name . '_u']) && isset($_COOKIE[$phpbb_cookie_name . '_sid'])) {
            $userid = intval($_COOKIE[$phpbb_cookie_name.'_u']);
            $sessionid = $_COOKIE[$phpbb_cookie_name.'_sid'];
            $status['debug'] .= 'Found session cookie:' . $sessionid .' for userid:' . $userid .'<br/>';
        } else {
            $status['error'] = 'No userid and session cookie found';
            return $status;
        }

        if ($userid > 0 && $sessionid) {
            $db = JFusionFactory::getDatabase($this->getJname());

            //update session time for the user into user table
            $query = 'UPDATE #__users SET user_lastvisit =' . time() . ' WHERE user_id =' . $userid;
            $db->setQuery($query);
            if (!$db->query()) {
                $status['debug'] .= 'Error could not update the last visit field</br>';
            }

            // delete session data in db and cookies
            $query = 'DELETE FROM #__sessions WHERE session_user_id =' . $userid . " AND session_id=" . $db->Quote($sessionid);
            $db->setQuery($query);
            if ($db->query()) {
                setcookie($phpbb_cookie_name . '_u', '', time()-3600, $phpbb_cookie_path, $phpbb_cookie_domain);
                setcookie($phpbb_cookie_name . '_sid', '', time()-3600, $phpbb_cookie_path, $phpbb_cookie_domain);
            } else {
                $status['error'] = 'Error: Could not delete session in database';
                return $status;
            }

            // delete remember me cookie and session keys
            if (isset($_COOKIE[$phpbb_cookie_name . '_k'])) {
                $query = 'DELETE FROM #__sessions_keys WHERE user_id =' . $userid . ' AND session_id=' . $db->Quote($sessionid);
                $db->setQuery($query);
                if ($db->query()) {
                    setcookie($phpbb_cookie_name . '_k', '', time()-3600, $phpbb_cookie_path, $phpbb_cookie_domain);
                    $status['debug'] .= 'Deleted the session key</br>';
                } else {
                    $status['debug'] .= 'Error could not delete the session key</br>';
                }
            }
            $status['debug'] .= 'Session destroyed succesfully</br>';
            return $status;
        } else {
            $status['error'] = 'Userid and sessionid were not valid';
            return $status;
        }
    }

    function createSession($userinfo, $options)
    {
        $status = array();
        $status['debug'] = '';
        $db = JFusionFactory::getDatabase($this->getJname());

        $userid = $userinfo->userid;

        if ($userid && !empty($userid) && ($userid > 0)) {

            jimport('joomla.user.helper');
            $session_key = JUserHelper::genRandomPassword(32);

            //Check for admin access
            $query = 'SELECT b.group_name FROM #__user_group as a INNER JOIN #__groups as b ON a.group_id = b.group_id WHERE b.group_name = \'ADMINISTRATORS\' and a.user_id = ' . $userinfo->userid;
            $db->setQuery($query);
            $usergroup = $db->loadResult();

            if ($usergroup == 'ADMINISTRATORS') {
                $admin_access = 1;
            } else {
                $admin_access = 0;
            }

            $params = JFusionFactory::getParams($this->getJname());
            $phpbb_cookie_name = $params->get('cookie_prefix');

            if ($phpbb_cookie_name) {

                //get cookie domain from config table
                $phpbb_cookie_domain = $params->get('cookie_domain');
                if ($phpbb_cookie_domain == 'localhost' || $phpbb_cookie_domain == '127.0.0.1') {
                    $phpbb_cookie_domain = '';
                }

                //get cookie path from config table
                $phpbb_cookie_path = $params->get('cookie_path');

                //get autologin perm
                $phpbb_allow_autologin = $params->get('allow_autologin');

                $jautologin = 0;
                if (isset($options['remember'])) {
                    $jautologin = $options['remember'] ? 1 : 0;
                }

                if ($jautologin > 0 && $phpbb_allow_autologin>0) {
                    $jautologin = $phpbb_allow_autologin;
                }

                if ($jautologin) {
                    $expires = 60*60*24*365;
                } else {
                    $expires = 60*30;
                }

                $session_start = time();

                //Insert the session into sessions table
                $session_obj = new stdClass;
                $session_obj->session_id = substr($session_key, 0, 32);
                $session_obj->session_user_id = $userid;
                $session_obj->session_last_visit = $userinfo->lastvisit;
                $session_obj->session_start = $session_start;
                $session_obj->session_time = $session_start;
                $session_obj->session_ip = $_SERVER['REMOTE_ADDR'];
                $session_obj->session_browser = $_SERVER['HTTP_USER_AGENT'];
                $session_obj->session_page = 0;
                $session_obj->session_autologin = $jautologin;
                $session_obj->session_admin = $admin_access;
                if (!$db->insertObject('#__sessions', $session_obj )) {
                    //could not save the user
                    $status['error'] = JText::_('ERROR_CREATE_SESSION') . $db->stderr();
                    return $status;
                } else {
                    //Set cookies
                    JFusionFunction::addCookie($phpbb_cookie_name . '_u', $userid, $expires, $phpbb_cookie_path, $phpbb_cookie_domain, true);
                    $status['debug'] .= JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $phpbb_cookie_name . '_u' . ', ' . JText::_('VALUE') . '=' . $userid .', ' .JText::_('EXPIRES') . '=' .$expires .', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;
                    JFusionFunction::addCookie($phpbb_cookie_name . '_sid', $session_key, $expires, $phpbb_cookie_path, $phpbb_cookie_domain, true);
                    $status['debug'] .= JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $phpbb_cookie_name . '_sid' . ', ' . JText::_('VALUE') . '=' . substr($session_key,0,6) .'********, ' .JText::_('EXPIRES') . '=' .$expires .', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;

                    // Remember me option?
                    if ($jautologin>0) {
                        //Insert the session key into sessions_key table
                        $session_key_ins = new stdClass;
                        $session_key_ins->key_id = substr($session_key, 0, 32);
                        $session_key_ins->user_id = $userid;
                        $session_key_ins->last_ip = $_SERVER['REMOTE_ADDR'];
                        $session_key_ins->last_login = $session_start;
                        if (!$db->insertObject('#__sessions_keys', $session_key_ins )) {
                            //could not save the session_key
                            $status['error'] = JText::_('ERROR_CREATE_USER') . $database->stderr();
                            return $status;
                        } else {
                            JFusionFunction::addCookie($phpbb_cookie_name . '_k', $session_key, $expires, $phpbb_cookie_path, $phpbb_cookie_domain, true);
                    		$status['debug'] .= JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $phpbb_cookie_name . '_k' . ', ' . JText::_('VALUE') . '=' . substr($session_key,0,6) .'********, ' .JText::_('EXPIRES') . '=' .$expires .', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;
                        }

                    }
                    $status['error'] = false;
                    return $status;
                }
            } else {
                //could not find a valid userid
                $status['error'] = JText::_('INVALID_COOKIENAME') ;
                return $status;
            }
        } else {
            //could not find a valid userid
            $status['error'] = JText::_('INVALID_USERID');
            return $status;
        }
    }

    function filterUsername($username)
    {
        if (!function_exists('utf8_clean_string_phpbb')) {
            //load the filtering functions for phpBB3
            require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'phpbb3'.DS.'username_clean.php');
        }
        $username_clean = utf8_clean_string_phpbb($username);
        return $username_clean;
    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        // get the encryption PHP file
        if(!class_exists('PasswordHash')){
	        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $this->getJname().DS.'PasswordHash.php');
        }

        $t_hasher = new PasswordHash(8, TRUE);
        $existinguser->password = $t_hasher->HashPassword($userinfo->password_clear);
        unset($t_hasher);

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET user_password =' . $db->quote($existinguser->password) . ' WHERE user_id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
        }

    }

    function updateUsername($userinfo, &$existinguser, &$status)
    {
    }

    function updateEmail($userinfo, &$existinguser, &$status)
    {
        //we need to update the email
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET user_email ='.$db->quote($userinfo->email) .' WHERE user_id =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    function blockUser($userinfo, &$existinguser, &$status)
    {
        //block the user
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'INSERT INTO #__banlist (ban_userid, ban_start) VALUES ('.$existinguser->userid.',' . time() .')';
        $db->setQuery($query);
        $db->query();
        if (!$db->query()) {
            $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        }
    }

    function unblockUser($userinfo, &$existinguser, &$status)
    {
        //unblock the user
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'DELETE FROM #__banlist WHERE ban_userid=' . $existinguser->userid;
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
        //activate the user
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET user_actkey = \'\'  WHERE user_id =' . $existinguser->userid;
        $db->setQuery($query);
        $db->query();
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function inactivateUser($userinfo, &$existinguser, &$status)
    {
        //set activation key
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET user_actkey =' . $db->quote($userinfo->activation) . ' WHERE user_id =' . $existinguser->userid;
        $db->setQuery($query);
        $db->query();
        if (!$db->query()) {
            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        }
    }

    function createUser($userinfo, &$status)
    {
        //found out what usergroup should be used
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup = $params->get('usergroup');

        $username_clean = $this->filterUsername($userinfo->username);
        //prepare the variables
        $user = new stdClass;
        $user->id = NULL;
        $user->username = $userinfo->username;
        $user->username_clean = $username_clean;

        if (isset($userinfo->password_clear)) {
            //we can update the password
            require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'phpbb3'.DS.'PasswordHash.php');
            $t_hasher = new PasswordHash(8, TRUE);
            $user->user_password = $t_hasher->HashPassword($userinfo->password_clear);
            unset($t_hasher);
        } else {
            $user->user_password = $userinfo->password;
        }


        $user->user_pass_convert = 0;
        $user->user_email = strtolower($userinfo->email);
        $user->user_email_hash = crc32(strtolower($userinfo->email)) . strlen($userinfo->email);
        $user->group_id = $usergroup;
        $user->user_type = 0;
        $user->user_permissions = '';
        $user->user_allow_pm = 1;
        $user->user_actkey = '';
        $user->user_ip = '';
        $user->user_regdate = time();
        $user->user_passchg = time();
        $user->user_options = 895;
        $user->user_inactive_reason = 0;
        $user->user_inactive_time = 0;
        $user->user_lastmark = time();
        $user->user_lastvisit = 0;
        $user->user_lastpost_time = 0;
        $user->user_lastpage = '';
        $user->user_posts = 0;
        $user->user_colour = '';
        $user->user_occ = '';
        $user->user_interests = '';
        $user->user_avatar = '';
        $user->user_avatar_type = 0;
        $user->user_avatar_width = 0;
        $user->user_avatar_height = 0;
        $user->user_new_privmsg = 0;
        $user->user_unread_privmsg = 0;
        $user->user_last_privmsg = 0;
        $user->user_message_rules = 0;
        $user->user_emailtime = 0;
        $user->user_notify = 0;
        $user->user_notify_pm = 1;
        $user->user_allow_pm = 1;
        $user->user_allow_viewonline = 1;
        $user->user_allow_viewemail = 1;
        $user->user_allow_massemail = 1;
        $user->user_sig = '';
        $user->user_sig_bbcode_uid = '';
        $user->user_sig_bbcode_bitfield = '';

        //Find some default values
        $query = "SELECT config_name, config_value FROM #__config WHERE config_name IN('board_timezone', 'default_dateformat', 'default_lang', 'default_style', 'board_dst', 'rand_seed');";
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        foreach($rows as $row ) {
            $config[$row->config_name] = $row->config_value;
        }

        $user->user_timezone = $config['board_timezone'];
        $user->user_dateformat = $config['default_dateformat'];
        $user->user_lang = $config['default_lang'];
        $user->user_style = $config['default_style'];
        $user->user_dst = $config['board_dst'];
        $user->user_full_folder = -4;
        $user->user_notify_type = 0;

        //generate a unique id
        jimport('joomla.user.helper');
        $user->user_form_salt = JUserHelper::genRandomPassword(13);

        //now append the new user data
        if (!$db->insertObject('#__users', $user, 'id' )) {
            //return the error
            $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
            return;
        } else {
            //now create a user_group entry
            $query = 'INSERT INTO #__user_group (group_id, user_id, group_leader, user_pending) VALUES (' .$usergroup.','. $user->id .', 0,0 )';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //update the total user count
            $query = 'UPDATE #__config SET config_value = config_value + 1 WHERE config_name = \'num_users\'';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //update the newest username
            $query = 'UPDATE #__config SET config_value = '. $db->quote($userinfo->username) . ' WHERE config_name = \'newest_username\'';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //update the newest userid
            $query = 'UPDATE #__config SET config_value = ' . $user->id . ' WHERE config_name = \'newest_user_id\'';
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //return the good news
            $status['debug'][] = JText::_('USER_CREATION');
        }
    }
    function deleteUsername($username)
    {
    }
}
