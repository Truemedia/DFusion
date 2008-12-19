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
      if ($usedforums)
      {
         $where = ' WHERE a.fid IN (' . $usedforums .')';
      } else {
         $where = '';
      }
      $query = array(0 => array( 0 => "SELECT a.tid , a.username, a.uid, a.subject, a.dateline, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.firstpost = b.pid " . $where . " ORDER BY a.lastpost ".$result_order." LIMIT 0,".$result_limit.";",
                                 1 => "SELECT a.tid , a.lasposter, a.lasposteruid, a.subject, a.laspost, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.lastpost = b.dateline " . $where . " ORDER BY a.lastpost ".$result_order." LIMIT 0,".$result_limit.";"),
                     1 => array( 0 => "SELECT a.tid , a.username, a.uid, a.subject, a.dateline, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.firstpost = b.pid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";",
                                 1 => "SELECT a.tid , a.lastposter, a.lasposteruid, a.subject, a.dateline, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.lastpost = b.dateline " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";"),
                     2 => array( 0 => "SELECT a.pid , b.username, a.uid, a.subject, a.dateline, left(a.message, $display_limit), a.tid  FROM `#__posts` as a INNER JOIN #__users as b ON a.uid = b.uid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";",
                                 1 => "SELECT a.pid , b.username, a.uid, a.subject, a.dateline, left(a.message, $display_limit), a.tid  FROM `#__posts` as a INNER JOIN #__users as b ON a.uid = b.uid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";")
                 );
                 return $query;
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