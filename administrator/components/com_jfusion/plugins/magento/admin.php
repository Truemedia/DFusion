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
        return 'admin_user';
    }

    function setupFromPath($forumPath){
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) != DS) {
            $forumpath = $forumPath.DS;
        }
        $xmlfile = $forumpath.'app'.DS.'etc'.DS.'local.xml';
        if (file_exists($xmlfile)) {
   			$xml = JFactory::getXMLParser('Simple');
            if (!$xml->loadFile($xmlfile)) {
                unset($xml);
                JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). " $xmlfile " . JText::_('WIZARD_MANUAL'));
	            $result = false;
    	        return $result;
            }

            //save the parameters into array
            $params = array();
            $params['database_host']     = (string)$xml->document->global[0]->resources[0]->default_setup[0]->connection[0]->host[0]->data();
            $params['database_name']     = (string)$xml->document->global[0]->resources[0]->default_setup[0]->connection[0]->dbname[0]->data();
            $params['database_user']     = (string)$xml->document->global[0]->resources[0]->default_setup[0]->connection[0]->username[0]->data();
            $params['database_password'] = (string)$xml->document->global[0]->resources[0]->default_setup[0]->connection[0]->password[0]->data();
            $params['database_prefix']   = (string)$xml->document->global[0]->resources[0]->db[0]->table_prefix[0]->data();
            $params['database_type']     = "mysql";
            $params['source_path']       = $forumpath;
            unset($xml);
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