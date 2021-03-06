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

//please support JFusion
JFusionFunction::displayDonate();

/**
* 	Load debug library
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');

/**
* Output information about the server for future support queries
*/
?>
<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/login_checker2.png" height="75px" width="75px">
<td><h2><?php echo JText::_('LOGIN_CHECKER_RESULT');?></h2></td>
</tr></table>

<div style="border: 0pt none ; margin: 0pt; padding: 0pt 5px; width: 800px; float: left;">

<?php
//get the submitted login details
$credentials['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$credentials['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$user['username'] = JRequest::getVar('check_username', '', 'POST', 'STRING' );
$user['password'] = JRequest::getVar('check_password', '', 'POST', 'STRING' );
$options['group'] = 'USERS';

if(isset($_REQUEST['remember'])){
	$options['remember']= 1;
}
if(isset($_REQUEST['skip_password'])){
	$skip_password = 1;
} else {
	$skip_password = 0;
}
if(isset($_REQUEST['overwrite'])){
	$overwrite = 1;
} else {
	$overwrite = 0;
}

//check to see if a password was submitted
if (empty($credentials['password']) && empty($skip_password)) {
    echo JText::_('NO_PASSWORD');
    echo '</div>';
    ob_end_flush();
    $result = false;
    return $result;
}

//check to see if a username was submitted
if (empty($credentials['username'])) {
    echo JText::_('NO_USERNAME');
    echo '</div>';
    ob_end_flush();
    $result = false;
    return $result;
}

//get server specs
$version =& new JVersion;
$phpinfo = JFusionFunction::phpinfo_array();

//put the relevant specs into an array
$server_info = array();
$server_info['Joomla Version'] = $version->getShortVersion();
$server_info['PHP Version'] = phpversion();
$mysql_version = $phpinfo['mysql']['Client API version'];
if (empty($mysql_version) || strpos($mysql_version,'X')){
	//get the version directly from mySQL
	$db = & JFactory::getDBO();
	$query = 'SELECT version();';
	$db->setQuery($query );
	$mysql_version = $db->loadResult();
}
$server_info['MySQL Version'] = $mysql_version;
$server_info['System Information'] = $phpinfo['phpinfo']['System'];
$server_info['Browser Information'] = $_SERVER['HTTP_USER_AGENT'];

//check to see if JFusion is enabled
$plugin_user = JFusionFunction::isPluginInstalled('jfusion', 'user', 1);
$plugin_auth = JFusionFunction::isPluginInstalled('jfusion', 'authentication', 1);

if ($plugin_user && $plugin_auth){
	$server_info['JFusion User and Auth Plugins'] = JText::_('ENABLED');
} else {
	$server_info['JFusion User and Auth Plugins'] = JText::_('DISABLED');
}
//output the information to the user
debug::show($server_info, JText::_('SERVER') . ' ' . JText::_('CONFIGURATION'),1);

echo '<br/>';

function getVersionNumber($filename, $name, &$jfusion_version){
	if (file_exists($filename)) {
	    //get the version number
		$parser = JFactory::getXMLParser('Simple');
	    $parser->loadFile($filename);
    	$jfusion_version[JText::_('JFUSION') . ' ' .$name. ' ' . JText::_('VERSION')] = ' ' . $parser->document->version[0]->data() . ' ';
    	unset($parser);
	}
}
//get the JFusion version numbers
$jfusion_version = array();
getVersionNumber(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'com_jfusion.xml', JText::_('COMPONENT'), $jfusion_version);
getVersionNumber(JPATH_SITE .DS.'plugins'.DS.'authentication'.DS.'jfusion.xml',JText::_('AUTHENTICATION') .' ' . JText::_('PLUGIN') , $jfusion_version);
getVersionNumber(JPATH_SITE .DS.'plugins'.DS.'user'.DS.'jfusion.xml',JText::_('USER') . ' ' . JText::_('PLUGIN') , $jfusion_version);
getVersionNumber(JPATH_SITE .DS.'modules'.DS.'mod_jfusion_activity'.DS.'mod_jfusion_activity.xml',JText::_('ACTIVITY') . ' ' . JText::_('MODULE') , $jfusion_version);
getVersionNumber(JPATH_SITE .DS.'modules'.DS.'mod_jfusion_login'.DS.'mod_jfusion_login.xml',JText::_('LOGIN') .' ' . JText::_('MODULE') , $jfusion_version);
getVersionNumber(JPATH_SITE .DS.'plugins'.DS.'search'.DS.'jfusion.xml',JText::_('SEARCH') .' ' . JText::_('PLUGIN'),$jfusion_version);
getVersionNumber(JPATH_SITE .DS.'plugins'.DS.'content'.DS.'jfusion.xml',JText::_('DISCUSSION') .' ' . JText::_('PLUGIN'),$jfusion_version);

//output the information to the user
debug::show($jfusion_version, JText::_('JFUSION') . ' ' . JText::_('VERSIONS'),1);

echo '<br/>';

//output the current configuration
$db =& JFactory::getDBO();
$query = 'SELECT * from #__jfusion WHERE master = 1 OR slave = 1 or check_encryption = 1 ORDER BY master DESC;';
$db->setQuery($query);
$plugin_list = $db->loadObjectList();

foreach($plugin_list as $plugin_details)
{
	 $plugin = new stdClass;
	 $plugin->configuration = new stdClass;
	 $plugin->configuration->master = $plugin_details->master;
	 $plugin->configuration->slave = $plugin_details->slave;
	 $plugin->configuration->dual_login = $plugin_details->dual_login;
	 $plugin->configuration->check_encryption = $plugin_details->check_encryption;
	 debug::show($plugin, JText::_('JFUSION') . ' ' . $plugin_details->name . ' ' . JText::_('PLUGIN'),1);
}

/**
* Output the results of the JFusion authentication plugin
*/

// Initialize variables
$conditions = '';
$db =& JFactory::getDBO();


//get the JFusion master
$master = JFusionFunction::getMaster();
if(!empty($master)) {
	$JFusionMaster = JFusionFactory::getUser($master->name);
	$userinfo = $JFusionMaster->getUser($credentials['username']);
} else {
	$userinfo = '';
}

//check if a user was found
if (!empty($userinfo)) {
    //output the userdetails
    echo '<br/><h2>' . JText::_('MASTER'). ' ' . JText::_('JFUSION') . ' ' . JText::_('PLUGIN') . ' ' . JText::_('LOGIN') .': ' . $master->name . '</h2>';
    debug::show($credentials['username'], JText::_('LOGIN') . ' ' . JText::_('USERNAME'),1);

	//hide some sensitive details for output
	if(isset($userinfo->password)){
		$userinfo_password = $userinfo->password;
		$userinfo->password = substr($userinfo->password,0,6) .'********';
	}
	if(isset($userinfo->password_salt)){
		$userinfo_password_salt = $userinfo->password_salt;
		$userinfo->password_salt = substr($userinfo->password_salt,0,6) .'********';
	}

    debug::show($userinfo, $master->name . ' ' . JText::_('USER'). ' ' . JText::_('INFORMATION'),1);

    //restore the sensitive information
	if(isset($userinfo_password)){
		$userinfo->password = $userinfo_password;
	}
	if(isset($userinfo_password_salt)){
		$userinfo->password_salt = $userinfo_password_salt;
	}

    //get a list of authentication models
    $query = 'SELECT name FROM #__jfusion WHERE master = 1 OR check_encryption = 1 ORDER BY master DESC';
    $db->setQuery($query);
    $auth_models = $db->loadObjectList();

    //apply the cleartext password to the user object
    $userinfo->password_clear = $credentials['password'];

	//see if we need to check password
	if (!$skip_password){
	    //loop through the different models
    	$match = null;
    	$auth_result = array();
    	foreach($auth_models as $auth_model) {
	        //Generate an encrypted password for comparison
    	    $model = JFusionFactory::getAuth($auth_model->name);
        	$testcrypt = $model->generateEncryptedPassword($userinfo);
    	    if ($testcrypt == $userinfo->password) {
        	    //found a match
            	$match = $auth_model->name;
	        }
	        $auth_result[$auth_model->name] =  substr($testcrypt,0,6) . '********';
    	}

    	debug::show($auth_result, JText::_('PASSWORD') . ' ' . JText::_('CHECK'),1);


	    //check to see if the passwords matched
    	if ($match) { ?>
			<table bgcolor="#d9f9e2" width ="100%"><tr style="height:30px"><td width="50px">
			<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px"></td>
			<td><font size="2"><b><?php echo JText::_('VALID_PASSWORD') . ': ' . JText::_('JFUSION') . ' ' . $match; ?></b></font></td></tr></table>
  <?php  } else { ?>
			<table bgcolor="#f9ded9" width ="100%"><tr style="height:30px"><td width="50px">
			<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px"></td>
			<td><font size="2"><b><?php echo JText::_('INVALID_PASSWORD'); ?></b></font></td></tr></table>
  <?php
        	//no password found: abort the login checker
        	echo '</div>';
	        ob_end_flush();
            $result = false;
            return $result;
    	}
	}
} else {
    echo JText::_('USER_NOT_FOUND');
    echo '</div>';
    ob_end_flush();
    $result = false;
    return $result;
}

/**
* Output the results of the JFusion user plugin
*/
jimport('joomla.user.helper');
global $JFusionActive;
$JFusionActive = true;

//get the JFusion master
$master = JFusionFunction::getMaster();
if(!empty($master)) {
	$JFusionMaster = JFusionFactory::getUser($master->name);
	$userinfo = $JFusionMaster->getUser($user['username']);
} else {
	$userinfo = '';
}
//apply the cleartext password to the user object if set
if(!empty($user['password'])){
	$userinfo->password_clear = $user['password'];
}

$MasterUser = $JFusionMaster->updateUser($userinfo,$overwrite);
if ($MasterUser['error']) {
	debug::show($MasterUser['error'], JText::_('MASTER') . ' ' . JText::_('USER') . ' ' . JText::_('UPDATE'). ' ' . JText::_('ERROR'),1);
	debug::show($MasterUser['debug'], JText::_('MASTER') . ' ' . JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' .JText::_('DEBUG'),1);
} else {
	debug::show($MasterUser['debug'], JText::_('MASTER') . ' ' . JText::_('USER') . ' ' . JText::_('UPDATE') . ' ' .JText::_('DEBUG'),1);
}

// See if the user has been blocked or is not activated
if (!empty($userinfo->block) || !empty($userinfo->activation)) {
    echo '<h2>' . JText::_('USER_BLOCKED_INACTIVE') . '</h2>';
    //make sure the block is also applied in slave softwares
    $slaves = JFusionFunction::getSlaves();
    foreach($slaves as $slave) {
        $JFusionSlave = JFusionFactory::getUser($slave->name);
        $SlaveUser = $JFusionSlave->updateUser($userinfo,$overwrite);
        if ($SlaveUser['error']) {
            debug::show($SlaveUser['error'], $slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'),1);
        } else {
            debug::show($SlaveUser['debug'], $slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), 1);
        }
    }

    if (!empty($userinfo->block)) {
        JFusionFunction::raiseWarning('500', JText::_('FUSION_BLOCKED_USER'),0);
        echo '</div>';
        ob_end_flush();
        $success = false;
        return $success;
    } else {
        JFusionFunction::raiseWarning('500', JText::_('FUSION_INACTIVE_USER'),0);
        echo '</div>';
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
        debug::show($MasterSession['error'], $master->name .' ' .JText::_('SESSION').' ' .JText::_('ERROR'), 1);
        debug::show($MasterSession['debug'],$master->name .' ' .JText::_('SESSION').' ' .JText::_('DEBUG'), 1);
    } else {
        debug::show($MasterSession['debug'],$master->name .' ' .JText::_('SESSION').' ' .JText::_('DEBUG'), 1);
    }
} else {
        debug::show(JText::_('SKIPPED_SESSION_CREATE'),'joomla_int' .' ' .JText::_('SESSION').' ' .JText::_('CREATE'), 1);

}

//check to see if we need to setup a Joomla session
if ($master->name != 'joomla_int') {
    //setup the Joomla user
    echo '<br/><h2>' . JText::_('SLAVE'). ' ' . JText::_('JFUSION') . ' ' . JText::_('PLUGIN') . ' ' . JText::_('LOGIN') .': joomla_int</h2>';
    $JFusionJoomla = JFusionFactory::getUser('joomla_int');
    $JoomlaUser = $JFusionJoomla->updateUser($userinfo,$overwrite);
    if ($JoomlaUser['error']) {
        //no Joomla user could be created, fatal error
        debug::show($JoomlaUser['error'], 'joomla_int: '.' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('ERROR'), 0);
        debug::show($JoomlaUser['debug'], 'joomla_int: '.' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);
        echo '</div>';
        ob_end_flush();
        $success = false;
        return $success;
    } else {

	//hide some sensitive details for output
	$userinfo_password = $JoomlaUser['userinfo']->password;
	$userinfo_password_salt = $JoomlaUser['userinfo']->password_salt;
	$JoomlaUser['userinfo']->password = substr($JoomlaUser['userinfo']->password,0,6) .'********';
	$JoomlaUser['userinfo']->password_salt = substr($JoomlaUser['userinfo']->password_salt,0,6) .'********';
    debug::show($JoomlaUser['userinfo'], 'joomla_int ' . JText::_('USER'). ' ' . JText::_('INFORMATION'),1);

    //restore the sensitive information
	$JoomlaUser['userinfo']->password = $userinfo_password;
	$JoomlaUser['userinfo']->password_salt = $userinfo_password_salt;

        debug::show($JoomlaUser['debug'], 'joomla_int: '.' ' .JText::_('USER')  .' ' .JText::_('UPDATE') .' ' .JText::_('DEBUG'), 0);
        debug::show(JText::_('SKIPPED_SESSION_CREATE'),'joomla_int' .' ' .JText::_('SESSION').' ' .JText::_('CREATE'), 1);

    }
} else {
    //joomla already setup, we can copy its details from the master
    $JFusionJoomla = $JFusionMaster;
    $JoomlaUser = array('userinfo' => $userinfo, 'error' => '');
}


if ($master->name != 'joomla_int') {
    JFusionFunction::updateLookup($userinfo, $JoomlaUser['userinfo']->userid, $master->name);
}


//setup the other slave JFusion plugins
$slaves = JFusionFunction::getPlugins();
foreach($slaves as $slave) {
    echo '<br/><h2>' . JText::_('SLAVE'). ' ' . JText::_('JFUSION') . ' ' . JText::_('PLUGIN') . ' ' . JText::_('LOGIN') .': '. $slave->name . '</h2>';
    $JFusionSlave = JFusionFactory::getUser($slave->name);
    $SlaveUser = $JFusionSlave->updateUser($userinfo,$overwrite);
    if ($SlaveUser['error']) {
        debug::show($SlaveUser['error'], $slave->name.' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('ERROR'), 0);
        debug::show($SlaveUser['debug'], $slave->name.' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);
       	//output the conflicting user information

			unset($SlaveUser['userinfo']->password_clear);
			if (isset($SlaveUser['userinfo']->password)){
				$SlaveUser['userinfo']->password = substr($SlaveUser['userinfo']->password,0,6) .'********';
			}
			if (isset($SlaveUser['userinfo']->password_salt)){
				$SlaveUser['userinfo']->password_salt = substr($SlaveUser['userinfo']->password_salt,0,6) .'********';
			}

    		debug::show($SlaveUser['userinfo'], $slave->name . ' ' .JText::_('CONFLICT'). ' ' . JText::_('USER'). ' ' . JText::_('INFORMATION'),1);

    } else {

		//hide some sensitive details for output
		unset($SlaveUser['userinfo']->password_clear);
		$userinfo_password = $SlaveUser['userinfo']->password;
		$userinfo_password_salt = $SlaveUser['userinfo']->password_salt;
		$SlaveUser['userinfo']->password = substr($SlaveUser['userinfo']->password,0,6) .'********';
		$SlaveUser['userinfo']->password_salt = substr($SlaveUser['userinfo']->password_salt,0,6) .'********';
    	debug::show($SlaveUser['userinfo'], $slave->name . ' ' . JText::_('USER'). ' ' . JText::_('INFORMATION'),1);

	    //restore the sensitive information
		$SlaveUser['userinfo']->password = $userinfo_password;
		$SlaveUser['userinfo']->password_salt = $userinfo_password_salt;
        debug::show($SlaveUser['debug'], $slave->name.' ' .JText::_('USER')  .' ' .JText::_('UPDATE').' ' .JText::_('DEBUG'), 0);

		//apply the cleartext password to the user object if set
		if(!empty($user['password'])){
	        $SlaveUser['userinfo']->password_clear = $user['password'];
		}

        JFusionFunction::updateLookup($SlaveUser['userinfo'], $JoomlaUser['userinfo']->userid, $slave->name);

        if ($slave->dual_login == 1) {
            $SlaveSession = $JFusionSlave->createSession($SlaveUser['userinfo'], $options);
            if ($SlaveSession['error']) {
    		    debug::show($SlaveSession['error'], $slave->name .' ' .JText::_('SESSION').' ' .JText::_('ERROR'), 1);
	        	debug::show($SlaveSession['debug'],$slave->name .' ' .JText::_('SESSION').' ' .JText::_('DEBUG'), 1);
            } else {
	        	debug::show($SlaveSession['debug'],$slave->name .' ' .JText::_('SESSION').' ' .JText::_('DEBUG'), 1);
            }
        }
    }
}

//create a link to test out the logout function
?>
<br/><br/>
<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="logoutcheckerresult" />

<input type="hidden" name="JoomlaId" value="<?php echo $JoomlaUser['userinfo']->userid;?>">
<input type="submit" value="Debug the Logout Function">
</form>

</div>
<?php
ob_end_flush();
return;

