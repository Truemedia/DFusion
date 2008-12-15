<?php

/**
* @package JFusion_Moodle
* @version 1.0.8-007
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
 * JFusion plugin class for Moodle 1.8+
 * @package JFusion_Moodle
 */

class JFusionAdmin_moodle extends JFusionAdmin{

    function getJname(){
        return 'moodle';
    }

    function getTablename(){
        return 'user';
    }

    function setupFromPath($forumPath){
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'config.php';
        } else {
            $myfile = $forumPath . DS. 'config.php';
        }
        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
            return false;
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
            $params['database_host']        = $config['CFG->dbhost'];
            $params['database_name']        = $config['CFG->dbname'];
            $params['database_user']        = $config['CFG->dbuser'];
            $params['database_password']    = $config['CFG->dbpass'];
            $params['database_prefix']      = $config['CFG->prefix'];
            $params['database_type']        = $config['CFG->dbtype'];
            $params['passwordsaltmain']    = $config['CFG->passwordsaltmain'];
            for ($i=1; $i<=20; $i++) { //20 alternative salts should be enough, right?
                  $alt = 'passwordsaltalt'.$i;
                  if ($config['CFG->'.$alt]){$params[$alt] = $config['CFG->'.$alt];}
            }
            $params['source_path']        = $forumPath;
            if (substr($config['wwwroot'], -1) == '/') {
              $params['source_url'] = $config['CFG->wwwroot'];
            } else {
                //no slashes found, we need to add one
                $params['source_url'] = $config['CFG->wwwroot'] . '/' ;
            }
            $params['usergroup'] = '7';  #make sure we do not assign roles with more capabilities automatically
            //return the parameters so it can be saved permanently
            return $params;
        }
    }


    function getUserList(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username, email from #__user';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__user';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList(){
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT id, name from #__role;';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getDefaultUsergroup(){
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup_id = $params->get('usergroup');

        //we want to output the usergroup name
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT name from #__role WHERE id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__config WHERE name = 'auth'";
        $db->setQuery($query );
        $auths = $db->loadResult();
        if (empty($auths)) {
            return false;
        } else {
            return true;
        }
    }
}