<?php
/**
 * @package JFusion_Magento
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');

/**
 * JFusion user class for Magento
 * @package JFusion_Magento
 */
class JFusionUser_magento extends JFusionUser
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


	function getJname()
	{
		return 'magento';
	}

	function &getUser($username)
	{
        // Get a database object
        $db = JFusionFactory::getDatabase($this->getJname());

		$query = 'SELECT entity_id FROM #__customer_entity WHERE email =' . $db->Quote($username);
		$db->setQuery($query);
		$entity = (int) $db->loadResult();

		if (!$entity) {
			// USER NOT FOUND!
			return false;
		}

		$db->setQuery('SELECT attribute_id, value FROM #__customer_entity_varchar WHERE entity_id = '.$entity);
		$myInfo = $db->loadObjectList('attribute_id');

  		$user = new stdClass;
		$user->id = $entity;
		$user->name = $myInfo[4]->value . ' ' . $myInfo[5]->value;
		$user->email = $username;
		$user->username = $username;
		$user->password = $myInfo[8]->value;
		return $user;
	}

    function deleteUser($username)
    {
	    //TODO: create a function that deletes a user
    }


	function createSession($userinfo, $options){
		/* posting info to magento */
		// Setup a string with the form parameters in it
    	$status = array();
        $status['debug'] = '';
		$strParameters = "login[username]=".urlencode($userinfo->username)."&login[password]=".urlencode($userinfo->password_clear);
		if (!function_exists('curl_init')) {
        	$status['error'] = 'You need cURL enabled to run this plugin';
        	return $status;
		}

		// Initialize the CURL library
		$cCURL = curl_init();

		/* --- first part. --- */
		// Set the URL to execute
		curl_setopt($cCURL, CURLOPT_URL, JFusionFunction::createURL('index.php'.DS.'customer'.DS.'account'.DS.'loginPost'.DS,$this->getJname(), 'direct'));

		// Set options
		curl_setopt( $cCURL, CURLOPT_HEADER, true );
		curl_setopt( $cCURL, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt( $cCURL, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $cCURL, CURLOPT_RETURNTRANSFER, true );

		// hijacking sessions!
		$lastWord = md5(time() . $userinfo->username); // unique session id.

		curl_setopt( $cCURL, CURLOPT_COOKIE, 'frontend='.$lastWord.'; magento='.$lastWord); // $lastWord

		// POST method
		curl_setopt( $cCURL, CURLOPT_POST, true );
		curl_setopt( $cCURL, CURLOPT_POSTFIELDS, $strParameters);

		// Execute
		$strPage = curl_exec($cCURL);

		// Close CURL resource
		curl_close($cCURL);

		setcookie('frontend', $lastWord,0,'/');
		setcookie('magento', $lastWord,0,'/');
        $status['debug'] = 'cookie value: ' . $lastword;
        return $status;
	}


	function destroySession($userinfo, $options)
	{
		setcookie('frontend', rand(0,999),time()-1800,'/');
		setcookie('magento', rand(0,999),time()-1800,'/');
        $status['error'] = false;
        return $status;
	}

	function filterUsername($username) {
	    //no username filtering implemented yet
	    return $username;
    }

 }
