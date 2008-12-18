<?php
/**
 * @package JFusion_Joomla_Ext
 * @author JFusion development team -- Henk Wevers
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the common Joomla JFusion plugin functions
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

/**
 * JFusion Admin Class for an external Joomla database.
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_Joomla_Ext
 */
class JFusionAdmin_joomla_ext extends JFusionAdmin
{
    function getJname()
    {
    	return 'joomla_ext';
    }

    function getTablename()
    {
        return JFusionJplugin::getTablename();
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