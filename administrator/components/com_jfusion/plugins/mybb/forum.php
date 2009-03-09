<?php

/**
 * @package JFusion_MyBB
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Forum Class for MyBB
 * For detailed descriptions on these functions please check the model.abstractforum.php
 * @package JFusion_MyBB
 */
class JFusionForum_mybb extends JFusionForum
{

    function getJname()
    {
        return 'mybb';
    }

    function getThreadURL($threadid)
    {
       return  'showthread.php?tid='.$threadid;

    }

    function getPostURL($threadid, $postid)
    {
        return  'showthread.php?tid='.$threadid.'&amp;pid='.$postid.'#pid'.$postid;
    }

    function getProfileURL($uid)
    {
        return  'member.php?action=profile&amp;uid'.$uid;
    }


    function getQuery($usedforums, $result_order, $result_limit, $display_limit)
    {              
		$where = (!empty($usedforums)) ? ' WHERE a.fid IN (' . $usedforums .')' : '';
		$end = $result_order." LIMIT 0,".$result_limit;
		
		$query = array(
			LAT => "SELECT a.tid AS threadid, b.pid AS postid, b.username, b.uid AS userid, b.subject, b.dateline FROM #__threads as a INNER JOIN #__posts as b ON a.firstpost = b.pid $where ORDER BY a.lastpost $end",
			LCT => "SELECT a.tid AS threadid, b.pid AS postid, b.username, b.uid AS userid, b.subject, b.dateline, left(b.message, $display_limit) AS body FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpost = b.pid $where ORDER BY a.dateline $end", 
			LCP => "SELECT tid AS threadid, pid AS postid, username, uid AS userid, subject, dateline, left(message, $display_limit) AS body FROM `#__post` " . str_replace('a.fid','fid',$where) . " ORDER BY dateline $end"
		);
	
		return $query;
    }

    function getThread($threadid)
    {
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT tid AS threadid, fid AS forumid, firstpost AS postid FROM #__threads WHERE tid = $threadid";
		$db->setQuery($query);
		$results = $db->loadObject();
		return $results;
    }

	function getReplyCount(&$existingthread)
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT replies FROM #__threads WHERE tid = {$existingthread->threadid}";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}    
    
    function getForumList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT fid as id, name FROM #__forums';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }


    function getPrivateMessageCounts($userid)
    {

        if ($userid)
        {

        	//get the connection to the db
        	$db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT totalpms, newpms FROM #__users WHERE uid = '.$userid);
            $pminfo = $db->loadObject();


            return array('unread' => $pminfo->newpms, 'total' => $pminfo->totalpms);
        }
        return array('unread' => 0, 'total' => 0);
    }


    function getPrivateMessageURL()
    {
        return 'private.php';
    }

    function getViewNewMessagesURL()
    {
        return 'search.php?action=getnew';
    }


    function getAvatar($userid)
    {
        	//get the connection to the db
        	$db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT avatar FROM #__users WHERE uid = '.$userid);
            $avatar = $db->loadResult();

            $avatar = substr($avatar, 2);
            $params = JFusionFactory::getParams($this->getJname());
            $url = $params->get('source_url'). $avatar;
            return $url;
    }

}