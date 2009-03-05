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
    function allowRegistration()
    {
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

        //for joomla_int check to see if params are set
        if ($jname == 'joomla_int') {
            $params = JFusionFactory::getParams($jname);
            $autoregister = $params->get('autoregister');
            if ($autoregister == 0 || $autoregister ==1) {
                $status['config'] = 1;
                $status['message'] = JText::_('GOOD_CONFIG');
                return $status;
            } else {
                $status['config'] = 0;
                $status['message'] = JText::_('NOT_CONFIGURED');
                return $status;
            }
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
            if (!file_exists($admin_file)) {
                $status['config'] = 0;
                $status['message'] = JText::_('NO_FILES');
                return $status;
            }

            //get the user table name
            $JFusionPlugin = JFusionFactory::getAdmin($jname);
            $tablename = $JFusionPlugin->getTablename();

            // lets check if the table exists, now using the Joomla API
			$table_list = $db->getTableList();
			$table_prefix = $db->getPrefix();
			if (array_search($table_prefix . $tablename,$table_list) == false) {
	            $status['config'] = 0;
    	        $status['message'] = JText::_('NO_TABLE');
        	    return $status;
			} else {
                $status['config'] = 1;
                $status['message'] = JText::_('GOOD_CONFIG');
                return $status;
			}
        }
    }

    function debugConfig($jname)
    {
    	//get registration status
		$JFusionPlugin = JFusionFactory::getAdmin($jname);
		$new_registration  = $JFusionPlugin->allowRegistration();

        //get the data about the JFusion plugins
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query );
        $plugin = $db->loadObject();

		//output a warning to the administrator if the allowRegistration setting is wrong
		if ($new_registration && $plugin->slave == '1'){
   			JError::raiseNotice(0, $jname . ': ' . JText::_('DISABLE_REGISTRATION'));
		}
		if (!$new_registration && $plugin->master == '1'){
   			JError::raiseNotice(0, $jname . ': ' . JText::_('ENABLE_REGISTRATION'));
		}

  		//most dual login problems are due to incorrect cookie domain settings
		//therefore we should check it and output a warning if needed.
		$params = JFusionFactory::getParams($this->getJname());
		$cookie_domain = $params->get('cookie_domain');
		$correct_array = explode('.' , html_entity_decode($_SERVER['SERVER_NAME']));
		if(isset($correct_array[count($correct_array)-2]) && isset($correct_array[count($correct_array)-1])){
			$correct_domain = '.' . $correct_array[count($correct_array)-2] . '.' .$correct_array[count($correct_array) -1];
			if ($correct_domain != $cookie_domain){
	   			JError::raiseNotice(0, $jname . ': ' . JText::_('BEST_COOKIE_DOMAIN') . ' ' . $correct_domain);
			}
		}

		//also check the cookie path as it can intefere with frameless
		$params = JFusionFactory::getParams($this->getJname());
		$cookie_path = $params->get('cookie_path');
		if ($correct_domain != $cookie_domain){
	   		JError::raiseNotice(0, $jname . ': ' . JText::_('BEST_COOKIE_PATH') . ' /');
		}
    }
}
