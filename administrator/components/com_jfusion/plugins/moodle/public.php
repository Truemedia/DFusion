<?php

/**
* @package JFusion_Moodle
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Public Class for Moodle 1.8+
 * For detailed descriptions on these functions please check the model.abstractpublic.php
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