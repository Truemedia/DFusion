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

    function &getUser($username)
    {
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());
		$username = $this->filterUsername($username);

        $query = 'SELECT user_id as userid, username as name, username_clean as username, user_email as email, user_password as password, user_lastvisit as lastvisit FROM #__users '.
        'WHERE username_clean=' . $db->Quote($username);
        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT userid FROM #__banlist WHERE userid=' . $result->userid;
            $db->setQuery($query);
            if ($db->loadObject()) {
                $result->block = 1;
            } else {
                $result->block = 0;
            }
            return $result;
        } else {
        	return false;
        }
    }

    function updateUser($userinfo)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);

        if ($userlookup) {
            //a matching user has been found
            if ($userlookup->email == $userinfo->email) {
                //emails match up
                if($userinfo->password_clear) {
                	//we can update the password
			        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'phpbb3'.DS.'PasswordHash.php');
        			$t_hasher = new PasswordHash(8, TRUE);
					$userlookup->password = $t_hasher->HashPassword($userinfo->password_clear);
					unset($t_hasher);
					$query = 'UPDATE #__users SET user_password =' . $db->quote($userlookup->password) . ' WHERE user_id =' . $userlookup->userid;
                	$db->setQuery($query);
            		if (!$db->query()) {
                		//return the error
                		$status['error'] = 'Error while creating the user: ' . $db->stderr();
                		return $status;
            		}
  		            $status['userinfo'] = $userlookup;
       		        $status['error'] = false;
       	    	    $status['debug'] = 'User already exists, password was updated to:' . $userlookup->password;
           	    	return $status;
                } else {
	                //TODO: Update the password
  		            $status['userinfo'] = $userlookup;
       		        $status['error'] = false;
       	    	    $status['debug'] = 'User already exists, password was not updated.';
           	    	return $status;

                }



            } else {
                //this could be a username conflict -> return an error
                $status['userinfo'] = $userlookup;
                $status['error'] = 'There is an email conflict with userid:' . $userlookup->userid;
                return $status;
            }
        } else {
            //we need to create a new user

            //found out what usergroup should be used
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('usergroup');

			$username_clean = $this->filterUsername($userinfo->username);
            //prepare the variables
            $user = new stdClass;
            $user->id = NULL;
            $user->username = $userinfo->username;
            $user->username_clean = $username_clean;

            if($userinfo->password_clear) {
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
            $query = "SELECT config_name, config_value FROM #__config WHERE config_name IN ('board_timezone', 'default_dateformat', 'default_lang', 'default_style', 'board_dst', 'rand_seed');";
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
                $status['error'] = 'Error while creating the user: ' . $db->stderr();
                return $status;
            } else {
                //now create a user_group entry
                $query = 'INSERT INTO #__user_group (group_id, user_id, group_leader, user_pending) VALUES (' .$usergroup.','. $user->id .', 0,0 )';
                $db->setQuery($query);
            	if (!$db->query()) {
                	//return the error
                	$status['error'] = 'Error while creating the user: ' . $db->stderr();
                	return $status;
            	}

				//update the total user count
                $query = 'UPDATE #__phpbb_config SET config_value = config_value + 1 WHERE config_name = \'num_users\'';
                $db->setQuery($query);
            	if (!$db->query()) {
                	//return the error
                	$status['error'] = 'Error while creating the user: ' . $db->stderr();
                	return $status;
            	}

				//update the newest username
                $query = 'UPDATE #__phpbb_config SET config_value = '. $db->quote($userinfo->username) . ' WHERE config_name = \'newest_username\'';
                $db->setQuery($query);
            	if (!$db->query()) {
                	//return the error
                	$status['error'] = 'Error while creating the user: ' . $db->stderr();
                	return $status;
            	}

            	//update the newest userid
                $query = 'UPDATE #__phpbb_config SET config_value = ' . $user->id . ' WHERE config_name = \'newest_user_id\'';
                $db->setQuery($query);
            	if (!$db->query()) {
                	//return the error
                	$status['error'] = 'Error while creating the user: ' . $db->stderr();
                	return $status;
            	}


                //return the good news
                $status['debug'] = 'Created new user with userid:' . $user->id;
                $status['error'] = false;
                $status['userinfo'] = $this->getUser($username_clean);
                return $status;

            }
        }
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
            $query = 'SELECT a.group_name from #__groups as a INNER JOIN #__users as b ON a.group_id = b.group_id WHERE username_clean=' . $db->Quote($userinfo->username);
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

                $autologin = 0;
                if (isset($options['remember'])) {
                    $jautologin = $options['remember'] ? 1 : 0;
                }

                if ($jautologin>0 && $phpbb_allow_autologin>0) {
                    $autologin = $phpbb_allow_autologin;
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
                $session_obj->session_autologin = $autologin;
                $session_obj->session_admin = $admin_access;
                if (!$db->insertObject('#__sessions', $session_obj )) {
        	        //could not save the user
            		$status['error'] = JText::_('ERROR_CREATE_SESSION') . $db->stderr();
            		return $status;
                } else {
                    //Set cookies
                    setcookie($phpbb_cookie_name . '_u', $userid, time()+(86400*365), $phpbb_cookie_path, $phpbb_cookie_domain);
                    setcookie($phpbb_cookie_name . '_sid', $session_key, time()+(86400*365), $phpbb_cookie_path, $phpbb_cookie_domain);
            		$status['debug'] .= JText::_('CREATED') . ' ' . JText::_('SESSION') . ': ' .JText::_('USERID') . '=' . $userid . ', ' . JText::_('SESSIONID') . '=' . $session_key . ', ' . JText::_('COOKIE_PATH') . '=' . $phpbb_cookie_path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $phpbb_cookie_domain;

                    // Remember me option?
                    if ($autologin>0) {
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
                            setcookie($phpbb_cookie_name . '_k', $session_key, time()+(86400*365), $phpbb_cookie_path, $phpbb_cookie_domain);
            		        $status['debug'] .= 'Created session_key:' . $session_key;
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
        if (!function_exists('utf8_clean_string')) {
            //load the filtering functions for phpBB3
            require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'phpbb3'.DS.'username_clean.php');
        }
        $username_clean = utf8_clean_string($username);
        return $username_clean;
    }
}
