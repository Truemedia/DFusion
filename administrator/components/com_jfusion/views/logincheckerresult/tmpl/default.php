<?php
/**
* @package JFusion
* @subpackage Views
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');


//use an output buffer, in order for cookies to be passed onto the header
ob_start();


JFusionFunction::displayDonate();

/**
* Output information about the server for future support queries
*/
?>
<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/login_checker2.png" height="75px" width="75px">
<td><h2><? echo JText::_('LOGIN_CHECKER_RESULT');?></h2></td>
</tr></table>

<?php

//get the submitted login details
$credentials['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$credentials['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$user['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$user['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$options['group'] = 'USERS';
$options['remember'] = JRequest::getVar('remember', '', 'POST', 'STRING' );
$skip_password = JRequest::getVar('skip_password', '', 'POST', 'STRING' );


//check to see if a password was submitted
if (empty($credentials['password']) && empty($skip_password)) {
    echo JText::_('NO_PASSWORD');
    ob_end_flush();
    $result = false;
    return $result;
}

//check to see if a username was submitted
if (empty($credentials['username'])) {
    echo JText::_('NO_USERNAME');
    ob_end_flush();
    $result = false;
    return $result;
}

echo '<h2>' . JText::_('SERVER') . ' ' . JText::_('CONFIGURATION') . '</h2>';

$version =& new JVersion;
$short_version = $version->getShortVersion();

echo '<br/>Joomla version:' . $short_version . '<br/>';
echo 'PHP version: ' . phpversion() .  '<br/>';

$phpinfo = JFusionFunction::phpinfo_array();

echo "System: {$phpinfo['phpinfo']['System']}<br />\n";
echo "MySQL: {$phpinfo['mysql']['Client API version']}<br />\n";
echo "Browser:" . $_SERVER['HTTP_USER_AGENT'] . '<br/>';


//check if the JFusion component is installed
$component_xml = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'com_jfusion.xml';
$auth_xml = JPATH_SITE .DS.'plugins'.DS.'authentication'.DS.'jfusion.xml';
$user_xml = JPATH_SITE .DS.'plugins'.DS.'user'.DS.'jfusion.xml';
$activity_xml = JPATH_SITE .DS.'modules'.DS.'mod_jfusion_activity'.DS.'mod_jfusion_activity.xml';
$login_xml = JPATH_SITE .DS.'modules'.DS.'mod_jfusion_login'.DS.'mod_jfusion_login.xml';

if (file_exists($component_xml)) {
    //get the version number
    $xml = simplexml_load_file($component_xml);
    echo 'JFusion Component Version:' . $xml->version . '<br/>';
    unset($xml);
}
if (file_exists($auth_xml)) {
    //get the version number
    $xml = simplexml_load_file($auth_xml);
    echo 'JFusion Auth Plugin Version:' . $xml->version . '<br/>';
    unset($xml);
}
if (file_exists($user_xml)) {
    //get the version number
    $xml = simplexml_load_file($user_xml);
    echo 'JFusion User Plugin Version:' . $xml->version . '<br/>';
    unset($xml);
}
if (file_exists($activity_xml)) {
    //get the version number
    $xml = simplexml_load_file($activity_xml);
    echo 'JFusion Activity Module Version:' . $xml->version . '<br/>';
    unset($xml);
}
if (file_exists($login_xml)) {
    //get the version number
    $xml = simplexml_load_file($login_xml);
    echo 'JFusion Login Module Version:' . $xml->version . '<br/>';
    unset($xml);
}



//output the current configuration
$db =& JFactory::getDBO();
$query = 'SELECT * from #__jfusion WHERE master = 1 OR slave = 1 or check_encryption = 1 ORDER BY master DESC;';
$db->setQuery($query);
$plugin_list = $db->loadObjectList();

echo '<h2>' . JText::_('JFUSION') . ' ' . JText::_('CONFIGURATION') . '</h2>';
foreach($plugin_list as $plugin_details)
{
    if ($plugin_details->master == 1) {
        echo JText::_('MASTER') . ':'. $plugin_details->name . ' ' .JText::_('VERSION') . ':'. $plugin_details->version . ' ' . JText::_('CHECK_ENCRYPTION') . ':'. $plugin_details->check_encryption . ' ' . JText::_('DUAL_LOGIN') . ':'. $plugin_details->dual_login . '<br/>';
    } else if ($plugin_details->slave == 1) {
        echo JText::_('SLAVE') . ':'. $plugin_details->name . ' ' .JText::_('VERSION') . ':'. $plugin_details->version . ' ' . JText::_('CHECK_ENCRYPTION') . ':'. $plugin_details->check_encryption . ' ' . JText::_('DUAL_LOGIN') . ':'. $plugin_details->dual_login . '<br/>';
    } else {
        echo $plugin_details->name . ' ' .JText::_('VERSION') . ':'. $plugin_details->version . ' ' . JText::_('CHECK_ENCRYPTION') . ':'. $plugin_details->check_encryption . ' ' . JText::_('DUAL_LOGIN') . ':'. $plugin_details->dual_login . '<br/>';
    }
}

/**
* Output the results of the JFusion authentication plugin
*/
// Initialize variables
$conditions = '';
$db =& JFactory::getDBO();


//get the JFusion master
$master = JFusionFunction::getMaster();
$JFusionMaster = JFusionFactory::getUser($master->name);
$userinfo = $JFusionMaster->getUser($credentials['username']);

//check if a user was found
if (!empty($userinfo)) {
    //apply the cleartext password to the user object
    $userinfo->password_clear = $credentials['password'];

    //output the userdetails
    echo '<h2>' . JText::_('MASTER_USER_INFORMATION') . '</h2>';
    echo JText::_('LOGIN') .' ' .JText::_('USERNAME') .': ' . $credentials['username'] .'<br/>';
    echo $master->name . ' ' . JText::_('USERNAME') .': ' . $userinfo->username .'<br/>';
    echo $master->name . ' ' . JText::_('USERID') . ': ' . $userinfo->userid .'<br/>';
    echo $master->name . ' ' . JText::_('NAME') .': ' . $userinfo->name .'<br/>';
    echo $master->name . ' ' . JText::_('PASSWORD') .': ' . substr($userinfo->password,0,6) .'********<br/>';
    echo $master->name . ' ' . JText::_('SALT') .': ' . $userinfo->password_salt,0,4  .'<br/>';
    echo $master->name . ' ' . JText::_('EMAIL') .': ' . $userinfo->email .'<br/>';
    echo $master->name . ' ' . JText::_('BLOCK') .': ' . $userinfo->block .'<br/>';
    echo $master->name . ' ' . JText::_('ACTIVATION') .': ' . $userinfo->activation .'<br/>';

    //get a list of authentication models
    $query = 'SELECT name FROM #__jfusion WHERE master = 1 OR check_encryption = 1 ORDER BY master DESC';
    $db->setQuery($query);
    $auth_models = $db->loadObjectList();

	//see if we need to check password
	if (!$skip_password){
	    //loop through the different models
    	echo '<h3>' . JText::_('PASSWORD') . ' ' . JText::_('CHECK'). '</h3>';
    	$match = null;
    	foreach($auth_models as $auth_model) {
	        //Generate an encrypted password for comparison
    	    $model = JFusionFactory::getAuth($auth_model->name);
        	$testcrypt = $model->generateEncryptedPassword($userinfo);
	        echo $auth_model->name . ' -> ' . substr($testcrypt,0,6) . '********<br/>';
    	    if ($testcrypt == $userinfo->password) {
        	    //found a match
            	$match = $auth_model->name;
	        }
    	}

	    //check to see if the passwords matched
    	if ($match) {
        	echo JText::_('VALID_PASSWORD') . ': ' . $match;
	    } else {
    	    echo JText::_('INVALID_PASSWORD');
        	//no password found: abort the login checker
	        ob_end_flush();
            $result = false;
            return $result;
    	}
	}
} else {
    echo JText::_('USER_NOT_FOUND');
    ob_end_flush();
    $result = false;
    return $result;
}

/**
* Output the results of the JFusion user plugin
*/

echo '<h2>' . JText::_('MASTER') . ' ' . JText::_('USER') . ' ' . JText::_('LOGIN'). '</h2>';

jimport('joomla.user.helper');
global $JFusionActive;
$JFusionActive = true;

//get the JFusion master
$master = JFusionFunction::getMaster();
$JFusionMaster = JFusionFactory::getUser($master->name);
$userinfo = $JFusionMaster->getUser($user['username']);

//apply the cleartext password to the user object
$userinfo->password_clear = $user['password'];

$MasterUser = $JFusionMaster->updateUser($userinfo,0);
if ($MasterUser['error']) {
    JFusionFunction::raiseWarning($master->name . ' ' .JText::_('USER') . ' ' . JText::_('UPDATE'), $MasterUser['error'],0);
} else {
    JFusionFunction::raiseWarning($master->name . ' ' .JText::_('USER') . ' ' . JText::_('UPDATE'), $MasterUser['debug'],0);
}

// See if the user has been blocked or is not activated
if (!empty($userinfo->block) || !empty($userinfo->activation)) {
    echo '<h2>' . JText::_('USER_BLOCKED_INACTIVE') . '</h2>';
    //make sure the block is also applied in slave softwares
    $slaves = JFusionFunction::getSlaves();
    foreach($slaves as $slave) {
        $JFusionSlave = JFusionFactory::getUser($slave->name);
        $SlaveUser = $JFusionSlave->updateUser($userinfo,0);
        if ($SlaveUser['error']) {
            JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['error'],0);
        } else {
            JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['debug'],0);
        }
    }

    if (!empty($userinfo->block)) {
        JFusionFunction::raiseWarning('500', JText::_('FUSION_BLOCKED_USER'),0);
        ob_end_flush();
        $success = false;
        return $success;
    } else {
        JFusionFunction::raiseWarning('500', JText::_('FUSION_INACTIVE_USER'),0);
        ob_end_flush();
        $success = false;
        return $success;
    }
}

