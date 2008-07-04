<?php

/**
 * @package JFusion_IPB
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');

/**
 * JFusion plugin class for IPB
 * @package JFusion_IPB
 */
class JFusionUser_ipb extends JFusionUser
{

    function updateUser($userinfo)
    {
        // Initialise some variables
        $db = JFusionFactory::getDatabase($this->getJname());
        $status = array();

        //find out if the user already exists
        $userlookup = $this->getUser($userinfo->username);
      	if ($userlookup->email == $userinfo->email) {
        	//emails match up
            $status['userinfo'] = $userlookup;
        	$status['error'] = false;
        	$status['debug'] = JText::_('USER_EXISTS');
            return $status;
	    } elseif ($userlookup) {
        	//emails match up
            $status['userinfo'] = $userlookup;
        	$status['error'] = JText::_('EMAIL_CONFLICT');
            return $status;
	    } else {
            $status['userinfo'] = $userlookup;
        	$status['error'] = JText::_('UNABLE_CREATE_USER');
            return $status;
	    }
    }

    function &getUser($username)
    {
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());

        // Get user info from database
        $query = 'SELECT b.id as userid, b.name as username, email as email, members_display_name as name, temp_ban as block, converge_pass_hash as password, converge_pass_salt as password_salt FROM #__members_converge as a INNER JOIN #__members as b ON a.converge_email = b.email WHERE b.name = ' . $db->Quote($username);
        $db->setQuery($query);
        $result = $db->loadObject();

        return $result;


    }

    function getJname()
    {
        return 'ipb';
    }

    function destroySession($userinfo, $options)
    {
        // Get needed params
        //get the cookie parameters
        $params = JFusionFactory::getParams($this->getJname());
        $status = array();
        $status['debug'] = '';
        $expires = time() - 60*60*24*365;
        $prefix = $params->get('cookie_prefix');
        $path   = $params->get('cookie_path', '/');
        $domain = $params->get('cookie_domain');

        // Destroy cookies
        setcookie($prefix . 'member_id', '0', $expires, $path, $domain);
        setcookie($prefix . 'pass_hash', '0', $expires, $path, $domain);
        setcookie($prefix . 'session_id', '', $expires, $path, $domain);
        if ($params->get('ipb_cookie_stronghold'))
        {
            setcookie($prefix . $params->get('ipb_cookie_stronghold'), '', $expires, $path, $domain);
        }
        return $status;
    }

    function createSession($userinfo, $options)
    {
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());
        $params = JFusionFactory::getParams($this->getJname());
    	$status = array();
        $status['debug'] = '';

        // Get IPB member id and login key for single sign-in
        $query = 'SELECT id, member_login_key FROM #__members WHERE name = ' . $db->Quote($userinfo->username);
        $db->setQuery($query);
        $result = $db->loadObject();

        // Set some cookie params
        if (isset($options['remember']) && $options['remember'])
        {
            $expires = time() + 60*60*24*365;
        }
        else
        {
        	$expires = false;
        }

        $prefix = $params->get('cookie_prefix');
        $path   = $params->get('cookie_path', '/');
        $domain = $params->get('cookie_domain');

        // Set basic cookies
        setcookie($prefix . 'member_id', $result->id, $expires, $path, $domain);
        setcookie($prefix . 'pass_hash', $result->member_login_key, $expires, $path, $domain);
        setcookie($prefix . 'session_id', '', $expires, $path, $domain);
        $status['debug'] .= JText::_('USERID') . '=' . $result->id . ', ' . JText::_('PASSWORD') . '=' . $result->member_login_key . ', ' . JText::_('COOKIE_PATH') . '=' . $path . ', ' . JText::_('COOKIE_DOMAIN') . '=' . $domain ', ' . JText::_('EXPIRES') . '=' . $expires;

        // Set strong protection cookie
        if ($params->get('ipb_cookie_stronghold'))
        {
    	    $ip = '';

    	    if (is_array($_SERVER) && count($_SERVER))
    	    {
    		    if (isset($_SERVER['REMOTE_ADDR']))
    		    {
    			    $ip = $_SERVER['REMOTE_ADDR'];
    		    }
    	    }

    	    if (!$ip)
    	    {
    		    $ip = getenv('REMOTE_ADDR');
    	    }

    	    if (!$ip)
    	    {
    	        return false;
    	    }

    		$ip_octets  = explode('.', $ip);
    		$crypt_salt = md5($params->get('database_password') . $params->get('database_user'));
    		$stronghold = md5(md5($result->id . '-' . $ip_octets[0] . '-' . $ip_octets[1] . '-' . $result->member_login_key) . $crypt_salt);

    		setcookie($prefix . $params->get('ipb_cookie_stronghold'), $stronghold, $expires, $path, $domain);
            $status['debug'] .= ' , stronghold = '. $stronghold;
        }
        $status['error'] = false;
        return $status;
    }
}