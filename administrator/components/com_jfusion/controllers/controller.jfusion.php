<?php
/**
* @package JFusion
* @subpackage Controller
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Load the JFusion framework
*/
jimport('joomla.application.component.controller');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

/**
* JFusion Component Controller
* @package JFusion
*/

class JFusionController extends JController
{

    /**
* Displays the JFusion plugin overview
*/
    function plugindisplay()
    {
        JRequest::setVar('view', 'plugindisplay');
        parent::display();
    }

    /**
* Displays the JFusion control panel
*/
    function cpanel()
    {
        JRequest::setVar('view', 'cpanel');
        parent::display();
    }

    /**
* Displays specific JFusion plugin parameters
*/
    function plugineditor()
    {
        //set jname as a global variable in order for elements to access it.
        global $jname;

        //find out the submitted name of the JFusion module
        $jname = JRequest::getVar('jname');

        if ($jname) {

            $parameters = JFusionFactory::getParams($jname);
            $param_output = $parameters->render();

            $view = &$this->getView('plugineditor', 'html');
            $view->assignRef('parameters', $param_output);
            $view->assignRef('jname', $jname);
            $view->setLayout('default');
            $view->display();

        } else {
            //show main screen
            JError::raiseWarning(500, JText::_('NONE_SELECTED'));
            JRequest::setVar('view', 'plugindisplay');
            parent::display();


        }
    }

    /**
* Shows the wizards screen with a input for the integrated software path
*/
    function wizard()
    {
        //find out the submitted name of the JFusion module
        $jname = JRequest::getVar('jname');

        //check to see if a integration was selected
        if ($jname) {
        	if ($jname != 'joomla_int'){
            	JRequest::setVar('view', 'wizard');
            	parent::display();
        	} else {
            	JError::raiseWarning(500, JText::_('WIZARD_MANUAL'));
        	}


        } else {
            JError::raiseWarning(500, JText::_('NONE_SELECTED'));
            JRequest::setVar('view', 'plugindisplay');
            parent::display();
        }

    }

    /**
* Display the results of the wizard set-up
*/
    function wizardresult()
    {
        //set jname as a global variable in order for elements to access it.
        global $jname;

        //find out the submitted values
        $jname = JRequest::getVar('jname');
        $post = JRequest::getVar('params', array(), 'post', 'array' );

        //check to see data was posted
        if ($jname && $post) {

            //Initialize the forum
            $JFusionPlugin = JFusionFactory::getAdmin($jname);
            $params = $JFusionPlugin->setupFromPath($post['source_path']);

            if ($params) {
                //save the params first in order for elements to utilize data
                JFusionFunction::saveParameters($jname, $params);

                $parameters = JFusionFactory::getParams($jname);
                $param2_output = $parameters->render();

                JError::raiseNotice(0, JText::_('WIZARD_SUCCESS'));
                $view = &$this->getView('plugineditor', 'html');
                $view->assignRef('parameters', $param2_output);
                $view->assignRef('jname', $jname);
                $view->setLayout('default');
                $view->display();
            } else {
                //load the default XML parameters
                $parameters = JFusionFactory::getParams($jname);
                $param_output = $parameters->render();
                JError::raiseWarning(500, JText::_('WIZARD_FAILURE'));
                $view = &$this->getView('plugineditor', 'html');
                $view->assignRef('parameters', $param_output);
                $view->setLayout('default');
                $view->display();
            }
        } else {
            JError::raiseWarning(500, JText::_('WIZARD_FAILURE'));
            JRequest::setVar('view', 'plugineditor');
            parent::display();
        }
    }


