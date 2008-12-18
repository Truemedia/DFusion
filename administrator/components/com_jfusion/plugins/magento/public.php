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
 * JFusion Public Class for Magento 1.1
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * @package JFusion_Magento
 */
class JFusionPublic_magento extends JFusionPublic{

    function getJname(){
        return 'magento';
    }

    function getRegistrationURL(){
        return 'index.php/customer/account/create/';
    }

    function getLostPasswordURL(){
        return 'index.php/customer/account/forgotpassword/';
    }

    function getLostUsernameURL(){
        return 'index.php/customer/account/forgotpassword/'; // not available in Magento, map to lostpassword
    }

}