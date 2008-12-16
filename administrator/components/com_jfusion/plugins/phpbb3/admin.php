<?php

/**
* @package JFusion_phpBB3
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');

/**
* JFusion plugin class for phpBB3
* @package JFusion_phpBB3
*/
class JFusionAdmin_phpbb3 extends JFusionAdmin{

    function getJname()
    {
        return 'phpbb3';
    }

    function getTablename()
    {
        return 'users';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'config.php';
        } else {
            $myfile = $forumPath . DS. 'config.php';
        }

        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
            $result = false;
            return $result;
        } else {
            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$') === 0) {
                    //extract the name and value, it was coded to avoid the use of eval() function
                    $vars = split("'", $line);
                    $name = trim($vars[0], ' $=');
                    $value = trim($vars[1], ' $=');
                    $config[$name] = $value;
                }
            }
            fclose($file_handle);

            //save the parameters into array
            $params = array();
            $params['database_host'] = $config['dbhost'];
            $params['database_name'] = $config['dbname'];
            $params['database_user'] = $config['dbuser'];
            $params['database_password'] = $config['dbpasswd'];
            $params['database_prefix'] = $config['table_prefix'];
            $params['database_type'] = $config['dbms'];

            //create a connection to the database
            $options = array('driver' => $config['dbms'], 'host' => $config['dbhost'], 'user' => $config['dbuser'], 'password' => $config['dbpasswd'], 'database' => $config['dbname'], 'prefix' => $config['table_prefix'] );

            //Get configuration settings stored in the database
            $vdb =& JDatabase::getInstance($options);
            $query = "SELECT config_name, config_value FROM #__config WHERE config_name IN ('script_path', 'cookie_path', 'server_name', 'cookie_domain', 'cookie_name', 'allow_autologin');";
            if (JError::isError($vdb) || !$vdb ) {
                JError::raiseWarning(0, JText::_('NO_DATABASE'));
	            $result = false;
    	        return $result;
            } else {
                $vdb->setQuery($query);
                $rows = $vdb->loadObjectList();
                foreach($rows as $row ) {
                    $config[$row->config_name] = $row->config_value;
                }
                //store the new found parameters
                $params['cookie_path'] =  $config['cookie_path'];
                $params['cookie_domain'] =  $config['cookie_domain'];
                $params['cookie_prefix'] =  $config['cookie_name'];
                $params['allow_autologin'] =  $config['allow_autologin'];
                $params['source_path'] = $forumPath;
            }

            //check for trailing slash
            if (substr($config['server_name'], -1) == '/' && substr($config['script_path'], 0, 1) == '/') {
                //too many slashes, we need to remove one
                $params['source_url'] = $config['server_name'] . substr($config['script_path'],1);
            } else if (substr($config['server_name'], -1) == '/' || substr($config['script_path'], 0, 1) == '/') {
                //the correct number of slashes
                $params['source_url'] = $config['server_name'] . $config['script_path'];
            } else {
                //no slashes found, we need to add one
                $params['source_url'] = $config['server_name'] . '/' . $config['script_path'] ;
            }

            //return the parameters so it can be saved permanently
            return $params;
        }
    }


    function getUserList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username_clean as username, user_email as email, user_id as userid from #__users WHERE user_email NOT LIKE \'\' and user_email IS NOT NULL';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__users WHERE user_email NOT LIKE \'\' and user_email IS NOT NULL ';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT group_id as id, group_name as name from #__groups;';
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
        $query = 'SELECT group_name from #__groups WHERE group_id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT config_value FROM #__config WHERE config_name = 'require_activation'";
        $db->setQuery($query );
        //getting the results

        $new_registration = $db->loadResult();

        if ($new_registration == 3) {
            $result = false;
            return $result;
        } else {
            $result = true;
            return $result;
        }
    }
}

