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

 		require_once(DIR . '/includes/class_hook.php');
		$hookobj =& vBulletinHook::init();
		if ($vbulletin->options['enablehooks'] AND !defined('DISABLE_HOOKS'))
		{
			if (!empty($vbulletin->pluginlist) AND is_array($vbulletin->pluginlist))
			{
				$vbulletin->pluginlist = array_merge($vbulletin->pluginlist, $this->getHooks($this->vars));
			}
			$hookobj->set_pluginlist($vbulletin->pluginlist);
		}
		unset($hookobj);

		return true;

	}

 	function getHooks($software)
	{
		//we need to set up the hooks

		//retrieve the hooks that jFusion will use to make vB work framelessly
		if($software=="joomla") $hookNames = array("global_complete","header_redirect","redirect_generic");
		//retrieve the hooks that vBulletin will use to login to Joomla
		elseif($software=="vbulletin") $hookNames = array("login_process","logout_process");

	 	$hooks = array();
	 	if(defined('_JEXEC')) $hookFile = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. _JFUSION_JNAME .DS.'hooks.php';
	 	elseif(defined('HOOK_FILE')) $hookFile = HOOK_FILE;
	 	else die("The jFusion vbulletin hook file was not specified!");

	 	foreach($hookNames as $h)
	 	{
	 		if($h=="global_complete") $toPass = '$vars =& $output; ';
	 		elseif($h=="redirect_generic") $toPass = '$vars = array(); $vars["url"] =& $url; $vars["js_url"] =& $js_url; $vars["formfile"] =& $formfile;';
	 		elseif($h=="header_redirect") $toPass = '$vars =& $url;';
			else $toPass = '$vars = null;';

	 		$hooks[$h] = 'include_once(\'' . $hookFile . '\'); ' . $toPass . ' $jFusionHook = new executeJFusionHook(' . $h . ',$vars);';
	 	}
		return $hooks;
	}

	function header_redirect()
 	{
 		//reworks the URL for header redirects ie header('Location: $url');

 		//set the jfusion references for Joomla
		$Itemid = JRequest::getVar('Itemid');
		if ($Itemid) $query = "index.php?option=com_jfusion&Itemid=$Itemid&jfile=";
		else $query = $query = "index.php?option=com_jfusion&jname=". _JFUSION_JNAME . "&jfile=";

		$vBquery = str_replace(JURI::root(),"",$this->vars);
		$vBquery = str_replace("?","&",$vBquery);

 		$this->vars = JURI::root().$query.$vBquery;
 	}

  	function redirect_generic()
 	{
 		//reworks the URL for generic redirects that use JS or html meta header

 		//set the jfusion references for Joomla
		$Itemid = JRequest::getVar('Itemid');
		if ($Itemid) $query = "index.php?option=com_jfusion&Itemid=$Itemid&jfile=";
		else $query = $query = "index.php?option=com_jfusion&jname=". _JFUSION_JNAME . "&jfile=";

 		$this->vars["url"] = str_replace(JURI::root(),"",$this->vars["url"]);

 		$vBquery = str_replace(JURI::root(),"",$this->vars["url"]);
		$vBquery = str_replace("?","&",$vBquery);

 		$this->vars["url"] = JURI::root().$query.$vBquery;
 		$this->vars["js_url"] = addslashes_js($this->vars["js_url"]);
 		$this->vars["formfile"] = $this->vars["url"];
 	}

	function global_complete()
	{
		//echo the output and return an exception to allow Joomla to continue
 		echo trim($this->vars,"\n\r\t.");
 		Throw new Exception("vBulletin exited.");
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

	function logout_process()
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