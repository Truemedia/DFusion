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
* Abstract interface for all JFusion functions that are accessed through the Joomla administrator interface
* @package JFusion
*/
class JFusionAdmin{

    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
    }

     /**
     * Returns the a list of users of the integrated software
     * @return array List of usernames/emails
     */
    function getUserList()
    {
        return 0;
    }

     /**
     * Returns the the number of users in the integrated software. Allows for fast retrieval total number of users for the usersync
     * @return integer Number of registered users
     */
    function getUserCount()
    {
        return 0;
    }

     /**
     * Returns the a list of usersgroups of the integrated software
     * @return array List of usergroups
     */
    function getUsergroupList()
    {
        return 0;
    }

     /**
     * Function used to display the default usergroup in the JFusion plugin overview
     * @return string Default usergroup name
     */
    function getDefaultUsergroup()
    {
        return '';
    }

     /**
     * Checks if the software allows new users to register
     * @return boolean True if new user registration is allowed, otherwise returns false
     */
    function allowRegistration(){
	    return true;
    }

    /**
     * returns the name of user table of integrated software
     * @return string table name
     */
    function getTablename()
    {
        return '';
    }

     /**
     * Function finds config file of integrated software and automatically configures the JFusion plugin
     * @param string $softwarePath path to root of integrated software
     * @return object JParam JParam objects with ne newly found configuration
     */
    function setupFromPath($softwarePath)
    {
        return 0;
    }

}



