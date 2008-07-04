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
* Abstract interface for all JFusion plugin implementations.
* @package JFusion
*/
class JFusionPlugin{



    /**
     * returns the name of this JFusion plugin
     * @return string name of current JFusion plugin
     */
    function getJname()
    {
        return '';
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


     /**
     * Returns the registration URL for the integrated software
     * @return string registration URL
     */
    function getRegistrationURL()
    {
        return '';
    }

     /**
     * Returns the lost password URL for the integrated software
     * @return string lost password URL
     */
    function getLostPasswordURL()
    {
        return '';
    }

     /**
     * Returns the lost username URL for the integrated software
     * @return string lost username URL
     */
    function getLostUsernameURL()
    {
        return '';
    }

     /**
     * Returns the URL to a thread of the integrated software
     * @param integer $threadid threadid
     * @return string URL
     */
    function getThreadURL($threadid)
    {
        return '';
    }

     /**
     * Returns the URL to a post of the integrated software
     * @param integer $threadid threadid
     * @param integer $postid postid
     * @return string URL
     */
    function getPostURL($threadid, $postid)
    {
        return '';
    }

     /**
     * Returns the URL to a userprofile of the integrated software
     * @param integer $uid userid
     * @return string URL
     */
    function getProfileURL($uid)
    {
        return '';
    }

     /**
     * Returns the URL to the view all private messages URL of the integrated software
     * @return string URL
     */
    function getPrivateMessageURL()
    {
        return '';
    }

     /**
     * Returns the URL to a view new private messages URL of the integrated software
     * @return string URL
     */
    function getViewNewMessagesURL()
    {
        return '';
    }

     /**
     * Returns the URL to a get private messages URL of the integrated software
     * @return string URL
     */
    function getPrivateMessageCounts($puser_id)
    {
        return 0;
    }

     /**
     * Returns the an array with SQL statements used by the activity module
     * @return array
     */
    function getQuery($usedforums, $result_order, $result_limit, $display_limit)
    {
        return 0;
    }

     /**
     * Returns the a list of forums of the integrated software
     * @return array List of forums
     */
    function getForumList()
    {
        return 0;
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
}



