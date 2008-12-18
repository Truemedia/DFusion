<?php

/**
 * @package JFusion
 * @subpackage Models
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Abstract interface for all JFusion plugin implementations.
* @package JFusion
*/
class JFusionUser{

    /**
     * gets the userinfo from the JFusion integrated software. Definition of object:
     * $userinfo->userid
     * $userinfo->name
     * $userinfo->username
     * $userinfo->email
     * $userinfo->password (encrypted password)
     * $userinfo->password_salt (salt used to encrypt password)
     * $userinfo->block (0 if allowed to access site, 1 if user access is blocked)
     * $userinfo->registerdate
     * $userinfo->lastvisitdate
     * @param string $username username
     * @return object userinfo Object containing the user information
     */
    function &getUser($username)
    {
        return 0;
    }

    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
    }

     /**
     * Function that automatically logs out the user from the integrated software
     * $result['error'] (contains any error messages)
     * $result['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the userinfo
     * @param array $options Array with the login options, such as remember_me
     * @return array result Array containing the result of the session destroy
     */
    function destroySession($userinfo, $options)
    {
    }

     /**
     * Function that automatically logs in the user from the integrated software
     * $result['error'] (contains any error messages)
     * $result['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the userinfo
     * @param array $options Array with the login options, such as remember_me     *
     * @return array result Array containing the result of the session creation
     */
    function createSession($userinfo, $options)
    {
    }


     /**
     * Function that filters the username according to the JFusion plugin
     * @param string $username Username as it was entered by the user
     * @return string filtered username that should be used for lookups
     */
    function filterUsername($username)
    {
        return '';
    }


     /**
     * Updates or creates a user for the integrated software. This allows JFusion to have external softwares as slave for user management
     * $result['error'] (contains any error messages)
     * $result['userinfo'] (contains the userinfo object of the integrated software user)
     * @param object $suserinfo Object containing the userinfo
     * @return array result Array containing the result of the user update
     */
    function updateUser($userinfo)
    {
    }

     /**
     * Function that updates the user password
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function updatePassword($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that updates the username
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function updateUsername($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that updates the user email address
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function updateEmail($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that updates the blocks the user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function blockUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that unblocks the user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function unblockUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that activates the users account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function activateUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that inactivates the users account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param object $exisitinguser Object containg the old userinfo
     * @param array status Array containing the errors and result of the function
     */
    function inactivateUser($userinfo, &$existinguser, &$status)
    {
    }

     /**
     * Function that creates a new user account
     * $status['error'] (contains any error messages)
     * $status['debug'] (contains information on what was done)
     * @param object $userinfo Object containing the new userinfo
     * @param array status Array containing the errors and result of the function
     */
    function createUser($userinfo, &$status)
    {
    }


}



