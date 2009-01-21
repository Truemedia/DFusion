<?php
/**
* @package JFusion_phpBB3
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
    exit;
}

/**
 * Login function
 */
function login_jfusion(&$username, &$password)
{
    require_once('auth_db.php');
    $result = login_db($username, $password);

    //check to see if login succesful and jFusion is not active
	global $JFusionActive;
    if ($result['status'] == LOGIN_SUCCESS && empty($JFusionActive))
    {
        $mainframe = startJoomla();

        //define that the phpBB3 JFusion plugin needs to be excluded
        global $JFusionActivePlugin;
        $JFusionActivePlugin = 'phpbb3';

        // do the login
        $credentials = array('username' => $username, 'password' => $password);
        $options = array('entry_url' => JURI::root().'index.php?option=com_user&task=login');
        $mainframe->login($credentials, $options);

        // clean up the joomla session object before continuing
        $session =& JFactory::getSession();
        $session->close();
    }

    return $result;
}

function logout_jfusion(&$data)
{
    //check to see if JFusion is not active
	global $JFusionActive;
    if (empty($JFusionActive))
    {
        //define that the phpBB3 JFusion plugin needs to be excluded
        global $JFusionActivePlugin;
        $JFusionActivePlugin = 'phpbb3';

        $mainframe = startJoomla();

        // logout any joomla users
        $mainframe->logout();

        // clean up session
        $session =& JFactory::getSession();
        $session->close();
    }
}

function startJoomla()
{
    global $phpbb_root_path;

    // trick joomla into thinking we're running through joomla
    define('_JEXEC', true);
    define('DS', DIRECTORY_SEPARATOR);
    define('JPATH_BASE', $phpbb_root_path.DS.'..');

    // load joomla libraries
    require_once(JPATH_BASE.DS.'includes'.DS.'defines.php' );
    require_once(JPATH_LIBRARIES.DS.'loader.php');
    jimport('joomla.base.object');
    jimport('joomla.factory');
    jimport('joomla.filter.filterinput');
    jimport('joomla.error.error');
    jimport('joomla.event.dispatcher');
    jimport('joomla.plugin.helper');
    jimport('joomla.utilities.arrayhelper');
    jimport('joomla.environment.uri');
    jimport('joomla.user.user');
    // JText cannot be loaded with jimport since it's not in a file called text.php but in methods
    JLoader::register('JText' , JPATH_BASE.DS.'libraries'.DS.'joomla'.DS.'methods.php');

    $mainframe = &JFactory::getApplication('site');
    $GLOBALS['mainframe'] =& $mainframe;
    return $mainframe;
}