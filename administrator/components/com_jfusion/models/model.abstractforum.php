<?php

/**
 * @package JFusion
 * @subpackage Models
 * @version 1.0.9
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
class JFusionForum
{
	 /**
     * Checks to see if a thread already exists for the content item and calls the appropriate function
     *
     * @param object $contentitem object containing content information
     * @return array Returns status of actions with errors if any
     */
	function checkThreadExists($contentitem)
	{
	    $status = array();
        $status['debug'] = array();
        $status['error'] = array();

        return $status;
	}

    /**
     * Retrieves the default forum based on section/category stipulations or default set in the plugins config
     *
     * @param object $contentitem object containing content information
     * @return int Returns id number of the forum
     */
	function getDefaultForum($contentitem)
	{
		return 0;
	}

    /**
     * Retrieves thread information
     *
     * @param int Id of specific thread
     * @return object Returns object with thread information
     */
    function &getThread($contentid)
    {
		return new stdObject();
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
     * Creates new thread and posts first post
     * @param object $contentitem object containing content information
     * @param int Id of forum to create thread
     * @param array $status contains errors and status of actions
     */
	function createThread($contentitem, $forumid, &$status)
	{

	}

	 /**
     * Updates information in a specific thread/post
     * @param int Id of thread
     * @param int Id of the first post
     * @param object $contentitem object containing content information
     * @param array $status contains errors and status of actions
     */
	function updateThread($threadid,$postid,$contentitem,&$status)
	{

	}

	/**
     * Prepares text before saving to db
     * @param string Text to be modified
     * @return string Modified text
     */
	function prepareText($text)
	{
		return '';
	}

	/**
     * Creates a table of posts to be displayed in content item
     * @param obj of thread information
     * @return string HTML of table to displayed
     */
	function createPostTable($existingthread)
	{
		return '';
	}

	/**
     * Retrieves the posts to be displayed in the content item if enabled
     * @param int Id of thread
     * @param int Id of first post which is useful if you do not want the first post to be included in results
     * @return array or object Returns retrieved posts
     */
	function getPosts($threadid,$postid)
	{
		return array();
	}
}