<?php
/**
* @package JFusion_vBulletin
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin Class for vBulletin
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_vBulletin
 */
class JFusionUser_vbulletin extends JFusionUser{

	var $params;
	var $joomla_globals;

	function JFusionUser_vbulletin()
	{
		//get the params object
	    $this->params =& JFusionFactory::getParams($this->getJname());
	}

	function vBulletinInit()
	{
		//only initialize the vb framework if it has not already been done and if we are outside of vbulletin
		if(!defined('VB_AREA') && !defined('_VBULLETIN_JFUSION_HOOK'))
		{
			//load the vbulletin framework
			define('VB_AREA','External');
			define('SKIP_SESSIONCREATE', 1);
			define('SKIP_USERINFO', 1);
			define('CWD', $this->params->get('source_path'));

			if(file_exists(CWD))
			{
				require_once(CWD.'/includes/init.php');

				//force into global scope
				$GLOBALS["vbulletin"] =& $vbulletin;
				$GLOBALS["db"] =& $vbulletin->db;
				return true;
			}
			else
			{
				JError::raiseWarning(500, JText::_('SOURCE_PATH_NOT_FOUND'));
				return false;
			}
		}
		else
		{
			return true;
		}
	}

    function &getUser($username)
    {
        // Get user info from database
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT userid, username, username as name, usergroupid, displaygroupid, customtitle, usertitle, posts, email, password, salt as password_salt  FROM #__user WHERE username=' . $db->Quote($username);
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT userid FROM #__userban WHERE userid='. $result->userid;
            $db->setQuery($query);
            if ($db->loadObject()) {
                $result->block = 1;
            } else {
                $result->block = 0;
            }

            //check to see if the user is awaiting activation
            $activationgroup = $this->params->get('activationgroup');

            if ($activationgroup == $result->usergroupid) {
                jimport('joomla.user.helper');
                $result->activation = JUserHelper::genRandomPassword(32);
            } else {
                $result->activation = '';
            }
        }
        return $result;
    }


    function getJname()
    {
        return 'vbulletin';
    }

    function getTablename()
    {
        return 'user';
    }


    function deleteUser($userinfo)
    {
    	//setup status array to hold debug info and errors
        $status = array();
        $status['debug'] = array();
        $status['error'] = array();

		//backup Joomla's global scope
		$this->backupGlobals();

    	//initialize vb framework
		if(!$this->vBulletinInit()) return null;

		//setup the existing user
		$userdm =& datamanager_init('User', $GLOBALS["vbulletin"], ERRTYPE_SILENT);
		$existinguser = $this->convertUserData($userinfo);
		$userdm->set_existing($existinguser);

		//delete the user
		$userdm->delete();
		if (empty($userdm->errors)) {
			foreach ($userdm->errors AS $index => $error)
    		{
        		$status['error'][] = JText::_('USER_DELETION_ERROR') . ' ' . $error;
    		}
		} else {
			$status['error'] = false;
			$status['debug'][] = JText::_('USER_DELETION'). ' ' . $existinguser->userid;
		}
		unset($userdm);

       //restore Joomla's global scope
		$this->restoreGlobals();

		return $status;
    }

    function destroySession($userinfo, $options)
    {
    	//vbulletin will take care of this when logging out via jfusion vbulletin authentication plugin
   	  	if(!defined("_VBULLETIN_JFUSION_HOOK"))
    	{
    		//backup Joomla's global scope
			$this->backupGlobals();

	    	//initialize vb framework
			if(!$this->vBulletinInit()) return null;

    		//set the user so that vb deletes the session from its db
			$GLOBALS["vbulletin"]->userinfo['userid'] = $userinfo->userid;

			//destroy vb cookies and sessions
			require_once(CWD . "/includes/functions_login.php");
			process_logout();

			//destroy vbulletin global variable
			unset($GLOBALS["vbulletin"]);
			unset($GLOBALS["db"]);

			$status['debug'] .= 'Destroyed session: userid = ' . $userinfo->userid . ', password = '.substr($userinfo->password,0,6) . '********' ;

			//restore Joomla's global scope
			$this->restoreGlobals();

			return $status;
    	}

    }

    function createSession($userinfo, $options)
    {
    	//vbulletin will take care of this when logging in via jfusion vbulletin authentication plugin
    	if(!defined("_VBULLETIN_JFUSION_HOOK"))
    	{
    		//backup Joomla's global scope
			$this->backupGlobals();

  			//initialize vb framework
			if(!$this->vBulletinInit()) return null;

			//setup the status array to hold debug info and errors
	        $status = array();
	        $status['debug'] = '';
			$status["error"] = '';

	        $userid = $userinfo->userid;

	        if ($userid && !empty($userid) && ($userid > 0)) {

				$existinguser = $this->getUser($userinfo->username);
	        	$vbLicense = $this->params->get('source_license','');

				if (isset($options['remember'])) {
					$expires = false;
				} else {
					$expires = true;
				}

				//setup the existing user
				$userdm =& datamanager_init('User', $GLOBALS["vbulletin"], ERRTYPE_SILENT);
				$vbuser = $this->convertUserData($existinguser);
				$userdm->set_existing($vbuser);

				//update password expiration time if password expiration is enabled via vbulletin
				$userdm->set('passworddate', 'FROM_UNIXTIME('.TIMENOW.')', false);
				$userdm->save();

				//include vb login functions
				require_once(CWD . '/includes/functions_login.php');

				//set cookies
				vbsetcookie('userid', $existinguser->userid,$expires, true, true);
				vbsetcookie('password',md5($existinguser->password.$vbLicense),$expires, true, true);

				//process login
				process_new_login('', 1, '');

				$status['error'] = false;

				$vdb =& JFusionFactory::getDatabase($this->getJname());
				$query = "SELECT varname, value FROM #__setting WHERE varname IN ('cookiedomain', 'cookiepath')";
				$vdb->setQuery($query);
				$cookie = $vdb->loadObjectList('varname');
				$host =  (empty($cookie['cookiedomain']->value)) ? str_replace(array('http://','https://'),'',  $_SERVER['SERVER_NAME']) : $cookie['cookiedomain']->value;
				$status['debug'] .= JText::_('CREATED_SESSION'). ' userid = ' . $userinfo->userid . ', password = '.substr($userinfo->password,0,6) . '********, ' . JText::_('COOKIE_SALT_STR'). ' = '.substr($vbLicense,0,4).'******, ' . JText::_('COOKIE_PATH'). ' = '.$cookie['cookiepath']->value. ', ' . JText::_('COOKIE_DOMAIN') .' = '.$host;
	        } else {
	            //could not find a valid userid
	            $status['error'] = JText::_('INVALID_USERID');
	        }

			unset($userdm);

			//restore Joomla's global scope
			$this->restoreGlobals();

	        return $status;
    	}
    }

    function filterUsername($username)
    {
        //no username filtering implemented yet
        return $username;

    }

    function updatePassword ($userinfo, &$existinguser, &$status)
    {
        jimport('joomla.user.helper');
        $existinguser->password_salt = JUserHelper::genRandomPassword(3);
		$existinguser->password = md5(md5($userinfo->password_clear).$existinguser->password_salt);
        $db = JFusionFactory::getDatabase($this->getJname());
                $query = 'UPDATE #__user SET password = ' . $db->Quote($existinguser->password). ', salt = ' . $db->Quote($existinguser->password_salt). ' WHERE userid  = ' . $existinguser->userid;
            $db->setQuery($query );
        if (!$db->query()) {
            $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . ': ' . $db->stderr();
        } else {
	        $status['debug'][] = JText::_('PASSWORD_UPDATE') . ' ' . substr($existinguser->password,0,6) . '********';
        }
    }

    function updateUsername ($userinfo, &$existinguser, &$status)
    {

    }

    function updateEmail ($userinfo, &$existinguser, &$status)
    {
        //we need to update the email
        $db = JFusionFactory::getDatabase($this->getJname());
       	$query = 'UPDATE #__user SET email ='.$db->Quote($userinfo->email) .' WHERE userid =' . $existinguser->userid;
       	$db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . ': ' . $db->stderr();
        } else {
        	$status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    function blockUser ($userinfo, &$existinguser, &$status)
    {
		//backup Joomla's global scope
		$this->backupGlobals();

    	//initialize vb framework
		if(!$this->vBulletinInit()) return null;

		$db = JFusionFactory::getDatabase($this->getJname());

        //get the id of the banned group
		$bannedgroup = $this->params->get('bannedgroup');

        //update the usergroup to banned
		$query = 'UPDATE #__user SET usergroupid = ' . $bannedgroup . ' WHERE userid  = ' . $existinguser->userid;
		$db->setQuery($query);

		if(!$db->query()) {
			$status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . ': ' . $db->stderr();
		} else {

			//add a banned user catch to vbulletin's database
			$ban = new stdClass;
	        $ban->userid = $existinguser->userid;
	        $ban->usergroupid = $existinguser->usergroupid;
	        $ban->displaygroupid = $existinguser->displaygroupid;
	        $ban->customtitle = $existinguser->customtitle;
	        $ban->usertitle = $existinguser->usertitle;
	        $ban->adminid = 1;
	        $ban->bandate = time();
	        $ban->liftdate = 0;
	        $ban->reason = 'You have been banned from this software. Please contact your site admin for more details';

	        //now append the new user data
			if (!$db->insertObject('#__userban', $ban, 'userid' )) {
				$status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . ': ' . $db->stderr();
			} else {
				$status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
			}
		}

        //backup Joomla's global scope
		$this->restoreGlobals();
    }

    function unblockUser ($userinfo, &$existinguser, &$status)
    {
		//backup Joomla's global scope
		$this->backupGlobals();

    	//initialize vb framework
		if(!$this->vBulletinInit()) return null;

		//found out what usergroup should be used
		$usergroup = $this->params->get('usergroup');
		$bannedgroup = $this->params->get('bannedgroup');

		//setup the existing user
		$userdm =& datamanager_init('User', $GLOBALS["vbulletin"], ERRTYPE_SILENT);
		$userinfo = $this->convertUserData($existinguser);

		$userdm->set_existing($userinfo);

    	//first check to see if user is banned and if so, retrieve the prebanned fields
    	//must be something other than $db because it conflicts with vbulletin's global variables
    	$jdb = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT * FROM #__userban WHERE userid = ' . $existinguser->userid;
    	$jdb->setQuery($query );
        $result = $jdb->loadObject();

        if ($result)
		{
	        //set the user title
	      	if($result->customtitle)
			{
				$usertitle = $result->usertitle;
			}
			elseif (!$result->usertitle)
			{
				$usertitle = $this->getDefaultUserTitle($existinguser->posts);
			}
			else
			{
				$usertitle = $result->usertitle;
			}

			$userdm->set('usertitle', $usertitle);
			$userdm->set('posts', $existinguser->posts); // This will activate the rank update

			//keep user from getting stuck as banned
			if($result->usergroupid==$bannedgroup) $usergroupid = $usergroup;
			else $usergroupid = $result->usergroupid;
			if($result->displaygroupid==$bannedgroup) $displaygroupid = $usergroup;
			else $displaygroupid = $result->displaygroupid;

			$userdm->set('usergroupid', $usergroupid);
			$userdm->set('displaygroupid', $displaygroupid);
			$userdm->set('customtitle', $result->customtitle);

			//remove any banned user catches from vbulletin's database
		    $query = 'DELETE FROM #__userban WHERE userid='. $existinguser->userid;
	       	$jdb->setQuery($query);
	        if (!$jdb->Query()) {
	   	        $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . ': ' . $jdb->stderr();
	       	}
		}
		else
		{
			$userdm->set('usergroupid', $usergroup);
			$userdm->set('displaygroupid', 0);
		}

		//performs some final VB checks before saving
		$userdm->pre_save();
	    if(empty($userdm->errors)){

			$userdm->save();

		    $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
	    } else {
    		foreach ($userdm->errors AS $index => $error)
    		{
        		$status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . ' ' . $error;
    		}
	    }

	    unset($userdm);

        //backup Joomla's global scope
		$this->restoreGlobals();
    }

    function activateUser ($userinfo, &$existinguser, &$status)
    {
		//found out what usergroup should be used
		$usergroup = $this->params->get('usergroup');

		//update the usergroup to default group
		$db = JFusionFactory::getDatabase($this->getJname());
		$query = 'UPDATE #__user SET usergroupid = ' . $usergroup . ' WHERE userid  = ' . $existinguser->userid;
		$db->setQuery($query );

		if ($db->query()) {
			//remove any activation catches from vbulletins database
			$query = 'DELETE FROM #__useractivation WHERE userid = ' . $existinguser->userid;
			$db->setQuery($query);

			if(!$db->Query()){
				$status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . ': ' . $db->stderr();
			} else {
				$status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
			}
		} else {
			$status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . ': ' . $db->stderr();
		}
	}

    function inactivateUser ($userinfo, &$existinguser, &$status)
    {
		//found out what usergroup should be used
		$usergroup = $this->params->get('activationgroup');

		//update the usergroup to awaiting activation
		$db = JFusionFactory::getDatabase($this->getJname());
		$query = 'UPDATE #__user SET usergroupid = ' . $usergroup . ' WHERE userid  = ' . $existinguser->userid;
		$db->setQuery($query );

		if ($db->Query()) {
			//update the activation status
			//check to see if the user is already inactivated
			$query = 'SELECT COUNT(*) FROM #__useractivation WHERE userid = ' . $existinguser->userid;
			$db->setQuery($query);
	        if($db->loadResult() == 0)
	        {
	        	//if not, then add an activation catch to vbulletin's database
				$useractivation = new stdClass;
				$useractivation->userid = $existinguser->userid;
				$useractivation->dateline = time();
				$useractivation->activationid = mt_rand();
				$useractivation->usergroupid = $this->params->get('usergroup');

				if($db->insertObject('#__useractivation', $useractivation, 'useractivationid' )){
					$status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
				}
				else {
					$status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . ': ' . $db->stderr();
				}
	        }
	        else{
	        	$status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
	        }
		}
		else{
			$status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . ': ' . $db->stderr();
		}
    }

    function createUser ($userinfo, &$status)
    {
		//backup Joomla's global scope
		$this->backupGlobals();

    	//initialize vb framework
		if(!$this->vBulletinInit()) return null;

    	//get the default user group and determine if the user needs to be set as needing activation
		if(empty($userinfo->activation)){
            $usergroup = $this->params->get('usergroup');
            $setAsNeedsActivation = false;
		} else {
            $usergroup = $this->params->get('activationgroup');
            $setAsNeedsActivation = true;
		}

		//create the new user
		$userdm =& datamanager_init('User', $GLOBALS["vbulletin"], ERRTYPE_SILENT);
		$userdm->set('username', $userinfo->username);
		$userdm->set('email', $userinfo->email);
		$userdm->set('usergroupid', $usergroup);
		$usertitle = $this->getDefaultUserTitle();
		$userdm->set('usertitle',$usertitle);

        if(isset($userinfo->password_clear)){
			$userdm->set('password', $userinfo->password_clear);
		} else {
			//clear password is not available, set a random password for now
			jimport('joomla.user.helper');
			$random_password = JUtility::getHash(JUserHelper::genRandomPassword(10));
			$userdm->set('password', $random_password);
		}

		//use VB default via datamanager instead of manually setting
		//$userdm->set_bitfield('options', 'coppauser', 0);

		//performs some final VB checks before saving
		$userdm->pre_save();
	    if(empty($userdm->errors)){
			$userdmid = $userdm->save();

			//does the user still need to be activated?
			if($setAsNeedsActivation)
			{
				$db = JFusionFactory::getDatabase($this->getJname());
				$useractivation = new stdClass;
				$useractivation->userid = $userdmid;
				$useractivation->dateline = time();
				$useractivation->activationid = mt_rand();
				$useractivation->usergroupid = $this->params->get('usergroup');

	  			$db->insertObject('#__useractivation', $useractivation, 'useractivationid' );
			}

            //return the good news
            $status['userinfo'] = $this->getUser($userinfo->username);
            $status['debug'][] = JText::_('USER_CREATION') .'. '. JText::_('USERID') . $userdmid;
	    } else {
    		foreach ($userdm->errors AS $index => $error)
    		{
        		$status['error'][] = JText::_('USER_CREATION_ERROR') . ' ' . $error;
    		}
	    }

	    unset($userdm);
        //backup Joomla's global scope
		$this->restoreGlobals();
    }

    //convert the existinguser variable into something vbulletin understands
    function convertUserData($existinguser)
    {
    	$userinfo = array(
    		'userid' => $existinguser->userid,
    		'username' => $existinguser->username,
   			'email' => $existinguser->email,
    		'password' => $existinguser->password
    	);

    	return $userinfo;
    }

    //returns the user's title based on number of posts
    function getDefaultUserTitle($posts = 0)
    {
		$db = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT title FROM #__usertitle WHERE minposts <= ' . $posts . ' ORDER BY minposts DESC LIMIT 1';
		$db->setQuery($query);
		$result	 = $db->loadObject();
		return $result->title;
    }

    //backs up joomla's global scope
    function backupGlobals()
    {
    	$this->joomla_globals = $GLOBALS;
    }

    //restore joomla's global scope
    function restoreGlobals()
    {
    	if(is_array($this->joomla_globals)) {
    		$GLOBALS = $this->joomla_globals;
    		$this->joomla_globals = "";
    	}
    }
}