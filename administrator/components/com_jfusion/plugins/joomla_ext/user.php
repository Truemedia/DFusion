<?php

/**
* @package JFusion_Joomla_Ext
* @version 1.1.0-001
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

jimport('joomla.user.helper');
/**
* @package JFusion_Joomla_Ext
*/


class JFusionUser_joomla_ext extends JFusionUser{


    function getJname()
    {
        return 'joomla_ext';
    }

    function updateUser($userinfo, $overwrite)
    {
        $status = JFusionJplugin::updateUser($userinfo, $overwrite,$this->getJname());
        return $status;
    }

    function deleteUsername($username)
    {
        //get the database ready
        $db = & JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT id FROM #__jfusion_users WHERE username='.$db->Quote($username);
        $db->setQuery($query );
        $userid = $db->loadResult();

        if ($userid) {
            //this user was created by JFusion and we need to delete them from the joomla user and jfusion lookup table
            $user =& JUser::getInstance($userid);
            $user->delete();
            $db->Execute('DELETE FROM #__jfusion_users_plugin WHERE id='.$userid);
            $db->Execute('DELETE FROM #__jfusion_users WHERE id='.$userid);
            return true;
        } else {
            //this user was NOT create by JFusion. Therefore we need to delete it in the Joomla user table only
            $query = 'SELECT id from #__users WHERE username = ' . $db->quote($username);
            $db->setQuery($query);
            $userid = $db->loadResult();
            if ($userid) {
                //delete it from the Joomla usertable
                $user =& JUser::getInstance($userid);
                $user->delete();
                return true;
            } else {
                //could not find user and return an error
                JError::raiseWarning(0, JText::_('ERROR_DELETE') . $username);
                return '';
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
    }

    function destroySession($userinfo, $options){
		$status = JFusionJplugin::destroySession($userinfo, $options,$this->getJname());
		return $status;
     }

    function createSession($userinfo, $options){
		$status = JFusionJplugin::createSession($userinfo, $options,$this->getJname());
		return $status;
    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        JFusionJplugin::updatePassword($userinfo, $existinguser, $status,$this->getJname());
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