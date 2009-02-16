<?php

/**
* @package JFusion_SMF
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin Class for SMF 1.1.x
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_SMF
 */
 require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');

class JFusionAdmin_smf extends JFusionAdmin{

    function getJname()
    {
        return 'smf';
    }

    function getTablename()
    {
        return 'members';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'Settings.php';
        } else {
            $myfile = $forumPath . DS. 'Settings.php';
        }

        //try to open the file
        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));

            //get the default parameters object
            $params = JFusionFactory::getParams($this->getJname());
            return $params;

        } else {
            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$') === 0) {
                    $vars = split("'", $line);
                     if(isset($vars[1]) && isset($vars[0])){
	                    $name = trim($vars[0], ' $=');
    	                $value = trim($vars[1], ' $=');
        	            $config[$name] = $value;
                    }
                }
            }
            fclose($file_handle);
            //Save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['db_server'];
            $params['database_type'] = 'mysql';
            $params['database_name'] = $config['db_name'];
            $params['database_user'] = $config['db_user'];
            $params['database_password'] = $config['db_passwd'];
            $params['database_prefix'] = $config['db_prefix'];
            $params['source_url'] = $config['boardurl'];
            $params['cookie_name'] = $config['cookiename'];
            $params['source_path'] = $forumPath;

            return $params;
        }
    }

    function getUserList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT memberName as username, emailAddress as email from #__members';
        $db->setQuery($query );
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__members';
        $db->setQuery($query );

        //getting the results
        return $db->loadResult();
    }

    function getUsergroupList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT ID_GROUP as id, groupName as name FROM #__membergroups WHERE minPosts = -1';
        $db->setQuery($query );
 		$usergrouplist = $db->loadObjectList();

 		//append the default usergroup
 		$default_group = new stdClass;
        $default_group->id = 0;
        $default_group->name = 'Default User';
        $usergrouplist[] = $default_group;


        return $usergrouplist;
    }

    function getDefaultUsergroup()
    {
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup_id = $params->get('usergroup');

        if ($usergroup_id==0) return "Default Usergroup";

        //we want to output the usergroup name
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT groupName FROM #__membergroups WHERE ID_GROUP = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function getUserpostgroupList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT ID_GROUP as id, groupName as name FROM #__membergroups WHERE minPosts != -1';
        $db->setQuery($query);
	    return $db->loadObjectList();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__settings WHERE variable ='registration_method';";
        $db->setQuery($query );
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

