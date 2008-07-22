<?php
/**
* @package JFusion
* @subpackage Plugin_User
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
jimport('joomla.plugin.plugin');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');


/**
* JFusion User class
* @package JFusion
*/
class plgUserJfusion extends JPlugin
{
    /**
* Constructor
*
* For php4 compatability we must not use the __constructor as a constructor for plugins
* because func_get_args ( void ) returns a copy of all passed arguments NOT references.
* This causes problems with cross-referencing necessary for the observer design pattern.
*
* @param object $subject The object to observe
* @param array $config An array that holds the plugin configuration
* @since 1.5
*/
    function plgUserJfusion(& $subject, $config)
    {
        parent::__construct($subject, $config);
    }

    /**
* Remove all sessions for the user name
*
* Method is called after user data is deleted from the database
*
* @param array holds the user data
* @param boolean true if user was succesfully stored in the database
* @param string message
*/
    function onAfterDeleteUser($user, $succes, $msg)
    {
        if (!$succes) {
            return false;
        }

        $db =& JFactory::getDBO();
        $db->setQuery('DELETE FROM #__session WHERE userid = '.$db->Quote($user['id']));
        $db->Query();

        return true;
    }

    /**
* This method should handle any login logic and report back to the subject
*
* @access public
* @param array holds the user data
* @param array array holding options (remember, autoregister, group)
* @return boolean True on success
* @since 1.5
*/
    function onLoginUser($user, $options = array())
    {
        jimport('joomla.user.helper');
        //get the JFusion master
        $jname = JFusionFunction::getMaster();

        if (!$jname->name || $jname->name == 'joomla_int') {
            //use Joomla as the master
        	//get the master userinfo
        	$JFusionMaster = JFusionFactory::getUser('joomla_int');
        	$userinfo = $JFusionMaster->getUser($user['username']);

            //apply the cleartext password to the user object
            $userinfo->password_clear = $user['password'];
			$joomla_user['userinfo'] = $userinfo;

            //allow for password updates
            $user_update = $JFusionMaster->updateUser($userinfo);
        	if ($user_update['error']){
            	//report any errors
           		JError::raiseWarning('500', $jname->name . ': '. $user_update['error']);
        		if ($user_update['userinfo']) {
	            	JError::raiseWarning('500', 'joomla_int ' . JText::_('USERNAME'). ' ' . $userinfo->username . ' ' . JText::_('CONFLICT') . ': ' . $jname->name . ' '. JText::_('USERID') . ' ' . $user_update['userinfo']->userid);
        		}
				return false;
        	}

			//create a Joomla session
        	$joomla_session = $JFusionMaster->createSession($userinfo, $options);
        	if ($joomla_session['error']){
            	//no Joomla session could be created -> deny login
            	JError::raiseWarning('500', 'joomla_int' . ': '. $joomla_session['error']);
				return false;
        	}

        } else {
        	//a JFusion plugin other than Joomla is the master
        	//get the master userinfo
        	$JFusionMaster = JFusionFactory::getUser($jname->name);
        	$userinfo = $JFusionMaster->getUser($user['username']);

            //apply the cleartext password to the user object
            $userinfo->password_clear = $user['password'];
            //allow for password updates
            $user_update = $JFusionMaster->updateUser($userinfo);
        	if ($user_update['error']){
            	//report any errors
            	JError::raiseWarning('500', $jname->name . ': '. $user_update['error']);

        	}

			//setup the master session
			if($jname->dual_login && $options['group'] != 'Public Backend'){
        		$master_session = $JFusionMaster->createSession($userinfo, $options);
        		if ($master_session ['error']){
            		//no Joomla session could be created -> deny login
            		JError::raiseWarning('500', $jname->name . ': '. $master_session ['error']);
        		}
			}

        	//setup the Joomla user
            $joomla_int = JFusionFactory::getUser('joomla_int');
            $joomla_user = $joomla_int->updateUser($userinfo);
            if ($joomla_user['error']) {
                //no Joomla user could be created
           		JError::raiseWarning('500', $jname->name . ': '. $joomla_user['error']);
        		if ($joomla_user['userinfo']) {
	            	JError::raiseWarning('500', 'joomla_int ' . JText::_('USERNAME'). ' ' . $userinfo->username . ' ' . JText::_('CONFLICT') . ': ' . $jname->name . ' '. JText::_('USERID') . ' ' . $joomla_user['userinfo']->userid);
        		}
                return false;
            }

			//create a Joomla session
        	$joomla_session = $joomla_int->createSession($joomla_user['userinfo'], $options);
        	if ($joomla_session['error']){
            	//no Joomla session could be created -> deny login
            	JError::raiseWarning('500', $jname->name . ': '. $joomla_session ['error']);
				return false;
        	}
        }

        //update the JFusion user lookup table
       	//Delete old user data in the lookup table
        $db =& JFactory::getDBO();
        $query = 'DELETE FROM #__jfusion_user WHERE id =' . $joomla_user['userinfo']->userid . ' OR username =' . $db->quote($user['username']);
        $db->setQuery($query);
        $db->query();

        //create a new entry in the lookup table
        $db =& JFactory::getDBO();
        $query = 'INSERT INTO #__jfusion_user (id, username) VALUES (' . $joomla_user['userinfo']->userid . ', ' . $db->quote($user['username']) . ')';
        $db->setQuery($query);
        $db->query();

        if ($jname->name != 'joomla_int'){
            JFusionFunction::updateLookup($userinfo, $jname->name, $joomla_user['userinfo']->userid);
        }


        //setup the other slave JFusion plugins
        $plugins = JFusionFunction::getPlugins();
        foreach($plugins as $plugin) {
            $JFusionPlugin = JFusionFactory::getUser($plugin->name);
            $plugin_user = $JFusionPlugin->updateUser($userinfo);
            if ($plugin_user['error']) {
           		JError::raiseWarning('500', $plugin->name . ': '. $plugin_user['error']);
        		if ($joomla_user['userinfo']) {
	            	JError::raiseWarning('500', $jname->name . ' ' . JText::_('USERNAME'). ' ' . $userinfo->username . ' ' . JText::_('CONFLICT') . ': ' . $plugin->name . ' '. JText::_('USERID') . ' ' . $plugin_user['userinfo']->userid);
        		}
                JError::raiseWarning('500', $plugin_user['error']);
            } else {
                JFusionFunction::updateLookup($plugin_user['userinfo'], $plugin_name, $joomla_user['userinfo']->userid);
                if ($options['group'] != 'Public Backend' && $plugin->dual_login) {
                    $session_result = $JFusionPlugin->createSession($plugin_user['userinfo'], $options);
                    if ($session_result['error']){
                        JError::raiseWarning('500', $plugin->name . ': ' . $session_result['error']);
                    }
                }
                //clean up for the next loop
                unset($JFusionPlugin,$plugin_user, $session_result);
            }
        }

        return true;
    }


