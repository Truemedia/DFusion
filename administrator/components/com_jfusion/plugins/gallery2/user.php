<?php

/**
 * @package JFusion_Gallery2
 * @version 1.0.0
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractuser.php');



/**
 * JFusion plugin class for Gallery2
 * @package JFusion_Gallery2
 */
class JFusionUser_gallery2 extends JFusionUser {

	function updateUser($userinfo, $overwrite)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(false);

		// Initialise some variables
		$db = & JFusionFactory::getDatabase($this->getJname());
		$params = JFusionFactory::getParams($this->getJname());
		$update_block = $params->get('update_block');
		$update_activation = $params->get('update_activation');
		$update_email = $params->get('update_email');

		$status = array();
		$status['debug'] = array();
		$status['error'] = array();

		//check to see if a valid $userinfo object was passed on
		if(!is_object($userinfo)){
			$status['error'][] = JText::_('NO_USER_DATA_FOUND');
			return $status;
		}

		//find out if the user already exists
		list ($ret, $g2_existinguser) = GalleryCoreApi::fetchUserByUserName($userinfo->username);
		$changed = false;

		if (!empty($g2_existinguser)) {
			//a matching user has been found

			//Set Write Lock
			list($ret, $id) = GalleryCoreApi::acquireWriteLock($g2_existinguser->getId());
			if($ret) {
				echo $ret->getAsHtml();
			}

			//Set user Attributes
			$g2_existinguser->setFullName($userinfo->name);

			//Check Email
			if ($g2_existinguser->email != $userinfo->email) {
				if ($update_email || $overwrite) {
					$g2_existinguser->setEmail($userinfo->email);
					$changed = true;
				} else {
					//return a email conflict
					$status['error'][] = JText::_('EMAIL') . ' ' . JText::_('CONFLICT').  ': ' . $g2_existinguser->email . ' -> ' . $userinfo->email;
					$status['userinfo'] = $this->_getUser($g2_existinguser);
					return $status;
				}
			}

			//Check Password
			if (isset($userinfo->password_clear) && !empty($userinfo->password_clear)){
				$testcrypt = GalleryUtilities::md5Salt($userinfo->password_clear, $g2_existinguser->hashedPassword);
				if ($testcrypt != $g2_existinguser->hashedPassword) {
					$g2_existinguser->setHashedPassword($testcrypt);
					$changed = true;
				} else {
					$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ':' .  JText::_('PASSWORD_VALID');
				}
			} else {
				$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' . JText::_('PASSWORD_UNAVAILABLE');
			}

			//Sets a user can edite the user Profile itself
			if($g2_existinguser->locked != false) {
				$g2_existinguser->locked = false;
				$change = true;
			}

			/*//check the blocked status
			if ($g2_existinguser->locked != $userinfo->block) {
				if ($update_block || $overwrite) {
					if ($userinfo->block) {
						//block the user
						$g2_existinguser->locked = true;
						$changed = true;
					}
				} else {
					//return a debug to inform we skiped this step
					$status['debug'][] = JText::_('SKIPPED_BLOCK_UPDATE') . ': ' . $g2_existinguser->block . ' -> ' . $userinfo->block;
				}
			}

			//check the activation status
			if ($g2_existinguser->locked != $userinfo->activation) {
				if ($update_activation || $overwrite) {
					if ($userinfo->activation) {
						//inactiva the user
						$g2_existinguser->locked = true;
						$changed = true;
					}
				} else {
					//return a debug to inform we skiped this step
					$status['debug'][] = JText::_('SKIPPED_EMAIL_UPDATE') . ': ' . $g2_existinguser->email . ' -> ' . $userinfo->email;
				}
			}*/

			if($changed) {
				$ret = $g2_existinguser->save();
				if($ret) {
					echo $ret->getAsHtml();
				}
			}

			$status['userinfo'] = $this->_getUser($g2_existinguser);
			if (empty($status['error'])) {
				$status['action'] = 'updated';
			}
			GalleryEmbed::done();
			return $status;
		} else {
			list ($ret, $g2_user) = GalleryCoreApi::newFactoryInstance('GalleryEntity', 'GalleryUser');
			if ($ret) {
				return $ret;
			}
			if (!isset($g2_user)) {
				$status['error'][] = JText::_('ERROR_CREATING_USER') . ": gallery2 : " . $userinfo->username;
			}

			$ret = $g2_user->create($userinfo->username);
			if ($ret) {
				$status['error'][] = JText::_('ERROR_CREATING_USER') . ": gallery2 : " . $userinfo->username;
			}
			$testcrypt = GalleryUtilities::md5Salt($userinfo->password_clear);
			$g2_user->setHashedPassword($testcrypt);
			$g2_user->setUserName($userinfo->username);
			$g2_user->setEmail($userinfo->email);
			$g2_user->setFullName($userinfo->name);
			$ret = $g2_user->save();
			if ($ret) {
				$status['error'][] = JText::_('ERROR_CREATING_USER') . ": gallery2 : " . $userinfo->username;
			}

			if (empty($status['error'])) {
				$status['action'] = 'created';
			}
			GalleryEmbed::done();
			return $status;
		}
	}

	function updatePassword($userinfo, &$existinguser, &$status)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(false);

		// Initialise some variables
		$db = & JFusionFactory::getDatabase($this->getJname());
		$params = JFusionFactory::getParams($this->getJname());

		//Set Write Lock
		list($ret, $id) = GalleryCoreApi::acquireWriteLock($g2_existinguser->getId());
		if($ret) {
			echo $ret->getAsHtml();
		}

		//Check Password
		if (isset($userinfo->password_clear) && !empty($userinfo->password_clear)){
			$testcrypt = GalleryUtilities::md5Salt($userinfo->password_clear, $g2_existinguser->hashedPassword);
			if ($testcrypt != $g2_existinguser->hashedPassword) {
				$g2_existinguser->setHashedPassword($testcrypt);
				$changed = true;
			} else {
				$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ':' .  JText::_('PASSWORD_VALID');
			}
		} else {
			$status['debug'][] = JText::_('SKIPPED_PASSWORD_UPDATE') . ': ' . JText::_('PASSWORD_UNAVAILABLE');
		}


		if($changed) {
			$ret = $g2_existinguser->save();
			if($ret) {
				echo $ret->getAsHtml();
			}
		}

		GalleryEmbed::done();
	}

	function &getUser($username)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(false);

		list ($ret, $g2_user) = GalleryCoreApi::fetchUserByUserName($username);
		if ($ret) {
			return null;
		}
		return $this->_getUser($g2_user);
	}

	function &_getUser($g2_user)
	{
		$userinfo = new stdClass;
		$userinfo->userid = $g2_user->id;
		$userinfo->name = $g2_user->fullName;
		$userinfo->username = $g2_user->userName;
		$userinfo->email = $g2_user->email;
		$userinfo->password = $g2_user->hashedPassword;
		$userinfo->password_salt = substr($g2_user->hashedPassword, 0, 4);
		//TODO: Research if and in how to detect blocked Users
		$userinfo->block = 0; //(0 if allowed to access site, 1 if user access is blocked)
		//Not found jet
		$userinfo->registerdate = NULL;
		$userinfo->lastvisitdate = NULL;
		//Not activated users are saved sepperated so not to set. (PendingUser)
		$userinfo->activation = NULL;
		return $userinfo;
	}

	function getJname()
	{
		return 'gallery2';
	}

	function deleteUser($userinfo)
	{
		$username = $userinfo->username;
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(true);
		//Fetch GalleryUser
		list ($ret, $user) = GalleryCoreApi::fetchUserByUserName($username);
		if ($ret) {
			return false;
		}
		//Get Write Lock
		list ($ret, $lockId) = GalleryCoreApi::acquireWriteLock($user->getId());
		if ($ret) {
			return false;
		}
		//Delete User name
		$ret = $user->delete();
		if ($ret) {
			return false;
		}
		//Release Lock
		$ret = GalleryCoreApi::releaseLocks($lockId);
		if ($ret) {
			return false;
		}
		return true;
	}

	function filterUsername($username)
	{
		//TODO: Implement User filtering
		return $username;
	}

	function destroySession($userinfo, $options)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(true);
		GalleryEmbed::logout();
		GalleryEmbed::done();
	}

	function createSession($userinfo, $options, $framework = true )
	{
		if($framework) {
			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
			DS.'gallery2'.DS.'gallery2.php');
			jFusion_g2BridgeCore::loadGallery2Api(true);
		}
		global $gallery;

		//Code is taken from GalleryEmbed::checkActiveUser function
		$session =& $gallery->getSession();
		$activeUserId = $session->getUserId();
		if ($activeUserId === $userinfo->userid) {
			return null;
		}

		/* Logout the existing user from Gallery */
		if (!empty($idInSession)) {
			list ($ret, $anonymousUserId) = GalleryCoreApi::getAnonymousUserId();
			if ($ret) {
				return $ret;
			}
			/* Can't use getActiveUser() since it might not be set at this point */
			$activeGalleryUserId = $gallery->getActiveUserId();
			if ($anonymousUserId != $activeGalleryUserId) {
				list ($ret, $activeUser) =
				GalleryCoreApi::loadEntitiesById($activeGalleryUserId, 'GalleryUser');
				if ($ret) {
					return $ret;
				}
				$event = GalleryCoreApi::newEvent('Gallery::Logout');
				$event->setEntity($activeUser);
				list ($ret, $ignored) = GalleryCoreApi::postEvent($event);
				if ($ret) {
					return $ret;
				}
			}
			$ret = $session->reset();
			if ($ret) {
				return $ret;
			}
		}

		//Code is paticulary taken from the GalleryEmbed::login function
		list ($ret, $user) = GalleryCoreApi::fetchUserByUserName($userinfo->username);
		if ($ret) {
			return null;
		}
		//Login the Current User
		$gallery->setActiveUser($user);

		//Save the Session
		$session =& $gallery->getSession();
		$phpVm = $gallery->getPhpVm();
		//Set Siteadmin if necessarey
		list ($ret, $isSiteAdmin)  = GalleryCoreApi::isUserInSiteAdminGroup($user->id);
		if ($ret) {
			return $ret;
		}
		if ($isSiteAdmin) {
			$session->put('session.siteAdminActivityTimestamp', $phpVm->time());
		}
		$ret = $session->regenerate();
		
        $session =& $gallery->getSession();

		/* Touch this session - Done for WhoIsOnline*/
		$session->put('touch', time());
		$ret = $session->save();

		//Close GalleryApi
		if($framework) {
			GalleryEmbed::done();
		}
	}
}
