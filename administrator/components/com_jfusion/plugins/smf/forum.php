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

	if ($puser_id) {
		// Get SMF Params and get an instance of the database
		$params = JFusionFactory::getParams($this->getJname());
		$db = JFusionFactory::getDatabase($this->getJname());
		// Load member params from database "mainly to get the avatar"
		$db->setQuery('SELECT * FROM #__members WHERE ID_MEMBER='.$puser_id);
		$db->query();
		$result = $db->loadObject();

		if (!empty($result)) {
			// SMF has a wierd way of holding attachments. Get instance of the attachments table
			$db->setQuery('SELECT * FROM #__attachments WHERE ID_MEMBER='.$puser_id);
			$db->query();
			$attachment = $db->loadObject();
			// See if the user has a specific attachment ment for an avatar
			if($attachment->ID_THUMB == 0 && $attachment->ID_MESSAGE == 0 && empty($result->avatar))   {
				$url = JURI::base()."index.php?option=com_jfusion&Itemid=2&action=dlattach;attach=".$attachment->ID_ATTACH.";type=avatar";

			// If user didnt, check to see if the avatar specified in the first query is a url. If so use it.
			} else if(preg_match("/http(s?):\/\//",$result->avatar)){
				$url = $result->avatar;
			} else {
				// If the avatar specified in the first query is not a url but is a file name. Make it one
				$db->setQuery('SELECT * FROM #__settings WHERE variable = "avatar_url"');
				$avatarurl = $db->loadObject();
				// Check for trailing slash. If there is one DONT ADD ONE!
				if(substr($avatarurl->value, -1) == DS){
					$url = $avatarurl->value.$result->avatar;
				// I like redundancy. Recheck to see if there isnt a trailing slash. If there isnt one, add one.
				} else if(substr($avatarurl->value, -1) !== DS){
					$url = $avatarurl->value."/".$result->avatar;
				}
			}
		return $url;
		}
	}
	}

}
