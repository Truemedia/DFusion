<?php
/**
* @package JFusion
* @subpackage Plugin_User
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Load the JFusion framework
*/
jimport('joomla.plugin.plugin');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');


/**
* JFusion User class
* @package JFusion
*/
 class plgUserJfusion extends JPlugin
{
	/**
	* Constructor
	*
	* For php4 compatability we must not use the __constructor as a constructor for plugins
	* because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	* This causes problems with cross-referencing necessary for the observer design pattern.
	*
	* @param object $subject The object to observe
	* @param array $config An array that holds the plugin configuration
	* @since 1.5
	*/
	function plgUserJfusion(& $subject, $config)
	{
		parent::__construct($subject, $config);
		//load the language
		$this->loadLanguage('com_jfusion', JPATH_BASE);
	}
	
	/**
	* Remove all sessions for the user name
	*
	* Method is called after user data is deleted from the database
	*
	* @param array holds the user data
	* @param boolean true if user was succesfully stored in the database
	* @param string message
	*/
	function onAfterDeleteUser($user, $succes, $msg)
	{
		if (!$succes) {
			$result = false;
			return $result;
		}
		
		$db =& JFactory::getDBO();
		$db->setQuery('DELETE FROM #__session WHERE userid = '.$db->Quote($user['id']));
		$db->Query();
		
		//convert the user array into a user object
		$userinfo = (object) $user;
		
		/*
		 *   we do not want Joomla deleting the master
		 *   as the master should handle user management
		 *   
		    
		//delete the master user if it is not Joomla
		$master = JFusionFunction::getMaster();
		
		if($master->name != 'joomla_int'){
			$JFusionMaster = JFusionFactory::getUser($master->name);
			$MasterUser =& $JFusionMaster->getUser($userinfo->username);
			if(!empty($MasterUser)) {
				$status = $JFusionMaster->deleteUser($MasterUser);	
			} else {
				$status = array();
				$status['error'] = JText::_("NO_USER_DATA_FOUND");
			}
			
			if(!empty($status['error'])){
				//could not delete user
				JFusionFunction::raiseWarning($master->name . ' ' .JText::_('USER') . ' ' .JText::_('DELETE'), $status['error'],1);
			}
		}
		*/
		
		//delete the user in the slave plugins
		$slaves = JFusionFunction::getPlugins();
		foreach($slaves as $slave) {
			$JFusionSlave = JFusionFactory::getUser($slave->name);
			$slaveUser =& $JFusionSlave->getUser($userinfo->username);			
			if(!empty($slaveUser)) {
				$status = $JFusionSlave->deleteUser($slaveUser);	
			} else {
				$status = array();
				$status['error'] = JText::_("NO_USER_DATA_FOUND");
			}
			if(!empty($status['error'])){
				//could not delete user
				JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('DELETE'), $status['error'],1);
			}
		}
		
