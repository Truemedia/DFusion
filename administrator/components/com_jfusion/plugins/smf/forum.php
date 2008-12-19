<?php

/**
* @package JFusion_SMF
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Forum Class for SMF 1.1.x
 * For detailed descriptions on these functions please check the model.abstractforum.php
 * @package JFusion_SMF
 */
class JFusionForum_smf extends JFusionForum
{

    function getJname()
    {
        return 'smf';
    }

    function getThreadURL($threadid)
    {
        return  'index.php?topic=' . $threadid;
    }

    function getPostURL($threadid, $postid)
    {
        return  'index.php?topic=' . $threadid . '.msg'.$postid.'#msg' . $postid;
    }

    function getProfileURL($uid)
    {
        return  'member.php?u='.$uid;
    }

    function getQuery($usedforums, $result_order, $result_limit)
    {
        if ($usedforums) {
            $where = ' WHERE a.ID_BOARD IN (' . $usedforums .')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.ID_TOPIC , c.posterName, c.ID_MEMBER, c.subject, c.posterTime, left(c.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_LAST_MSG = b.ID_MSG INNER JOIN `#__messages` as c ON a.ID_FIRST_MSG = c.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";" ,
		1 => "SELECT b.ID_MSG, b.posterName, b.ID_MEMBER, b.subject, b.posterTime, a.ID_TOPIC, left(b.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_LAST_MSG = b.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";"  ),
        1 => array(0 => "SELECT a.ID_TOPIC , b.posterName, b.ID_MEMBER, b.subject, b.posterTime, left(b.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_FIRST_MSG = b.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.ID_TOPIC , c.posterName, c.ID_MEMBER, c.subject, c.posterTime, left(c.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_FIRST_MSG = b.ID_MSG INNER JOIN `#__messages` as c ON a.ID_LAST_MSG = c.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";"),
        2 => array(0 => "SELECT a.ID_MSG , a.posterName, a.ID_MEMBER, a.subject, a.posterTime, left(a.body, $result_limit), a.ID_TOPIC  FROM `#__messages` as a " . $where . " ORDER BY a.posterTime ".$result_order." LIMIT 0,".$result_limit.";" ,
        1 => "SELECT a.ID_MSG , a.posterName, a.ID_MEMBER, a.subject, a.posterTime, left(a.body, $result_limit), a.ID_TOPIC  FROM `#__messages` as a " . $where . " ORDER BY a.posterTime ".$result_order." LIMIT 0,".$result_limit.";")
        );

        return $query;

    }
    function getForumList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT ID_BOARD as id, name FROM #__boards';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getPrivateMessageCounts($userid)
    {

        if ($userid) {

            // initialise some objects
            $db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT unreadMessages FROM #__members WHERE ID_MEMBER = '.$userid);
            $unreadCount = $db->loadResult();

            // read total pm count
            $db->setQuery('SELECT instantMessages FROM #__members WHERE ID_MEMBER = '.$userid);
            $totalCount = $db->loadResult();

            return array('unread' => $unreadCount, 'total' => $totalCount);
        }
        return array('unread' => 0, 'total' => 0);
    }

    function getAvatar($puser_id)
    {
        return 0;
    }

}
