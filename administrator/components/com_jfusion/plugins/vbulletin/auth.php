<?php

/**
 * @package JFusion_vBulletin
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Authentication Class for vBulletin
 * For detailed descriptions on these functions please check the model.abstractauth.php
 * @package JFusion_vBulletin
 */
class JFusionAuth_vbulletin extends JFusionAuth{

    function generateEncryptedPassword($userinfo)
    {
		if($userinfo->password == $userinfo->password_clear)
		{
			return $userinfo->password_clear;
		}
		else
		{
            $testcrypt = md5(md5($userinfo->password_clear).$userinfo->password_salt);
            return $testcrypt;
		}
    }
}