if ($master->name != 'joomla_int') {
    //setup the master session
    $MasterSession = $JFusionMaster->createSession($userinfo, $options);
    if ($MasterSession['error']) {
        //report the error back
        JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION').' ' .JText::_('CREATE'), $MasterSession['error'],0);
    } else {
        JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION').' ' .JText::_('CREATE'), $MasterSession['debug'],0);
    }
}

//check to see if we need to setup a Joomla session
if ($master->name != 'joomla_int') {
    //setup the Joomla user
    echo '<h2>' . JText::_('JOOMLA') . ' ' . JText::_('USER') . ' ' . JText::_('LOGIN'). '</h2>';
    $JFusionJoomla = JFusionFactory::getUser('joomla_int');
    $JoomlaUser = $JFusionJoomla->updateUser($userinfo,0);
    if ($JoomlaUser['error']) {
        //no Joomla user could be created, fatal error
        JFusionFunction::raiseWarning('joomla_int: '.' ' .JText::_('USER')  .' ' .JText::_('UPDATE'), $JoomlaUser['error'],0);
        ob_end_flush();
        $success = false;
        return $success;
    } else {

        //output the userdetails
        echo 'joomla_int' . ' ' . JText::_('USERNAME') .': ' . $JoomlaUser['userinfo']->username .'<br/>';
        echo 'joomla_int' . ' ' . JText::_('USERID') . ': ' . $JoomlaUser['userinfo']->userid .'<br/>';
        echo 'joomla_int' . ' ' . JText::_('NAME') .': ' . $JoomlaUser['userinfo']->name .'<br/>';
        echo 'joomla_int' . ' ' . JText::_('BLOCK') .': ' . $JoomlaUser['userinfo']->block .'<br/>';
        echo 'joomla_int' . ' ' . JText::_('ACTIVATION') .': ' . $JoomlaUser['userinfo']->activation .'<br/>';
        JFusionFunction::raiseWarning('joomla_int: '.' ' .JText::_('USER')  .' ' .JText::_('UPDATE'), $JoomlaUser['debug'],0);
        echo JText::_('SKIPPED_SESSION_CREATE').'<br/>';

    }
} else {
    //joomla already setup, we can copy its details from the master
    $JFusionJoomla = $JFusionMaster;
    $JoomlaUser = array('userinfo' => $userinfo, 'error' => '');
}


