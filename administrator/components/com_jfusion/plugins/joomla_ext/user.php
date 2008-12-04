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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.curl.php');
jimport('joomla.user.helper');
/**
* @package JFusion_Joomla_Ext
*/


class JFusionUser_joomla_ext extends JFusionUser{


    function getJname(){
        return 'joomla_ext';
    }

    function updateUser($userinfo, $overwrite){
        // Initialise some variables
        $db = & JFusionFactory::getDatabase($this->getJname());
        return  JFusionJplugin::updateUser($userinfo, $overwrite,$db,$this->getJname());
    }

    function deleteUsername($username){
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


    function &getUser($identifier){
        //get database object
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::getUser($identifier,$db,$this->getJname());
    }



    function filterUsername($username){
        return  JFusionJplugin::filterUsername($username,$this->getJname());
    }

    function createSession($userinfo, $options){
        $status['error'] = '';
        $params = JFusionFactory::getParams($this->getJname());
        $source_url = $params->get('source_url');
        $cookiedomain = $params->get('cookie_domain');
        $cookiepath = $params->get('cookie_path');
        $cookieexpires = $params->get('cookie_expires');
        $post_url = $source_url.$params->get('login_url');
        $formid = $params->get('loginform_id');
        $override = $params->get('override');
        $hidden = true;
        $buttons = true;
        $integrationtype = 0;
        $relpath=false;
        $cookies = array();
        $cookie  = array();
        global $ch;
        global $cookiearr;
        global $cookies_to_set;
        global $cookies_to_set_index;
        $cookiearr = array();
        $cookies_to_set = array();
        $cookies_to_set_index = 0;
//echo"$post_url,$formid,$userinfo->username,$userinfo->password_clear,$integrationtype,$relpath,$hidden,$buttons,$override,$cookiedomain,$cookiepath,$cookieexpires";
//die('108');
        $status=JFusionCurl::RemoteLogin($post_url,$formid,$userinfo->username,$userinfo->password_clear,
              $integrationtype,$relpath,$hidden,$buttons,$override,$cookiedomain,$cookiepath,$cookieexpires);
        return $status;
    }

    function destroySession($userinfo, $options){

    }

    function updatePassword($userinfo, &$existinguser, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::updatePassword($userinfo, $existinguser, $status,$db);
    }

    function updateUsername($userinfo, &$existinguser, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::updateUsername($userinfo, $existinguser, $status,$db);
    }

    function updateEmail($userinfo, &$existinguser, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::updateEmail($userinfo, $existinguser, $status,$db);
    }

    function blockUser($userinfo, &$existinguser, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::blockUser($userinfo, $existinguser, $status,$db);
    }

    function unblockUser($userinfo, &$existinguser, &$status){
        //unblock the user
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::unblockUser($userinfo, $existinguser, $status,$db);
    }

    function activateUser($userinfo, &$existinguser, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::activateUser($userinfo, $existinguser, $status,$db);
    }

    function inactivateUser($userinfo, &$existinguser, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::inactivateUser($userinfo, $existinguser, $status,$db);
    }

    function createUser($userinfo, $overwrite, &$status){
        $db =& JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::createUser($userinfo, $overwrite, $status,$db,$this->getJname());
    }
}