    /**
* Function to change the master/slave/encryption settings in the jos_jfusion table
*/
    function changesetting()
    {
        //find out the posted ID of the JFusion module to publish
        $jname = JRequest::getVar('jname');
        $field_name = JRequest::getVar('field_name');
        $field_value = JRequest::getVar('field_value');


        //check to see if an integration was selected
        if ($jname) {
            $db = & JFactory::getDBO();

            if ($field_name == 'master') {
                //If a master is being set make sure all other masters are disabled first
                $query = 'UPDATE #__jfusion SET master = 0';
                $db->setQuery($query );
                $db->query();
            }

            //perform the update
            $query = 'UPDATE #__jfusion SET ' . $field_name . ' =' . $db->Quote($field_value) . ' WHERE name = ' . $db->Quote($jname);
            $db->setQuery($query );
            $db->query();

            //get the new plugin settings
            $query = 'SELECT * FROM #__jfusion WHERE name = ' . $db->Quote($jname);
            $db->setQuery($query );
            $result = $db->loadObject();

            //disable a slave when it is turned into a master
            if ($field_name == 'master' && $field_value == '1' && $result->slave == '1' ) {
                $query = 'UPDATE #__jfusion SET slave = 0 WHERE name = ' . $db->Quote($jname);
                $db->setQuery($query );
                $db->query();
            }

            //disable a master when it is turned into a slave
            if ($field_name == 'slave' && $field_value == '1' && $result->master == '1' ) {
                $query = 'UPDATE #__jfusion SET master = 0 WHERE name = ' . $db->Quote($jname);
                $db->setQuery($query );
                $db->query();
            }

            //auto enable the auth and dual login for newly enabled plugins
            if (($field_name == 'slave' || $field_name == 'master') && $field_value == '1') {
            	$query = 'SELECT dual_login FROM #__jfusion WHERE name = ' . $db->Quote($jname);
            	$db->setQuery($query );
            	$dual_login = $db->loadResult();
            	if ($dual_login > 1) {
                	//only set the encryption if dual login is disabled
                	$query = 'UPDATE #__jfusion SET check_encryption = 1 WHERE name = ' . $db->Quote($jname);
                	$db->setQuery($query );
                	$db->query();
            	} else {
                	$query = 'UPDATE #__jfusion SET dual_login = 1, check_encryption = 1 WHERE name = ' . $db->Quote($jname);
                	$db->setQuery($query );
                	$db->query();
            	}
            }

        } else {
            JError::raiseWarning(500, JText::_('NONE_SELECTED'));
        }

        //render configuration overview
        JRequest::setVar('view', 'plugindisplay');
        parent::display();

    }

    /**
* Function to save the JFusion plugin parameters
*/
    function saveconfig()
    {
        //set jname as a global variable in order for elements to access it.
        global $jname;

        //get the posted variables
        $post = JRequest::getVar('params', array(), 'post', 'array' );
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING' );

        //check for trailing slash in URL, in order for us not to worry about it later
        if (substr($post['source_url'], -1) == '/') {
        } else {
            $post['source_url'] .= '/';
        }

		//now also check to see that the url starts with http:// or https://
		if (substr($post['source_url'], 0, 7) != 'http://' &&
		    substr($post['source_url'], 0, 8) != 'https://') {
		   if(substr($post['source_url'], 0, 1) != '/') {
		      $post['source_url'] = 'http://' . $post['source_url'];
   			} else {
		      $post['source_url'] = $post['source_url'];
		   }
		}

        if (JFusionFunction::saveParameters($jname, $post)) {
            JError::raiseNotice(0, JText::_('SAVE_SUCCESS'));
        } else {
            JError::raiseWarning(500, JText::_('SAVE_FAILURE'));
        }

        //update the status field
		$JFusionPlugin = JFusionFactory::getAdmin($jname);
		$config_status =  $JFusionPlugin->checkConfig($jname);
        $db = & JFactory::getDBO();
   	    $query = 'UPDATE #__jfusion SET status = '. $config_status['config']. ' WHERE name =' . $db->Quote($jname);
       	$db->setQuery($query );
        $db->query();

		//check for any custom commands
        $customcommand = JRequest::getVar('customcommand');
        if (!empty($customcommand)){
            $JFusionPlugin = JFusionFactory::getAdmin($jname);
            if (method_exists($JFusionPlugin,$customcommand)){
                $JFusionPlugin->$customcommand();
            }
        }

        $action = JRequest::getVar('action');
        if ($action == 'apply'){

            $parameters = JFusionFactory::getParams($jname);
            $param_output = $parameters->render();

            $view = &$this->getView('plugineditor', 'html');
            $view->assignRef('parameters', $param_output);
            $view->assignRef('jname', $jname);
            $view->setLayout('default');
            $view->display();
        } else {
            JRequest::setVar('view', 'plugindisplay');
            parent::display();
        }


    }

    /**
* Displays the usersync main screen
*/
    function sync()
    {
        JRequest::setVar('view', 'sync');
        parent::display();
    }

    /**
* Resumes a usersync if it has stopped
*/
    function syncresume()
    {
    	$syncid = JRequest::getVar('syncid', '', 'GET');
		if ($syncid) {
			//Load usersync library
			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');
			$syncdata = JFusionUsersync::getSyncdata($syncid);
		    //start the usersync
       	    JFusionUsersync::SyncExecute($syncdata,$syncdata['action'],$syncdata['plugin_offset'],$syncdata['user_offset']);
       	}
    }

