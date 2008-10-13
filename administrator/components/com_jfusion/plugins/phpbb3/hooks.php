<?php

//add the phpBB3 exit hook
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

class JFusionHook
{
	function exit_handler($hook)
	{
               //throw an exception to allow Joomla to continue
               throw new Exception('phpBB exited.');
	}

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
		}

		$view = basename($url);

		//add an excemption for the admincp
		if(strpos($url, 'adm')) {
				$view = 'adm';
		}


		//add an excemption for editing profiles
		if($arrParams['i'] == 'profile') {
				$view = 'ucp.php';
		}


		$uri->setVar('jfile', $view);

		$url = 'index.php'.$uri->toString(array('query', 'fragment'));

		return JRoute::_($url, $is_amp);
	}

	function phpbb_user_session_handler($hook)
	{
	}

	function template_display($hook, $handle, $include_once = true)
	{
	}

	function msg_handler($errno, $msg_text, $errfile, $errline) {
		msg_handler($errno, $msg_text, $errfile, $errline);
	}


}

