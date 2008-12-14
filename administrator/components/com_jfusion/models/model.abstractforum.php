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