		$result = true;
		return $result;
	}
	
	/**
	* This method should handle any login logic and report back to the subject
	*
	* @access public
	* @param array holds the user data
	* @param array array holding options (remember, autoregister, group)
	* @return boolean True on success
	* @since 1.5
	*/
	function onLoginUser($user, &$options)
	{
		//prevent any output by the plugins (this could prevent cookies from being passed to the header)
		ob_start();
		
		jimport('joomla.user.helper');
		global $JFusionActive;
		$JFusionActive = true;
			
		//allow for the detection of external mods to exclude jfusion plugins
		global $JFusionActivePlugin;
		
		//get the JFusion master
		$master = JFusionFunction::getMaster();
		$JFusionMaster = JFusionFactory::getUser($master->name);
		
		//check to see if userinfo is already present
		if(!empty($user['userinfo'])){
			//the jfusion auth plugin is enabled
			$userinfo =$user['userinfo'];
		} else {
			//other auth plugin enabled get the userinfo again
			$userinfo = $JFusionMaster->getUser($user['username']);
			if(empty($userinfo)){
				//should be auto-create users?
				$params = JFusionFactory::getParams('joomla_int');
				$autoregister = $params->get('autoregister',0);
				if($autoregister == 1){
					//create a JFusion userinfo object
					$userinfo = new stdClass;
					$userinfo->username = $user['username'];
					$userinfo->password_clear = $user['password_clear'];
					$userinfo->email = $user['email'];
					$status = array();
					$status['debug'] = array();
					$status['error'] = array();
					
					//try to create a Joomla user
					require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');
					JFusionJplugin::createUser($userinfo, 0, $status,'joomla_int');
					if(empty($status['error'])){
						//success
						$userinfo = $status['userinfo'];
					} else {
						//could not create user
						ob_end_clean();
						JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['error'],1);
						$success = false;
						return $success;
					}
				} else {
					//return an error
					ob_end_clean();
					$success = false;
					return $success;
				}
			}
		}
		
		//apply the cleartext password to the user object
		$userinfo->password_clear = $user['password'];
		
		// See if the user has been blocked or is not activated
		if (!empty($userinfo->block) || !empty($userinfo->activation)) {	
			//make sure the block is also applied in slave softwares
			$slaves = JFusionFunction::getSlaves();
			foreach($slaves as $slave) {
				$JFusionSlave = JFusionFactory::getUser($slave->name);
				$SlaveUser = $JFusionSlave->updateUser($userinfo,0);
				if (!empty($SlaveUser['error'])) {
					JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['error'],1);
				}
			}
			
			if (!empty($userinfo->block)) {
				JError::raiseWarning('500', JText::_('FUSION_BLOCKED_USER'));
				//hide the default Joomla login failure message
				JError::setErrorHandling(E_WARNING, 'ignore');
				ob_end_clean();
				$success = false;
				return $success;
			} else {
				JError::raiseWarning('500', JText::_('FUSION_INACTIVE_USER'));
				//hide the default Joomla login failure message
				JError::setErrorHandling(E_WARNING, 'ignore');
				ob_end_clean();
				$success = false;
				return $success;
			}
		 }
		
		//setup the master session
		if ($JFusionActivePlugin != $master->name){
			$MasterSession = $JFusionMaster->createSession($userinfo, $options);
			if (!empty($MasterSession['error'])) {
				//report the error back
				JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION').' ' .JText::_('CREATE'), $MasterSession['error'],1);
				if ($master->name == 'joomla_int'){
					//we can not tolerate Joomla session failures
					ob_end_clean();
					//hide the default Joomla login failure message
					JError::setErrorHandling(E_WARNING, 'ignore');
					$success = false;
					return $success;
				}
			}
		}
		
		//check to see if we need to setup a Joomla session
		if ($master->name != 'joomla_int'){
			//setup the Joomla user
			$JFusionJoomla = JFusionFactory::getUser('joomla_int');
			$JoomlaUser = $JFusionJoomla->updateUser($userinfo,0);
			if (!empty($JoomlaUser['error'])) {
				//no Joomla user could be created, fatal error
				JFusionFunction::raiseWarning('joomla_int: '.' ' .JText::_('USER').' ' .JText::_('UPDATE'), $JoomlaUser['error'],1);
				//hide the default Joomla login failure message
				JError::setErrorHandling(E_WARNING, 'ignore');
				ob_end_clean();
				$success = false;
				return $success;
			}
			
			//create a Joomla session
			$JoomlaSession = $JFusionJoomla->createSession($JoomlaUser['userinfo'], $options);
			if (!empty($JoomlaSession['error'])) {
				//no Joomla session could be created -> deny login
				JFusionFunction::raiseWarning('joomla_int ' .' ' .JText::_('SESSION') .' ' .JText::_('CREATE'), $JoomlaSession ['error'],1);
				//hide the default Joomla login failure message
				JError::setErrorHandling(E_WARNING, 'ignore');
				ob_end_clean();
				$success = false;
				return $success;
			}
		} else {
			//joomla already setup, we can copy its details from the master
			$JFusionJoomla = $JFusionMaster;
			$JoomlaUser = array( 'userinfo' => $userinfo, 'error' => '');
		}
		
		//delete old entries in the jos_jfusion_users_plugin table
		$db = & JFactory::getDBO();
		$query = 'DELETE FROM #__jfusion_users_plugin WHERE id =' . $JoomlaUser['userinfo']->userid;
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(0,$db->stderr());
		}
		
		if ($master->name != 'joomla_int') {
			JFusionFunction::updateLookup($userinfo, $master->name, $JoomlaUser['userinfo']->userid);
		}
		
		//setup the other slave JFusion plugins
		$slaves = JFusionFunction::getPlugins();
		foreach($slaves as $slave) {
			$JFusionSlave = JFusionFactory::getUser($slave->name);
			$SlaveUser = $JFusionSlave->updateUser($userinfo,0);
			if (!empty($SlaveUser['error'])) {
				JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('USER') .' ' .JText::_('UPDATE') , $SlaveUser['error'],1);
			} else {
				//apply the cleartext password to the user object
				$SlaveUser['userinfo']->password_clear = $user['password'];
				
				JFusionFunction::updateLookup($SlaveUser['userinfo'], $JoomlaUser['userinfo']->userid, $slave->name);//@todo - change the order of the parameters
				
				if (!isset($options['group']) && $slave->dual_login == 1 && $JFusionActivePlugin != $slave->name) {
					$SlaveSession = $JFusionSlave->createSession($SlaveUser['userinfo'], $options);
					if (!empty($SlaveSession['error'])) {
						JFusionFunction::raiseWarning($slave->name . ' ' . JText::_('SESSION') .' ' .JText::_('CREATE'), $SlaveSession['error'],1);
					}
				}
			}
		}
		ob_end_clean();
		$result = true;
		return $result;
	}
	
	
	/**
	* This method should handle any logout logic and report back to the subject
	*
	* @access public
	* @param array holds the user data
	* @param array array holding options (client, ...)
	* @return object True on success
	* @since 1.5
	*/
	function onLogoutUser($user, $options = array())
		{
		global $JFusionActive;
		$JFusionActive = true;
		$my =& JFactory::getUser();
		
		//prevent any output by the plugins (this could prevent cookies from being passed to the header)
		ob_start();
		
		//logout from the JFusion plugins if done through frontend
		if ($options['clientid'][0] != 1) {
			//get the JFusion master
			$master = JFusionFunction::getMaster();
			if ($master->name && $master->name != 'joomla_int') {
				$JFusionMaster = JFusionFactory::getUser($master->name);
				$userlookup = JFusionFunction::lookupUser($master->name, $my->get('id'));
				$MasterUser = $JFusionMaster->getUser($userlookup->username);
				//check if a user was found
				if ($MasterUser) {
					$MasterSession = $JFusionMaster->destroySession($MasterUser, $options);
					if (!empty($MasterSession['error'])) {
						JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'), $MasterSession['error']);
					}
				} else {
					JFusionFunction::raiseWarning($master->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'),1);
				}
			}
			
			$slaves = JFusionFunction::getPlugins();
			foreach($slaves as $slave) {
				//check if sessions are enabled
				if ($slave->dual_login == 1) {
					$JFusionSlave = JFusionFactory::getUser($slave->name);
					$userlookup = JFusionFunction::lookupUser($slave->name, $my->get('id'));
					$SlaveUser = $JFusionSlave->getUser($userlookup->username);
					//check if a user was found
					if ($SlaveUser) {
						$SlaveSession = $JFusionSlave->destroySession($SlaveUser, $options);
						if (!empty($SlaveSession['error'])) {
							JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'),$SlaveSession['error'],1);
						}
					} else {
						JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'),1);
					}
				}
			}
		}
		
		//destroy the joomla session itself
		$JoomlaUser = JFusionFactory::getUser('joomla_int');
		$JoomlaUser->destroySession($user, $options);
		
		ob_end_clean();
		$result = true;
		return $result;
	}

	function onBeforeStoreUser($olduser, $isnew) {
		
		global $JFusionActive;
		
		if (! $JFusionActive) {
			// Recover old data from user before to save it. The purpose is to provide it to the plugins if needed
			$session = & JFactory::getSession ();
			$session->set ( 'olduser', $olduser );
		}
	}
	
	function onAfterStoreUser($user, $isnew, $succes, $msg)
	{
		if(!$succes){
			$result = false;
			return $result;
		}
		
		//prevent any output by the plugins (this could prevent cookies from being passed to the header)
		ob_start();
		
		global $JFusionActive;
		
		if (!$JFusionActive) {
			//A change has been made to a user without JFusion knowing about it
			
			/**
			* Load the JFusion framework
			*/
			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
			
			//convert the user array into a user object
			$JoomlaUser = new stdClass();
			foreach ($user as $key => $value) {
				$JoomlaUser->$key = $value;
			}
			
			//check to see if we need to update the master
			$master = JFusionFunction::getMaster();
			
			// Recover the old data of the user to manage to find correct username and/or email if it has been changed by the user or admin in Joomla
			// Needed for some plugins (Magento for example)
			$session = & JFactory::getSession ();
			$JoomlaUser->olduserinfo = (object) $session->get ( 'olduser' );
			$session->clear ( 'olduser' );
					
			if($master->name != 'joomla_int'){
				$JFusionMaster = JFusionFactory::getUser($master->name);
				$MasterUser = $JFusionMaster->updateUser($JoomlaUser, 1);
				if (!empty($MasterUser['error'])) {
					JFusionFunction::raiseWarning($master->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $MasterUser['error'],1);
				}
			}
			
			//update the user details in any JFusion slaves
			$slaves = JFusionFunction::getPlugins();
			foreach($slaves as $slave) {
				$JFusionSlave = JFusionFactory::getUser($slave->name);
				$SlaveUser = $JFusionSlave->updateUser($JoomlaUser, 1);
				if (!empty($SlaveUser['error'])) {
					JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('USER') . ' ' .JText::_('UPDATE'), $SlaveUser['error'],1);
				}
			}
		 }
		 //stop output buffer
		 ob_end_clean();
	}
}