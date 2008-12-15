<?php
/**
* @package JFusion_Joomla_Int
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/


defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');
/**
* JFusion plugin class for the internal Joomla database
* @package JFusion_Joomla_Int
*/
class JFusionAdmin_joomla_int extends JFusionAdmin
{
    function getJname()
    {
        return 'joomla_int';
    }

    function getTablename()
    {
        return JFusionJplugin::getTablename();
    }

    function getUserList()
    {
        return JFusionJplugin::getUserList($this->getJname());
    }

    function getUserCount()
    {
        return JFusionJplugin::getUserCount($this->getJname());
    }

    function getUsergroupList()
    {
        return JFusionJplugin::getUsergroupList($this->getJname());
    }

    function getDefaultUsergroup()
    {
        return JFusionJplugin::getDefaultUsergroup($this->getJname());
    }

    function allowRegistration()
    {
        return JFusionJplugin::allowRegistration($this->getJname());
    }
 }