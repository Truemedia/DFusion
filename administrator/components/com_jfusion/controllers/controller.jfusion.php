<?php
/**
* @package JFusion
* @subpackage Controller
* @version 1.0.7
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
* Displays specific JFusion plugin parameters
*/
    function plugineditor()
    {
        //set jname as a global variable in order for elements to access it.
        global $jname;

        //find out the submitted name of the JFusion module
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING');

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
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING' );

        //check to see if a integration was selected
        if ($jname) {
            JRequest::setVar('view', 'wizard');
            parent::display();

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
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING' );
        $post = JRequest::getVar('params', array(), 'post', 'array' );

        //check to see data was posted
        if ($jname && $post) {

            //Initialize the forum
            $JFusionPlugin = JFusionFactory::getPlugin($jname);
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
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING' );
        $field_name = JRequest::getVar('field_name', '', 'POST', 'STRING' );
        $field_value = JRequest::getVar('field_value', '', 'POST', 'STRING' );


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

            $query = 'UPDATE #__jfusion SET ' . $field_name . ' =' . $db->quote($field_value) . ' WHERE name = ' . $db->quote($jname);
            $db->setQuery($query );
            $db->query();

            //get the new plugin settings
            $query = 'SELECT * FROM #__jfusion WHERE name = ' . $db->quote($jname);
            $db->setQuery($query );
            $result = $db->loadObject();

            //disable a slave when it is turned into a master
            if ($field_name == 'master' && $field_value == '1' && $result->slave == '1' ) {
                $query = 'UPDATE #__jfusion SET slave = 0 WHERE name = ' . $db->quote($jname);
                $db->setQuery($query );
                $db->query();
            }

            //disable a master when it is turned into a slave
            if ($field_name == 'slave' && $field_value == '1' && $result->master == '1' ) {
                $query = 'UPDATE #__jfusion SET master = 0 WHERE name = ' . $db->quote($jname);
                $db->setQuery($query );
                $db->query();
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
        //get the posted variables
        $post = JRequest::getVar('params', array(), 'post', 'array' );
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING' );

        //check for trailing slash in URL, in order for us not to worry about it later
        if (substr($post['source_url'], -1) == '/') {
        } else {
            $post['source_url'] .= '/';
        }



        //now also check to see that the url starts with http:// or https://
        if (substr($post['source_url'], 0, 7) != 'http://' && substr($post['source_url'], 0, 8) != 'https://') {
            $post['source_url'] = 'http://' . $post['source_url'];
        }


        if (JFusionFunction::saveParameters($jname, $post)) {
            JError::raiseNotice(0, JText::_('SAVE_SUCCESS'));
            //the internal Joomla database does not need to be checked
            if ($jname != 'joomla_int') {
                JFusionFunction::checkConfig($jname);
            }

        } else {
            JError::raiseWarning(500, JText::_('SAVE_FAILURE'));
        }
        JRequest::setVar('view', 'plugindisplay');
        parent::display();

    }




    /**
* Displays the usersync step 1 screen
*/
    function sync1()
    {
        JRequest::setVar('view', 'sync1');
        parent::display();
    }

    /**
* Start the usersync step 1 process
*/
    function sync1status()
    {

	/**
	* 	Load usersync library
	*/
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

	//check to see if the sync has already started
    $syncid = JRequest::getVar('syncid', '', 'GET');
    $db = & JFactory::getDBO();
    $query = 'SELECT syncid FROM #__jfusion_sync WHERE syncid =' . $db->Quote($syncid);
    $db->setQuery($query);
    if ($db->loadResult()) {
		//sync has started output the status
        JRequest::setVar('view', 'sync1status');
        $view = &$this->getView('sync1status', 'html');

		//get the syncdata
		$syncdata = JFusionUsersync::getSyncdata($syncid);
	    //print out results to user
    	$view->assignRef('syncdata', $syncdata);
        $view->setLayout('default');
        $result = $view->loadTemplate();
        die($result);


    } else {
    	//sync has not started, lets get going :)
        $slaves = JRequest::getVar('slave', '', 'GET');

        //lets find out which slaves need to be imported into the Master
        foreach($slaves as $jname => $slave) {
            if ($slave['sync_into_master']) {
                $temp_data = array();
                $temp_data['jname'] = $jname;
                $JFusionPlugin = JFusionFactory::getPlugin($jname);
                $temp_data['total'] = $JFusionPlugin->getUserCount();
                $temp_data['created'] = 0;
                $temp_data['deleted'] = 0;
                $temp_data['updated'] = 0;
                $temp_data['error'] = 0;

                //save the data
                $slave_data[$jname] = $temp_data;

                //reset the variables
                unset($temp_data, $JFusionPlugin);
            }
        }

        //format the syncdata for storage in the JFusion sync table
        $syncdata['master'] = JRequest::getVar('master', '', 'GET');
        $syncdata['syncid'] = JRequest::getVar('syncid', '', 'GET');
        $syncdata['slave_data'] = $slave_data;

        //save the submitted syndata in order for AJAX updates to work
        JFusionUsersync::saveSyncdata($syncdata);

        //start the usersync
        JFusionUsersync::SyncStep1($syncdata);
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
* Function to uninstall JFusion plugins
*/
    function uninstall_plugin()
    {
        $jname = JRequest::getVar('jname', '', 'POST', 'STRING', _J_ALLOWHTML );

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
* Displays the JFusion Help Window
*/
    function help()
    {
        JRequest::setVar('view', 'help');
        parent::display();
    }

}


