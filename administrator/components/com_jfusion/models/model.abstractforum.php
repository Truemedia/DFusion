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
	function checkThreadExists($contentitem)
	{
	    $status = array();
        $status['debug'] = array();
        $status['error'] = array();

        return $status;
	}

	function getDefaultForum($contentitem)
	{
		return 0;
	}

    function &getThread($contentid)
    {
		return new stdObject();
    }

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



	function createThread($contentitem, $forumid, &$status)
	{

	}

	function updateThread($threadid,$postid,$contentitem,&$status)
	{

	}

	function prepareText($text)
	{
		return '';
	}

	function getPosts($threadid,$postid)
	{
		return array();
	}

	function createPostTable($existingthread)
	{
		return '';
	}

	function getSearchQueryColumns()
	{
		$columns = new stdClass();
		return $columns;
	}

	function getSearchQuery()
	{
		return '';
	}

	function cleanUpSearchText($text)
	{
		return $text;
	}
}