<?php
/**
 * @package JFusion
 * @subpackage Modules
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Class for the JFusion front-end login module
 * @package JFusion
 */
class modjfusionLoginHelper
{
	function getReturnURL($params, $type)
	{
		if($itemid =  $params->get($type))
		{
			$url = 'index.php?Itemid='.$itemid;
			$url = JRoute::_($url, false);
		}
		else
		{
			// Redirect to login
			$uri = JFactory::getURI();
			$url = $uri->toString();
		}

		return base64_encode($url);
	}

	function getType()
	{
		$user = & JFactory::getUser();
	    return (!$user->get('guest')) ? 'logout' : 'login';
	}

}
