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
 * load the common Joomla JFusion plugin functions
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

//require the standard joomla user functions
jimport('joomla.user.helper');

/**
* JFusion User Class for the internal Joomla database
* For detailed descriptions on these functions please check the model.abstractuser.php
* @package JFusion_Joomla_Int
*/
class JFusionUser_joomla_int extends JFusionUser{

    function getJname()
    {
        return 'joomla_int';
    }

    function updateUser($userinfo, $overwrite)
    {
    	$status = JFusionJplugin::updateUser($userinfo, $overwrite,$this->getJname());
    	return $status;
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
            $result = true;
            return $result;
        } else {
            //this user was NOT create by JFusion. Therefore we need to delete it in the Joomla user table only
            $query = 'SELECT id from #__users WHERE username = ' . $db->quote($username);
            $db->setQuery($query);
            $userid = $db->loadResult();
            if ($userid) {
                //delete it from the Joomla usertable
                $user =& JUser::getInstance($userid);
                $user->delete();
	            $result = true;
    	        return $result;
            } else {
                //could not find user and return an error
                JError::raiseWarning(0, JText::_('ERROR_DELETE') . $username);
	            $result = false;
    	        return $result;
            }
        }
    }


    function &getUser($identifier)
    {
        $userinfo = JFusionJplugin::getUser($identifier,$this->getJname());
        return $userinfo;
    }

    function filterUsername($username)
    {
    	$username = JFusionJplugin::filterUsername($username,$this->getJname());
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

    function destroySession($user, $options)
    {
        //destroy the Joomla session
        $table = & JTable::getInstance('session');
        $table->destroy($user['id'], $options['clientid']);

        $my =& JFactory::getUser();
        if ($my->get('id') == $user['id']) {
            // Hit the user last visit field
            $my->setLastVisit();

            // Destroy the php session for this user
            $session =& JFactory::getSession();
            $session->destroy();
        }

    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::updatePassword($userinfo, $existinguser, $status, $this->getJname());
    }

    function updateUsername($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::updateUsername($userinfo, $existinguser, $status,$this->getJname());
    }

    function updateEmail($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::updateEmail($userinfo, $existinguser, $status,$this->getJname());
    }

    function blockUser($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::blockUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function unblockUser($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::unblockUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function activateUser($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::activateUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function inactivateUser($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::inactivateUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function createUser($userinfo, $overwrite, &$status)
    {
        JFusionJplugin::createUser($userinfo, $overwrite, $status,$this->getJname());
    }
}