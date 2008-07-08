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

/**
* JFusion plugin class for the internal Joomla database
* @package JFusion_Joomla_Int
*/
class JFusionPlugin_joomla_int extends JFusionPlugin
{
    function getJname()
    {
        return 'joomla_int';
    }

    function getTablename()
    {
        return 'users';
    }

    function getRegistrationURL()
    {
        return 'index.php?option=com_user&task=register';
    }

    function getLostPasswordURL()
    {
        return 'index.php?option=com_user&view=reset';
    }

    function getLostUsernameURL()
    {
        return 'index.php?option=com_user&view=remind';
    }


    function getUserList()
    {
        //getting the connection to the db
        $db = & JFactory::getDBO();
        $query = 'SELECT username, email from #__users';
        $db->setQuery($query );
        $userlist = $db->loadObjectList();

        return $userlist;

    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = & JFactory::getDBO();
        $query = 'SELECT count(*) from #__users';
        $db->setQuery($query );

        //getting the results
        return $db->loadResult();
    }

    function getUsergroupList()
    {
        //getting the connection to the db
        $db = & JFactory::getDBO();
        $query = 'SELECT id, name FROM #__core_acl_aro_groups';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getDefaultUsergroup()
    {
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup_id = $params->get('usergroup');

        //we want to output the usergroup name
        $db = & JFactory::getDBO();
        $query = 'SELECT name from #__core_acl_aro_groups WHERE id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }



    function allowRegistration()
    {

        $row =& JTable::getInstance('component' );
        $row->loadByOption('com_users' );
        $parameters = JArrayHelper::fromObject($row );
        $params = $parameters['params'];
        $file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_user'.DS.'com_user.xml';
        $parametersInstance = new JParameter($params, $file );

        if ($parametersInstance->get(allowUserRegistration)) {
            return true;
        } else {
            return false;
        }
    }



}
