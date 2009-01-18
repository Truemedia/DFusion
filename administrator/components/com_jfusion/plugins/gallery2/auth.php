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
 * load the Abstract Auth Class
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractauth.php');

/**
 * @package JFusion_Gallery2
 */
class JFusionAuth_gallery2 extends JFusionAuth{

	function generateEncryptedPassword($userinfo)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		G2BridgeCore::loadGallery2Api(false);
		$testcrypt = GalleryUtilities::md5Salt($userinfo->password_clear, $userinfo->password_salt);
		return $testcrypt;
	}



}
