<?php

/**
* @package JFusion_Moodle
* @version 1.0.8-007
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');

/**
 * JFusion plugin class for Moodle 1.8+
 * @package JFusion_Moodle
 */

class JFusionPublic_moodle extends JFusionPublic{

    function getJname(){
        return 'moodle';
    }

    function getRegistrationURL(){
        return 'login/signup.php';
    }

    function getLostPasswordURL(){
        return 'login/forgot_password.php';
    }

    function getLostUsernameURL(){
        return 'login/forgot_password.php';
    }
}