    /**
* Displays the usersync error screen
*/
    function syncerror()
    {
		//Load usersync library
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

    	$syncerror = JRequest::getVar('syncerror', array(), 'POST', 'array' );
    	$syncid = JRequest::getVar('syncid', '', 'POST');
    	if ($syncerror) {
    		//apply the submitted sync error instructions
			JFusionUsersync::SyncError($syncid, $syncerror);
    	} else {
    		//output the sync errors to the user
        	JRequest::setVar('view', 'syncerror');
        	parent::display();
    	}

    }

    /**
* Displays the usersync history screen
*/
    function syncerrordetails()
    {
		//Load usersync library
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

        $view = &$this->getView('syncerrordetails', 'html');
        $view->setLayout('default');
        $result = $view->loadTemplate();
        die($result);
    }



    /**
* Displays the usersync history screen
*/
    function synchistory()
    {
		//Load usersync library
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

        JRequest::setVar('view', 'synchistory');
        parent::display();
    }


    /**
* Displays the usersync into master screen
*/
    function syncoptions()
    {
		//Load usersync library
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

        JRequest::setVar('view', 'syncoptions');
        parent::display();
    }

    /**
* Displays the version check screen
*/
    function versioncheck()
    {
        JRequest::setVar('view', 'versioncheck');
        parent::display();
    }

    /**
* Displays the usersync status
*/
    function syncstatus()
    {

	//Load usersync library
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

	//check to see if the sync has already started
    $syncid = JRequest::getVar('syncid');
    $action = JRequest::getVar('action');

    $db = & JFactory::getDBO();
    $query = 'SELECT syncid FROM #__jfusion_sync WHERE syncid =' . $db->Quote($syncid);
    $db->setQuery($query);
    if ($db->loadResult()) {
		//sync has started output the status
        JRequest::setVar('view', 'syncstatus');
        $view = &$this->getView('syncstatus', 'html');
        //get the syncdata
		$syncdata = JFusionUsersync::getSyncdata($syncid);
    	$view->assignRef('syncdata', $syncdata);
    	$view->assignRef('syncid', $syncid);
        $view->setLayout('default');
        $result = $view->loadTemplate();
        die($result);


    } else {

    	//sync has not started, lets get going :)
        $slaves = JRequest::getVar('slave');
        $master_plugin = JFusionFunction::getMaster();
        $master = $master_plugin->name;
        $JFusionMaster = JFusionFactory::getAdmin($master);

        //initialise the slave data array
        $slave_data = array();

        if(empty($slaves)){
        	//nothing was selected in the usersync
        	die(JText::_('SYNC_NODATA'));
        }

		echo '<a href="index.php?option=com_jfusion&task=syncresume&syncid=' . $syncid . '">' . JText::_('SYNC_RESUME') . '</a>';

        //lets find out which slaves need to be imported into the Master
        foreach($slaves as $jname => $slave) {
            if ($slave['perform_sync']) {
                $temp_data = array();
                $temp_data['jname'] = $jname;
                $JFusionPlugin = JFusionFactory::getAdmin($jname);
                if ($action == 'master') {
                	$temp_data['total'] = $JFusionPlugin->getUserCount();
                } else {
                	$temp_data['total'] = $JFusionMaster->getUserCount();
                }
                $temp_data['created'] = 0;
                $temp_data['deleted'] = 0;
                $temp_data['updated'] = 0;
                $temp_data['error'] = 0;

                //save the data
                $slave_data[] = $temp_data;

                //reset the variables
                unset($temp_data, $JFusionPlugin);
            }
        }

        //format the syncdata for storage in the JFusion sync table
        $syncdata['master'] = $master;
        $syncdata['syncid'] = JRequest::getVar('syncid');
        $syncdata['slave_data'] = $slave_data;
        $syncdata['action'] = $action;

        //save the submitted syndata in order for AJAX updates to work
        JFusionUsersync::saveSyncdata($syncdata);

        //start the usersync
       	JFusionUsersync::SyncExecute($syncdata,$action,0,0);

    }
    }


    /**
* Displays the JFusion plugin installation options
*/
    function pluginmanager()
    {
        JRequest::setVar('view', 'pluginmanager');
        parent::display();
    }

    /**
* Function to upload, parse & install JFusion plugins
*/
    function install_plugin()
    {
        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.install.php');
        $model = new JFusionModelInstaller();
        $model->install();



        JRequest::setVar('view', 'pluginmanager');
        parent::display();

    }
    /**
* Function to copy an existing JFusion plugins
*/
    function copy_plugin()
    {
        $jname = JRequest::getVar('jname');
        $new_jname = JRequest::getVar('new_jname');

        //check to see if an integration was selected
        if ($jname && $new_jname) {
            require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.install.php');
            $model = new JFusionModelInstaller();
            $model->copy($jname, $new_jname);

        } else {
            JError::raiseWarning(500, JText::_('NONE_SELECTED'));
        }

        JRequest::setVar('view', 'pluginmanager');
        parent::display();
    }
    /**
* Function to uninstall JFusion plugins
*/
    function uninstall_plugin()
    {
        $jname = JRequest::getVar('jname');

        //check to see if an integration was selected
        if ($jname) {
            require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.install.php');
            $model = new JFusionModelInstaller();
            $model->uninstall($jname);

        } else {
            JError::raiseWarning(500, JText::_('NONE_SELECTED'));
        }

        JRequest::setVar('view', 'pluginmanager');
        parent::display();
    }

