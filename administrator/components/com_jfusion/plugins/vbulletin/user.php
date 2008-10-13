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
                	$this->updateEmail($userinfo, &$existinguser, &$status);
            	} else {
            		//return a debug to inform we skiped this step
            		$status['debug'][] = JText::_('SKIPPED_EMAIL_UPDATE') . ': ' . $existinguser->email . ' -> ' . $userinfo->email;
            	}
            }

            if (!empty($userinfo->password_clear)) {
                //we can update the password
                $this->updatePassword($userinfo, &$existinguser, &$status);
            }

            //check the blocked status
            if ($existinguser->block != $userinfo->block) {
            	if ($update_block || $overwrite) {
	                if ($userinfo->block) {
    	                //block the user
        	            $this->blockUser($userinfo, &$existinguser, &$status);
            	    } else {
                	    //unblock the user
                    	$this->unblockUser($userinfo, &$existinguser, &$status);
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
        	            $this->inactivateUser($userinfo, &$existinguser, &$status);
            	    } else {
                	    //activate the user
	                    $this->activateUser($userinfo, &$existinguser, &$status);
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
            return $status;

        }
    }


    function &getUser($username)
    {
        // Get user info from database
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT userid, username, username as name, email, password, salt as password_salt,  FROM #__user WHERE username=' . $db->Quote($username);
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

            if ($activationgroup == $result->usergroup) {
                jimport('joomla.user.helper');
                $result->activation = JUserHelper::genRandomPassword(32);
            } else {
                $result->activation = '';
            }


            //update the usergroup
	        $db = JFusionFactory::getDatabase($this->getJname());
	        $query = 'UPDATE #__user SET membergroupids = ' . $usergroup . ', usergroupid = ' . $usergroup . ', displaygroupid = ' . $usergroup . ' WHERE userid  = ' . $existinguser->userid;


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
	    //TODO: create a function that deletes a user
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
            $expires = 60*30;

            if (isset($options['remember'])) {
                $expires = 365*24*60*60;
   				JFusionFunction::addCookie('usercookie[username]' , $userinfo->username, $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie('usercookie[password]' , $userinfo->password, $expires, $vbCookiePath, $vbCookieDomain, true);
            }
   				JFusionFunction::addCookie($vbCookiePrefix.'userid' , $userinfo->userid, $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie($vbCookiePrefix.'password' , md5($userinfo->password . $vbLicense ), $expires, $vbCookiePath, $vbCookieDomain, true);
   				JFusionFunction::addCookie('userid' , $userinfo->userid, $expires, $vbCookiePath, $vbCookieDomain, true);

            $status['error'] = false;
       	    $status['debug'] .= JText::_('CREATED') . ' ' . JText::_('SESSION') . ': ' .JText::_('USERID') . '=' . $userinfo->userid . ',  ' . JText::_('COOKIE_PATH') . '=' . $vbCookiePath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $vbCookieDomain . ' password:'.$userinfo->password ;
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
    		        $status['error'][] = JText::_('PASSWORD_UPDATE_ERROR')  . $db->stderr();
        		} else {
	        		$status['debug'][] = JText::_('PASSWORD_UPDATE') . $existinguser->password;
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
    		        $status['error'][] = JText::_('EMAIL_UPDATE_ERROR') . $db->stderr();
        		} else {
	        		$status['debug'][] = JText::_('PASSWORD_UPDATE'). ': ' . $existinguser->email . ' -> ' . $userinfo->email;
	        	}
    }

    function blockUser ($userinfo, &$existinguser, &$status)
    {
            $db = JFusionFactory::getDatabase($this->getJname());
            $ban = new stdClass;
            $ban->userid = $existinguser->userid;
            $ban->usergroupid = $existinguser->usergroup;
            $ban->displaygroupid = $existinguser->usergroup;
            $ban->customtitle = 0;
            $ban->adminid = 1;
            $ban->bandate = time();
            $ban->liftdate = 0;
            $ban->reason = 'You have been banned from this software. Please contact your site admin for more details';

            //now append the new user data
            if (!$db->insertObject('#__userban', $ban, 'userid' )) {
         	    $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
	        } else {
		        $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        	}
    }

    function unblockUser ($userinfo, &$existinguser, &$status)
    {
        	$db = JFusionFactory::getDatabase($this->getJname());
            $query = 'DELETE FROM #__userban WHERE userid='. $existinguser->userid;
            $db->setQuery($query);
        	if (!$db->Query()) {
         	   $status['error'][] = JText::_('BLOCK_UPDATE_ERROR') . $db->stderr();
	        } else {
		        $status['debug'][] = JText::_('BLOCK_UPDATE'). ': ' . $existinguser->block . ' -> ' . $userinfo->block;
        	}
    }

    function activateUser ($userinfo, &$existinguser, &$status)
    {
            //found out what usergroup should be used
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('usergroup');

            //update the usergroup
	        $db = JFusionFactory::getDatabase($this->getJname());
	        $query = 'UPDATE #__user SET membergroupids = ' . $usergroup . ', usergroupid = ' . $usergroup . ', displaygroupid = ' . $usergroup . ' WHERE userid  = ' . $existinguser->userid;
    	    $db->setQuery($query );
        	if (!$db->Query()) {
         	    $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
	        } else {
		        $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        	}
    }

    function inactivateUser ($userinfo, &$existinguser, &$status)
    {
            //found out what usergroup should be used
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('activationgroup');

            //update the usergroup
	        $db = JFusionFactory::getDatabase($this->getJname());
	        $query = 'UPDATE #__user SET membergroupids = ' . $usergroup . ', usergroupid = ' . $usergroup . ', displaygroupid = ' . $usergroup . ' WHERE userid  = ' . $existinguser->userid;
    	    $db->setQuery($query );
        	if (!$db->Query()) {
	            $status['error'][] = JText::_('ACTIVATION_UPDATE_ERROR') . $db->stderr();
    	    } else {
	    	    $status['debug'][] = JText::_('ACTIVATION_UPDATE'). ': ' . $existinguser->activation . ' -> ' . $userinfo->activation;
        	}

    }

    function createUser ($userinfo, &$status)
    {
            //found out what usergroup should be used
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('usergroup');

            //lookup the name of the usergroup
	        $db = JFusionFactory::getDatabase($this->getJname());
    	    $query = 'SELECT group_name from #__groups WHERE group_id = ' . $usergroup;
        	$db->setQuery($query );
        	$usergroupname = $db->loadResult();

            //prepare the variables
            $user = new stdClass;
			$user->userid = NULL;
			if(empty($userinfo->activation)){
            	$usergroup = $params->get('usergroup');
			} else {
            	$usergroup = $params->get('activationgroup');
			}
			$user->usergroupid = $usergroup;
			$user->displaygroupid = $usergroup;
			$user->membergroupids = $usergroup;
			$user->usertitle = $usergroupname;
			$user->username = $userinfo->username;

            if(isset($userinfo->password_clear)){
        		jimport('joomla.user.helper');
        		$user->salt = JUserHelper::genRandomPassword(3);
				$user->password = md5(md5($userinfo->password_clear).$user->salt);
            } else {
				$user->salt = $userinfo->password_salt;
				$user->password = $userinfo->password;
            }

			$user->email = $userinfo->email;
			$user->passworddate = date("Y/m/d");
			$user->joindate = time();

            //now append the new user data
            if (!$db->insertObject('#__user', $user, 'userid' )) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

	        //prepare the variables
    	    $userfield = new stdClass;
			$userfield->userid = $user->userid;

            if (!$db->insertObject('#__userfield', $userfield )) {
                //return the error
                $status['error'][] = JText::_('USER_CREATION_ERROR') . $db->stderr();
                return;
            }

            //return the good news
            $status['debug'][] = JText::_('USER_CREATION');
    }

    function deleteUsername($username)
    {
    }

}

