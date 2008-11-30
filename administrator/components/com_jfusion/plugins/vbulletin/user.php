<?php

/**
* @package JFusion_vBulletin
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


/**
* JFusion plugin class for vBulletin 3.6.8
* @package JFusion_vBulletin
*/
class JFusionUser_vbulletin extends JFusionUser{

	function updateUser($userinfo, $overwrite)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
        $update_block = $params->get('update_block');
        $update_activation = $params->get('update_activation');
        $update_email = $params->get('update_email');

        $status = array();

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

            if (!empty($userinfo->password_clear)) {
                //we can update the password
                $this->updatePassword($userinfo, $existinguser, $status);
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
            if (empty($existinguser->activation) != empty($userinfo->activation)) {
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
            //we need to create a new user
            $this->createUser($userinfo, &$status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            $status['userinfo'] = $this->getUser($userinfo->username);

            return $status;

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
            $params = JFusionFactory::getParams($this->getJname());
            $activationgroup = $params->get('activationgroup');

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


    function deleteUser($username)
    {
    	//load the vbulletin framework
		define('VB_AREA', 'External');
		define('SKIP_SESSIONCREATE', 1);
		define('SKIP_USERINFO', 1);
		define('CWD', $params->get('source_path'));

		global $vbulletin;
		require_once(CWD . './includes/init.php');

    	//work around to make global vbulletin stick
		$registry = $vbulletin;
		unset($vbulletin);
		$vbDb = $registry->db;
		//declare as global vbulletin's registry and db objects
		global $vbulletin,$db;
		$vbulletin = $registry;
		//vbulletin db object which is needed for vbulletin's project tools addon
		$db = $vbDb;

		$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);

		//setup the existing user
		$userinfo = $this->convertUserData($existinguser);
		$userdm->set_existing($userinfo);

		//delete the user
		$userdm->delete();
		if (empty($userdm->errors)) {
			foreach ($userdm->errors AS $index => $error)
    		{
        		$status['error'][] = JText::_('USER_DELETION_ERROR') . ' ' . $error;
    		}
		} else {
			$status['debug'][] = JText::_('USER_DELETION'). ' ' . $existinguser->userid;
		}

		unset($userdm);
    }

    function destroySession($userinfo, $options)
    {
        // Get the parameters
        $params = JFusionFactory::getParams($this->getJname());

        //Clear the vBulletin Cookie
        $vbCookiePrefix = $params->get('cookie_prefix','bb');
        $vbCookieDomain = $params->get('cookie_domain');
        $vbCookiePath = $params->get('cookie_path');

   		JFusionFunction::addCookie($vbCookiePrefix.'userid', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain, true );
        JFusionFunction::addCookie($vbCookiePrefix.'password', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain, true );
        JFusionFunction::addCookie($vbCookiePrefix.'styleid', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain, true );
        JFusionFunction::addCookie($vbCookiePrefix.'sessionhash', ' ', time() - 1800, $vbCookiePath, $vbCookieDomain, true );

        //also destroy the session in the database
        $db = JFusionFactory::getDatabase($this->getJname());
   	    $query = 'DELETE FROM #__session WHERE userid =' . $userinfo->userid;
  		$db->setQuery($query);
		$db->query();
    }

    function createSession($userinfo, $options)
    {
        // Get a database object, and prepare some basic data
        $status = array();
        $status['debug'] = '';
        $userid = $userinfo->userid;

        if ($userid && !empty($userid) && ($userid > 0)) {
            $params = JFusionFactory::getParams($this->getJname());
            $vbCookiePrefix = $params->get('cookie_prefix');
            $vbCookieDomain = $params->get('cookie_domain');
            $vbCookiePath = $params->get('cookie_path');
            $vbLicense = $params->get('source_license','');

            $bypost = 1;

            if (isset($options['remember'])) {
                $expires = 365*24*60*60;
            } else {
            	$expires = 60*30;
            }

   				JFusionFunction::addCookie('usercookie[username]' , $userinfo->username, $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie('usercookie[password]' , $userinfo->password, $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie($vbCookiePrefix.'userid' , $userinfo->userid, $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie($vbCookiePrefix.'password' , md5($userinfo->password . $vbLicense ), $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie('userid' , $userinfo->userid, $expires, $vbCookiePath, $vbCookieDomain, true);

            $status['error'] = false;
       	    $status['debug'] .= JText::_('CREATED') . ' ' . JText::_('SESSION') . ': ' .JText::_('USERID') . '=' . $userinfo->userid . ',  ' . JText::_('COOKIE_PATH') . '=' . $vbCookiePath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $vbCookieDomain . ' password:'.substr($userinfo->password,0,6) . '********' ;
            return $status;
        } else {
            //could not find a valid userid
            $status['error'] = JText::_('INVALID_USERID');
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
                $query = 'UPDATE #__user SET password = ' . $db->quote($existinguser->password). ', salt = ' . $db->quote($existinguser->password_salt). ' WHERE userid  = ' . $existinguser->userid;
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
       	$query = 'UPDATE #__user SET email ='.$db->quote($userinfo->email) .' WHERE userid =' . $existinguser->userid;
       	$db->setQuery($query);
        if (!$db->query()) {
            $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . ': ' . $db->stderr();
        } else {
        	$status['debug'][] = JText::_('EMAIL_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
        }
    }

    function blockUser ($userinfo, &$existinguser, &$status)
    {
        $db = JFusionFactory::getDatabase($this->getJname());

        //get the id of the banned group
        $params = JFusionFactory::getParams($this->getJname());
		$bannedgroup = $params->get('bannedgroup');

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
    }

    function unblockUser ($userinfo, &$existinguser, &$status)
    {
    	$params = JFusionFactory::getParams($this->getJname());
		//found out what usergroup should be used
		$usergroup = $params->get('usergroup');
		$bannedgroup = $params->get('bannedgroup');

    	//load the vbulletin framework
		define('VB_AREA', 'External');
		define('SKIP_SESSIONCREATE', 1);
		define('SKIP_USERINFO', 1);
		define('CWD', $params->get('source_path'));

		require_once(CWD . './includes/init.php');

		//setup the existing user
		$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
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
	   	        $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . ': ' . $db->stderr();
	       	}
		}
		else
		{
			$userdm->set('usergroupid', $usergroup);
			$userdm->set('displaygroupid', 0);
		}

		$userdm->pre_save();
	    if(empty($userdm->errors)){

	    	//work around to make global vbulletin stick
			$registry = $vbulletin;
			unset($vbulletin);
			$vbDb = $registry->db;
			//declare as global vbulletin's registry and db objects
			global $vbulletin,$db;
			$vbulletin = $registry;
			//vbulletin db object which is needed for vbulletin's project tools addon
			$db = $vbDb;

			$userdm->save();

		    $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
	    } else {
    		foreach ($userdm->errors AS $index => $error)
    		{
        		$status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . ' ' . $error;
    		}
	    }

		unset($userdm);
    }

    function activateUser ($userinfo, &$existinguser, &$status)
    {
		//found out what usergroup should be used
		$params = JFusionFactory::getParams($this->getJname());
		$usergroup = $params->get('usergroup');

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
		$params = JFusionFactory::getParams($this->getJname());
		$usergroup = $params->get('activationgroup');

		//update the usergroup to awaiting activation
		$db = JFusionFactory::getDatabase($this->getJname());
		$query = 'UPDATE #__user SET usergroupid = ' . $usergroup . ' WHERE userid  = ' . $existinguser->userid;
		$db->setQuery($query );

		if ($db->Query()) {
			//update the activation status
			//check to see if the user is already inactivated
			$query = 'SELECT COUNT(*) FROM #__useractivation WHERE userid = ' . $existing->userid;
			$db->setQuery($query);
	        if($db->loadObject() == 0)
	        {
	        	//if not, then add an activation catch to vbulletin's database
				$useractivation = new stdClass;
				$useractivation->userid = $existinguser->userid;
				$useractivation->dateline = time();
				$useractivation->activationid = mt_rand();
				$useractivation->usergroupid = $params->get('usergroup');

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
		//get the params object
        $params = JFusionFactory::getParams($this->getJname());

		//load the vbulletin framework
		define('VB_AREA','External');
		define('SKIP_SESSIONCREATE', 1);
		define('SKIP_USERINFO', 1);
		define('CWD', $params->get('source_path'));

		require_once(CWD.'./includes/init.php');

		if(empty($userinfo->activation)){
            $usergroup = $params->get('usergroup');
            $setAsNeedsActivation = false;
		} else {
            $usergroup = $params->get('activationgroup');
            $setAsNeedsActivation = true;
		}

		//work around to make global vbulletin stick
		$registry = $vbulletin;
		unset($vbulletin);
		$vbDb = $registry->db;
		//declare as global vbulletin's registry and db objects
		global $vbulletin,$db;
		$vbulletin = $registry;
		//vbulletin db object which is needed for vbulletin's project tools addon
		$db = $vbDb;

		//setup the new user
		$newuser =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
		$newuser->set('username', $userinfo->username);
		$newuser->set('email', $userinfo->email);
		$newuser->set('usergroupid', $usergroup);
		$usertitle = $this->getDefaultUserTitle();
		$newuser->set('usertitle',$usertitle);

        if(isset($userinfo->password_clear)){
			$newuser->set('password', $userinfo->password_clear);
		} else {
			//clear password is not available, set a random password for now
			jimport('joomla.user.helper');
			$random_password = JUtility::getHash(JUserHelper::genRandomPassword(10));
			$newuser->set('password', $random_password);
		}

		//use VB default via datamanager instead of manually setting
		//$newuser->set_bitfield('options', 'coppauser', 0);

		$newuser->pre_save();
	    if(empty($newuser->errors)){
			$newuserid = $newuser->save();

			//does the user still need to be activated?
			if($setAsNeedsActivation)
			{
				$db = JFusionFactory::getDatabase($this->getJname());
				$useractivation = new stdClass;
				$useractivation->userid = $newuserid;
				$useractivation->dateline = time();
				$useractivation->activationid = mt_rand();
				$useractivation->usergroupid = $params->get('usergroup');

	  			$db->insertObject('#__useractivation', $useractivation, 'useractivationid' );
			}

            //return the good news
            $status['debug'][] = JText::_('USER_CREATION') .'. '. JText::_('USERID') . $newuserid;
	    } else {
    		foreach ($newuser->errors AS $index => $error)
    		{
        		$status['error'][] = JText::_('USER_CREATION_ERROR') . ' ' . $error;
    		}
	    }

	    unset($newuser);
    }

    function deleteUsername($username)
    {
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
}