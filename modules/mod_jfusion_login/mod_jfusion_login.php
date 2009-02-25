<?php
/**
* @package JFusion
* @subpackage Modules
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
* require the module helper
*/
require_once(dirname(__FILE__).DS.'helper.php');
$user =& JFactory::getUser();
$params->def('greeting', 1);
$type 	= modjfusionLoginHelper::getType();
$return	= modjfusionLoginHelper::getReturnURL($params, $type);

//check if the JFusion component is installed
$model_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php';
$factory_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php';
if (file_exists($model_file) && file_exists($factory_file)) {

    /**
* require the JFusion libraries
*/
    require_once($model_file);
    require_once($factory_file);

    //get any custom URLs
    $lostpassword_url = $params->get('lostpassword_url');
    $lostusername_url = $params->get('lostusername_url');
    $register_url = $params->get('register_url');

    //get the itemid and jname to get any missing urls
    $link_itemid = $params->get('link_itemid');
    if (is_numeric($link_itemid)) {
        $menu = &JSite::getMenu();
        $menu_param =& $menu->getParams($link_itemid);
        $plugin_param = unserialize(base64_decode($menu_param->get('JFusionPluginParam')));
        $link_jname = $plugin_param['jfusionplugin'];
    } else {
        $link_jname = $link_itemid;
    }

    //get the default URLs if no custom URL specified
    $LinkPlugin = JFusionFactory::getPublic($link_jname);
    if (empty($lostpassword_url) && method_exists($LinkPlugin,'getLostPasswordURL')) {
        $lostpassword_url = JFusionFunction::routeURL($LinkPlugin->getLostPasswordURL(), $link_itemid);
    }
    if (empty($lostusername_url)&& method_exists($LinkPlugin,'getLostUsernameURL')) {
        $lostusername_url = JFusionFunction::routeURL($LinkPlugin->getLostUsernameURL(), $link_itemid);
    }
    if (empty($register_url) && method_exists($LinkPlugin,'getRegistrationURL')) {
        $register_url = JFusionFunction::routeURL($LinkPlugin->getRegistrationURL(), $link_itemid);
    }


    //now find out from which plugin the avatars need to be displayed
    $PluginName = $params->get('JFusionPlugin');
    if ($PluginName != 'joomla_int') {
        $JFusionPlugin = JFusionFactory::getForum($PluginName);
        $userlookup = JFusionFunction::lookupUser($PluginName, $user->get('id'));

        //check to see if we found a user
        if ($userlookup) {
            if ($params->get('avatar')) {
                // retrieve avatar
                $avatarSrc = $params->get('avatar_software');
                if ($avatarSrc=='' || $avatarSrc=='jfusion') {
                    $avatar = $JFusionPlugin->getAvatar($userlookup->userid);
                } else {
                    $avatar = JFusionFunction::getAltAvatar($avatarSrc, $user->get('id'));
                }
               
                if(empty($avatar)) {
					$avatar = JURI::base()."administrator".DS."components".DS."com_jfusion".DS."images".DS."noavatar.png";
				}	
            }

            if ($params->get('pmcount') && $PluginName!='joomla_ext') {
                $pmcount = $JFusionPlugin->getPrivateMessageCounts($userlookup->userid);
                $url_pm = JFusionFunction::routeURL($JFusionPlugin->getPrivateMessageURL(),  $link_itemid);
            } else {
            	$pmcount = false;
            }
            
            if ($params->get('viewnewmessages') && $PluginName!='joomla_ext') {
                $url_viewnewmessages = JFusionFunction::routeURL($JFusionPlugin->getViewNewMessagesURL(), $link_itemid);
            } else {
            	$url_viewnewmessages = false;
            }
        }
    } else {
        //show the avatar if it is not set to JFusion
        if ($params->get('avatar')) {
            //retrieve avatar
            $avatarSrc = $params->get('avatar_software');
            if ($avatarSrc!='jfusion') {
                $avatar = JFusionFunction::getAltAvatar($avatarSrc, $user->get('id'));
            } else {
                $avatar = JURI::base()."administrator".DS."components".DS."com_jfusion".DS."images".DS."noavatar.png";;
            }
        } else {
            $avatar = false;
        }
        
        $pmcount = $url_viewnewmessages = false;
    }

}
//use the Joomla default if JFusion specified none
if (empty($lostpassword_url)) {
    $lostpassword_url = JRoute::_('index.php?option=com_user&amp;view=reset' );
}
if (empty($lostusername_url)) {
    $lostusername_url = JRoute::_('index.php?option=com_user&amp;view=remind' );
}
if (empty($register_url)) {
    $register_url = JRoute::_('index.php?option=com_user&amp;task=register' );
}

//render the login module
require(JModuleHelper::getLayoutPath('mod_jfusion_login'));

