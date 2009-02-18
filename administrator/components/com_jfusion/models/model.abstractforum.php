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
* Abstract interface for all JFusion forum implementations.
* @package JFusion
*/
class JFusionForum
{
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
     * Retrieves the source path to the user's avatar
     * @param $uid software's user id
     * @return string with source path to users avatar
     */
    function getAvatar($uid)
    {
    	return 0;
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
     * Filter forums from a set of results sent in / useful if the plugin needs to restrict the forums visible to a user
     * @param $results set of results from query; note that the search plugin will pass in an object list (array of objects)  
     * where the activities module will pass in a row list (array of arrays with a numerical key which correlates with SQL in
     * $this->getQuery())
     * @param $idKey string name of forum id column to use if results is an object list
     */
 	function filterForumList(&$results, $idKey='')
 	{
 		
 	} 
 
    /************************************************
	 * Functions For JFusion Discussion Bot Plugin
	 ***********************************************/
    
    /**
     * Checks to see if a thread already exists for the content item and calls the appropriate function
     * @param object with discussion bot parameters 
     * @param object $contentitem object containing content information
     * @param int default forum id to create the new thread in if applicable (retrieved from $this->getDefaultForum)
     * @return array Returns status of actions with errors if any
     */
	function checkThreadExists(&$params, &$contentitem, $forumid)
	{
	    $status = array();
        $status['debug'] = array();
        $status['error'] = array();

        return $status;
	}

    /**
     * Retrieves the default forum based on section/category stipulations or default set in the plugins config
     * @param object with discussion bot parameters
     * @param object $contentitem object containing content information
     * @return int Returns id number of the forum
     */
	function getDefaultForum(&$dbparams, &$contentitem)
	{
		//content section/category
		$sectionid =& $contentitem->sectionid;
		$catid =& $contentitem->catid;

		//default forum to create post in
		$forumid = $dbparams->get("default_forum");

		//determine default forum
		$sections = $dbparams->get("pair_sections");
		$sectionPairs = empty($sections) ? false :  explode(";",$sections);

		$categories = $dbparams->get("pair_categories");
		$categoryPairs = empty($categories) ? false : explode(";",$categories);

		if($sectionPairs)
		{
			foreach($sectionPairs as $pairs)
			{
				$pair = explode(",",$pairs);
				//check to see if this section matches the articles
				if($pair[0]==$sectionid) $forumid = $pair[1];
			}
		}

		if($categoryPairs)
		{
			foreach($categoryPairs as $pairs)
			{
				$pair = explode(",",$pairs);
				//check to see if this category matches the articles
				if($pair[0]==$catid) $forumid = $pair[1];
			}
		}

		return $forumid;
	}

    /**
     * Retrieves thread information
     * @param int Id of specific thread
     * @return object Returns object with thread information
     * return the object with these three items
     * $result->forumid
     * $result->threadid (yes add it even though it is passed in as it will be needed in other functions)
     * $result->postid - this is the id of the first post in the thread
     */
    function getThread($threadid)
    {
		return '';
    }
    
     /**
     * Creates new thread and posts first post
     * @param object with discussion bot parameters
     * @param object $contentitem object containing content information
     * @param int Id of forum to create thread
     * @param array $status contains errors and status of actions
     */
	function createThread(&$params, &$contentitem, $forumid, &$status)
	{

	}

	 /**
     * Updates information in a specific thread/post
     * @param object with discussion bot parameters
     * @param $existingthread object with existin thread info
     * @param object $contentitem object containing content information
     * @param array $status contains errors and status of actions
     */
	function updateThread(&$params, &$existingthread, &$contentitem, &$status)
	{

	}

	/**
     * Prepares text before saving to db or presentint to joomla article
     * @param string Text to be modified
     * @param $prepareForJoomla boolean to indicate if the text is to be saved to software's db or presented in joomla article
     * @return string Modified text
     */
	function prepareText($text, $prepareForJoomla = false)
	{
		if($prepareForJoomla===false) {
			//first thing is to remove all joomla plugins
			preg_match_all('/\{(.*)\}/U',$text,$matches);
	
			//find each thread by the id
			foreach($matches[1] AS $plugin) {
				//replace plugin with nothing
				$text = str_replace('{'.$plugin.'}',"",$text);
			}
		}
		
		return $text;
	}

	/**
     * Creates a table of posts to be displayed in content item
     * @param object with discussion bot parameters
     * @param obj of thread information
     * @param obj list of posts retrieved from getPosts();
     * @param array of css classes
     * @param obj with discussion bot parameters
     * @return string HTML of table to displayed
     */
	function createPostTable(&$params, &$existingthread, &$posts, &$css)
	{
		/*
		Use the following CSS classes to style your post table.  They are here only
		for reference so there is no need to redeclare them as the array is passed
		in by the plugin

		$css['postArea'] = $params->get("cssClassPostArea");
		$css['postHeader'] = $params->get("cssClassPostHeader");
		$css['postBody'] = $params->get("cssClassPostBody");
		$css['postTitle'] = $params->get("cssClassPostTitle");
		$css['noPostMsg'] = $params->get("cssClassNoPostMsg");
		$css['postUser'] = $params->get("cssClassPostUser");
		$css['userAvatar'] = $params->get("cssClassUserAvatar");
		$css['postDate'] = $params->get("cssClassPostDate");
		$css['postText'] = $params->get("cssClassPostText");
		*/

		return '';
	}

	/**
     * Retrieves the posts to be displayed in the content item if enabled
     * @param object with discussion bot parameters
     * @param object with forumid, threadid, and postid (first post in thread)
     * @return array or object Returns retrieved posts
     */
	function getPosts(&$params, &$existingthread)
	{
		return array();
	}

	/**
	 * Returns the total number of posts in a thread
     * @param object with forumid, threadid, and postid (first post in thread)
	 * @return int
	 */
	function getReplyCount(&$existingthread)
	{
		return 0;
	}
	
	/**
	 * Returns HTML of a quick reply
	 * @return string of html
	 */
	function createQuickReply()
	{
	   	$html  = "<textarea name='quickReply' class='inputbox'></textarea><br>";
	   	$html .= "<div style='width:100%; text-align:right;'><input type='submit' value='Submit'></div>";
	   	return $html;
	}
	
	/**
	 * Creates a post from the quick reply
	 * @param object with discussion bot parameters
	 * @param $ids array with forum id ($ids["forumid"], thread id ($ids["threadid"]) and first post id ($ids["postid"]) 
	 * @param $contentitem object of content item
	 * @param $userinfo object info of the forum user
	 * @return array with status
	 */
	function createPost(&$params, &$ids, &$contentitem, &$userinfo)
	{
		$status = array();
		$status["error"] = false;
		return $status;	
	}
}