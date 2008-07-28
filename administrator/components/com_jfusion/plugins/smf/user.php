<?php

/**
 * @package JFusion_SMF
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');



/**
 * JFusion plugin class for SMF 1.1.4
 * @package JFusion_SMF
 */
class JFusionUser_smf extends JFusionUser{

    function updateUser($userinfo)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);
      	if ($userlookup->email == $userinfo->email) {
        	//emails match up
            $status['userinfo'] = $userlookup;
        	$status['error'] = false;
        	$status['debug'] = JText::_('USER_EXISTS');
            return $status;
	    } elseif ($userlookup) {
        	//emails match up
            $status['userinfo'] = $userlookup;
        	$status['error'] = JText::_('EMAIL_CONFLICT');
            return $status;
	    } else {
            $status['userinfo'] = $userlookup;
        	$status['error'] = JText::_('UNABLE_CREATE_USER');
            return $status;
	    }
    }

    function &getUser($username)
    {
        // initialise some objects
        $params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT ID_MEMBER as userid, memberName as username, realName as name, emailAddress as email, passwd as password, passwordSalt as password_salt FROM #__members WHERE memberName=' . $db->Quote($username) ;
        $db->setQuery($query );
        $result = $db->loadObject();

        if ($result) {
            //Check to see if they are banned
            $query = 'SELECT ID_MEMBER FROM #__ban_items WHERE ID_MEMBER= ' . $result->userid;
            $db->setQuery($query);
            if ($db->loadObject()) {
                $result->block = 1;
            } else {
                $result->block = 0;
            }
        }

        return $result;

        }


    function getJname()
    {
        return 'smf';
    }

    function deleteUser($username)
    {
	    //TODO: create a function that deletes a user
    }

    function destroySession($userinfo, $options)
    {
		//check to see if the smf_api.php file exists
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');
        if (substr($source_path, -1) == '/') {
			$api_file = $source_path .'smf_api.php';
        } else {
			$api_file = $source_path .DS.'smf_api.php';
        }

		if (file_exists($api_file)) {
			require_once($api_file);
    		smf_setLoginCookie(-3600,0);
            $status['debug'] = 'Destroyed the SMF session using the smf_api.php';
            $status['error'] = false;
            return $status;
        } else {
            $status['error'] = 'Dual login is not available for this plugin, as the smf_api.php file was not found at:'. $api_file . 'Please download the smf_api.php file and put it in your smf home directory:   http://www.simplemachines.org/community/index.php?action=dlattach;topic=42867.0;attach=9158';
            return $status;
        }
    }

    function createSession($userinfo, $options)
    {
		//check to see if the smf_api.php file exists
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');
        if (substr($source_path, -1) == '/') {
			$api_file = $source_path .'smf_api.php';
        } else {
			$api_file = $source_path .DS.'smf_api.php';
        }

		if (file_exists($api_file)) {
			require_once($api_file);
			$username = $userinfo->username;
			$password = $userinfo->password_clear;
			$cookie_length = 3600 + $options['remember']*31536000;
			smf_setLoginCookie($cookie_length,$username,$password,false);
			smf_loadSession();
			smf_authenticateUser();

            $status['debug'] = 'Created SMF session using the smf_api.php';
            $status['error'] = false;
            return $status;
		} else {
            $status['error'] = 'Dual login is not available for this plugin, as the smf_api.php file was not found at:'. $api_file . 'Please download the smf_api.php file and put it in your smf home directory:   http://www.simplemachines.org/community/index.php?action=dlattach;topic=42867.0;attach=9158';
            return $status;

		}



    }

    function filterUsername($username) {
	    //no username filtering implemented yet
    	return $username;
    }
}

