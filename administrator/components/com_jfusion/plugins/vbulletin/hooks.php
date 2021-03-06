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
		if($this->vars=="redirect" && !isset($_GET['noredirect']) && !defined('_JEXEC')) {
			//only redirect if in the main forum
			$s = $_SERVER['PHP_SELF'];

			if(strpos($s,'login.php')===false
			&& strpos($s,'ajax.php')===false
			&& strpos($s,'cron.php')===false
			&& strpos($s,'image.php')===false
			&& strpos($s,'printthread.php')===false
			&& strpos($s,'misc.php')===false
			&& strpos($s,'admincp')===false
			&& strpos($s,'modcp')===false
			&& strpos($s,'archive')===false) {
				$filename = basename($s);
				$query = $_SERVER["QUERY_STRING"];
				if(SEFENABLED) {
					if(SEFMODE==1) {
						$url = JOOMLABASEURL."jfile,$filename/";

						if(!empty($query)) {
							$q = explode('&',$query);
							foreach($q as $k => $v) {
								$url .= "$k,$v/";
							}
						}
					} else {
						$url = JOOMLABASEURL . $filename;
						$url .= (empty($query)) ? '' : "?$query";
					}
				} else {
					$url = JOOMLABASEURL."&jfile={$filename}";
					$url .= (empty($query)) ? '' : "&{$query}";
				}

				header("Location: $url");
				exit;
			}
		}
			
		//add our custom hooks into vbulletin's hook cache
		global $vbulletin;
	
		if ($vbulletin->options['enablehooks'] AND !defined('DISABLE_HOOKS')){
			if (!empty($vbulletin->pluginlist) AND is_array($vbulletin->pluginlist)){
				$vbulletin->pluginlist = array_merge($vbulletin->pluginlist, $this->getHooks($this->vars));
			}
		}
		
		return true;
	}

	function getHooks($plugin)
	{
		//we need to set up the hooks
		if($plugin=="frameless") {
			//retrieve the hooks that jFusion will use to make vB work framelessly
			$hookNames = array("global_start","global_complete","global_setup_complete","header_redirect","redirect_generic","logout_process","member_profileblock_fetch_unwrapped");
		} elseif($plugin=="duallogin") {
			//retrieve the hooks that vBulletin will use to login to Joomla
			$hookNames = array("login_verify_success","logout_process","global_setup_complete");
			define('DUALLOGIN',1);
		} else {
			$hookNames = array();
		}

		$hooks = array();

		foreach($hookNames as $h)
		{
			if($h=="global_complete") $toPass = '$vars =& $output; ';
			elseif($h=="redirect_generic") $toPass = '$vars = array(); $vars["url"] =& $url; $vars["js_url"] =& $js_url; $vars["formfile"] =& $formfile;';
			elseif($h=="header_redirect") $toPass = '$vars =& $url;';
			elseif($h=="member_profileblock_fetch_unwrapped") $toPass = '$vars =& $prepared;';
			else $toPass = '$vars = null;';

			$hooks[$h] = 'include_once(\'' . HOOK_FILE . '\'); ' . $toPass . ' $jFusionHook = new executeJFusionHook(\'' . $h . '\',$vars);';
		}
		return $hooks;
	}

	function global_start()
	{
		//lets rewrite the img urls now while we can
		global $stylevar,$vbulletin;

		//check for trailing slash
		$DS = (substr($vbulletin->options['bburl'], -1) == '/') ? "" : "/";

		foreach($stylevar as $k => $v) {
			if(strstr($k,'imgdir')) {
				$stylevar[$k] = $vbulletin->options['bburl'] . $DS . $v;
			}
		}

		return true;
	}

	function global_setup_complete()
	{
		//If Joomla SEF is enabled, the dash in the logout hash gets converted to a colon which must be corrected
		global $vbulletin,$show,$vbsefenabled,$vbsefmode;
		$vbulletin->GPC['logouthash'] = str_replace(':','-',$vbulletin->GPC['logouthash']);
		
		//if sef is enabled, we need to rewrite the nojs link
		if($vbsefenabled==1) {
			if($vbsefmode==1) {
				$show['nojs_link']  = $_SERVER['REQUEST_URI'];
				$show['nojs_link'] .= (substr($_SERVER['REQUEST_URI'], -1) != '/') ? '/nojs,1/' : 'nojs,1/'; 
			} else {
				$jfile = (JRequest::getVar('jfile',false)) ? JRequest::getVar('jfile') : 'index.php';
				$show['nojs_link'] = "$jfile"."?nojs=1";
			}
		}

		return true;
	}	
	
	function header_redirect()
	{
		//reworks the URL for header redirects ie header('Location: $url');

		if(defined('_JFUSION_DEBUG')) {
			$debug = array();
			$debug['url'] = $this->vars;
			$debug['function'] = 'header_redirect';
		}

		$url = basename($this->vars);

		if (SEFENABLED!='1') {
			//non sef URls
			$url = JOOMLABASEURL . '&amp;jfile=' .$url;
		} else {
			if (SEFMODE==1) {
				$url =  JFusionFunction::routeURL($url, JRequest::getVar('Itemid'));
			} else {
				//we can just append both variables
				$url = JOOMLABASEURL . $url;
			}
		}

		$this->vars = $url;
		
		if(defined('_JFUSION_DEBUG')) {
			$debug['parsed'] = $this->vars;
			$_SESSION["jfvbdebug"][] = $debug;
		}

		return true;
	}

	function redirect_generic()
	{
		//reworks the URL for generic redirects that use JS or html meta header

		if(defined('_JFUSION_DEBUG')) {
			$debug = array();
			$debug['url'] = $this->vars["url"];
			$debug['function'] = 'redirect_generic';
		}

		$url = basename($this->vars["url"]);

		if (SEFENABLED!==1){
			//non sef URls
			$url = JOOMLABASEURL . '&amp;jfile=' .$url;
		} else {
			if (SEFMODE==1) {
				$url =  JFusionFunction::routeURL($url, JRequest::getVar('Itemid'));
			} else {
				//we can just append both variables
				$url = JOOMLABASEURL . $url;
			}
		}

		$this->vars["url"] = $url;
		$this->vars["js_url"] = addslashes_js($this->vars["url"]);
		$this->vars["formfile"] = $this->vars["url"];

		if(defined('_JFUSION_DEBUG')) {
			$debug['parsed'] = $this->vars['url'];
			$_SESSION["jfvbdebug"][] = $debug;
		}

		return true;
	}

	function global_complete()
	{
		global $vbulletin, $vbJname;

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

		
		//we need to update the session table
		$vdb =& JFusionFactory::getDatabase($vbJname);
		if(!empty($vdb)) {
			$vars =& $vbulletin->session->vars;
			if($vbulletin->session->created) {	
				$bypass = ($vars[bypass]) ? 1 : 0; 			
				$query = "INSERT IGNORE INTO #__session 
							(sessionhash, userid, host, idhash, lastactivity, location, styleid, languageid, loggedin, inforum, inthread, incalendar, badlocation, useragent, bypass, profileupdate) VALUES 
							({$vdb->Quote($vars[dbsessionhash])},$vars[userid],{$vdb->Quote($vars[host])},{$vdb->Quote($vars[idhash])},$vars[lastactivity],{$vdb->Quote($vars[location])},$vars[styleid],$vars[languageid],
							$vars[loggedin],$vars[inforum],$vars[inthread],$vars[incalendar],$vars[badlocation],{$vdb->Quote($vars[useragent])},$bypass,$vars[profileupdate])";
			} else {
				$query = "UPDATE #__session SET lastactivty = $vars[lastactivity], inforum = $vars[inforum], inthread = $vars[inthread], incalendar = $vars[incalendar], badlocation = $vars[badlocation]
							WHERE sessionhash = {$vdb->Quote($vars[dbsessionhash])}";
			}
			
			$vdb->setQuery($query);
			$vdb->query();
		}
		
		//echo the output and return an exception to allow Joomla to continue
		echo trim($this->vars,"\n\r\t.");
		Throw new Exception("vBulletin exited.");
	}

	function logout_process()
	{
		global $vbJname;
		
		if(!empty($vbJname)) {
			//we are in frameless mode and need to kill the cookies to prevent getting stuck logged in
			global $vbulletin;

			JFusionFunction::addCookie(COOKIE_PREFIX.'userid' , 0, 0, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], true);
			JFusionFunction::addCookie(COOKIE_PREFIX.'password' , 0, 0, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain'], true);

			//prevent global_complete from recreating the cookies
			$vbulletin->userinfo['userid'] = 0;
			$vbulletin->userinfo['password'] = 0;
		}

		if(defined('DUALLOGIN')) {
			if(!defined('_JEXEC')) {
				$mainframe = $this->startJoomla();
			} else {
				global $mainframe;
				define('_VBULLETIN_JFUSION_HOOK',true);
			}

			// logout any joomla users
			$mainframe->logout();

			// clean up session
			$session =& JFactory::getSession();
			$session->close();
		}

		return true;
	}

	function member_profileblock_fetch_unwrapped()
	{
		global $vbsefmode,$vbsefenabled;
		if($vbsefenabled && $vbsefmode) {
			$uid = JRequest::getVar('u');
			if(!empty($this->vars[profileurl])) $this->vars[profileurl] = str_replace("member.php?u=$uid",'',$this->vars[profileurl]);
		}
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

	function login_verify_success()
	{
		global $vbulletin;

		if(!defined('_JEXEC')) {
			$mainframe = $this->startJoomla();
		} else {
			global $mainframe;
			define('_VBULLETIN_JFUSION_HOOK',true);
		}

		// do the login
		$credentials = array('username' => $vbulletin->userinfo['username'], 'password' => $vbulletin->userinfo['password'],'password_salt' => $vbulletin->userinfo['salt']);
		$options = array('entry_url' => JURI::root().'index.php?option=com_user&task=login');
		$mainframe->login($credentials, $options);

		// clean up the joomla session object before continuing
		$session =& JFactory::getSession();
		$session->close();

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
		jimport('joomla.environment.request');
		jimport('joomla.user.user');
		 
		// JText cannot be loaded with jimport since it's not in a file called text.php but in methods
		JLoader::register('JText' , JPATH_BASE.DS.'libraries'.DS.'joomla'.DS.'methods.php');
		JLoader::register('JRoute' , JPATH_BASE.DS.'libraries'.DS.'joomla'.DS.'methods.php');

		$mainframe = &JFactory::getApplication('site');
		$GLOBALS['mainframe'] =& $mainframe;
		return $mainframe;
	}
}
?>