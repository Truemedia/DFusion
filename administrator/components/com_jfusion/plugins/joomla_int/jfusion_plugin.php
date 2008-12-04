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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractplugin.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');
/**
* JFusion plugin class for the internal Joomla database
* @package JFusion_Joomla_Int
*/
class JFusionPlugin_joomla_int extends JFusionPlugin
{
    function getJname(){
        return 'joomla_int';
    }

    function getTablename(){
        return JFusionJplugin::getTablename();
    }

    function getRegistrationURL(){
        return JFusionJplugin::getRegistrationURL();
    }

    function getLostPasswordURL(){
        return JFusionJplugin::getLostPasswordURL();
    }

    function getLostUsernameURL(){
             return JFusionJplugin::getLostUsernameURL();
    }


    function getUserList(){
        $db = & JFactory::getDBO();
        return JFusionJplugin::getUserList($db);
    }

    function getUserCount(){
        $db = & JFactory::getDBO();
        return JFusionJplugin::getUserCount($db);
    }

    function getUsergroupList(){
        $db = & JFactory::getDBO();
        return JFusionJplugin::getUsergroupList($db);
    }

    function getDefaultUsergroup(){
        //we want to output the usergroup name
        $db = & JFactory::getDBO();
        return JFusionJplugin::getDefaultUsergroup($db,$this->getJname());
    }


    function allowRegistration()
    {

        $row =& JTable::getInstance('component' );
        $row->loadByOption('com_users' );
        $parameters = JArrayHelper::fromObject($row );
        $params = $parameters['params'];
        $file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_user'.DS.'com_user.xml';
        $parametersInstance = new JParameter($params, $file );

        if ($parametersInstance->get('allowUserRegistration')) {
            return true;
        } else {
            return false;
        }
    }

}