    /**
* This method should handle any logout logic and report back to the subject
*
* @access public
* @param array holds the user data
* @param array array holding options (client, ...)
* @return object True on success
* @since 1.5
*/
    function onLogoutUser($user, $options = array())
    {
    	$my =& JFactory::getUser();

    	//logout from the JFusion plugins if done through frontend
        if ($options['clientid'][0] != 1) {
            $plugins = JFusionFunction::getPlugins();
            foreach ($plugins as $plugin) {
            	//check if sessions are enabled
            	if ($plugin->dual_login){
                    $JFusionPlugin = JFusionFactory::getUser($plugin->name);
                    $username = JFusionFunction::lookupUserId($plugin->name, $my->get('id'));
                    $userinfo = $JFusionPlugin->getUser($username);
					//check if a user was found
                    if ($userinfo) {
                    	$session_result = $JFusionPlugin->destroySession($userinfo, $options);
                    	if ($session_result['error']){
                        	JError::raiseWarning('500', $plugin->name . ': ' . $session_result['error']);
                    	}
                    } else {
                        JError::raiseWarning('500', $plugin->name . ': ' . JText::_('COULD_NOT_FIND_USER'));
                    }
                    unset($JFusionPlugin, $username, $userinfo, $session_result);
            	}
            }
        }

		//destroy the Joomla session
        $table = & JTable::getInstance('session');
        $table->destroy($user['id'], $options['clientid']);

        $my =& JFactory::getUser();
        if ($my->get('id') == $user['id']) {
            // Hit the user last visit field
            $my->setLastVisit();

            // Destroy the php session for this user
            $session =& JFactory::getSession();
            $session->destroy();
        }

        return true;
    }
}

