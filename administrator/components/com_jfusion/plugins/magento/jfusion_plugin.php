<?php
/**
 * @package JFusion_Magento
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
 * JFusion plugin class for Magento
 * @package JFusion_Magento
 */
class JFusionPlugin_magento extends JFusionPlugin
{

    function getJname()
    {
		return 'magento';
    }

    function getTablename()
    {
		return 'customer_entity';
    }

	function getRegistrationURL()
	{
		return 'index.php/customer/account/create/magento';
	}

	function getLostPasswordURL()
	{
		return 'index.php/customer/account/forgotpassword/magento';
	}


	function getLostUsernameURL()
	{
		return 'index.php/customer/account/forgotpassword/magento';
	}

  function setupFromPath($forumPath){
    //check for trailing slash and generate file path
    if (substr($forumPath, -1) == DS) {
      $xmlfile = $forumPath . 'app'.DS.'etc'.DS.'local.xml';
	  } else {
      $xmlfile = $forumPath . DS.'app'.DS.'etc'.DS.'local.xml';
    }
    if (file_exists($xmlfile)) {
			if (!$xml = simplexml_load_file($xmlfile)) {
				unset($xml);
				JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $xmlfile " . JText::_('WIZARD_MANUAL'));
                return false;
			}

            //save the parameters into array
            $params = array();
      $params['database_type'] = 'mysql';
	  $params['database_host'] = (string)$xml->global->resources->default_setup->connection->host;
      $params['database_name'] = (string)$xml->global->resources->default_setup->connection->dbname;
      $params['database_user'] = (string)$xml->global->resources->default_setup->connection->username;
      $params['database_password'] = (string)$xml->global->resources->default_setup->connection->password;
      $params['database_prefix'] =  (string)$xml->global->resources->db->table_prefix;
      $params['source_path'] = $forumPath;
      return $params;

	  } else {
      JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $xmlfile " . JText::_('WIZARD_MANUAL'));
	  return false;
		}

	}

    function getUserList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT email as username, email as email from #__customer_entity';
        $db->setQuery($query );
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__customer_entity';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT \'1\' as id, \'customer\' as name';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getDefaultUsergroup()
    {
  		return 'customer';
    }
 }