if ($master->name != 'joomla_int') {
    JFusionFunction::updateLookup($userinfo, $master->name, $JoomlaUser['userinfo']->userid);
}


//setup the other slave JFusion plugins
$slaves = JFusionFunction::getPlugins();
foreach($slaves as $slave) {
    echo '<h2>' . $slave->name . ' ' . JText::_('USER') . ' ' . JText::_('LOGIN'). '</h2>';
    $JFusionSlave = JFusionFactory::getUser($slave->name);
    $SlaveUser = $JFusionSlave->updateUser($userinfo,0);
    if ($SlaveUser['error']) {
        JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('USER') .' ' .JText::_('UPDATE') , $SlaveUser['error'],0);
    } else {
        echo JText::_('USERNAME') .': ' . $SlaveUser['userinfo']->username .'<br/>';
        echo JText::_('USERID') . ': ' . $SlaveUser['userinfo']->userid .'<br/>';
        echo JText::_('NAME') .': ' . $SlaveUser['userinfo']->name .'<br/>';
        echo JText::_('BLOCK') .': ' . $SlaveUser['userinfo']->block .'<br/>';
        echo JText::_('ACTIVATION') .': ' . $SlaveUser['userinfo']->activation .'<br/>';
        JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('USER') .' ' .JText::_('UPDATE') , $SlaveUser['debug'],0);

        //apply the cleartext password to the user object
        $SlaveUser['userinfo']->password_clear = $user['password'];

        JFusionFunction::updateLookup($SlaveUser['userinfo'], $slave->name, $JoomlaUser['userinfo']->userid);

        if (!isset($options['group']) && $slave->dual_login == 1) {
            $SlaveSession = $JFusionSlave->createSession($SlaveUser['userinfo'], $options);
            if ($SlaveSession['error']) {
                JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('SESSION') .' ' .JText::_('CREATE'), $SlaveSession['error'],0);
            } else {
                JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('SESSION') .' ' .JText::_('CREATE'), $SlaveSession['debug'],0);
            }
        }
    }
}


