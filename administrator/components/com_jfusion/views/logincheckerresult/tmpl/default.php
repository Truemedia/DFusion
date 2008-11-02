<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

//use an output buffer, in order for cookies to be passed onto the header
ob_start();


/**
* Load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
JFusionFunction::displayDonate();

//output the header?>
<table><tr><td width="100px">
<img src="<?php echo 'components/com_jfusion/images/jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components/com_jfusion/images/login_checker2.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('LOGIN_CHECKER_RESULT');
?></h2></td></tr></table><br/>


<?php
//get the submitted login details
$credentials['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$credentials['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$user['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$user['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$options['group'] = 'USERS';
$options['remember'] = 0;


//check to see if a password was submitted
if (empty($credentials['password'])) {
    echo JText::_('NO_PASSWORD');
    ob_end_flush();
    return false;
}

//check to see if a username was submitted
if (empty($credentials['username'])) {
    echo JText::_('NO_USERNAME');
    ob_end_flush();
    return false;
}

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

echo '<h2>' . JText::_('CONFIGURATION_OVERVIEW') . '</h2>';
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

//check to see if a JFusion plugin is enabled
$jname = JFusionFunction::getMaster();

//if no master set then use the Joomla default
if (!$jname->name) {
    $jname->name = 'joomla_int';
}

//initialize the forum object
$JFusionPlugin = JFusionFactory::getUser($jname->name);
//Get the stored encrypted password
$userinfo = $JFusionPlugin->getUser($credentials['username']);

//output the userdetails
echo '<h2>' . JText::_('MASTER_USER_INFORMATION') . '</h2>';
echo JText::_('LOGIN') .' ' .JText::_('USERNAME') .': ' . $credentials['username'] .'<br/>';
echo $jname->name . ' ' . JText::_('USERNAME') .': ' . $userinfo->username .'<br/>';
echo $jname->name . ' ' . JText::_('USERID') . ': ' . $userinfo->userid .'<br/>';
echo $jname->name . ' ' . JText::_('NAME') .': ' . $userinfo->name .'<br/>';
echo $jname->name . ' ' . JText::_('PASSWORD') .': ' . substr($userinfo->password,0,6) .'********<br/>';
echo $jname->name . ' ' . JText::_('SALT') .': ' . substr($userinfo->password_salt,0,4)  .'****<br/>';
echo $jname->name . ' ' . JText::_('EMAIL') .': ' . $userinfo->email .'<br/>';

if ($userinfo) {
    //apply the cleartext password to the user object
    $userinfo->password_clear = $credentials['password'];
    $query = "SELECT name FROM #__jfusion WHERE master = 1 OR check_encryption = 1 ORDER BY master DESC";
    $db->setQuery($query);
    $auth_models = $db->loadObjectList();

    echo '<h2>' . JText::_('AUTHENTICATION_PLUGIN') . '</h2>';
    $match = null;
    foreach($auth_models as $auth_model) {
        //Generate an encrypted password for comparison
        $model = JFusionFactory::getAuth($auth_model->name);
        $testcrypt = $model->generateEncryptedPassword($userinfo);
        echo $auth_model->name . ' -> ' . substr($testcrypt,0,6) . '********<br/>';
        if ($testcrypt == $userinfo->password) {
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
        return false;
    }

    //now handle the user plugin
    echo '<h2>' . JText::_('USER_PLUGIN') . '</h2>';

    jimport('joomla.user.helper');
    global $JFusionActive;
    $JFusionActive = true;

    //filter the username
    $JFusionJoomla = JFusionFactory::getUser('joomla_int');
    $username_clean = $JFusionJoomla->filterUsername($user['username']);

    //get the JFusion master
    $master = JFusionFunction::getMaster();
    $JFusionMaster = JFusionFactory::getUser($master->name);
    $userinfo = $JFusionMaster->getUser($username_clean);

    //output the userdetails
    echo '<h3>' . $master->name . JText::_('USER_DETAILS') . '</h3>';
    echo $master->name . ' ' . JText::_('USERNAME') .': ' . $userinfo->username .'<br/>';
    echo $master->name . ' ' . JText::_('USERID') . ': ' . $userinfo->userid .'<br/>';
    echo $master->name . ' ' . JText::_('NAME') .': ' . $userinfo->name .'<br/>';
    echo $master->name . ' ' . JText::_('BLOCK') .': ' . $userinfo->block .'<br/>';
    echo $master->name . ' ' . JText::_('ACTIVATION') .': ' . $userinfo->activation .'<br/>';
    //apply the cleartext password to the user object
    $userinfo->password_clear = $user['password'];

    $MasterUser = $JFusionMaster->updateUser($userinfo,0);
    if ($MasterUser['error']) {
        foreach ($MasterUser['error'] as $errortext){
        	echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('ERROR'). ':' . $errortext .'<br/>';
        }
    } else {
		foreach ($MasterUser['debug'] as $debugtext) {
	        echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':' . $debugtext .'<br/>';
		}
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
            	foreach ($SlaveUser['error'] as $errortext){
                    echo $slave->name . ' ' . JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('ERROR'). ':' . $errortext .'<br/>';
            	}
            } else {
                echo $slave->name . ' ' . JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ' block:'. $SlaveUser['userinfo']->block . ' activation:' . $SlaveUser['userinfo']->activation . ' '  . print_r($SlaveUser['debug']) .'<br/>';
            }

        }

        if (!empty($userinfo->block)) {
            echo JText::_('FUSION_BLOCKED_USER');
                    ob_end_flush();
            return;
        } else {
            echo JText::_('FUSION_INACTIVE_USER');
                    ob_end_flush();
            return;
        }
    }

    //setup the master session
    if ($master->name != 'joomla_int'){
	    $MasterSession = $JFusionMaster->createSession($userinfo, $options);
    	if ($MasterSession['error']) {
        	//report the error back
	        echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('ERROR'). ':' . $MasterSession['error'] .'<br/>';
    	    if ($master->name == 'joomla_int') {
        	    //we can not tolerate Joomla session failures
            	echo JText::_('FATAL_ERROR');
                    ob_end_flush();
	            return;
    	    }
    	} else {
        	echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('SUCCESS'). ':' . $MasterSession['debug'] .'<br/>';
	    }
    } else {
       	echo JText::_('SKIPPED_SESSION_CREATE').'<br/>';
    }



    //check to see if we need to setup a Joomla session
    if ($master->name != 'joomla_int') {
        //setup the Joomla user
        $JFusionJoomla = JFusionFactory::getUser('joomla_int');
        $JoomlaUser = $JFusionJoomla->updateUser($userinfo,0);

        if ($JoomlaUser['error']) {
            //no Joomla user could be created
            echo 'joomla_int:' . JText::_('USER'). ' '. JText::_('ERROR'). ':' . $JoomlaUser['error'] .'<br/>';
                    ob_end_flush();
            return;
        } else {
		    //output the userdetails
    		echo '<h3>' . 'joomla_int' . JText::_('USER_DETAILS') . '</h3>';
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':' . print_r($JoomlaUser['debug']) .'<br/>';
	    	echo 'joomla_int' . ' ' . JText::_('USERNAME') .': ' . $JoomlaUser['userinfo']->username .'<br/>';
	    	echo 'joomla_int' . ' ' . JText::_('USERID') . ': ' . $JoomlaUser['userinfo']->userid .'<br/>';
    		echo 'joomla_int' . ' ' . JText::_('NAME') .': ' . $JoomlaUser['$userinfo']->name .'<br/>';
    		echo 'joomla_int' . ' ' . JText::_('BLOCK') .': ' . $JoomlaUser['$userinfo']->block .'<br/>';
    		echo 'joomla_int' . ' ' . JText::_('ACTIVATION') .': ' . $JoomlaUser['$userinfo']->activation .'<br/>';
        }
       	echo JText::_('SKIPPED_SESSION_CREATE').'<br/>';

    } else {
        //joomla already setup, we can copy its details from the master
        $JFusionJoomla = $JFusionMaster;
        $JoomlaUser = array('userinfo' => $userinfo, 'error' => '');
    }

    //setup the other slave JFusion plugins
    $slaves = JFusionFunction::getPlugins();
    foreach($slaves as $slave) {
        echo '<h3>' . $slave->name .':' . JText::_('USER_DETAILS') . '</h3>';
        $JFusionSlave = JFusionFactory::getUser($slave->name);
        $SlaveUser = $JFusionSlave->updateUser($userinfo,0);
        if ($SlaveUser['error']) {
			foreach( $SlaveUser['error'] as $errortext){
	            echo JText::_('USER') . JText::_('ERROR'). ':' . $errortext .'<br/>';
			}
        } else {
            foreach ($SlaveUser['debug'] as $debugtext){
	            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':<br/>' . $debugtext .'<br/>';
            }
            echo JText::_('USERNAME') .': ' . $SlaveUser['userinfo']->username .'<br/>';
            echo JText::_('USERID') . ': ' . $SlaveUser['userinfo']->userid .'<br/>';
            echo JText::_('NAME') .': ' . $SlaveUser['userinfo']->name .'<br/>';
            echo JText::_('BLOCK') .': ' . $SlaveUser['userinfo']->block .'<br/>';
            echo JText::_('ACTIVATION') .': ' . $SlaveUser['userinfo']->activation .'<br/>';


            //apply the cleartext password to the user object
            $SlaveUser['userinfo']->password_clear = $user['password'];

            if ($slave->dual_login == 1) {
                $SlaveSession = $JFusionSlave->createSession($SlaveUser['userinfo'], $options);
                if ($SlaveSession['error']) {
                    echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('ERROR'). ':' . $SlaveSession['error'] .'<br/>';
                } else {
                    echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('SUCCESS'). ':' . $SlaveSession['debug'] .'<br/>';
                }
            } else {
                echo JText::_('DUAL_LOGIN'). ' '. JText::_('DISABLED') .'<br/>';
            }
        }
    }
            ob_end_flush();
    return;

} else {
    echo JText::_('NO_USER_FOUND');
            ob_end_flush();
}
