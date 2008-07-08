<?php
/**
 * @package JFusion_Joomla_Ext
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
		return 'users';
    }

	function setupFromPath($forumPath)
    {
      //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $configfile = $forumPath . 'configuration.php';
        } else {
            $configfile = $forumPath . DS. 'configuration.php';
        }

        if (($file_handle = @fopen($configfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
			return false;
        } else {
            //parse the file line by line to get only the config variables
            //we can not directly include the config file as JConfig is already defined
            $file_handle = fopen($configfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$')) {
                    //extract the name and value, it was coded to avoid the use of eval() function
                    $vars = split ("'", $line);
                    $names = split ('var', $vars[0] );
                    $name = trim($names[1], ' $=');
                    $value = trim($vars[1], ' $=');
                    $config[$name] = $value;
                }
            }
            fclose($file_handle);
            //Save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['host'];
            $params['database_name'] = $config['db'];
            $params['database_user'] = $config['user'];
            $params['database_password'] = $config['password'];
            $params['database_prefix'] = $config['dbprefix'];
            $params['database_type'] = $config['dbtype'];

			//source path removed, as joomla no longer uses the $live_site parameter

            $params['source_path'] = $forumPath;
            return $params;
        }

	}

    function getRegistrationURL()
    {
       return 'index.php?option=com_user&amp;task=register';
    }

    function getLostPasswordURL()
    {
       return 'index.php?option=com_user&amp;view=reset';
    }

    function getLostUsernameURL()
    {
       return 'index.php?option=com_user&amp;view=remind';
    }

    function getUserList()
    {
        //getting the connection to the db
		$db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username, email from #__users';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;

    }

    function getUserCount()
    {
        //getting the connection to the db
		$db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__users';
        $db->setQuery($query );

        //getting the results
        return $db->loadResult();
    }

    function getUsergroupList()
    {
    	//getting the connection to the db
		$db = JFusionFactory::getDatabase($this->getJname());
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
		$db = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT name from #__core_acl_aro_groups WHERE id = ' . $usergroup_id;
    	$db->setQuery($query );
    	return $db->loadResult();
    }

    function allowRegistration(){
    	$db = JFusionFactory::getDatabase($this->getJname());
    	$query = "SELECT params FROM #__components  WHERE option = 'com_users' AND name = 'User Manager'";
    	$db->setQuery( $query );
    	$params = $db->loadResult();

    	$parametersInstance = new JParameter($params, '' );

		if ($parametersInstance->get(allowUserRegistration)) {
			return true;
		} else {
			return false;
		}
    }
 }
