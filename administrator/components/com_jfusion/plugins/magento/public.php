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
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');

/**
 * JFusion plugin class for Magento 1.1
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