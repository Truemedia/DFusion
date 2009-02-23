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
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.curl.php');


/**
* Common Class for Joomla JFusion plugins
* @package JFusion
*/
class JFusionJplugin{

	function generateEncryptedPassword($userinfo){
		jimport('joomla.user.helper');
		$crypt = JUserHelper::getCryptedPassword($userinfo->password_clear, $userinfo->password_salt);
		return $crypt;
    }

	function getTablename(){
		return 'users';
	}

	function getRegistrationURL(){
		return 'index.php?option=com_user&task=register';
	}

	function getLostPasswordURL(){
		return 'index.php?option=com_user&view=reset';
	}

	function getLostUsernameURL(){
		return 'index.php?option=com_user&view=remind';
	}

	function getUserList($jname){
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'SELECT username, email from #__users';
		$db->setQuery($query );
		$userlist = $db->loadObjectList();
		return $userlist;
	}

	function getUserCount($jname){
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'SELECT count(*) from #__users';
		$db->setQuery($query );

		//getting the results
		return $db->loadResult();
	}

	function getUsergroupList($jname){
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'SELECT id, name FROM #__core_acl_aro_groups';
		$db->setQuery($query );

		//getting the results
		return $db->loadObjectList();
	}

	function getDefaultUsergroup($jname){
		$params = JFusionFactory::getParams($jname);
        $db = & JFusionFactory::getDatabase($jname);
		$usergroup_id = $params->get('usergroup', 18);

		//we want to output the usergroup name
		$query = 'SELECT name from #__core_acl_aro_groups WHERE id = ' . $usergroup_id;
		$db->setQuery($query );
		return $db->loadResult();
	}

    function allowRegistration($jname)
    {
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'SELECT params FROM #__components WHERE `option` = \'com_users\'';
		$db->setQuery($query );
        $registry = new JRegistry();
        $registry->loadINI($db->loadResult());
	    $params = $registry->toObject();

        if ($params->allowUserRegistration) {
            $result = true;
            return $result;
        } else {
            $result = false;
            return $result;
        }
    }

