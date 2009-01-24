<?php

/**
* @package JFusion_phpBB3
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion User Class for phpBB3
 * For detailed descriptions on these functions please check the model.abstractuser.php
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
			$status['debug'][] = JText::_('USER_DATA_FOUND');
            if ($existinguser->email != $userinfo->email) {
			  $status['debug'][] = JText::_('EMAIL_CONFLICT');
              if ($update_email || $overwrite) {
			      $status['debug'][] = JText::_('EMAIL_CONFLICT_OVERWITE_ENABLED');
                  $this->updateEmail($userinfo, $existinguser, $status);
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
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());

		//temp comment out of filtering to test UTF8 functions
        $username = $this->filterUsername($username);

        $query = 'SELECT user_id as userid, username as name, username_clean as username, user_email as email, user_password as password, NULL as password_salt, user_actkey as activation, user_inactive_reason as reason, user_lastvisit as lastvisit FROM #__users '.
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

            //if no inactive reason is set clear the activation code
            if(empty($result->reason)){
            	$result->activation = '';
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
        $status = array();
        $status['error'] = array();
        $status['debug'] = array();
        $db = JFusionFactory::getDatabase($this->getJname());

        //get the cookie parameters
        $params = JFusionFactory::getParams($this->getJname());
        $phpbb_cookie_name = $params->get('cookie_prefix');
        $phpbb_cookie_path = $params->get('cookie_path');

        //baltie cookie domain fix
        $phpbb_cookie_domain = $params->get('cookie_domain');
        if ($phpbb_cookie_domain == 'localhost' || $phpbb_cookie_domain == '127.0.0.1') {
            $phpbb_cookie_domain = '';
        }

        //update session time for the user into user table
        $query = 'UPDATE #__users SET user_lastvisit =' . time() . ' WHERE user_id =' . $userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['debug'][] = 'Error could not update the last visit field ' . $db->stderr();
        }

		//delete the cookies
        setcookie($phpbb_cookie_name . '_u', '', time()-3600, $phpbb_cookie_path, $phpbb_cookie_domain);
        setcookie($phpbb_cookie_name . '_sid', '', time()-3600, $phpbb_cookie_path, $phpbb_cookie_domain);
        setcookie($phpbb_cookie_name . '_k', '', time()-3600, $phpbb_cookie_path, $phpbb_cookie_domain);


		//delete the database sessions
        $query = 'DELETE FROM #__sessions WHERE session_user_id =' . $userinfo->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = 'Error: Could not delete session in database ' . $db->stderr();
            return $status;
         }

        $query = 'DELETE FROM #__sessions_keys WHERE user_id =' . $userinfo->userid;
        $db->setQuery($query);
        if ($db->query()) {
            $status['debug'][] = 'Deleted the session key';
        } else {
            $status['debug'][] = 'Error could not delete the session key:' . $db->stderr();
        }
    }

    function createSession($userinfo, $options)
    {
        $status = array();
        $status['error'] = array();
        $status['debug'] = array();
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
            $phpbb_cookie_expiry = $params->get('cookie_expiry');

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
                    $expires = 60 * $phpbb_cookie_expiry;
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
                    $status['error'][] = JText::_('ERROR_CREATE_SESSION') . $db->stderr();
                    return $status;
                } else {
                    //Set cookies
                    JFusionFunction::addCookie($phpbb_cookie_name . '_u', $userid, $expires, $phpbb_cookie_path, $phpbb_cookie_domain, true);
                    $status['debug'][] = JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $phpbb_cookie_name . '_u' . ', ' . JText::_('VALUE') . '=' . $userid .', ' .JText::_('EXPIRES') . '=' .$expires .', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;
                    JFusionFunction::addCookie($phpbb_cookie_name . '_sid', $session_key, $expires, $phpbb_cookie_path, $phpbb_cookie_domain, true);
                    $status['debug'][] = JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $phpbb_cookie_name . '_sid' . ', ' . JText::_('VALUE') . '=' . substr($session_key,0,6) .'********, ' .JText::_('EXPIRES') . '=' .$expires .', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;

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
                            $status['error'][] = JText::_('ERROR_CREATE_USER') . $database->stderr();
                            return $status;
                        } else {
                            JFusionFunction::addCookie($phpbb_cookie_name . '_k', $session_key, $expires, $phpbb_cookie_path, $phpbb_cookie_domain, true);
                    		$status['debug'][] = JText::_('CREATED') . ' ' . JText::_('COOKIE') . ': ' . JText::_('NAME') . '=' . $phpbb_cookie_name . '_k' . ', ' . JText::_('VALUE') . '=' . substr($session_key,0,6) .'********, ' .JText::_('EXPIRES') . '=' .$expires .', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;
                        }

                    }
                    $status['error'] = false;
                    return $status;
                }
            } else {
                //could not find a valid userid
                $status['error'][] = JText::_('INVALID_COOKIENAME') ;
                return $status;
            }
        } else {
            //could not find a valid userid
            $status['error'][] = JText::_('INVALID_USERID');
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
        //die($username . ':' . $username_clean);
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
        $query = 'UPDATE #__users SET user_password =' . $db->Quote($existinguser->password) . ', user_pass_convert = 0 WHERE user_id =' . $existinguser->userid;
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
        $query = 'UPDATE #__users SET user_email ='.$db->Quote($userinfo->email) .' WHERE user_id =' . $existinguser->userid;
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
        $query = 'UPDATE #__users SET user_actkey =' . $db->Quote($userinfo->activation) . ' WHERE user_id =' . $existinguser->userid;
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
            $query = 'UPDATE #__config SET config_value = '. $db->Quote($userinfo->username) . ' WHERE config_name = \'newest_username\'';
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

            //get the username color
            $query = 'SELECT group_colour from #__groups WHERE group_id = ' . $usergroup;
        	$db->setQuery($query);
            $user_color = $db->loadResult();;

            //set the correct new username color
            $query = 'UPDATE #__config SET config_value = ' . $db->Quote($user_color) . ' WHERE config_name = \'newest_user_colour\'';
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

    function deleteUser($userinfo)
    {
    	//setup status array to hold debug info and errors
        $status = array();
        $status['debug'] = array();
        $status['error'] = array();

        //retreive the database object
        $db = & JFusionFactory::getDatabase($this->getJname());

        //set the userid
        $user_id = $userinfo->userid;

		// Before we begin, we will remove the reports the user issued.
		$query = 'SELECT r.post_id, p.topic_id
			FROM #__reports r, #__posts p
			WHERE r.user_id = ' . $user_id . '
				AND p.post_id = r.post_id';
		$db->setQuery($query);

		$report_posts = $report_topics = array();

		if($db->query()) {
			if($results = $db->loadObjectList()){
				foreach($results as $row)
				{
					$report_posts[] = $row->post_id;
					$report_topics[] = $row->topic_id;
				}
				$status["debug"][] = "Retrieved all reported posts/topics by user $user_id.";
			}
		} elseif($db->stderr()) {
			$status["error"][] = "Error Could not retrieve reported posts/topics by user $user_id: {$db->stderr()}";
		}

		if (sizeof($report_posts))
		{
			$report_posts = array_unique($report_posts);
			$report_topics = array_unique($report_topics);

			// Get a list of topics that still contain reported posts
			$query = 'SELECT DISTINCT topic_id
				FROM #__posts
				WHERE topic_id IN (' . implode(', ', $report_topics) . ')
					AND post_reported = 1
					AND post_id IN (' . implode(', ', $report_posts) . ')';
			$db->setQuery($query);

			$keep_report_topics = array();
			if($db->query()) {
				if($results = $db->loadObjectList()) {
					foreach($results as $row)
					{
						$keep_report_topics[] = $row->topic_id;
					}
					$status["debug"][] = "Sorted through reported topics by user $user_id to keep.";
				}
			} else {
				$status["error"][] = "Error Could not retrieve a list of topics that still contain reported posts by user $user_id: {$db->stderr()}";
			}

			if (sizeof($keep_report_topics))
			{
				$report_topics = array_diff($report_topics, $keep_report_topics);
			}
			unset($keep_report_topics);

			// Now set the flags back
			$query = 'UPDATE #__posts
				SET post_reported = 0
				WHERE post_id IN (' . implode(', ', $report_posts) . ')';
			$db->setQuery($query);
			if(!$db->query()){
				$status["error"][] = "Error Could not update post reported flag: {$db->stderr()}";
			} else {
				$status["debug"][] = "Updated reported posts flag.";
			}

			if (sizeof($report_topics))
			{
				$query = 'UPDATE #__topics
					SET topic_reported = 0
					WHERE topic_id IN (' . implode(', ', $report_topics) . ')';
				$db->setQuery($query);
				if(!$db->query()){
					$status["error"][] = "Error Could not update topics reported flag: {$db->stderr()}";
				} else {
					$status["debug"][] = "Updated reported topics flag.";
				}
			}
		}

		// Remove reports
		$query = 'DELETE FROM #__reports WHERE user_id = ' . $user_id;
		$db->setQuery($query);
		if(!$db->query()){
    		$status["error"][] = "Error Could not delete reports by user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Deleted reported posts/topics by user $user_id.";
		}

		//update all topics started by and posts by the user to anonymous
		$post_username = "Guest";

		$query = 'UPDATE #__forums
			SET forum_last_poster_id = 1, forum_last_poster_name = ' . $db->Quote($post_username) . ", forum_last_poster_colour = ''
			WHERE forum_last_poster_id = $user_id";
    	$db->setQuery($query);
		if(!$db->query()){
    		$status["error"][] = "Error Could not update forum last poster for user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated last poster to anonymous if last post was by user $user_id.";
		}

		$query = "UPDATE #__posts
			SET poster_id = 1, post_username = " . $db->Quote($post_username) . "
			WHERE poster_id = $user_id";
    	$db->setQuery($query);
		if(!$db->query()){
    		$status["error"][] = "Error Could not update posts by user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated posts to be from anonymous if posted by user $user_id.";
		}
		$query = "UPDATE #__posts
			SET post_edit_user = 1
			WHERE post_edit_user = $user_id";
    	$db->setQuery($query);
		if(!$db->query()){
    		$status["error"][] = "Error Could not update edited posts by user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated edited posts to be from anonymous if edited by user $user_id.";
		}

		$query = "UPDATE #__topics
			SET topic_poster = 1, topic_first_poster_name = " . $db->Quote($post_username) . ", topic_first_poster_colour = ''
			WHERE topic_poster = $user_id";
    	$db->setQuery($query);
		if(!$db->query()){
    		$status["error"][] = "Error Could not update topics by user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated topics to be from anonymous if started by user $user_id.";
		}


		$query = "UPDATE #__topics
			SET topic_last_poster_id = 1, topic_last_poster_name = " . $db->Quote($post_username) . ", topic_last_poster_colour = ''
			WHERE topic_last_poster_id = $user_id";
    	$db->setQuery($query);
		if(!$db->query()){
    		$status["error"][] = "Error Could not update last topic poster for user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated topic last poster to be anonymous if set as user $user_id.";
		}

		// Since we change every post by this author, we need to count this amount towards the anonymous user
		$query = "SELECT user_posts FROM #__users WHERE user_id = $user_id";
		$db->setQuery($query);
		$user_posts = $db->loadResult();

		// Update the post count for the anonymous user
		if($user_posts > 0)
		{
			$query = "UPDATE #__users
				SET user_posts = user_posts + $user_posts
				WHERE user_id = 1";
		    $db->setQuery($query);
			if(!$db->query()){
    			$status["error"][] = "Error Could not update the number of posts for anonymous user: {$db->stderr()}";
			} else {
				$status["debug"][] = "Updated post count for anonymous user.";
			}
		}

		$table_ary = array("users", "user_group", "topics_watch", "forums_watch", "acl_users", "topics_track", "topics_posted", "forums_track", "profile_fields_data", "moderator_cache", "drafts", "bookmarks");

		foreach ($table_ary as $table)
		{
			$query = "DELETE FROM #__$table
				WHERE user_id = $user_id";
		    $db->setQuery($query);
			if(!$db->query()){
    			$status["error"][] = "Error Could not delete records from $table for user $user_id: {$db->stderr()}";
			} else {
				$status["debug"][] = "Deleted records from $table for user $user_id.";
			}
		}

		//TODO clear moderator cache table
		//$cache->destroy('sql', MODERATOR_CACHE_TABLE);

		// Remove any undelivered mails...
		$query = 'SELECT msg_id, user_id
			FROM #__privmsgs_to
			WHERE author_id = ' . $user_id . '
				AND folder_id = -3';
        $db->setQuery($query);

		$undelivered_msg = $undelivered_user = array();
		if($db->query())
		{
			if($results = $db->loadObjectList()) {
				foreach($results as $row)
				{
					$undelivered_msg[] = $row->msg_id;
					$undelivered_user[$row->user_id][] = true;
				}
				$status["debug"][] = "Retrieved undelvered private messages from user $user_id.";
			}
		} else {
    		$status["error"][] = "Error Could not retrieve undeliverd messages to user $user_id: {$db->stderr()}";
		}

		if (sizeof($undelivered_msg))
		{
			$query = 'DELETE FROM #__privmsgs
				WHERE msg_id (' . implode(', ', $undelivered_msg) . ')';
			$db->setQuery($query);
			if(!$db->query()){
    			$status["error"][] = "Error Could not delete private messages for user $user_id: {$db->stderr()}";
			} else {
				$status["debug"][] = "Deleted undelivered private messages from user $user_id.";
			}
		}

		$query = 'DELETE FROM #__privmsgs_to
			WHERE author_id = ' . $user_id . '
				AND folder_id = -3';
		$db->setQuery($query);
    	if(!$db->query()){
    		$status["error"][] = "Error Could not delete private messages that are in no folder from user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Deleted private messages that are in no folder from user $user_id.";
		}


		// Delete all to-information
		$query = 'DELETE FROM #__privmsgs_to
			WHERE user_id = ' . $user_id;
		$db->setQuery($query);
    	if(!$db->query()){
    		$status["error"][] = "Error Could not delete private messages to user $user_id: {$db->stderr()}";
		} else {
			$status["debug"][] = "Deleted private messages sent to user $user_id.";
		}


		// Set the remaining author id to anonymous - this way users are still able to read messages from users being removed
		$query = 'UPDATE #__privmsgs_to
			SET author_id = 1
			WHERE author_id = ' . $user_id;
		$db->setQuery($query);
    	if(!$db->query()){
    		$status["error"][] = "Error Could not update rest of private messages for user $user_id to anonymous: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated the author to anonymous for the rest of the PMs in the 'to' table if originally sent by user $user_id.";
		}

		$query = 'UPDATE #__privmsgs
			SET author_id = 1
			WHERE author_id = ' . $user_id;
		$db->setQuery($query);
    	if(!$db->query()){
    		$status["error"][] = "Error Could not update rest of private messages for user $user_id to anonymous: {$db->stderr()}";
		} else {
			$status["debug"][] = "Updated the author to anonymous for the rest of the PMs in the main PM table if originally sent by user $user_id.";
		}

		foreach ($undelivered_user as $_user_id => $ary)
		{
			if ($_user_id == $user_id)
			{
				continue;
			}

			$query = 'UPDATE #__users
				SET user_new_privmsg = user_new_privmsg - ' . sizeof($ary) . ',
					user_unread_privmsg = user_unread_privmsg - ' . sizeof($ary) . '
				WHERE user_id = ' . $_user_id;
			$db->setQuery($query);
    		if(!$db->query()){
    			$status["error"][] = "Error Could not update the number of PMs for user $_user_id for user $user_id was deleted: {$db->stderr()}";
			} else {
				$status["debug"][] = "Updated the the number of PMs for user $_user_id since user $user_id was deleted.";
			}
		}

		//TODO update newest user id
		// Reset newest user info if appropriate
		//if ($config['newest_user_id'] == $user_id)
		//{
		//	update_last_username();
		//}

		// TODO Decrement number of users if this user is active
		/*if ($user_type != 1 && $user_type != 2)
		{
			set_config('num_users', $config['num_users'] - 1, true);
		}
		*/

		return $status;
    }
}