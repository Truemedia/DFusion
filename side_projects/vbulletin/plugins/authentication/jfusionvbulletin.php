<?php

/**
 * @package JFusion
 * @subpackage Plugin_Auth
 * @version 1.1.0
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Load the JFusion framework
*/

jimport('joomla.event.plugin');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

/**
* JFusion Authentication class
* @package JFusion
*/

class plgAuthenticationjfusionvbulletin extends JPlugin
{
    /**
* Constructor
*
* For php4 compatability we must not use the __constructor as a constructor for
* plugins because func_get_args ( void ) returns a copy of all passed arguments
* NOT references. This causes problems with cross-referencing necessary for the
* observer design pattern.
*/
    function plgAuthenticationjfusionvbulletin(& $subject, $config)
    {
        parent::__construct($subject, $config);

        //load the language
        $this->loadLanguage('com_jfusion', JPATH_BASE);
    }

    /**
* This method should handle any authentication and report back to the subject
*
* @access public
* @param array $credentials Array holding the user credentials
* @param array $options Array of extra options
* @param object $response Authentication response object
* @return boolean
* @since 1.5
*/
    function onAuthenticate($credentials, $options, &$response )
    {
        jimport('joomla.user.helper');
        // Joomla does not like blank passwords
        if (empty($credentials['password'])) {
            $response->status = JAUTHENTICATE_STATUS_FAILURE;
            $response->error_message = 'Empty password not allowed';
            return false;
        }

        // Initialize variables
        $conditions = '';
        $db =& JFactory::getDBO();

        //get the JFusion master as a check that the user exists in Joomla
        $master = JFusionFunction::getMaster();
        if(!empty($master)) {
        	$JFusionMaster = JFusionFactory::getUser($master->name);
        	$userinfo = $JFusionMaster->getUser($credentials['username']);
        } else {
        	$userinfo = '';
        }

        if(!empty($userinfo))
        {
			//load each slave and see if the user matches
        	$slaves = JFusionFunction::getSlaves();
	    	foreach($slaves as $slave)
	    	{
	  	    	$slave = JFusionFactory::getUser($slave->name);
	  	    	$userinfo = $slave->getUser($credentials["username"]);

				//check if a user was found
		        if (!empty($userinfo))
		        {
			        //does the request sent from vbulletin and match the user's credentials?
			        //probably a bit redundant but serves as an extra check
			        $testcrypt = md5($credentials["password"].$credentials["password_salt"]);
			        $userpass = md5($userinfo->password.$userinfo->password_salt);

		            if ($testcrypt==$userpass && defined('_VBULLETIN_JFUSION_HOOK')){
			            //found a match
			            $response->status = JAUTHENTICATE_STATUS_SUCCESS;
			            $response->email = $userinfo->email;
			            $response->fullname = $userinfo->name;
			            $response->error_message = '';
			            return true;
					}

		            //no matching password found
		            $response->status = JAUTHENTICATE_STATUS_FAILURE;
		            $response->error_message = 'Invalid password';
		        }
		        else
		        {
		            $response->status = JAUTHENTICATE_STATUS_FAILURE;
		            $response->error_message = 'User does not exist';
		        }
	    	}
    	}
		else
		{
			$response->status = JAUTHENTICATE_STATUS_FAILURE;
			$response->error_message = 'User does not exist';
		}
    }
}