  function setupFromPath($path)
    {
      //check for trailing slash and generate file path
        if (substr($path, -1) == DS) {
            $configfile = $path . 'configuration.php';
        } else {
            $configfile = $path . DS. 'configuration.php';
        }

        if (($file_handle = @fopen($configfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
            $result = false;
            return $result;
        } else {
            //parse the file line by line to get only the config variables
            //we can not directly include the config file as JConfig is already defined
            $file_handle = fopen($configfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$')) {
                    //extract the name and value, it was coded to avoid the use of eval() function
                    $vars = split ("'", $line);
                    $names = split ('var', $vars[0] );
                    if(isset($vars[1]) && isset($names[1])){
    	                $name = trim($names[1], ' $=');
	                    $value = trim($vars[1], ' $=');
        	            $config[$name] = $value;
                    }
                }
            }
            fclose($file_handle);
            //Save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['host'];
            $params['database_name'] = $config['db'];
            $params['database_user'] = $config['user'];
            $params['database_password'] = $config['password'];
            $params['database_prefix'] = $config['dbprefix'];
            $params['database_type'] = $config['dbtype'];
            $params['source_path'] = $path;
            return $params;
        }
  }


/**
 * Common code for user.php
 */


    function createSession($userinfo, $options,$jname){
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $cookiearr = array();
        $cookies_to_set = array();
        $cookies = array();
        $cookie  = array();
		$curl_options = array();
		$status = array();
        $status['error'] = '';
       	$status['debug'] = '';
        $cookies_to_set_index = 0;
        $params = JFusionFactory::getParams($jname);

		$source_url = $params->get('source_url');
        #prevent usererror by not supplying trailing backslash
        if (!(substr($source_url,-1) == "/")) {
           $source_url = $source_url."/";
        }


		$curl_options['post_url']			= $source_url.$params->get('login_url');
		$curl_options['formid']				= $params->get('loginform_id');
		$curl_options['username']			= $userinfo->username;
		$curl_options['password']			= $userinfo->password_clear;
 		$curl_options['integrationtype']	= $params->get('integrationtype');
	 	$curl_options['relpath']			= $params->get('relpath');
	 	$curl_options['hidden']				= $params->get('hidden');
		$curl_options['buttons']			= $params->get('buttons');
		$curl_options['override']			= $params->get('override');
	 	$curl_options['cookiedomain']		= $params->get('cookie_domain');
	 	$curl_options['cookiepath']			= $params->get('cookie_path');
	 	$curl_options['expires']			= $params->get('cookie_expires');
 	 	$curl_options['input_username_id']	= $params->get('input_username_id');
		$curl_options['input_password_id']	= $params->get('input_password_id');
		$curl_options['secure']				= $params->get('secure');
		$curl_options['httponly']			= $params->get('httponly');
        $status=JFusionCurl::RemoteLogin($curl_options);
        return $status;
    }

    function destroySession($userinfo, $options,$jname){
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $cookiearr = array();
        $cookies_to_set = array();
        $cookies = array();
        $cookie  = array();
		$curl_options = array();
		$status = array();
        $status['error'] = '';
       	$status['debug'] = '';
        $cookies_to_set_index = 0;

        $params = JFusionFactory::getParams($jname);
		$curl_options['post_url']			= $params->get('source_url').$params->get('logout_url');
	 	$curl_options['cookiedomain']		= $params->get('cookie_domain');
	 	$curl_options['cookiepath']			= $params->get('cookie_path');
	 	$curl_options['leavealone']			= $params->get('leavealone');
		$curl_options['secure']				= $params->get('secure');
		$curl_options['httponly']			= $params->get('httponly');
        $status = JFusionCurl::RemoteLogout($curl_options);
        return $status;
     }


    function getUser($identifier,$jname){
		$params = JFusionFactory::getParams($jname);
        $db = & JFusionFactory::getDatabase($jname);

		//decide what can be used as a login credential
		$login_identifier = $params->get('login_identifier');
		if ($login_identifier == 1){
			$identifier_type = 'b.username';
//			$identifier = $this->filterUsername($identifier);
		} elseif ($login_identifier == 3){
			if(strpos($identifier, '@')) {
				$identifier_type = 'b.email';
			} else {
				$identifier_type = 'b.username';
//				$identifier = $this->filterUsername($identifier);
			}
		} else {
			$identifier_type = 'b.email';
		}

		if ($jname == 'joomla_int') {
			//first check the JFusion user table
			$db->setQuery('SELECT a.id as userid, a.activation, b.username, a.name, a.password, a.email, a.block FROM #__users as a INNER JOIN #__jfusion_users as b ON a.id = b.id WHERE '. $identifier_type . '=' . $db->Quote($identifier));
			$result = $db->loadObject();

			if (!$result) {
				//check directly in the joomla user table
				$db->setQuery('SELECT b.id as userid, b.activation, b.username, b.name, b.password, b.email, b.block FROM #__users as b WHERE '. $identifier_type . '=' .$db->Quote($identifier));
				$result = $db->loadObject();

				if($result){
					//Delete old user data in the lookup table
					$query = 'DELETE FROM #__jfusion_users WHERE id =' . $result->userid . ' OR username =' . $db->Quote($result->username);
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
					$query = 'INSERT INTO #__jfusion_users (id, username) VALUES (' . $result->userid . ', ' . $db->Quote($identifier) . ')';
					$db->setQuery($query);
					if (!$db->query()) {
						JError::raiseWarning(0,$db->stderr());
					}
				}
			}
		} else {
			$db->setQuery('SELECT b.id as userid, b.activation, b.username, b.name, b.password, b.email, b.block FROM #__users as b WHERE '. $identifier_type . '=' .$db->Quote($identifier));
			$result = $db->loadObject();
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

	function updateEmail($userinfo, &$existinguser, &$status,$jname){
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'UPDATE #__users SET email ='.$db->Quote($userinfo->email) .' WHERE id =' . $existinguser->userid;
		$db->setQuery($query);
		if (!$db->query()) {
			$status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
		} else {
			$status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
		}
	}

	function updatePassword($userinfo, &$existinguser, &$status,$jname){
        $db = & JFusionFactory::getDatabase($jname);
		$userinfo->password_salt  = JUserHelper::genRandomPassword(32);
		$userinfo->password = JUserHelper::getCryptedPassword($userinfo->password_clear, $userinfo->password_salt);
		$new_password = $userinfo->password . ':' . $userinfo->password_salt;
		$query = 'UPDATE #__users SET password =' . $db->Quote($new_password) . ' WHERE id =' . $existinguser->userid;
		$db->setQuery($query);
			if (!$db->query()) {
			$status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
		} else {
			$status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
		}
	}

	function blockUser($userinfo, &$existinguser, &$status,$jname){
		//block the user
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'UPDATE #__users SET block = 1 WHERE id =' . $existinguser->userid;
		if (!$db->query()) {
			$status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
		} else {
			$status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
		}
	}

	function unblockUser($userinfo, &$existinguser, &$status,$jname){
		//unblock the user
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'UPDATE #__users SET block = 0 WHERE id =' . $existinguser->userid;
		$db->setQuery($query);
		$db->query();
		if (!$db->query()) {
			$status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
		} else {
			$status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
		}
	}

	function activateUser($userinfo, &$existinguser, &$status,$jname){
		//unblock the user
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'UPDATE #__users SET block = 0, activation = \'\' WHERE id =' . $existinguser->userid;
		$db->setQuery($query);
		if (!$db->query()) {
			$status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
		} else {
			$status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
		}
	}

	function inactivateUser($userinfo, &$existinguser, &$status,$jname){
		//unblock the user
        $db = & JFusionFactory::getDatabase($jname);
		$query = 'UPDATE #__users SET block = 1, activation = '.$db->Quote($userinfo->activation) .' WHERE id =' . $existinguser->userid;
		$db->setQuery($query);
		$db->query();
		if (!$db->query()) {
			$status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
		} else {
			$status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
		}
	}

	function filterUsername($username,$jname){
		//check to see if additional username filtering need to be applied
		$params = JFusionFactory::getParams($jname);
		$added_filter = $params->get('username_filter');
		if ($added_filter && $added_filter != $jname) {
			$JFusionPlugin = JFusionFactory::getUser($added_filter);
			if(method_exists($JFusionPlugin, 'filterUsername')){
				$username = $JFusionPlugin->filterUsername($username);
			}
		}
		return $username;
	}

	function updateUsername($userinfo, &$existinguser, &$status,$jname){
		//generate the filtered integration username
		$username_clean = $userinfo->username;
    	$status['debug'][] = JText::_('USERNAME'). ': ' . $userinfo->username . ' -> ' .  JText::_('FILTERED_USERNAME') . ':' . $username_clean;

		$query = 'UPDATE #__users SET username =' . $db->Quote($username_clean) . 'WHERE id =' . $existinguser->userid;
		$db->setQuery($query);
		if (!$db->query()) {
			//update failed, return error
			$status['error'][] = JText::_('USERNAME_UPDATE_ERROR'). ': ' . $db->stderr();
		} else {
			$status['debug'][] = JText::_('USERNAME_UPDATE') .': ' . $username_clean;
		}

		if ($jname == 'joomla_int'){
			//delete old entries in the #__jfusion_users table
			$query = 'DELETE FROM #__jfusion_users WHERE id =' . $existinguser->userid;
			$db->setQuery($query);
			if (!$db->query()) {
				$status['error'][] = JText::_('USERNAME_UPDATE_ERROR'). ': ' . $db->stderr();
			}

			//delete old entries in the #__jfusion_users_plugin table
			$query = 'DELETE FROM #__jfusion_users_plugin WHERE id =' . $existinguser->userid;
			$db->setQuery($query);
			if (!$db->query()) {
				$status['error'][] = JText::_('USERNAME_UPDATE_ERROR'). ': ' . $db->stderr();
			}

			//add a new entry in the #__jfusion_users table to allow login with the new username
			$query = 'INSERT INTO #__jfusion_users (id, username) VALUES (' . $existinguser->userid . ',' . $db->Quote($userinfo->username) . ')';
			$db->setQuery($query);
			if (!$db->query()) {
				$status['error'][] = JText::_('USERNAME_UPDATE_ERROR'). ': ' . $db->stderr();
			}
		}
	}

	function createUser($userinfo, $overwrite, &$status,$jname){
		//load the database
        $db = & JFusionFactory::getDatabase($jname);
		//joomla does not allow duplicate email addresses, check to see if the email is unique
		$query = 'SELECT id as userid, username, email from #__users WHERE email ='.$db->Quote($userinfo->email);
		$db->setQuery($query);
		$existinguser = $db->loadObject();

		if (empty($existinguser)) {
			//apply username filtering
			$username_clean = $userinfo->username;//$this->filterUsername($userinfo->username);

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
           	$status['debug'][] = JText::_('USERNAME') .':'. $userinfo->username . '   ' . JText::_('FILTERED_USERNAME') .':'. $username_clean;

				//also store the salt if present
				if ($userinfo->password_salt) {
					$password = $userinfo->password . ':' . $userinfo->password_salt;
				} else {
					$password = $userinfo->password;
				}

				$instance = new JUser();
				$instance->set('name'         , $userinfo->name );
				$instance->set('username'     , $username_clean );
				$instance->set('password'     , $password);
				$instance->set('email'        , $userinfo->email );
				$instance->set('block'        , $userinfo->block );
				$instance->set('activation'   , $userinfo->activation );
				$instance->set('sendEmail'   , 1 );

				//find out what usergroup the new user should have
				$params = JFusionFactory::getParams($jname);
				$gid = $params->get('usergroup', 18);
				$query = 'SELECT name FROM #__core_acl_aro_groups WHERE id = ' . $gid;
				$db->setQuery($query);
				$usergroup = $db->loadResult();

				$instance->set('usertype'     , $usergroup );
				$instance->set('gid'          , $gid );

				if ($jname == 'joomla_int'){
					// save the user
					if (!$instance->save(false)) {
						//report the error
						$status = array();
						$status['error'] = $instance->getError();
						return $status;
					} else {
						//find out the new userid
						$query = 'SELECT id FROM #__users WHERE username =' . $db->Quote($username_clean);
						$db->setQuery($query);
						$userid = $db->loadResult();

						//add a new entry in the #__jfusion_users table to allow login with the new username
						$query = 'INSERT INTO #__jfusion_users (id, username) VALUES (' . $userid . ',' . $db->Quote($userinfo->username) . ')';
						$db->setQuery($query);
						if (!$db->query()) {
							$status['error'][] = JText::_('USER_CREATION_ERROR'). ': ' . $db->stderr();
						}
					}
				} else {// joomla_ext
					// convert the Joomla userobject to a std object
					$user=$instance->getProperties();
					// get rid of internal properties
					unset ($user['password_clear']);
					unset ($user['aid']);
					unset ($user['guest']);
					// set the creationtime and lastaccess time
					$user['registerDate'] = date('Y-m-d H:i:s', time());
					$user= (object) $user;
					$user->id = NULL;
					if (!$db->insertObject('#__users', $user, 'id' )) {
						//return the error
						$status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
						return;
					}
					//find out the new userid
					$query = 'SELECT id FROM #__users WHERE username =' . $db->Quote($username_clean);
					$db->setQuery($query);
					$userid = $db->loadResult();
					//add the user to the core_acl_aro
					$acl = array();
					$acl['section_value']='users';
					$acl['value']=$userid;
					$acl['order_value']=0;
					$acl['name']=$userinfo->name;
					$acl['hidden']=0;
					$acl= (object) $acl;
					$acl->id = NULL;
					if (!$db->insertObject('#__core_acl_aro', $acl, 'id' )) {
						//return the error
						$status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
						return;
					}
					// find out the new aro id
          			$aro_id = $db->insertid();
					// and finally add the user to the core_acl_groups_aro_map
					$query = 'INSERT INTO #__core_acl_groups_aro_map (group_id, aro_id) VALUES (' . $gid . ',' . $aro_id . ')';
					$db->setQuery($query);
					if (!$db->query()) {
						$status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
					}
				}

				//check to see if the user exists now
				$joomla_user = $this->getUser($userinfo->username,$jname);

				if ($joomla_user) {
					//report back success
					$status['userinfo'] = $joomla_user;
					$status['debug'][] = JText::_('USER_CREATION');
					return;
				} else {
					$status['error'] = JText::_('COULD_NOT_CREATE_USER');
					return;
				}

		} else {
			//Joomla does not allow duplicate emails report error
			$status['debug'][] = JText::_('USERNAME') . ' ' . JText::_('CONFLICT').  ': ' . $existinguser->username . ' -> ' . $userinfo->username;
			if ($overwrite) {
				$status['debug'][] = JText::_('USERNAME_CONFLICT_OVERWITE_ENABLED');
				$this->updateUsername($userinfo, $existinguser, $status,$jname);
			} else {
				$status['debug'][] = JText::_('USERNAME_CONFLICT_OVERWITE_DISABLED');
				$status['error'] = JText::_('EMAIL_CONFLICT') . '. UserID:' . $existinguser->userid . ' JFusionPlugin:' . $this->getJname();
			}
			$status['userinfo'] = $existinguser;
			return;
		}
	}

	function updateUser($userinfo, $overwrite,$jname){
		// Initialise some variables
		$params = JFusionFactory::getParams($jname);
        $db = & JFusionFactory::getDatabase($jname);
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
		$login_identifier = $params->get('login_identifier');
		if ($login_identifier == 2){
			$existinguser = $this->getUser($userinfo->email,$jname);
		} else {
			$existinguser = $this->getUser($userinfo->username,$jname);
		}


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
				//if not salt set, update the password
				if(empty($userinfo->password_salt)){
					$this->updatePassword($userinfo, $existinguser, $status,$jname);
				} else {
					// add password_clear to existinguser for the Joomla helper routines
					$existinguser->password_clear=$userinfo->password_clear;
					//check if the password needs to be updated
					$model = JFusionFactory::getAuth($jname);
					$testcrypt = $model->generateEncryptedPassword($existinguser);
					if ($testcrypt != $existinguser->password) {
						$this->updatePassword($userinfo, $existinguser, $status,$jname);
					} else {
						$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ':' .  JText::_('PASSWORD_VALID');
					}
				}
			} else {
				$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' . JText::_('PASSWORD_UNAVAILABLE');
			}
			//check the blocked status
			if ($existinguser->block != $userinfo->block) {
				if ($update_block || $overwrite) {
					if ($userinfo->block) {
						//block the user
						$this->blockUser($userinfo, $existinguser, $status,$jname);
					} else {
						//unblock the user
						$this->unblockUser($userinfo, $existinguser, $status,$jname);
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
						$this->inactivateUser($userinfo, $existinguser, $status,$jname);
					} else {
						//activate the user
						$this->activateUser($userinfo, $existinguser, $status,$jname);
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
			$this->createUser($userinfo, $overwrite, $status);
			if (empty($status['error'])) {
				$status['action'] = 'created';
			}
			return $status;
		}
	}

    /************************************************
	 * Functions For JFusion Who's Online Module
	 ***********************************************/

	function getOnlineUserQuery()
	{
		$query = 'SELECT DISTINCT u.id AS userid, u.username, u.name' .
				 ' FROM #__users AS u INNER JOIN #__session AS s' .
				 ' ON u.id = s.userid' .
				 ' WHERE s.client_id = 0' .
				 ' AND s.guest = 0';
		return $query;
	}

	function getNumberOnlineGuests()
	{
	    $db		  =& JFactory::getDBO();
		$query = 'SELECT COUNT(*)' .
					' FROM #__session' .
					' WHERE guest = 1 AND usertype = "" AND client_id = 0';
		$db->setQuery($query);
		return $db->loadResult();
	}

	function getNumberOnlineMembers()
	{
	    $db		  =& JFactory::getDBO();
		$query = 'SELECT COUNT(DISTINCT userid) AS c' .
					' FROM #__session' .
					' WHERE guest = 0 AND client_id = 0';
		$db->setQuery($query);

		return $db->loadResult();
	}
}