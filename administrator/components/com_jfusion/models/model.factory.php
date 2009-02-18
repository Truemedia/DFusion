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
* Singleton static only class that creates instances for each specific JFusion plugin.
* @package JFusion
*/

class JFusionFactory{

    /**
    * Gets an Fusion front object
    * @param string $jname name of the JFusion plugin used
    * @return object JFusionPlugin JFusionPlugin object for the JFusion plugin
    */
    function &getPublic($jname)
    {
        static $public_instances;
        if (!isset($public_instances )) {
            $public_instances = array();
        }

        //only create a new plugin instance if it has not been created before
        if (!isset($public_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'public.php';
		    if (file_exists($filename)){
				 //load the Abstract Public Class
				require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');
				//load the plugin class itself
                require_once($filename);
                $class = "JFusionPublic_" . $jname;
                $public_instances[$jname]= new $class;
                return $public_instances[$jname];
      		} else {
        		$result = false;            // prevent php warning
       			return $result;
      		}
        } else {
            return $public_instances[$jname];
        }
    }

    /**
    * Gets an Fusion front object
    * @param string $jname name of the JFusion plugin used
    * @return object JFusionPlugin JFusionPlugin object for the JFusion plugin
    */
    function &getAdmin($jname)
    {
        static $admin_instances;
        if (!isset($admin_instances )) {
            $admin_instances = array();
        }

        //only create a new plugin instance if it has not been created before
        if (!isset($admin_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'admin.php';
		    if (file_exists($filename)){
				 //load the Abstract Admin Class
				require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');
				//load the plugin class itself
				require_once($filename);
                $class = "JFusionAdmin_" . $jname;
                $admin_instances[$jname]= new $class;
                return $admin_instances[$jname];
      		} else {
        		$result = false;            // prevent php warning
       			return $result;
      		}
        } else {
            return $admin_instances[$jname];
        }
    }

    /**
    * Gets an Authentication Class for the JFusion Plugin
    * @param string $jname name of the JFusion plugin used
    * @return object JFusionAuth JFusion Authentication class for the JFusion plugin
    */
    function &getAuth($jname)
    {
        static $auth_instances;
        if (!isset($auth_instances )) {
            $auth_instances = array();
        }

        //only create a new authentication instance if it has not been created before
        if (!isset($auth_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'auth.php';
		    if (file_exists($filename)){
				 //load the Abstract Auth Class
				require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractauth.php');
				//load the plugin class itself
            	require_once($filename);
                $class = "JFusionAuth_" . $jname;
                $auth_instances[$jname]= new $class;
                return $auth_instances[$jname];
      		} else {
        		$result = false;            // prevent php warning
       			return $result;
       		}
        } else {
            return $auth_instances[$jname];
        }
    }

    /**
    * Gets an User Class for the JFusion Plugin
    * @param string $jname name of the JFusion plugin used
    * @return object JFusionUser JFusion User class for the JFusion plugin
    */
    function &getUser($jname)
    {
        static $user_instances;
        if (!isset($user_instances )) {
            $user_instances = array();
        }

        //only create a new user instance if it has not been created before
        if (!isset($user_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'user.php';
      		if (file_exists($filename)){
				 //load the User Public Class
				require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');
				//load the plugin class itself
                require_once($filename);
                $class = "JFusionUser_" . $jname;
                $user_instances[$jname]= new $class;
                return $user_instances[$jname];
      		} else {
        		$result = false;            // prevent php warning
       			return $result;
      		}
        } else {
            return $user_instances[$jname];
        }
    }

    /**
    * Gets a Forum Class for the JFusion Plugin
    * @param string $jname name of the JFusion plugin used
    * @return object JFusionForum JFusion Thread class for the JFusion plugin
    */
    function &getForum($jname)
    {
        static $forum_instances;
        if (!isset($forum_instances )) {
            $forum_instances = array();
        }

        //only create a new thread instance if it has not been created before
        if (!isset($forum_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'forum.php';
      		if (file_exists($filename)){
				 //load the Abstract Forum Class
				require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractforum.php');
				//load the plugin class itself
                require_once($filename);
                $class = "JFusionForum_" . $jname;
                $forum_instances[$jname]= new $class;
                return $forum_instances[$jname];
      		} else {
        		$result = false;            // prevent php warning
       			return $result;
      		}
        } else {
            return $forum_instances[$jname];
        }
    }

    /**
    * Gets an Database Connection for the JFusion Plugin
    * @param string $jname name of the JFusion plugin used
    * @return object JDatabase Database connection for the JFusion plugin
    */
    function &getDatabase($jname)
    {
        static $database_instances;
        if (!isset($database_instances )) {
            $database_instances = array();
        }

        //only create a new database instance if it has not been created before
        if (!isset($database_instances[$jname] )) {
            $database_instances[$jname]= JFusionFactory::createDatabase($jname);
            return $database_instances[$jname];
        } else {
            return $database_instances[$jname];
        }
    }

    /**
    * Gets an Parameter Object for the JFusion Plugin
    * @param string $jname name of the JFusion plugin used
    * @return object JParam JParam object for the JFusion plugin
    */
    function &getParams($jname, $reset = false)
    {
        static $params_instances;
        if (!isset($params_instances )) {
            $params_instances = array();
        }

        //only create a new database instance if it has not been created before
        if (!isset($params_instances[$jname] ) || $reset) {
            $params_instances[$jname] = JFusionFactory::createParams($jname);
            return $params_instances[$jname];
        } else {
            return $params_instances[$jname];
        }
    }

	function &createParams($jname)
    {
        //get the current parameters from the jfusion table
        $db = & JFactory::getDBO();
        $query = 'SELECT params from #__jfusion WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query );
        $serialized = $db->loadResult();

        //get the parameters from the XML file
        $file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname . DS.'jfusion.xml';
        $parametersInstance = new JParameter('', $file );

        //apply the stored valued
        if ($serialized) {
            $params = unserialize(base64_decode($serialized));

            if (is_array($params)) {
                foreach($params as $key => $value) {
                    $parametersInstance->set($key, $value );
                }
            }
        }

        if (!is_object($parametersInstance)) {
            JError::raiseError(500, JText::_('NO_FORUM_PARAMETERS'));
        }

        return $parametersInstance;
    }

    /**
* Acquires a database connection to the database of the software integrated by JFusion
* @param string $jname name of the JFusion plugin used
* @return object JDatabase
*/
    function &createDatabase($jname)
    {
		//check to see if joomla DB is requested
		if ($jname == 'joomla_int'){
        	$db = & JFactory::getDBO();
        	return $db;
		}

        //get the debug configuration setting
        $conf =& JFactory::getConfig();
        $debug = $conf->getValue('config.debug');

        //make sure the database model is loaded
        jimport('joomla.database.database');
        jimport('joomla.database.table' );

        //get config values
        $conf =& JFactory::getConfig();
        $params = JFusionFactory::getParams($jname);

        //prepare the data for creating a database connection
        $host = $params->get('database_host');
        $user = $params->get('database_user');
        $password = $params->get('database_password');
        $database = $params->get('database_name');
        $prefix = $params->get('database_prefix');
        $driver = $params->get('database_type');
        $debug = $conf->getValue('config.debug');

		//added extra code to prevent error when $driver is incorrect
		if ($driver != 'mysql' && $driver != 'mysqli') {
			//invalid driver
            JError::raiseWarning(0, JText::_('INVALID_DRIVER'));
            $result = false;
            return $result;
		}

        //create an options variable that contains all database connection variables
        $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );

        //create the actual connection
        $jfusion_database =& JDatabase::getInstance($options );
        if (!method_exists($jfusion_database,'Execute')){
            JError::raiseWarning(0, JText::_('NO_DATABASE'));
            $result = false;
            return $result;
        } else {
	        //add support for UTF8
    	    $jfusion_database->Execute('SET names \'utf8\'');
    	    //support debugging
			$jfusion_database->debug($debug);
	        return $jfusion_database;
        }
    }
}