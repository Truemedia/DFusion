<?php

/**
 * @package JFusion_phpBB3
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
 * @package JFusion_phpBB3
 */
class JFusionAuth_phpbb3 extends JFusionAuth{

    function generateEncryptedPassword($userinfo)
    {
        // get the encryption PHP file
        if(!class_exists('PasswordHash')){
	        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $this->getJname().DS.'PasswordHash.php');
        }

        $t_hasher = new PasswordHash(8, TRUE);
        $check = $t_hasher->CheckPassword($userinfo->password_clear, $userinfo->password);
        //$check will be true or false if the passwords match
        unset($t_hasher);
        //cleanup

        if ($check) {
            //password is correct and return the phpbb3 password hash
            return $userinfo->password;
        } else {
            //no phpbb3 encryption used and return the phpbb2 password hash
            $encrypt_password = md5($userinfo->password_clear);
            return $encrypt_password;
        }
    }



}
