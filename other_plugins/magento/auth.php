<?php

/**
 * @package JFusion_Magento
 * @version 1.0.7
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
 * @package JFusion_Magento
 */
class JFusionAuth_magento extends JFusionAuth{

    function generateEncryptedPassword($userinfo)
    {
		$arrPass = explode(":", $userinfo->password);

		if (!$arrPass[1]) {
			$ret = md5($userinfo->password_clear);
		} else {
			$ret = md5($arrPass[1].$userinfo->password_clear).':'.$arrPass[1];
		}

		return $ret;

    }

}
