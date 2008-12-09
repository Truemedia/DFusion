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
        return  JFusionJplugin::updateUser($userinfo, $overwrite,$this->getJname());
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
        return JFusionJplugin::getUser($identifier,$this->getJname());
    }

    function filterUsername($username)
    {
        return  JFusionJplugin::filterUsername($username,$this->getJname());
    }

    function destroySession($userinfo, $options){
		return JFusionJplugin::destroySession($userinfo, $options,$this->getJname());
     }

    function createSession($userinfo, $options){
		return JFusionJplugin::createSession($userinfo, $options,$this->getJname());
    }

    function updatePassword($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::updatePassword($userinfo, $existinguser, $status,$this->getJname());
    }

    function updateUsername($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::updateUsername($userinfo, $existinguser, $status,$this->getJname());
    }

    function updateEmail($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::updateEmail($userinfo, $existinguser, $status,$this->getJname());
    }

    function blockUser($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::blockUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function unblockUser($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::unblockUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function activateUser($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::activateUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function inactivateUser($userinfo, &$existinguser, &$status)
    {
        return JFusionJplugin::inactivateUser($userinfo, $existinguser, $status,$this->getJname());
    }

    function createUser($userinfo, $overwrite, &$status)
    {
        return JFusionJplugin::createUser($userinfo, $overwrite, $status,$this->getJname());
    }
}