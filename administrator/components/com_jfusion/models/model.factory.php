<?php

/**
 * @package JFusion
 * @subpackage Models
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


/**
* Singleton static only class that creates instances for each specific JFusion plugin.
* @package JFusion
*/

class JFusionFactory{

    /**
    * Gets an Fusion plugin object
    * @param string $jname name of the JFusion plugin used
    * @return object JFusionPlugin JFusionPlugin object for the JFusion plugin
    */
    function &getPlugin($jname)
    {
        static $plugin_instances;
        if (!isset($plugin_instances )) {
            $plugin_instances = array();
        }

        //only create a new plugin instance if it has not been created before
        if (!isset($plugin_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'jfusion_plugin.php';
      if (file_exists($filename)){
              require_once($filename);
              $class = "JFusionPlugin_" . $jname;
              $plugin_instances[$jname]= new $class;
              return $plugin_instances[$jname];
      } else {
        $false=false;            // prevent php warning
        return $false;
      }
        } else {
            return $plugin_instances[$jname];
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
              require_once($filename);
              $class = "JFusionAuth_" . $jname;
              $auth_instances[$jname]= new $class;
              return $auth_instances[$jname];
      } else {
        return false;
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

        //only create a new authentication instance if it has not been created before
        if (!isset($user_instances[$jname] )) {
            $filename = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname .DS.'user.php';
      if (file_exists($filename)){
              require_once($filename);
              $class = "JFusionUser_" . $jname;
              $user_instances[$jname]= new $class;
              return $user_instances[$jname];
      } else {
        return false;
      }
        } else {
            return $user_instances[$jname];
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
            $database_instances[$jname]= JFusionFunction::getDatabase($jname);
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
    function &getParams($jname)
    {
        static $params_instances;
        if (!isset($params_instances )) {
            $params_instances = array();
        }

        //only create a new database instance if it has not been created before
        if (!isset($params_instances[$jname] )) {
            $params_instances[$jname] = JFusionFunction::getParameters($jname);
            return $params_instances[$jname];
        } else {
            return $params_instances[$jname];
        }
    }



}