//check to see if we need to debug the logout process
$debug_logout = JRequest::getVar('debug_logout', '', 'POST', 'STRING' );
if ($debug_logout) {
    //get the JFusion master
    $master = JFusionFunction::getMaster();
    if ($master->name && $master->name != 'joomla_int') {
        echo '<h2>' . $master->name . ' ' . JText::_('USER') . ' ' . JText::_('LOGOUT'). ' ' . JText::_('LOGOUT_REFRESH_MESSAGE') . '</h2>';
        $JFusionMaster = JFusionFactory::getUser($master->name);
        $userlookup = JFusionFunction::lookupUser($master->name, $JoomlaUser['userinfo']->userid);
        $MasterUser = $JFusionMaster->getUser($userlookup->username);
        //check if a user was found
        if ($MasterUser) {
            $MasterSession = $JFusionMaster->destroySession($MasterUser, $options);
            if (!empty($MasterSession['error'])) {
                JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'), $MasterSession['error'],0);
            } else {
                JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'), $MasterSession['debug'],0);
            }
        } else {
            JFusionFunction::raiseWarning($master->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'),0);
        }
    }
    $slaves = JFusionFunction::getPlugins();
    foreach($slaves as $slave) {
        //check if sessions are enabled
        if ($slave->dual_login == 1) {
            echo '<h2>' . $slave->name . ' ' . JText::_('USER') . ' ' . JText::_('LOGOUT'). '</h2>';
            $JFusionSlave = JFusionFactory::getUser($slave->name);
            $userlookup = JFusionFunction::lookupUser($slave->name, $JoomlaUser['userinfo']->userid);
            $SlaveUser = $JFusionSlave->getUser($userlookup->username);
            //check if a user was found
            if ($SlaveUser) {
                $SlaveSession = $JFusionSlave->destroySession($SlaveUser, $options);
                if ($SlaveSession['error']) {
                    JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'),$SlaveSession['error'],0);
                } else {
                    JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'),$SlaveSession['debug'],0);
                }
            } else {
                JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'));
            }
        }
    }
}
ob_end_flush();
return;

