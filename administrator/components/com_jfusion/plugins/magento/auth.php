<?php

/**
* @package JFusion_Magento
* @version 1.1.0-b001
* @author Henk Wevers
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
 * @package JFusion_myplugin
 */
class JFusionAuth_magento extends JFusionAuth{

    function generateEncryptedPassword($userinfo){
        if($userinfo->password_salt) {
          return md5($userinfo->password_salt.$userinfo->password_clear);
        } else {
          return md5($userinfo->password_clear);
        }
    }
}