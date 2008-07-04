<?php

/**
 * @package JFusion_MyBB
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
 * @package JFusion_MyBB
 */
class JFusionAuth_mybb extends JFusionAuth{


    function generateEncryptedPassword($userinfo)
    {
           //Apply myBB encryption
            $testcrypt = md5(md5($userinfo->password_salt).md5($userinfo->password_clear));
            return $testcrypt;
    }



}
