<?php
/**
 * @package JFusion_Joomla_Ext
 * @version 1.1.0-001
 * @author JFusion development team -- Henk Wevers
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractplugin.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

/**
 * JFusion plugin class for an external Joomla database
 * @package JFusion_Joomla_Ext
 */
class JFusionPlugin_joomla_ext extends JFusionPlugin
{
    function getJname()
    {
    	return 'joomla_ext';
    }

    function getTablename()
    {
        return JFusionJplugin::getTablename();
    }

    function getRegistrationURL()
    {
        return JFusionJplugin::getRegistrationURL();
    }

    function getLostPasswordURL()
    {
        return JFusionJplugin::getLostPasswordURL();
    }

    function getLostUsernameURL()
    {
        return JFusionJplugin::getLostUsernameURL();
    }

    function getUserList(){
        return JFusionJplugin::getUserList($this->getJname());
    }

    function getUserCount(){
        return JFusionJplugin::getUserCount($this->getJname());
    }

    function getUsergroupList(){
        return JFusionJplugin::getUsergroupList($this->getJname());
    }

    function getDefaultUsergroup(){
        return JFusionJplugin::getDefaultUsergroup($this->getJname());
    }

    function setupFromPath($path)
    {
        return JFusionJplugin::setupFromPath($path);
    }

    function allowRegistration()
    {
        return JFusionJplugin::allowRegistration($this->getJname());
    }


 }