<?php

/**
* @package JFusion_vBulletin
* @version 1.1.0-001
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
* JFusion plugin class for vBulletin 3.6.8
* @package JFusion_vBulletin
*/
class JFusionAdmin_vbulletin extends JFusionAdmin{

    function getJname()
    {
        return 'vbulletin';
    }

    function getTablename()
    {
        return 'user';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'includes'. DS .'config.php';
        } else {
            $myfile = $forumPath . DS . 'includes'. DS . 'config.php';
        }

        //try to open the file
        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));

            //get the default parameters object
            $params = AbstractForum::getSettings($this->getJname());
            return $params;


        } else {

            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$config') === 0) {
                    $vars = split("'", $line);
                    $name1 = trim($vars[1], ' $=');
                    $name2 = trim($vars[3], ' $=');
                    $value = trim($vars[5], ' $=');
                    $config[$name1][$name2] = $value;

                } else if (strpos($line, 'Licence Number')) {
                    //extract the vbulletin license code while we are at it
                    $vb_lic = substr($line, strpos($line, 'Licence Number') + 14, strlen($line));
                }
            }
            fclose($file_handle);

            //save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['dbhost'];

            $params['database_host'] = $config['MasterServer']['servername'];
            $params['database_type'] = $config['Database']['dbtype'];
            $params['database_name'] = $config['Database']['dbname'];
            $params['database_user'] = $config['MasterServer']['username'];
            $params['database_password'] = $config['MasterServer']['password'];
            $params['database_prefix'] = $config['Database']['tableprefix'];
         	$params['source_path'] = $forumPath;

            //find the path to vbulletin, for this we need a database connection
            $host = $config['MasterServer']['servername'];
            $user = $config['MasterServer']['username'];
            $password = $config['MasterServer']['password'];
            $database = $config['Database']['dbname'];
            $prefix = $config['Database']['tableprefix'];
            $driver = 'mysql';
            $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );
            $vdb =& JDatabase::getInstance($options );

            //Find the path to vbulletin
            $query = "SELECT value FROM #__setting WHERE varname = 'bburl'";
            $vdb->setQuery($query);
            $vb_url = $vdb-> loadResult();
            $params['source_url'] = $vb_url;

            $params['source_license'] = trim($vb_lic);

            return $params;
        }
    }

    function getRegistrationURL()
    {
        return 'register.php';
    }

    function getLostPasswordURL()
    {
        return 'login.php?do=lostpw';
    }

    function getLostUsernameURL()
    {
        return 'login.php?do=lostpw';
    }

    function getUserList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username, email from #__user';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__user';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT usergroupid as id, title as name from #__usergroup';
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
        $query = 'SELECT title from #__usergroup WHERE usergroupid = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__setting WHERE varname = 'allowregistration'";
        $db->setQuery($query );
        //getting the results

        $new_registration = $db->loadResult();

        if ($new_registration == 1) {
            return true;
        } else {
            return false;
        }
    }

}

