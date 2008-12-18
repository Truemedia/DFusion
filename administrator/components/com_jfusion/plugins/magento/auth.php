<?php

/**
* @package JFusion_Magento
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
 * JFusion Authentication Class for Magento 1.1
 * For detailed descriptions on these functions please check the model.abstractauth.php
 * @package JFusion_Magento
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