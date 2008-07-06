<?php
/**
* @package JFusion
* @subpackage Modules
* @version 1.0.7
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
if (file_exists($model_file) && file_exists($model_file)) {

    /**
* require the JFusion libraries
*/
    require_once($model_file);
    require_once($factory_file);

    //Get the forum integration object
    $jname = JFusionFunction::getMaster();
    //now check to see if the plugin is configured
    $jdb =& JFactory::getDBO();
    $query = 'SELECT status from #__jfusion WHERE name = ' . $jdb->quote($jname->name);
    $jdb->setQuery($query );
    $plugin_status =$jdb->loadResult;


    if ($jname && $plugin_status == 3) {


        $JFusionPlugin = JFusionFactory::getPlugin($jname);
        $allow_registration = $JFusionPlugin->allowRegistration();
        $url_lostpass = $JFusionPlugin->getLostPasswordURL();
        $url_lostuser = $JFusionPlugin->getLostUsernameURL();
        $url_register = $JFusionPlugin->getRegistrationURL();

        $userlookup = JFusionFunction::lookupUser($jname, $user->get('id'));

        if ($userlookup) {
            $userid = $userlookup->userid;

            if ($params->get('avatar')) {
                // retrieve avatar
                $avatar = $forum->getAvatar($userid);
            }
            if ($params->get('pmcount')) {
                $pmcount = $forum->getPrivateMessageCounts($userid);
                $url_pm = $forum->getPrivateMessageURL();
            }
            if ($params->get('viewnewmessages')) {
                $url_viewnewmessages = $forum->getViewNewMessagesURL();
            }
        }
    } else {
        //use the Joomla default urls
        $url_lostpass = JRoute::_('index.php?option=com_user&view=reset' );
        $url_lostuser = JRoute::_('index.php?option=com_user&view=remind' );
        $url_register = JRoute::_('index.php?option=com_user&task=register' );
    }

    require(JModuleHelper::getLayoutPath('mod_jfusionlogin'));

} else {
    //use the Joomla default urls
    $url_lostpass = JRoute::_('index.php?option=com_user&view=reset' );
    $url_lostuser = JRoute::_('index.php?option=com_user&view=remind' );
    $url_register = JRoute::_('index.php?option=com_user&task=register' );
    require(JModuleHelper::getLayoutPath('mod_jfusionlogin'));

}
