<?php

/**
 * @package JFusion_Moodle
 * @version 1.0.8-001
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
class JFusionAuth_moodle extends JFusionAuth{

    function generateEncryptedPassword($userinfo) {
      return md5($userinfo->password_clear);
    }
}