<?php

/**
 * @package JFusion_MyBB
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Authentication Class for MyBB
 * For detailed descriptions on these functions please check the model.abstractauth.php
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
