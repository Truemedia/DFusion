<?php

/**
 * @package JFusion
 * @subpackage Models
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Abstract interface for all JFusion auth implementations.
* @package JFusion
*/
class JFusionAuth{

    /**
     * Generates an encrypted password based on the userinfo passed to this function
     *
     * @param array $userinfo userdata object containing the userdata
     * @return string Returns generated password
     */
    function generateEncryptedPassword($userinfo)
    {
    }

}



