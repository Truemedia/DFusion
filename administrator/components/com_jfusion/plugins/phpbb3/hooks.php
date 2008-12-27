<?php

/**
 * @package JFusion_phpBB3
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Function that registers the JFusion phpBB3 hooks
*/
function phpbb_hook_register(&$hook)
{
	global $phpbb_root_path, $phpEx, $db, $mainframe, $config, $user;


	//Register the hooks
	foreach($hook->hooks as $definition => $hooks)
	{
		foreach($hooks as $function => $data)
		{
			$callback = $definition == '__global' ? $function : $definition.'_'.$function;
			$hook->register(array($definition, $function), array('JFusionHook', $callback));
		}
	}
}

/**
 * JFusion Hooks for phpBB3
 * @package JFusion_phpBB3
 */
class JFusionHook
{
	/**
	* Throws an exeption at the end of the phpBB3 execution to return to JFusion
	*/
	function exit_handler($hook)
	{
               //throw an exception to allow Joomla to continue
               throw new Exception('phpBB exited.');
	}

	/**
	* Function that parses the phpBB3 URLs
	*/
	function append_sid($hook, $url, $params = false, $is_amp = true, $session_id = false)
	{
		global $_SID, $_EXTRA_URL;

		$arrParams = array();
		$arrExtra  = array();
		$anchor    = '';

		// Assign sid if session id is not specified
		if ($session_id === false) {
			$session_id = $_SID;
		}

		//Clean the url and the params first
		if($is_amp)
		{
			$url  = str_replace( '&amp;', '&', $url );
			if(!is_array($params)) {
				$params = str_replace( '&amp;', '&', $params );
			}
		}

		// Process the parameters array
		if (is_array($params))
		{
			foreach ($params as $key => $item)
			{
				if ($item === NULL)
				{
					continue;
				}

				if ($key == '#')
				{
					$anchor = '#' . $item;
					continue;
				}

				$arrParams[$key] = $item;
			}
		}
		else
		{
			if(strpos($params, '#') !== false)
			{
				list($params, $anchor) = explode('#', $params, 2);
				$anchor = '#' . $anchor;
			}

			parse_str($params, $arrParams);
		}

		//Process the extra array
		if(!empty($_EXTRA_URL))
		{
			$extra_url = str_replace( '&amp;', '&', $_EXTRA_URL);
			$extra = implode('&', $extra_url);
			parse_str($extra, $arrExtra);
		}

		//Create the URL
		$uri = new JURI($url);

		$query = $uri->getQuery(true);
		$query = $query + $arrParams + $arrExtra;

		$uri->setQuery($query);

		//Set session id variable
		if($session_id) {
			$uri->setVar('sid', $session_id);
		}

		//Set fragment
		if($anchor) {
			$uri->setFragment($anchor);
		}

		if(strpos($url, 'jfile=')) {
            $url = preg_replace('#.*jfile=(.*?\.php).*#mS', '$1', $url);
            return $url;
		}

		$view = basename($url);

		//add an excemption for the admincp
		if(strpos($url, 'adm')) {
	        global $jfusion_source_url;
	        return $jfusion_source_url . 'adm/index.php?sid=' . $session_id;
		}

		//add an excemption for editing profiles
		if($arrParams['i'] == 'profile' || $arrParams['i'] == 'prefs' ||$arrParams['i'] == 'zebra') {
				$view = 'ucp.php';
		}

		//set the jfile param if needed
        if(!empty($view)){
			$uri->setVar('jfile', $view);
        }

        //set the jfusion references for Joomla
        $Itemid = JRequest::getVar('Itemid');
        if ($Itemid){
			$uri->setVar('Itemid', $Itemid);
        }
		$uri->setVar('option', 'com_jfusion');

		$url = urldecode('index.php'.$uri->toString(array('query', 'fragment')));

		return urldecode(JRoute::_($url, $is_amp));
	}

	/**
	* Function not implemented
	*/
	function phpbb_user_session_handler($hook)
	{
	}

	/**
	* Function not implemented
	*/
	function template_display($hook, $handle, $include_once = true)
	{
	}

	/**
	* Function not implemented
	*/
	function msg_handler($errno, $msg_text, $errfile, $errline) {
		msg_handler($errno, $msg_text, $errfile, $errline);
	}
}

