<?php
 /**
 * @version 1.1.0
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

//force required variables into global scope
 $GLOBALS["vbulletin"] =& $vbulletin;
 $GLOBALS["db"] =& $db;

 //function exec_postvar_call_back()

 //execute a hook
 class executeJFusionHook
 {
	var $vars;

	function executeJFusionHook($hook,&$vars)
	{
		//execute the hook
		$this->vars =& $vars;
		eval('$success = $this->'.$hook.'();');
		//if($success) die('<pre>'.print_r($GLOBALS["vbulletin"]->pluginlist,true)."</pre>");
	}

	function init_startup()
	{
		//add our custom hooks into vbulletin's hook cache
		global $vbulletin;

		if ($vbulletin->options['enablehooks'] AND !defined('DISABLE_HOOKS')){
			if (!empty($vbulletin->pluginlist) AND is_array($vbulletin->pluginlist)){
				$vbulletin->pluginlist = array_merge($vbulletin->pluginlist, $this->getHooks($this->vars));
			}
		}

		return true;
	}

 	function getHooks($software)
	{
		//we need to set up the hooks

		//retrieve the hooks that jFusion will use to make vB work framelessly
		if($software=="joomla") $hookNames = array("global_start","global_complete","header_redirect","redirect_generic","logout_process");
		//retrieve the hooks that vBulletin will use to login to Joomla
		elseif($software=="vbulletin") $hookNames = array("login_process","logout_process");

	 	$hooks = array();
	 	if(defined('_JEXEC')) define('HOOK_FILE',JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. _JFUSION_JNAME .DS.'hooks.php');
	 	elseif(defined('HOOK_FILE')) $hookFile = HOOK_FILE;
	 	else die("The jFusion vbulletin hook file was not specified!");

	 	foreach($hookNames as $h)
	 	{
	 		if($h=="global_complete") $toPass = '$vars =& $output; ';
	 		elseif($h=="redirect_generic") $toPass = '$vars = array(); $vars["url"] =& $url; $vars["js_url"] =& $js_url; $vars["formfile"] =& $formfile;';
	 		elseif($h=="header_redirect") $toPass = '$vars =& $url;';
	 		elseif($h=="logout_process") $toPass = '$vars = "' . $software . '";';
			else $toPass = '$vars = null;';

	 		$hooks[$h] = 'include_once(\'' . HOOK_FILE . '\'); ' . $toPass . ' $jFusionHook = new executeJFusionHook(\'' . $h . '\',$vars);';
	 	}
		return $hooks;
	}

	function global_start()
	{
		//lets rewrite the img urls now while we can
		global $stylevar;

		foreach($stylevar as $k => $v)
		{
			if(strstr($k,'imgdir')) {
				$stylevar[$k] = _JFUSION_SOURCE_URL . $v;
			}
		}
	}

	function header_redirect()
 	{
 		//reworks the URL for header redirects ie header('Location: $url');

 		if(defined('_JFUSION_DEBUG')) {
			$debug = array();
			$debug['url'] = $this->vars;
			$debug['function'] = 'header_redirect';
		}
 		//set the jfusion references for Joomla if we do not have a jfusion url
 		if(!strstr($this->vars["url"],'jfile')) {
	 		//set the jfusion references for Joomla
			$Itemid = JRequest::getVar('Itemid');
			if ($Itemid) $query = "index.php?option=com_jfusion&Itemid=$Itemid&jfile=";
			else $query = $query = "index.php?option=com_jfusion&jname=". _JFUSION_JNAME . "&jfile=";

			$vBquery = str_replace(JURI::root(),"",$this->vars);
			$vBquery = str_replace("?","&",$vBquery);

	 		$this->vars = JURI::root().$query.$vBquery;

			if(defined('_JFUSION_DEBUG')) {
				$debug['parsed'] = $this->vars;
			}
 		}

 		if(defined('_JFUSION_DEBUG')) {
			$_SESSION["jfvbdebug"][] = $debug;
		}
 	}

  	function redirect_generic()
 	{
 		//reworks the URL for generic redirects that use JS or html meta header

 		if(defined('_JFUSION_DEBUG')) {
			$debug = array();
			$debug['url'] = $this->vars["url"];
			$debug['function'] = 'redirect_generic';
		}

 		//set the jfusion references for Joomla if we do not have a jfusion url
 		if(!strstr($this->vars["url"],'com_jfusion')) {

			$Itemid = JRequest::getVar('Itemid');
			if ($Itemid) $query = "index.php?option=com_jfusion&Itemid=$Itemid&jfile=";
			else $query = "index.php?option=com_jfusion&jname=". _JFUSION_JNAME . "&jfile=";

			$vBquery = str_replace(JURI::root(),"",$this->vars["url"]);
			$vBquery = str_replace("?","&",$vBquery);

	 		$this->vars["url"] = JRoute::_(JURI::base(). $query . $vBquery);
	 		$this->vars["js_url"] = addslashes_js($this->vars["url"]);
	 		$this->vars["formfile"] = $this->vars["url"];

	 		if(defined('_JFUSION_DEBUG')) {
				$debug['parsed'] = $this->vars['url'];
			}
 		}

 	 	if(defined('_JFUSION_DEBUG')) {
			$_SESSION["jfvbdebug"][] = $debug;
		}
 	}

	function global_complete()
	{
		global $vbulletin;

		//create cookies to allow direct login into vb frameless
		if($vbulletin->userinfo['userid'] != 0 && empty($vbulletin->GPC[COOKIE_PREFIX . 'userid'])) {
			if($vbulletin->GPC['cookieuser']) {
				$expire = 60 * 60 * 24 * 365;
			} else {
				$expire = 0;
			}

			JFusionFunction::addCookie(COOKIE_PREFIX.'userid' , $vbulletin->userinfo['userid'], $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], true);
			JFusionFunction::addCookie(COOKIE_PREFIX.'password' , md5($vbulletin->userinfo['password'] . COOKIE_SALT ), $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], true);
		}

		//echo the output and return an exception to allow Joomla to continue
 		echo trim($this->vars,"\n\r\t.");
 		Throw new Exception("vBulletin exited.");
 	}

 	function logout_process()
 	{
 		$function = 'logout_process_' . $this->vars;
 		eval('$success = $this->$function();');
 		return $success;
 	}

	function logout_process_joomla()
	{
		global $vbulletin;

		//we need to kill the cookies to prevent getting stuck logged in
		JFusionFunction::addCookie(COOKIE_PREFIX.'userid' , 0, 0, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], true);
		JFusionFunction::addCookie(COOKIE_PREFIX.'password' , 0, 0, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], true);

		//prevent global_complete from recreating the cookies
		$vbulletin->userinfo['userid'] = 0;
		$vbulletin->userinfo['password'] = 0;
	}

	/**
	 * This login portion of this script was originally created for phpBB and customized for vBulletin
	 * Original Copyright:
	 * Authentication plug-ins is largely down to Sergey Kanareykin, our thanks to him.
	 * @package login
	 * @version $Id: auth_db.php,v 1.24 2007/10/05 12:42:06 acydburn Exp $
	 * @copyright (c) 2005 phpBB Group
	 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
	 */

	function login_process()
	{
		global $vbulletin;

		if(VB_AREA!='External')
		{
			$mainframe = $this->startJoomla();
			// do the login
			$credentials = array('username' => $vbulletin->userinfo['username'], 'password' => $vbulletin->userinfo['password'],'password_salt' => $vbulletin->userinfo['salt']);
			$options = array('entry_url' => JURI::root().'index.php?option=com_user&task=login');
			$mainframe->login($credentials, $options);

			// clean up the joomla session object before continuing
			$session =& JFactory::getSession();
			$session->close();
		}

		return true;
	}

	function logout_process_vbulletin()
	{
		if(VB_AREA!='External')
		{
		    $mainframe = $this->startJoomla();

		    // logout any joomla users
		    $mainframe->logout();

		    // clean up session
		    $session =& JFactory::getSession();
		    $session->close();

		}

		return true;
	}


	function startJoomla()
	{
		define('_VBULLETIN_JFUSION_HOOK',true);
		define('_JEXEC', true);
		define('DS', DIRECTORY_SEPARATOR);
		if(!defined('JPATH_BASE')) define('JPATH_BASE', '..');

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
}
?>