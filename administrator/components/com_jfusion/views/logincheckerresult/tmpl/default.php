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

//output the header?>
<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'login_checker2.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('LOGIN_CHECKER_RESULT'); ?></h2></td></tr></table><br/>


<?php
//get the submitted login details
$credentials['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$credentials['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$user['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$options['group'] = 'USERS';

//check to see if a password was submitted
if (empty($credentials['password'])) {
    echo JText::_('NO_PASSWORD');
    return false;
}

//check to see if a username was submitted
if (empty($credentials['username'])) {
    echo JText::_('NO_USERNAME');
    return false;
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
echo $jname->name . ' ' . JText::_('PASSWORD') .': ' . $userinfo->password .'<br/>';
echo $jname->name . ' ' . JText::_('EMAIL') .': ' . $userinfo->email .'<br/>';

if ($userinfo) {
    //apply the cleartext password to the user object
    $userinfo->password_clear = $credentials['password'];
    $query = "SELECT name FROM #__jfusion WHERE master = 1 OR check_encryption = 1 ORDER BY master DESC";
    $db->setQuery($query);
    $auth_models = $db->loadObjectList();

    echo '<h2>' . JText::_('AUTHENTICATION_PLUGIN') . '</h2>';
    foreach($auth_models as $auth_model) {
        //Generate an encrypted password for comparison
        $model = JFusionFactory::getAuth($auth_model->name);
        $testcrypt = $model->generateEncryptedPassword($userinfo);
        echo $auth_model->name . ' -> ' . $testcrypt . '<br/>';
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
        return false;
    }

    //now handle the user plugin
    echo '<h2>' . JText::_('USER_PLUGIN') . '</h2>';
    if (!$jname->name || $jname->name == 'joomla_int') {
        //use Joomla as the master
        //get the master userinfo
        $JFusionMaster = JFusionFactory::getUser('joomla_int');
        $userinfo = $JFusionMaster->getUser($user['username']);

        //output the userdetails
        echo '<h3>' . 'joomla_int:' . JText::_('USER_DETAILS') . '</h3>';
        echo $jname->name . ' ' . JText::_('USERNAME') .': ' . $userinfo->username .'<br/>';
        echo $jname->name . ' ' . JText::_('USERID') . ': ' . $userinfo->userid .'<br/>';
        echo $jname->name . ' ' . JText::_('NAME') .': ' . $userinfo->name .'<br/>';

        //apply the cleartext password to the user object
        $userinfo->password_clear = $user['password'];
        $joomla_user['userinfo'] = $userinfo;

        //allow for password updates
        $user_update = $JFusionMaster->updateUser($userinfo);
        if ($user_update['error']) {
            //report any errors
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('ERROR'). ':' . $user_update['error'] .'<br/>';
            if ($user_update['userinfo']) {
                //output the details of the conflicting user
                echo JText::_('USER') . ' ' . JText::_('CONFLICT') . '. ' . JText::_('USERNAME'). ':' . $user_update['userinfo']->username . ' ' . JText::_('USERID') . ':' . $user_update['userinfo']->userid . ' ' . JText::_('EMAIL'). ':' . $user_update['userinfo']->email .'<br/>';
            }
        } else {
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':' . $user_update['debug'] .'<br/>';
        }

        //create a Joomla session
        $joomla_session = $JFusionMaster->createSession($userinfo, $options);
        if ($joomla_session['error']) {
            //no Joomla session could be created -> deny login
            echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('ERROR'). ':' . $joomla_session['error'] .'<br/>';
            echo JText::_('FATAL_ERROR');
            return false;
        } else {
            echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('SUCCESS'). ':' . $joomla_session['debug'] .'<br/>';
        }

    } else {
        //a JFusion plugin other than Joomla is the master
        //get the master userinfo
        $JFusionMaster = JFusionFactory::getUser($jname->name);
        $userinfo = $JFusionMaster->getUser($user['username']);

        //output the userdetails
        echo '<h3>' . $jname->name .':' . JText::_('USER_DETAILS') . '</h3>';
        echo JText::_('USERNAME') .': ' . $userinfo->username .'<br/>';
        echo JText::_('USERID') . ': ' . $userinfo->userid .'<br/>';
        echo JText::_('NAME') .': ' . $userinfo->name .'<br/>';

        //apply the cleartext password to the user object
        $userinfo->password_clear = $user['password'];
        //allow for password updates
        $user_update = $JFusionMaster->updateUser($userinfo);
        if ($user_update['error']) {
            //report any errors
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('ERROR'). ':' . $user_update['error'] .'<br/>';
            if ($user_update['userinfo']) {
                //output the details of the conflicting user
                echo JText::_('USER') . ' ' . JText::_('CONFLICT') . '. ' . JText::_('USERNAME'). ':' . $user_update['userinfo']->username . ' ' . JText::_('USERID') . ':' . $user_update['userinfo']->userid . ' ' . JText::_('EMAIL'). ':' . $user_update['userinfo']->email .'<br/>';
            }
        } else {
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':' . $user_update['debug'] .'<br/>';
        }

        //setup the master session
        if ($jname->dual_login == 1) {
            $master_session = $JFusionMaster->createSession($userinfo, $options);
            if ($master_session['error']) {
                //no Joomla session could be created -> deny login
            	echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('ERROR'). ':' . $master_session['error'] .'<br/>';
            	echo JText::_('FATAL_ERROR');
                return false;
            } else {
            echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('SUCCESS'). ':' . $master_session['debug'] .'<br/>';
            }
        }

        //setup the Joomla user
        echo '<h3>' . 'joomla_int:' . JText::_('USER_DETAILS') . '</h3>';
        $joomla_int = JFusionFactory::getUser('joomla_int');
        $joomla_user = $joomla_int->updateUser($userinfo);
        if ($joomla_user['error']) {
            //no Joomla user could be created
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('ERROR'). ':' . $joomla_user['error'] .'<br/>';
            if ($joomla_user['userinfo']) {
                //output the details of the conflicting user
                echo JText::_('USER') . ' ' . JText::_('CONFLICT') . '. ' . JText::_('USERNAME'). ':' . $joomla_user['userinfo']->username . ' ' . JText::_('USERID') . ':' . $joomla_user['userinfo']->userid . ' ' . JText::_('EMAIL'). ':' . $joomla_user['userinfo']->email .'<br/>';
            }
            echo JText::_('FATAL_ERROR');
            return false;
        } else {
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':' . $joomla_user['debug'] .'<br/>';
            echo JText::_('USERNAME') .': ' . $joomla_user['userinfo']->username .'<br/>';
            echo JText::_('USERID') . ': ' . $joomla_user['userinfo']->userid .'<br/>';
            echo JText::_('NAME') .': ' . $joomla_user['userinfo']->name .'<br/>';
        }

        //skip Joomla session
        echo JText::_('SKIPPED_SESSION_CREATE');
    }

    //update the JFusion user lookup table
    //Delete old user data in the lookup table
    $db =& JFactory::getDBO();
    $query = 'DELETE FROM #__jfusion_user WHERE id =' . $joomla_user['userinfo']->userid . ' OR username =' . $db->quote($user['username']);
    $db->setQuery($query);
    $db->query();

    //create a new entry in the lookup table
    $query = 'INSERT INTO #__jfusion_user (id, username) VALUES (' . $joomla_user['userinfo']->userid . ', ' . $db->quote($user['username']) . ')';
    $db->setQuery($query);
    $db->query();

    if ($jname->name != 'joomla_int'){
        JFusionFunction::updateLookup($userinfo, $jname->name, $joomla_user['userinfo']->userid);
    }

    //setup the other slave JFusion plugins
    $plugins = JFusionFunction::getPlugins();
    foreach($plugins as $plugin) {
        echo '<h3>' . $plugin->name .':' . JText::_('USER_DETAILS') . '</h3>';
        $JFusionPlugin = JFusionFactory::getUser($plugin->name);
        $plugin_user = $JFusionPlugin->updateUser($userinfo);
        if ($plugin_user['error']) {
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('ERROR'). ':' . $plugin_user['error'] .'<br/>';
            if ($plugin_user['userinfo']) {
                //output the details of the conflicting user
                echo JText::_('USER') . ' ' . JText::_('CONFLICT') . '. ' . JText::_('USERNAME'). ':' . $plugin_user['userinfo']->username . ' ' . JText::_('USERID') . ':' . $plugin_user['userinfo']->userid . ' ' . JText::_('EMAIL'). ':' . $plugin_user['userinfo']->email .'<br/>';
            }
        } else {
            echo JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' . JText::_('SUCCESS'). ':' . $plugin_user['debug'] .'<br/>';
            echo JText::_('USERNAME') .': ' . $plugin_user['userinfo']->username .'<br/>';
            echo JText::_('USERID') . ': ' . $plugin_user['userinfo']->userid .'<br/>';
            echo JText::_('NAME') .': ' . $plugin_user['userinfo']->name .'<br/>';

            JFusionFunction::updateLookup($plugin_user['userinfo'], $plugin_name, $joomla_user['userinfo']->userid);
            if ($options['group'] != 'Public Backend' && $plugin->dual_login == 1) {
                $session_result = $JFusionPlugin->createSession($plugin_user['userinfo'], $options);
                if ($session_result['error']) {
            		echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('ERROR'). ':' . $session_result['error'] .'<br/>';
                } else {
            		echo JText::_('SESSION'). ' '. JText::_('CREATE') . ' ' . JText::_('SUCCESS'). ':' . $session_result['debug'] .'<br/>';
                }
            }
            //clean up for the next loop
            unset($JFusionPlugin,$plugin_user, $session_result);
        }
    }
    return true;

} else {
    echo JText::_('NO_USER_FOUND');
}