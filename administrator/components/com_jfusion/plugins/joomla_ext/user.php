<?php
/**
 * @package JFusion_Joomla_Ext
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
 * JFusion plugin class for an external Joomla database
 * @package JFusion_Joomla_Ext
 */
class JFusionUser_joomla_ext extends JFusionUser
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
        $db->setQuery('SELECT a.id as userid, a.username, a.name, a.password, a.email, a.block, a.registerDate as registerdate, lastvisitDate as lastvisitdate FROM #__users as a WHERE a.username=' . $db->quote($username));
        $result = $db->loadObject();
		return $result;
    }

    function getJname()
    {
		return 'joomla_ext';
    }

    function deleteUser($username)
    {
        //get the database ready
        $db = JFusionFactory::getDatabase($this->getJname());

        //delete user from the Joomla usertable
  		$query = 'DELETE FROM #__users WHERE username = ' . $db->quote($username);
   		$db->setQuery($query);
       	$db->query();
    }



    function destroySession($userinfo, $options)
    {
            $status['error'] = 'Dual login is not available for this plugin';
            return $status;
    }

    function createSession($userinfo, $options)
    {
            $status['error'] = 'Dual login is not available for this plugin';
            return $status;
    }

	function filterUsername($username) {
	    //no username filtering implemented yet
	    return $username;
    }

 }
