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


function append_sid($hook, $url, $params = false, $is_amp = true, $session_id = false)
{
	global $_SID, $_EXTRA_URL, $phpbb_hook;

	$params_is_array = is_array($params);

	// Get anchor
	$anchor = '';
	if (strpos($url, '#') !== false)
	{
		list($url, $anchor) = explode('#', $url, 2);
		$anchor = '#' . $anchor;
	}
	else if (!$params_is_array && strpos($params, '#') !== false)
	{
		list($params, $anchor) = explode('#', $params, 2);
		$anchor = '#' . $anchor;
	}

	// Handle really simple cases quickly
	if ($_SID == '' && $session_id === false && empty($_EXTRA_URL) && !$params_is_array && !$anchor)
	{
		if ($params === false)
		{
			return $url;
		}

		$url_delim = (strpos($url, '?') === false) ? '?' : (($is_amp) ? '&amp;' : '&');
		return $url . ($params !== false ? $url_delim. $params : '');
	}

	// Assign sid if session id is not specified
	if ($session_id === false)
	{
		$session_id = $_SID;
	}

	$amp_delim = ($is_amp) ? '&amp;' : '&';
	$url_delim = (strpos($url, '?') === false) ? '?' : $amp_delim;

	// Appending custom url parameter?
	$append_url = (!empty($_EXTRA_URL)) ? implode($amp_delim, $_EXTRA_URL) : '';

	// Use the short variant if possible ;)
	if ($params === false)
	{
		// Append session id
		if (!$session_id)
		{
			return $url . (($append_url) ? $url_delim . $append_url : '') . $anchor;
		}
		else
		{
			return $url . (($append_url) ? $url_delim . $append_url . $amp_delim : $url_delim) . 'sid=' . $session_id . $anchor;
		}
	}

	// Build string if parameters are specified as array
	if (is_array($params))
	{
		$output = array();

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

			$output[] = $key . '=' . $item;
		}

		$params = implode($amp_delim, $output);
	}

	// Append session id and parameters (even if they are empty)
	// If parameters are empty, the developer can still append his/her parameters without caring about the delimiter
	return $url . (($append_url) ? $url_delim . $append_url . $amp_delim : $url_delim) . $params . ((!$session_id) ? '' : $amp_delim . 'sid=' . $session_id) . $anchor;
}

	/**
	* Function that parses the phpBB3 URLs
	*/
	function append_sid_old($hook, $url, $params = false, $is_amp = true, $session_id = false)
	{

		global $_SID, $_EXTRA_URL;
		$arrParams = array();
		$arrExtra  = array();
		$anchor    = '';

      	//JError::raiseWarning(500, '1:'. htmlentities(print_r($url, true)) . ' AND ' .htmlentities(print_r($params, true)) . ' AND ' . htmlentities(print_r($_EXTRA_URL, true)));

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

		$view = basename($url);

		if ($view == 'posting.php' && $arrParams['mode'] == 'popup'){
			global $jfusion_source_url;
			return $jfusion_source_url . 'posting.php' .$uri->toString(array('query', 'fragment'));
		}

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
		//phpbb 3.0.4 now can handle SEF urls, return a non-sef url if it contains a bracket
		if (strpos($url,'{')){
			$url = JURI::Base() . $url;
		} else {
			$url = JRoute::_($url, $is_amp);
		}
		//JError::raiseWarning(500, '2: ' . htmlentities(print_r($url, true)) );
		return $url;
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

