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
$view = $params->get('link_mode', 'direct');
$view2 = $params->get('link_mode2', 'direct');
$itemid = $params->get('itemid');
$itemid2 = $params->get('itemid2');

//check if the JFusion component is installed
$model_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php';
$factory_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php';
if (file_exists($model_file) && file_exists($factory_file)) {

    /**
* require the JFusion libraries
*/
    require_once($model_file);
    require_once($factory_file);

    //Get the forum integration object
    $jname = JFusionFunction::getMaster();
    if ($jname->status == 3 ) {
        $MasterPlugin = JFusionFactory::getPublic($jname->name);
        $url_lostpass = JFusionFunction::createURL($MasterPlugin->getLostPasswordURL(), $jname->name, $view, $itemid);
        $url_lostuser = JFusionFunction::createURL($MasterPlugin->getLostUsernameURL(), $jname->name, $view, $itemid);
        $url_register = JFusionFunction::createURL($MasterPlugin->getRegistrationURL(), $jname->name, $view, $itemid);

		//now find out from which plugin the avatars need to be displayed
		$PluginName = $params->get('JFusionPlugin');
		if ($PluginName != 'joomla_int'){
			$JFusionPlugin = JFusionFactory::getForum($PluginName);
        	$userlookup = JFusionFunction::lookupUser($PluginName, $user->get('id'));

			//check to see if we found a user
	        if ($userlookup) {
	            if ($params->get('avatar')) {
    	            // retrieve avatar
					$avatar = $JFusionPlugin->getAvatar($userlookup->userid);
            	}

            	if ($params->get('pmcount')) {
                	$pmcount = $JFusionPlugin->getPrivateMessageCounts($userlookup->userid);
                	$url_pm = JFusionfunction::createURL($JFusionPlugin->getPrivateMessageURL(), $PluginName, $view2,  $itemid2);
            	}

            	if ($params->get('viewnewmessages')) {
                	$url_viewnewmessages = JFusionfunction::createURL($JFusionPlugin->getViewNewMessagesURL(), $PluginName, $view2, $itemid2);
            	}
    			//output the login module
    		require(JModuleHelper::getLayoutPath('mod_jfusion_login', 'jfusion'));
	        } else {
    			require(JModuleHelper::getLayoutPath('mod_jfusion_login'));
	        }

        } else {
	        //use the Joomla default urls
    	    $url_lostpass = JRoute::_('index.php?option=com_user&amp;view=reset' );
        	$url_lostuser = JRoute::_('index.php?option=com_user&amp;view=remind' );
	        $url_register = JRoute::_('index.php?option=com_user&amp;task=register' );
    		require(JModuleHelper::getLayoutPath('mod_jfusion_login'));
        }

    } else {
        //use the Joomla default urls
        $url_lostpass = JRoute::_('index.php?option=com_user&amp;view=reset' );
        $url_lostuser = JRoute::_('index.php?option=com_user&amp;view=remind' );
        $url_register = JRoute::_('index.php?option=com_user&amp;task=register' );
    	require(JModuleHelper::getLayoutPath('mod_jfusion_login'));
    }

} else {
    //use the Joomla default urls
    $url_lostpass = JRoute::_('index.php?option=com_user&amp;view=reset' );
    $url_lostuser = JRoute::_('index.php?option=com_user&amp;view=remind' );
    $url_register = JRoute::_('index.php?option=com_user&amp;task=register' );
    require(JModuleHelper::getLayoutPath('mod_jfusion_login'));
}
