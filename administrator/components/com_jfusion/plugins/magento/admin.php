<?php

/**
* @package JFusion_Magento
* @author Henk Wevers
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin class for Magento 1.1
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_Magento
 */
class JFusionAdmin_magento extends JFusionAdmin{

    function getJname(){
        return 'magento';
    }

    function getTablename(){
        return 'customer_entity';
    }

    function setupFromPath($forumPath){
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) != DS) {
            $forumpath = $forumPath.DS;
        }
        $xmlfile = $forumpath.'app'.DS.'etc'.DS.'local.xml';
        if (file_exists($xmlfile)) {
            if (!$xml = simplexml_load_file($xmlfile)) {
                unset($xml);
                JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). " $xmlfile " . JText::_('WIZARD_MANUAL'));
	            $result = false;
    	        return $result;
            }
            //save the parameters into array
            $params = array();
            $params['database_host']     = (string)$xml->global->resources->default_setup->connection->host;
            $params['database_name']     = (string)$xml->global->resources->default_setup->connection->dbname;
            $params['database_user']     = (string)$xml->global->resources->default_setup->connection->username;
            $params['database_password'] = (string)$xml->global->resources->default_setup->connection->password;
            $params['database_prefix']   = (string)$xml->global->resources->db->table_prefix;
            $params['database_type']     = "mysql";
            $params['source_path']       = $forumpath;
            return $params;
        } else {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). " $xmlfile " . JText::_('WIZARD_MANUAL'));
            $result = false;
            return $result;
        }
    }

    function getUserList(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT email as username, email from #__customer_entity';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__customer_entity';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList(){
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT customer_group_id as id, customer_group_code as name from #__customer_group;';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getDefaultUsergroup(){
		 return JText::_('USING_PLUGIN_DEFAULT');
    }

}