    /**
* Displays the login checker screen to the user
*/
    function loginchecker()
    {
        JRequest::setVar('view', 'loginchecker');
        parent::display();
    }

    /**
* Displays the result of the login checker screen to the user
*/
    function logincheckerresult()
    {
        JRequest::setVar('view', 'logincheckerresult');
        parent::display();
    }

    /**
* Displays the result of the logout checker screen to the user
*/
    function logoutcheckerresult()
    {
        JRequest::setVar('view', 'logoutcheckerresult');
        parent::display();
    }

    /**
* Displays the JFusion Help Window
*/
    function help()
    {
        JRequest::setVar('view', 'help');
        parent::display();
    }

    /**
* Enables the JFusion Plugins
*/
    function enableplugins()
    {
		//enable the JFusion login behaviour
		$db =& JFactory::getDBO();
		$db->setQuery('UPDATE #__plugins SET published = 0 WHERE element =\'joomla\' and folder = \'authentication\'');
		$db->Query();
		$db->setQuery('UPDATE #__plugins SET published = 0 WHERE element =\'joomla\' and folder = \'user\'');
		$db->Query();
		$db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'jfusion\' and folder = \'authentication\'');
		$db->Query();
		$db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'jfusion\' and folder = \'user\'');
		$db->Query();

        JRequest::setVar('view', 'cpanel');
        parent::display();
    }

    /**
* Disables the JFusion Plugins
*/
    function disableplugins()
    {
		//restore the normal login behaviour
		$db =& JFactory::getDBO();
		$db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'joomla\' and folder = \'authentication\'');
		$db->Query();
		$db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'joomla\' and folder = \'user\'');
		$db->Query();
		$db->setQuery('UPDATE #__plugins SET published = 0 WHERE element =\'jfusion\' and folder = \'authentication\'');
		$db->Query();
		$db->setQuery('UPDATE #__plugins SET published = 0 WHERE element =\'jfusion\' and folder = \'user\'');
		$db->Query();

        JRequest::setVar('view', 'cpanel');
        parent::display();
    }

	function deletehistory()
	{
		$db =& JFactory::getDBO();
    	$syncid = JRequest::getVar('syncid');
    	foreach($syncid as $key => $value){
			$db->setQuery('DELETE FROM #__jfusion_sync WHERE syncid = ' . $db->Quote($key));
			$db->Query();
    	}
        JRequest::setVar('view', 'synchistory');
        parent::display();
	}

	function resolveerror()
	{
		$db =& JFactory::getDBO();
    	$syncid = JRequest::getVar('syncid');
    	foreach($syncid as $key => $value){
	    	$syncid = JRequest::setVar('syncid', $key);
	        //output the sync errors to the user
	        JRequest::setVar('view', 'syncerror');
        	parent::display();
        	return;
		}
	}

        function itemidselect()
        {
                JRequest::setVar('view', 'itemidselect');
                parent::display();
        }
        
        /**
         * Displays the JFusion PluginMenu Parameters
         */
        function advancedparam()
        {
                JRequest::setVar('view', 'advancedparam');
                parent::display();
        }

        /**
         * Displays the JFusion PluginMenu Parameters
         */
        function advancedparamsubmit()
        {
                $param = JRequest::getVar('params');
                $multiselect = JRequest::getVar('multiselect');
                if($multiselect) $multiselect = true;
                else $multiselect = false;

                $serParam = base64_encode(serialize($param));
                $title = "";
                if(isset($param["jfusionplugin"])) {
                	$title = $param["jfusionplugin"];
                } else if($multiselect) {
                	$del = "";
                	foreach($param as $key => $value) {
                		if(isset($value["jfusionplugin"])) {
                			$title .= $del . $value["jfusionplugin"];
                			$del = "; ";
                		}
                	}
                }
                if(empty($title)) {
                	$title = "No Plugin Selected";
                }

                echo '<script type="text/javascript">'.
                     'window.parent.jAdvancedParamSet("'.$title.'", "'.$serParam.'");'.
                     '</script>';
                return;
        }
}
