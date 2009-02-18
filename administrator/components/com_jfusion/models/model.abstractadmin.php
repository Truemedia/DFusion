<?php

/**
 * @package JFusion
 * @subpackage Models
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Abstract interface for all JFusion functions that are accessed through the Joomla administrator interface
* @package JFusion
*/
class JFusionAdmin{

    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
    }

     /**
     * Returns the a list of users of the integrated software
     * @return array List of usernames/emails
     */
    function getUserList()
    {
        return 0;
    }

     /**
     * Returns the the number of users in the integrated software. Allows for fast retrieval total number of users for the usersync
     * @return integer Number of registered users
     */
    function getUserCount()
    {
        return 0;
    }

     /**
     * Returns the a list of usersgroups of the integrated software
     * @return array List of usergroups
     */
    function getUsergroupList()
    {
        return 0;
    }

     /**
     * Function used to display the default usergroup in the JFusion plugin overview
     * @return string Default usergroup name
     */
    function getDefaultUsergroup()
    {
        return '';
    }

     /**
     * Checks if the software allows new users to register
     * @return boolean True if new user registration is allowed, otherwise returns false
     */
    function allowRegistration(){
       $result = true;
       return $result;
    }

    /**
     * returns the name of user table of integrated software
     * @return string table name
     */
    function getTablename()
    {
        return '';
    }

     /**
     * Function finds config file of integrated software and automatically configures the JFusion plugin
     * @param string $softwarePath path to root of integrated software
     * @return object JParam JParam objects with ne newly found configuration
     */
    function setupFromPath($softwarePath)
    {
        return 0;
    }

    function checkConfig($jname)
    {

		//internal Joomla plugin is always properly configured
		if ($jname == 'joomla_int') {
            $status['config'] = 1;
			$status['message'] = JText::_('GOOD_CONFIG');
            return $status;
        }

        $db = JFusionFactory::getDatabase($jname);
        $jdb =& JFactory::getDBO();
        $status = array();

        if (JError::isError($db) || !$db || !method_exists($jdb,'setQuery')) {
            $status['config'] = 0;
			$status['message'] = JText::_('NO_DATABASE');
            return $status;
        } else {
        	//added check for missing files of copied plugins after upgrade
			$admin_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$jname.DS.'admin.php';
			if (!file_exists($admin_file)){
	            $status['config'] = 0;
				$status['message'] = JText::_('NO_FILES');
	            return $status;
			}

            //get the user table name
            $JFusionPlugin = JFusionFactory::getAdmin($jname);
            $tablename = $JFusionPlugin->getTablename();

           // lets check if the table exists (HJW: if an integration has a seperate table for  backoffice users
           // we cannot test if we can read data from the usertable because it can be empty. So here is a function that
           // works even if the table is empty)
           $conf =& JFactory::getConfig();
           $params = JFusionFactory::getParams($jname);
           $database = $params->get('database_name');
           $prefix = $params->get('database_prefix');
           $query = "SHOW TABLES FROM $database";
           $db->setQuery($query);
           $tablesresult = $db->loadObjectlist();
           foreach ($tablesresult as $table) {
              foreach ($table as $row) {
                 if ($row == $prefix.$tablename) {
                    $status['config'] = 1;
                    $status['message'] = JText::_('GOOD_CONFIG');
                    return $status;
                 }
              }
           }
           $status['config'] = 0;
           $status['message'] = JText::_('NO_TABLE');
           return $status;
        }
    }
}