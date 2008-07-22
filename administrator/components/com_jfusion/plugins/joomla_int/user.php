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

/**
* @package JFusion_Joomla_Int
*/

class JFusionUser_joomla_int extends JFusionUser{

    function updateUser($userinfo)
    {
        // Initialise some variables
        $db =& JFactory::getDBO();
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);
        if ($userlookup->email == $userinfo->email) {
            //emails match up
            //user exist however some details need to be updates
			//TODO:Update the password

            //return the good news
            $status = array();
            $status['userinfo'] = $userlookup;
            $status['error'] = false;
            $status['debug'] = JText::_('USER_EXISTS');
            return $status;
        } else if ($userlookup) {
            //emails match up
            $status['userinfo'] = $userlookup;
            $status['error'] = JText::_('EMAIL_CONFLICT');
            return $status;
        } else {

            //check for conlicting email addresses
            $db->setQuery('SELECT a.id as userid, a.username, a.name, a.password, a.email, a.block, a.registerDate as registerdate, lastvisitDate as lastvisitdate FROM #__users as a WHERE a.email='.$db->Quote($userinfo->email)) ;
            $conflict_user = $db->loadObject();

            if ($conflict_user) {
                $status = array();
            	$status['userinfo'] = $conflict_user;
                $status['error'] = JText::_('EMAIL_CONFLICT') . '. UserID:' . $conflict_user->userid . ' JFusionPlugin:' . $this->getJname();

                return $status;
            }

            //generate the filtered integration username
            $username_clean = $this->filterUsername($userinfo->username);

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
            $instance->set('registerDate' , $userinfo->registerdate);
            $instance->set('block'        , $userinfo->block );
            $instance->set('lastvisitDate', $userinfo->lastvisitdate);
            $instance->set('sendEmail '   , 1 );

            //find out what usergroup the new user should have
            $params = JFusionFactory::getParams($this->getJname());
            $gid = $params->get('usergroup');
            $query = 'SELECT name FROM #__core_acl_aro_groups WHERE id = ' . $db->quote($usergroup);
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
                    $status = array();
                    $status['userinfo'] = $joomla_user;
                    $status['error'] = false;
                    return $status;
                } else {
                    //report back error
                    $status = array();
                    $status['error'] = 'Could not create Joomla user';
                    return $status;
                }
                $status = array();
                $status['error'] = 'Could not create Joomla user';
                return $status;
                {

                }
            }
        }
    }

    function deleteUser($username)
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
        } else {
            //this user was NOT create by JFusion. Therefore we need to delete it in the Joomla user table only

            $query = 'SELECT id from #__users WHERE username = ' . $db->quote($username);
            $db->setQuery($query);
            $userid = $db->loadResult();
            if ($userid) {
                //delete it from the Joomla usertable
                $user =& JUser::getInstance($userid);
                $user->delete();
            } else {
                //could not find user and return an error
                JError::raiseWarning(0, JText::_('ERROR_DELETE') . $username);
            }
        }
    }


    function &getUser($username)
    {
        //get database object
        $db =& JFactory::getDBO();
        $username = $this->filterUsername($username);

        //first check the JFusion user table
        $db->setQuery('SELECT a.id as userid, b.username, a.name, a.password, a.email, a.block, a.registerDate as registerdate, lastvisitDate as lastvisitdate FROM #__users as a INNER JOIN #__jfusion_users as b ON a.id = b.id WHERE b.username=' . $db->quote($username));
        $result = $db->loadObject();

        if (!$result) {
            //no user found, now check the Joomla user table
            $JFusionUser = JFusionfactory::getUser('joomla_int');
            $filtered_username = $JFusionUser->filterUsername($username);
            $db->setQuery('SELECT a.id as userid, a.username, a.name, a.password, a.email, a.block, a.registerDate as registerdate, lastvisitDate as lastvisitdate FROM #__users as a WHERE a.username=' . $db->quote($filtered_username));
            $result = $db->loadObject();
        }

        if ($result) {
            //update the JFusion user table
            $query = 'INSERT INTO #__jfusion_users (id, username) VALUES (' . $result->userid . ','. $db->quote($username) .')';
            $db->setQuery($query);
            $db->query();

            //split up the password if it contains a salt
            $parts = explode(':', $userinfo->password );
        	if($parts[1]) {
        		$result->password_salt = $parts[1];
        		$result->password = $parts[0];
        	}

            return $result;
        } else {
            return false;
        }
    }


    function getJname()
    {
        return 'joomla_int';
    }

    function filterUsername($username)
    {
        //no username filtering implemented yet
        //define which characters have to be replaced
        $trans = array('&amp;' => '_', '&#123;' => '_', '&#124;' => '_', '&#37;' => '_' , '&#39;' => '_' , '&#40;' => '_' , '&#43;' => '_' , '&#45;' => '_' , '&#41;' => '_', '&#125;' => '_', '&quot;' => '_', '&#039;' => '_', '&lt;' => '_', '&gt;' => '_', '<' => '_', '>' => '_', '"' => '_', "'" => '_', '%' => '_', ';' => '_', '(' => '_', ')' => '_', '&' => '_', '+' => '_', '-' => '_');

        //remove forbidden characters for the username
        $username_esc = strtr($username, $trans);
        return $username_esc;
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

}
