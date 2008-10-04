    <?php

    /**
* @package JFusion_MyBB
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
* JFusion plugin class for myBB
* @package JFusion_MyBB
*/
    class JFusionUser_mybb extends JFusionUser{

        function updateUser($userinfo, $overwrite)
        {
            // Initialise some variables
            $db = JFusionFactory::getDatabase($this->getJname());
            $status = array();
            $status['debug'] = '';
            $status['error'] = '';

            //find out if the user already exists
            $existinguser = $this->getUser($userinfo->username);

            if (!empty($existinguser)) {
                //a matching user has been found
                if ($existinguser->email != $userinfo->email) {
                    $this->updateEmail($userinfo, $existinguser, $status);
                }

                if (!empty($userinfo->password_clear)) {
                    //we can update the password
                    $this->updatePassword($userinfo, $existinguser, $status);
                }

                //check the blocked status
                if ($existinguser->block != $userinfo->block) {
                    if ($userinfo->block) {
                        //block the user
                        $this->blockUser($userinfo, $existinguser, &$status);
                    } else {
                        //unblock the user
                        $this->unblockUser($userinfo, $existinguser, &$status);
                    }
                }

                //check the blocked status
                if ($existinguser->block != $userinfo->block) {
                    if ($userinfo->block) {
                        //block the user
                        $this->blockUser($userinfo, $existinguser, &$status);
                    } else {
                        //unblock the user
                        $this->unblockUser($userinfo, $existinguser, &$status);
                    }
                }

                $status['userinfo'] = $existinguser;
                if (empty($status['error'])) {
                    $status['action'] = 'updated';
                    $status['debug'] .= ' ,User already exists.';
                }
                return $status;
            } else {
                //we need to create a new user
                $this->createUser($userinfo, $status);
                if (empty($status['error'])) {
                    $status['action'] = 'created';
                    $status['debug'] .= ' ,User created.';
                }
                return $status;

            }
        }

        function &getUser($username)
        {
            // Get user info from database
            $db = JFusionFactory::getDatabase($this->getJname());
            $query = 'SELECT a.uid as userid, a.username, a.usergroup, a.username as name, a.email, a.password, a.salt as password_salt, a.usergroup as activation, b.isbannedgroup as block FROM #__users as a INNER JOIN #__usergroups as b ON a.usergroup = b.gid WHERE username=' . $db->Quote($username);
            $db->setQuery($query );
            $result = $db->loadObject();

            if ($result) {
                //Check to see if user needs to be activated
                if ($result->usergroup == 5) {
                    jimport('joomla.user.helper');
                    $result->activation = JUserHelper::genRandomPassword(32);
                    ;
                } else {
                    $result->activation = NULL;
                }
            }

            return $result;

        }


        function getJname()
        {
            return 'mybb';
        }

        function deleteUser($username)
        {
            //TODO: create a function that deletes a user
        }

        function destroySession($userinfo, $options)
        {
            $params = JFusionFactory::getParams($this->getJname());
            $cookiedomain = $params->get('cookie_domain');
            $cookiepath = $params->get('cookie_path', '/');

            //Set cookie values
            $expires = mktime(12,0,0,1, 1, 1990);

            if (!$cookiepath) {
                $cookiepath = "/";
            }

            // Clearing Forum Cookies
            $remove_cookies = array('mybb', 'mybbuser', 'mybbadmin');
            if ($cookiedomain) {
                foreach($remove_cookies as $name){
                    @setcookie($name, '', $expires, $cookiepath, $cookiedomain);
                }
            } else {
                foreach($remove_cookies as $name){
                    @setcookie($name, '', $expires, $cookiepath);
                }
            }
            $status['error'] = false;
            return $status;
        }


        function createSession($userinfo,$options)
        {
            //get cookiedomain, cookiepath (theIggs solution)
            $params = JFusionFactory::getParams($this->getJname());
            $cookiedomain = $params->get('cookie_domain','');
            $cookiepath = $params->get('cookie_path','/');

            //get myBB uid, loginkey
            $db = JFusionFactory::getDatabase($this->getJname());
            $query = 'SELECT uid, loginkey FROM #__users WHERE username=' .$db->quote($userinfo->username) ;
            $db->setQuery($query );
            $user = $db->loadObject();

            // Set cookie values
            $name='mybbuser';
            $value=$user->uid.'_'.$user->loginkey;
            $httponly=true;

            if (isset($options['remember'])) {
                if ($options['remember']) {
                    // Make the cookie expire in a years time
                    $expires = 60*60*24*365;
                } else {
                    // Make the cookie expire in 30 minutes
                    $expires = 60*30;
                }
            } else {
                //Make the cookie expire in 30 minutes
                $expires = 60*30;
            }

            $cookiepath = str_replace(array("\n","\r"), "", $cookiepath);
            $cookiedomain = str_replace(array("\n","\r"), "", $cookiedomain);

            JFusionFunction::addCookie($name, $value, $expires, $cookiepath, $cookiedomain, $httponly);

            $status = array();
            $status['debug'] = JText::_('NAME') . '=' . $name . ', ' . JText::_('VALUE') . '=' . $value . ', ' . JText::_('COOKIE_PATH') . '=' . $cookiepath . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $cookiedomain;
            $status['error'] = false;
            return $status;

        }

        function filterUsername($username)
        {
            //TODO: no username filtering implemented yet
            return $username;
        }

        function blockUser($userinfo, &$existinguser, &$status)
        {
            $db = JFusionFactory::getDatabase($this->getJname());
            $user = new stdClass;
            $user->uid = $existinguser->userid;
            $user->gid = 7;
            $user->oldgroup = $existinguser->usergroup;
            $user->admin = 1;
            $user->dateline = time();
            $user->bantime = '---';
            $user->reason = 'JFusion';
            $user->lifted = 0;

            //now append the new user data
            if (!$db->insertObject('#__banned', $user, 'uid' )) {
                //return the error
                $status['error'] = 'Error while banning the user: ' . $db->stderr();
                return $status;
            }

            //change its usergroup
            $query = 'UPDATE #__users SET usergroup = 7 WHERE uid = '.$existinguser->userid;
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'] = 'Error while banning the user: ' . $db->stderr();
                return $status;
            }
            $status['debug'] .= ' ,the user was blocked in mybb. ';


        }

        function unblockUser ($userinfo, &$existinguser, &$status)
        {
            $db = JFusionFactory::getDatabase($this->getJname());

			//found out what the old usergroup was
        	$query = 'SELECT oldgroup from #__banned WHERE uid =' . $existinguser->userid;;
	        $db->setQuery($query );
    	    $oldgroup = $db->loadResult();

    	    //delete the ban
            $query = 'DELETE #__banned WHERE uid=' . $existinguser->userid;
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'] = 'Error while unbanning the user: ' . $db->stderr();
                return $status;
            }
    	    //restore the usergroup
            $query = 'UPDATE #__users SET usergroup = '.$oldgroup.' WHERE uid = '.$existinguser->userid;
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'] = 'Error while unbanning the user: ' . $db->stderr();
                return $status;
            }

            $status['debug'] .= ' ,the user was unblocked in mybb. ';
        }

        function updatePassword ($userinfo, &$existinguser, &$status)
        {
            jimport('joomla.user.helper');
            $existinguser->password_salt = JUserHelper::genRandomPassword(6);
            $existinguser->password = md5(md5($existinguser->password_salt).md5($userinfo->password_clear));

            $db = JFusionFactory::getDatabase($this->getJname());
            $query = 'UPDATE #__users SET password =' . $db->quote($existinguser->password) . ', salt = '.$db->quote($existinguser->password_salt). ' WHERE uid =' . $existinguser->userid;
            $db->setQuery($query);
            if (!$db->query()) {
                //return the error
                $status['error'] = 'Error while creating the user: ' . $db->stderr();
                return $status;
            }
            $status['debug'] .= 'Password was updated to:' . $existinguser->password;

        }

        function createUser ($userinfo, &$status)
        {
            //found out what usergroup should be used
            $db = JFusionFactory::getDatabase($this->getJname());
            $params = JFusionFactory::getParams($this->getJname());
            $usergroup = $params->get('usergroup');
            $username_clean = $this->filterUsername($userinfo->username);

            //prepare the variables
            $user = new stdClass;
            $user->uid = NULL;
            $user->username = $username_clean;
            $user->email = $userinfo->email;

            jimport('joomla.user.helper');
            if (isset($userinfo->password_clear)) {
                //we can update the password
                $user->salt = JUserHelper::genRandomPassword(6);
                $user->password = md5(md5($user->salt).md5($userinfo->password_clear));
                $user->loginkey  = JUserHelper::genRandomPassword(50);
            } else {
                $user->password = $userinfo->password;
                if (!isset($userinfo->password_salt)) {
                    $user->salt = JUserHelper::genRandomPassword(6);
                } else {
                    $user->salt = $userinfo->password_salt;
                }
                $user->loginkey  = JUserHelper::genRandomPassword(50);
            }

			if (!empty($userinfo->activation)) {
            	$user->usergroup = 3;
			} elseif (!empty($userinfo->block)) {
            	$user->usergroup = 7;
			} else {
            	$user->usergroup = $usergroup;
			}

            //now append the new user data
            if (!$db->insertObject('#__users', $user, 'uid' )) {
                //return the error
                $status['error'] = 'Error while creating the user: ' . $db->stderr();
                return $status;
            } else {
                //return the good news
                $status['debug'] = 'Created new user with userid:' . $user->uid;
                $status['error'] = false;
                $status['userinfo'] = $this->getUser($username_clean);
                $status['action'] = 'created';
                return $status;
            }
        }

        function updateEmail ($userinfo, &$existinguser, &$status)
        {
        //we need to update the email
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'UPDATE #__users SET email ='.$db->quote($userinfo->email) .' WHERE uid =' . $existinguser->userid;
        $db->setQuery($query);
        if (!$db->query()) {
            //update failed, return error
            $status['userinfo'] = $existinguser;
            $status['error'] = 'Error while updating the user email: ' . $db->stderr();
            return $status;
        } else {
            $status['userinfo'] = $userinfo;
            $status['error'] = false;
            $status['action'] = 'updated';
            $status['debug'] = ' Update the email address from: ' . $existinguser->email . ' to:' . $userinfo->email;
            return $status;
        }
    }
}

