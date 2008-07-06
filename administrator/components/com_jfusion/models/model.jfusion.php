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
* Class for general JFusion functions
* @package JFusion
*/

class JFusionFunction{

    /**
* Returns the JFusion plugin name of the software that is currently the master of user management
* @param string $jname Name of master JFusion plugin
*/

    function getMaster()
    {
        //find the first forum that is enabled
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE master = 1 and status = 3';
        $db->setQuery($query );
        $jname = $db->loadObject();

        if ($jname) {
            return $jname;
        }
    }

    /**
* Returns the JFusion plugin name of the software that is currently the master of user management
* @param string $jname Name of master JFusion plugin
*/

    function getSlaves()
    {
        //find the first forum that is enabled
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE slave = 1 and status = 3';
        $db->setQuery($query );
        $jname = $db->loadObjectList();
        return $jname;

    }

    /**
* Returns the JFusion plugin name of the software that is currently the slave of user management
* @param array $jname Array list of slave JFusion plugin names
*/

    function getPlugins()
    {
        //find the first forum that is enabled
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE (slave = 1 AND status = 3 AND name NOT LIKE \'joomla_int\')';
        $db->setQuery($query );
        $list = $db->loadObjectList();
        return $list;
    }

    /**
* Returns the parameters of a specific JFusion integration
* @param string $jname name of the JFusion plugin used
* @return object Joomla parameters object
*/

    function &getParameters($jname)
    {
        //get the current parameters from the jfusion table
        $db = & JFactory::getDBO();
        $query = 'SELECT params from #__jfusion WHERE name = ' . $db->quote($jname);
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
* Saves the posted JFusion component variables
* @param string $jname name of the JFusion plugin used
* @param array $post Array of JFusion plugin parameters posted to the JFusion component
* @return true|false returns true if succesful and false if an error occurred
*/

    function saveParameters($jname, $post)
    {

        //serialize the $post to allow storage in a SQL field
        $serialized = base64_encode(serialize($post));

        //set the current parameters in the jfusion table
        $db = & JFactory::getDBO();
        $query = 'UPDATE #__jfusion SET params = ' . $db->quote($serialized) .' WHERE name = ' . $db->quote($jname);
        $db->setQuery($query );

        if (!$db->query()) {
            //there was an error saving the parameters
            JError::raiseWarning(0,$db->stderr());
            return false;
        }

        return true;
    }

    /**
* Checks to see if the JFusion plugins are installed and enabled
*/
    function checkPlugin()
    {
        $userPlugin = true;
        $authPlugin = true;
        if (!JFusionFunction::isPluginInstalled('jfusion','authentication', false)) {
            JError::raiseWarning(0,JText::_('FUSION_MISSING_AUTH'));
            $authPlugin = false;
        }

        if (!JFusionFunction::isPluginInstalled('jfusion','user', false)) {
            JError::raiseWarning(0,JText::_('FUSION_MISSING_USER'));
            $userPlugin = false;
        }

        if ($authPlugin && $userPlugin) {
            $jAuth = JFusionFunction::isPluginInstalled('jfusion','user',true);
            $jUser = JFusionFunction::isPluginInstalled('jfusion','authentication',true);
            if (!$jAuth) {
                JError::raiseNotice(0,JText::_('FUSION_READY_TO_USE_AUTH'));
            }
            if (!$jUser) {
                JError::raiseNotice(0,JText::_('FUSION_READY_TO_USE_USER'));
            }
        }
    }


    /**
* Checks to see if the configuration of a Jfusion plugin is valid and stores the result in the JFusion table
* @param string $jname name of the JFusion plugin used
*/

    function checkConfig($jname)
    {

        $db = JFusionFactory::getDatabase($jname);
        $jdb =& JFactory::getDBO();

        if (JError::isError($db) ) {
            //Save this error for the integration
            $query = 'UPDATE #__jfusion SET status = 1 WHERE name =' . $db->quote($jname);
            $jdb->setQuery($query );
            $jdb->query();
        } else {
            //get the user table name
            $JFusionPlugin = JFusionFactory::getPlugin($jname);
            $tablename = $JFusionPlugin->getTablename();

            //lets see if we can get some data
            $query = 'SELECT * FROM #__' . $tablename . ' LIMIT 1';
            $db->setQuery($query );
            $result = $db->loadObject();
            if ($result) {
                //Save this succes on check
                $query = 'UPDATE #__jfusion SET status = 3 WHERE name =' . $db->quote($jname);
                $jdb->setQuery($query );
                $jdb->query();
            } else {
                //Save this error for the integration
                $query = 'UPDATE #__jfusion SET status = 2 WHERE name =' . $db->quote($jname);
                $jdb->setQuery($query );
                $jdb->query();

            }

        }
    }



    /**
* Tests if a plugin is installed with the specified name, where folder is the type (e.g. user)
* @param string $element element name of the plugin
* @param string $folder folder name of the plugin
* @param integer $testPublished Variable to determine if the function should test to see if the plugin is published
* @return true|false returns true if succesful and false if an error occurred
*/
    function isPluginInstalled($element,$folder, $testPublished)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT published FROM #__plugins WHERE element=' . $db->quote($element) . ' AND folder=' . $db->quote($folder);
        $db->setQuery($query);
        $result = $db->loadObject();
        if ($result) {
            if ($testPublished) {
                return($result->published == 1);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
* Acquires a database connection to the database of the software integrated by JFusion
* @param string $jname name of the JFusion plugin used
* @return object JDatabase
*/
    function &getDatabase($jname)
    {

        //get the debug configuration setting
        $conf =& JFactory::getConfig();
        $debug = $conf->getValue('config.debug');


        //TODO see if we can delete these jimports below
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

        //create an options variable that contains all database connection variables
        $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );

        //create the actual connection
        $jfusion_database =& JDatabase::getInstance($options );
        if (JError::isError($jfusion_database) ) {
            JError::raiseWarning(0, JText::_('NO_DATABASE'));
        }

        return $jfusion_database;
    }

    /**
* Returns either the Joomla wrapper URL or the full URL directly to the forum
* @param string $url relative path to a webpage of the integrated software
* @param string $jname name of the JFusion plugin used
* @return string full URL to the filename passed to this function
*/

    function createURL($url, $jname, $view)
    {
        if ($view == 'wrapper') {
            $url_root = JURI::root();
            $url = $url_root . 'index.php?option=com_jfusion&amptask=wrapper&amp;wrap='. urlencode($url);
        } else {
            $params = JFusionFactory::getParams($jname);
            $url = $params->get('source_url') . $url;
        }

        return $url;

    }

    function updateLookup($userinfo, $jname, $joomla_id)
    {

        $db =& JFactory::getDBO();
        //prepare the variables
        $lookup = new stdClass;
        $lookup->userid = $userinfo->userid;
        $lookup->username = $userinfo->username;
        $lookup->jname = $jname;
        $lookup->id = $joomla_id;


        //insert the entry into the lookup table
        $db->insertObject('#__jfusion_users_plugin', $lookup, 'autoid' );
    }
    function lookupUser($jname, $userid)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT * FROM #__jfusion_users_plugin WHERE id =' . $userid . ' AND jname = ' . $db->quote($element) . ' AND folder=' . $db->quote($jname);
        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;

    }

    function saveSyncdata($syncdata)
    {
        //serialize the $syncdata to allow storage in a SQL field
        $serialized = base64_encode(serialize($syncdata));

        //find out if the syncid already exists
        $db =& JFactory::getDBO();
        $query = 'SELECT syncid FROM #__jfusion_sync WHERE syncid =' . $db->Quote($syncid);
        $db->setQuery($query);
        if ($db->loadResult()) {
            //run an update statement
            $db =& JFactory::getDBO();
            $query = 'UPDATE #__jfusion_sync SET syncdata = ' . $db->quote($serialized) .' WHERE syncid =' . $db->Quote($syncdata['syncid']);
            $db->setQuery($query );
            $db->query();
        } else {
            //run an insert statement
            $db =& JFactory::getDBO();
            $query = 'INSERT INTO #__jfusion_sync (syncdata, syncid) VALUES (' . $db->quote($serialized) .', ' . $db->Quote($syncdata['syncid']) . ')';
            $db->setQuery($query );
            $db->query();

        }
    }

    function getSyncdata($syncid)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT syncdata FROM #__jfusion_sync WHERE syncid =' . $db->Quote($syncid);
        $db->setQuery($query);
        $serialized = $db->loadResult();
        $syncdata = unserialize(base64_decode($serialized));

        return $syncdata;
    }

    function SyncStep1($syncdata)
    {

        //setup some variables
        $MasterPlugin = JFusionFactory::getPlugin($syncdata['master']);
        $sync_errors = array();

        //we should start with the import of slave users into the master
        foreach($syncdata['slave_data'] as $slave_sync) {
            //update the database every x users
            $update_count = 10;
            $count = 0;

            //get a list of users
            $slave_sync['jname'] = $jname;
            $SlavePlugin = & JFusionFactory::getPlugin($jname);
            $userlist = $SlavePlugin->getUserList();

            //perform the actual sync
            foreach($userlist as $user) {
                $userinfo = $SlavePlugin->getUser($user->username);
                $status = $MasterPlugin->updateUser($userinfo);
                if ($status['error']) {
                    $sync_error = array();
                    $sync_error['master']['username'] = $status['userinfo']->username;
                    $sync_error['master']['email'] = $status['userinfo']->email;
                    $sync_error['slave']['username'] = $userinfo->username;
                    $sync_error['slave']['email'] = $userinfo->email;
                    //save the error for later
                    $syncdata['slave_data'][$jname]['errors'][] = $sync_error;

                    //update the counters
                    $syncdata['slave_data'][$jname]['error'] += 1;
                    $syncdata['slave_data'][$jname]['total'] -= 1;
                } else {
                    if ($status['action'] == 'created') {
                        $syncdata['slave_data'][$jname]['created'] += 1;
                    } else {
                        $syncdata['slave_data'][$jname]['updated'] += 1;
                    }
                    $syncdata['slave_data'][$jname]['total'] -= 1;
                }

                //update the database
                ++$count;
                if ($count > $update_count) {
                    //save the syncdata
                    $count = 0;
                    JFusionFunction::saveSyncdata($syncdata);
                }
            }
            //end of sync
        }
    }
}

