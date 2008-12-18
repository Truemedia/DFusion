<?php

/**
 * @package JFusion_MyBB
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin Class for MyBB
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_MyBB
 */
class JFusionAdmin_mybb extends JFusionAdmin{

    function getJname()
    {
        return 'mybb';
    }

    function getTablename()
    {
    	return 'users';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate config file path
        if (substr($forumPath, -1) != DS) {
			$forumPath .= DS;
        }
      	$myfile = $forumPath . 'inc'. DS . 'config.php';


        //include config file
        require_once $myfile;

         //Save the parameters into the standard JFusion params format
         $params = array();
      $params['database_type'] = $config['database']['type'];
         $params['database_host'] = $config['database']['hostname'];
         $params['database_user'] = $config['database']['username'];
         $params['database_password'] = $config['database']['password'];
         $params['database_name'] = $config['database']['database'];
         $params['database_prefix'] = $config['database']['table_prefix'];
         $params['source_path'] = $forumPath;

         //find the source url to mybb
         $driver = $params['database_type'];
         $host = $params['database_host'];
         $user = $params['database_user'];
         $password = $params['database_password'];
         $database = $params['database_name'];
         $prefix = $params['database_prefix'];
         $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );
         $bb =& JDatabase::getInstance($options );
         $query = "SELECT value FROM #__settings WHERE name = 'bburl'";
         $bb->setQuery($query);
         $bb_url = $bb->loadResult();
         if (substr($bb_url, -1) != DS) $bb_url .= DS;
         $params['source_url'] = $bb_url;

         $query = "SELECT value FROM #__settings WHERE name='cookiedomain'";
         $bb->setQuery($query);
         $cookiedomain = $bb->loadResult();
         $params['cookie_domain'] =  $cookiedomain;

         $query = "SELECT value FROM #__settings WHERE name='cookiepath'";
         $bb->setQuery($query);
         $cookiepath = $bb->loadResult();
         $params['cookie_path'] =  $cookiepath;

         return $params;
   }


    function getUserList()
    {
    	//getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT username, email from #__users';
    	$db->setQuery( $query );
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
    	$query = 'SELECT gid as id, title as name FROM #__usergroups';
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
    	$query = 'SELECT title from #__usergroups WHERE gid = ' . $usergroup_id;
    	$db->setQuery($query );
    	return $db->loadResult();
    }

    function allowRegistration()
    {
    	$db = JFusionFactory::getDatabase($this->getJname());
    	$query = "SELECT value FROM #__settings  WHERE name ='disableregs'";
    	$db->setQuery( $query );
    	$disableregs = $db->loadResult();

		if ($disableregs == '0') {
            $result = true;
            return $result;
		} else {
            $result = false;
            return $result;
		}
    }
}