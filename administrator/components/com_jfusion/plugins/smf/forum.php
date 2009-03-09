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
//        return  'member.php?u='.$uid;
        return  'index.php?action=profile&u='.$uid;
    }

    function getActivityQuery($usedforums, $result_order, $result_limit, $display_limit)
    {
        $where = (!empty($usedforums)) ? ' WHERE a.ID_BOARD IN (' . $usedforums .')' : '';
		$end = $result_order." LIMIT 0,".$result_limit;
		
		$query = array(
			LAT => "SELECT a.ID_TOPIC AS threadid, a.ID_LAST_MSG AS postid, b.posterName AS username, b.ID_MEMBER AS userid, b.subject AS subject, b.posterTime AS dateline FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_FIRST_MSG = b.ID_MSG INNER JOIN `#__messages` AS c ON a.ID_LAST_MSG = c.ID_MSG $where ORDER BY c.posterTime $end",
			LCT => "SELECT a.ID_TOPIC AS threadid, b.ID_MSG AS postid, b.posterName AS username, b.ID_MEMBER AS userid, b.subject AS subject, left(b.body, $display_limit) AS body, b.posterTime AS dateline FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_FIRST_MSG = b.ID_MSG $where ORDER BY b.posterTime $end", 
			LCP => "SELECT ID_TOPIC AS threadid, ID_MSG AS postid, posterName AS username, ID_MEMBER AS userid, subject AS subject, left(body, $display_limit) AS body, posterTime AS dateline FROM `#__messages` " . str_replace('a.ID_BOARD','ID_BOARD',$where) . " ORDER BY posterTime $end"
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
					$url = JFusionFunction::getJoomlaURL()."index.php?option=com_jfusion&Itemid=2&action=dlattach;attach=".$attachment->ID_ATTACH.";type=avatar";

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

	function checkThreadExists(&$dbparams, &$contentitem, &$existingthread, $forumid)
	{
	    $status = array();
        $status['debug'] = array();
        $status['error'] = array();

		//set the timezone to UTC
		date_default_timezone_set('UTC');

		if(!empty($existingthread))
		{
			//check to make sure the thread still exists in the software
			$jdb = & JFusionFactory::getDatabase($this->getJname());
			$query = "SELECT COUNT(*) FROM #__messages WHERE ID_BOARD = {$existingthread->forumid} AND ID_TOPIC = {$existingthread->threadid} AND ID_MSG = {$existingthread->postid}";
			$jdb->setQuery($query);
			if($jdb->loadResult()==0)
			{
				//the thread no longer exists in the software!!  recreate it
				$this->createThread($dbparams, $contentitem, $forumid, $status);
            	if (empty($status['error'])) {
                	$status['action'] = 'created';
            	}

            	return $status;
			}
			elseif(strtotime($contentitem->modified) > $existingthread->modified)
			{
				//update the post if the content has been updated
				$this->updateThread($dbparams, $existingthread, $contentitem, $status);
				if (empty($status['error'])) {
                	$status['action'] = 'updated';
            	}

            	return $status;
			}
		}
	    else
	    {
	    	//thread does not exist; create it
            $this->createThread($dbparams, $contentitem, $forumid, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            return $status;
        }
	}

     /**
     * Creates new thread and posts first post
     * @param object with discussion bot parameters
     * @param object $contentitem object containing content information
     * @param int Id of forum to create thread
     * @param array $status contains errors and status of actions
     */
	function createThread(&$dbparams, &$contentitem, $forumid, &$status)
	{
		//setup some variables
		$userid = $dbparams->get("default_userid");
		$firstPost = $dbparams->get("first_post");
		$jdb =& JFusionFactory::getDatabase($this->getJname());
		$subject = trim(strip_tags($contentitem->title));


		//set what should be posted as the first post
		if($firstPost=="articleLink") {
			//create link
			$forumText = $dbparams->get("first_post_link_text");
			if(empty($forumText)){
				$forumText = $this->prepareText($contentitem->title);
			}
			$text = $this->prepareText(JFusionFunction::createJoomlaArticleURL($contentitem,$forumText));
		} else {
			//prepare the text for posting
			$text = $this->prepareText($contentitem->introtext.$contentitem->fulltext);
		}

		//the user information
		$query = "SELECT memberName, emailAddress FROM #__members WHERE ID_MEMBER = '$userid'";
		$jdb->setQuery($query);
		$smfUser = $jdb->loadObject();

		$current_time = time();

		$topic_row = new stdClass();

		$topic_row->isSticky = 0;
		$topic_row->ID_BOARD = $forumid;
		$topic_row->ID_FIRST_MSG = $topic_row->ID_LAST_MSG = 0;
		$topic_row->ID_MEMBER_STARTED = $topic_row->ID_MEMBER_UPDATED =  $userid;
		$topic_row->ID_POLL = 0;
		$topic_row->numReplies = 0;
		$topic_row->numViews = 0;
		$topic_row->locked = 0;

		if(!$jdb->insertObject('#__topics', $topic_row, 'ID_TOPIC' )){
			$status['error'] = $jdb->stderr();
			return;
		}
		$topicid = $jdb->insertid();

		$post_row = new stdClass();
		$post_row->ID_BOARD			= $forumid;
		$post_row->ID_TOPIC 		= $topicid;
		$post_row->posterTime		= $current_time;
		$post_row->ID_MEMBER		= $userid;
		$post_row->ID_MSG_MODIFIED	= $userid;
		$post_row->subject			= $subject;
		$post_row->posterName		= $smfUser->memberName;
		$post_row->posterEmail		= $smfUser->emailAddress;
		$post_row->posterIP			= $_SERVER["REMOTE_ADDR"];
		$post_row->smileysEnabled	= 1;
		$post_row->modifiedTime		= 0;
		$post_row->modifiedName		= '';
		$post_row->body				= $text;
		$post_row->icon				= 'xx';

		if(!$jdb->insertObject('#__messages', $post_row, 'ID_MSG')) {
			$status['error'] = $jdb->stderr();
			return;
		}
		$postid = $jdb->insertid();

		$topic_row = new stdClass();

		$topic_row->ID_FIRST_MSG	= $postid;
		$topic_row->ID_LAST_MSG		= $postid;
		$topic_row->ID_TOPIC 		= $topicid;
		if(!$jdb->updateObject('#__topics', $topic_row, 'ID_TOPIC' )) {
			$status['error'] = $jdb->stderr();
			return;
		}

		$query = "SELECT numTopics, numPosts FROM #__boards WHERE ID_BOARD = $forumid";
		$jdb->setQuery($query);
		$num = $jdb->loadObject();

		$forum_stats = new stdClass();
		$forum_stats->numPosts =  $num->numPosts +1;
		$forum_stats->numTopics =  $num->numTopics +1;
		$forum_stats->ID_LAST_MSG =  $postid;
		$forum_stats->ID_MSG_UPDATED =  $postid;
		$forum_stats->ID_BOARD =  $forumid;
		if(!$jdb->updateObject('#__boards', $forum_stats, 'ID_BOARD' )) {
			$status['error'] = $jdb->stderr();
			return;
		}

		if(!empty($topicid) && !empty($postid)) {
			//save the threadid to the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $forumid, $topicid, $postid, $this->getJname());
		}
	}

	 /**
     * Updates information in a specific thread/post
     * @param object with discussion bot parameters
     * @param object with existing thread info
     * @param object $contentitem object containing content information
     * @param array $status contains errors and status of actions
     */
	function updateThread(&$dbparams, &$existingthread, &$contentitem, &$status)
	{
		$threadid =& $existingthread->threadid;
		$forumid =& $existingthread->forumid;
		$postid =& $existingthread->postid;

		//setup some variables
		$firstPost = $dbparams->get("first_post");
		$jdb =& JFusionFactory::getDatabase($this->getJname());
		$subject = trim(strip_tags($contentitem->title));

		//set what should be posted as the first post
		if($firstPost=="articleLink") {
			//create link
			$forumText = $dbparams->get("first_post_link_text");
			if(empty($forumText)){
				$forumText = $this->prepareText($contentitem->title);
			}
			$text = $this->prepareText(JFusionFunction::createJoomlaArticleURL($contentitem,$forumText));
		} else {
			//prepare the text for posting
			$text = $this->prepareText($contentitem->introtext.$contentitem->fulltext);
		}

		$current_time = time();
		$userid = $dbparams->get('default_user');

		$query = "SELECT memberName FROM #__members WHERE ID_MEMBER = '$userid'";
		$jdb->setQuery($query);
		$smfUser = $jdb->loadObject();

		$post_row = new stdClass();
		$post_row->subject			= $subject;
		$post_row->body				= $text;
		$post_row->modifiedTime 	= $current_time;
		$post_row->modifiedName 	= $smfUser->memberName;
		$post_row->ID_MSG_MODIFIED	= $userid;
		$post_row->ID_MSG 			= $postid;
		if(!$jdb->updateObject('#__messages', $post_row, 'ID_MSG')) {
			$status['error'] = $jdb->stderr();
		} else {
			//update the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $forumid, $threadid, $postid, $this->getJname());
		}
	}

	/**
	 * Creates a post from the quick reply
	 * @param object with discussion bot parameters
	 * @param $ids stdClass with thread id ($ids->threadid) and first post id ($ids->postid)
	 * @param $contentitem object of content item
	 * @param $userinfo object info of the forum user
	 * @return array with status
	 */
	function createPost(&$dbparams, &$ids, &$contentitem, &$userinfo)
	{
		$status = array();
		$status["error"] = false;

		//setup some variables
		$userid = $userinfo->userid;
		$jdb =& JFusionFactory::getDatabase($this->getJname());
		$text = JRequest::getVar('quickReply', false, 'POST');

		if(!empty($text)) {
			$text = $this->prepareText($text);

			//get some topic information
			$where = "WHERE t.ID_TOPIC = {$ids->threadid} AND m.ID_MSG = t.ID_FIRST_MSG";
			$query = "SELECT t.ID_FIRST_MSG , t.numReplies, m.subject FROM `#__messages` as m INNER JOIN `#__topics` as t ON t.ID_TOPIC = m.ID_TOPIC $where";

			$jdb->setQuery($query);
			$topic = $jdb->loadObject();

			//the user information
			$query = "SELECT memberName,posterEmail FROM #__members WHERE ID_MEMBER = '$userid'";
			$jdb->setQuery($query);
			$smfUser = $jdb->loadObject();

			$current_time = time();

			$post_row = new stdClass();
			$post_row->ID_BOARD			= $ids->forumid;
			$post_row->ID_TOPIC 		= $ids->threadid;
			$post_row->posterTime		= $current_time;
			$post_row->ID_MEMBER		= $userid;
			$post_row->ID_MSG_MODIFIED	= $userid;
			$post_row->subject			= 'Re: '.$topic->subject;
			$post_row->posterName		= $smfUser->memberName;
			$post_row->posterEmail		= $smfUser->posterEmail;
			$post_row->posterIP			= $_SERVER["REMOTE_ADDR"];
			$post_row->smileysEnabled	= 1;
			$post_row->modifiedTime		= 0;
			$post_row->modifiedName		= '';
			$post_row->body				= $text;
			$post_row->icon				= 'xx';

			if(!$jdb->insertObject('#__messages', $post_row, 'ID_MSG')) {
				$status['error'] = $jdb->stderr();
				return $status;
			}
			$postid = $jdb->insertid();

			$topic_row = new stdClass();
			$topic_row->ID_LAST_MSG			= $postid;
			$topic_row->ID_MEMBER_UPDATED	= (int) $userid;
			$topic_row->numReplies			= $topic->numReplies + 1;
			$topic_row->ID_TOPIC			= $ids->threadid;
			if(!$jdb->updateObject('#__topics', $topic_row, 'ID_TOPIC' )) {
				$status['error'] = $jdb->stderr();
				return $status;
			}

			$forum_stats = new stdClass();
			$forum_stats->ID_LAST_MSG 		=  $postid;
			$forum_stats->ID_MSG_UPDATED	=  $postid;
			$query = "SELECT numPosts FROM #__boards WHERE ID_BOARD = {$ids->forumid}";
			$jdb->setQuery($query);
			$num = $jdb->loadObject();
			$forum_stats->numPosts = $num->numPosts + 1;
			$forum_stats->ID_BOARD 			= $ids->forumid;
			if(!$jdb->updateObject('#__boards', $forum_stats, 'ID_BOARD' )) {
				$status['error'] = $jdb->stderr();
				return $status;
			}
		}
		return $status;
	}

	/**
     * Retrieves the posts to be displayed in the content item if enabled
     * @param object with discussion bot parameters
     * @param int Id of thread
     * @param int Id of first post which is useful if you do not want the first post to be included in results
     * @return array or object Returns retrieved posts
     */
	function getPosts(&$dbparams, &$existingthread)
	{
		$threadid =& $existingthread->threadid;
		$postid =& $existingthread->postid;

		//set the query
		$limit_posts = $dbparams->get("limit_posts");
		$limit = empty($limit_posts) || trim($limit_posts)==0 ? "" :  "LIMIT 0,$limit_posts";
		$sort = $dbparams->get("sort_posts");
		$body_limit = (int) $dbparams->get("body_limit");

		$bodyLimit = empty($body_limit) || trim($body_limit)==0 ? "p.body" : "left(p.body, $body_limit) as body";

		$where = "WHERE p.ID_TOPIC = {$threadid} AND p.ID_MSG != {$postid}";
		$query = "SELECT p.ID_TOPIC , u.memberName, u.ID_MEMBER, p.subject, p.posterTime, $bodyLimit, p.ID_TOPIC FROM `#__messages` as p INNER JOIN `#__members` as u ON p.ID_MEMBER = u.ID_MEMBER $where ORDER BY p.posterTime $sort $limit";

		$jdb = & JFusionFactory::getDatabase($this->getJname());
		$jdb->setQuery($query);
		$posts = $jdb->loadObjectList();

		return $posts;
	}

	function getReplyCount(&$existingthread)
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT numReplies FROM #__topics WHERE ID_TOPIC = {$existingthread->threadid}";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
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
	function createPostTable(&$dbparams, &$existingthread, &$posts, &$css)
	{
		//get required params
		defined('_DATE_FORMAT_LC2') or define('_DATE_FORMAT_LC2','%A, %d %B %Y %H:%M');
		$date_format = $dbparams->get('custom_date', _DATE_FORMAT_LC2);
		$tz_offset = intval($dbparams->get('tz_offset'));
		$showdate = intval($dbparams->get('show_date'));
		$showuser = intval($dbparams->get('show_user'));
		$showavatar = $dbparams->get("show_avatar");
		$avatar_software = $dbparams->get("avatar_software",false);
		$userlink = intval($dbparams->get('user_link'));
		$link_software = $dbparams->get('userlink_software',false);
		$userlink_custom = $dbparams->get('userlink_custom',false);
		$itemid = $dbparams->get("itemid");
		$jname = $this->getJname();
		$header = $dbparams->get("post_header");

		if($showdate && $showuser) $colspan = 2;
		else $colspan = 1;

		$table  = "<div class='{$css["postHeader"]}'>$header</div>\n";

		for ($i=0; $i<count($posts); $i++)
		{
			$p = &$posts[$i];

			$table .= "<div class = '{$css["postBody"]}'> \n";

			//avatar
			if($showavatar){
                if(empty($avatar_software) || $avatar_software=='jfusion') {
					$avatarSrc = $this->getAvatar($p->ID_MEMBER);
                } else {
                	$avatarSrc = JFusionFunction::getAltAvatar($avatar_software,$p->ID_MEMBER,true,$this->getJname(),$p->memberName);
                }
    	           
				if(empty($avatarSrc)) {
					$avatarSrc = JFusionFunction::getJoomlaURL()."administrator".DS."components".DS."com_jfusion".DS."images".DS."noavatar.png";
				}
				
				$size = @getimagesize($avatarSrc);
				$w = $size[0];
				$h = $size[1];
				if($size[0]>60) {
					$scale = min(60/$w, 80/$h);
					$w = floor($scale*$w);
					$h = floor($scale*$h);
				} else {
					$w = 60;
					$h = 80;
				}
			
				$avatar = "<div class='{$css["userAvatar"]}'><img height='$h' width='$w' src='$avatarSrc'></div>";			
			} else {
				$avatar = "";
			}
			$table .= $avatar;

			//post title
			$urlstring_pre = JFusionFunction::routeURL($this->getPostURL($p->ID_TOPIC,$p->ID_MSG), $itemid);
			$title = '<a href="'. $urlstring_pre . '">'. $p->subject .'</a>';
			$table .= "<div class = '{$css["postTitle"]}'>{$title}</div>\n";

			//user info
			if ($showuser)
			{
				if ($userlink) {
					if(!empty($link_software) && $link_software != 'jfusion' && $link_software!='custom') {
						$user_url = JFusionFunction::getAltProfileURL($link_software,$p->memberName);
					} elseif ($link_software=='custom' && !empty($userlink_custom)) {
						$userlookup = JFusionFunction::lookupUser($this->getJname(),$p->ID_MEMBER,false, $p->memberName);
						$user_url =  $userlink_custom.$userlookup->id;
					} else {
						$user_url = false;
					}

					if($user_url === false) {
						$user_url = JFusionFunction::routeURL($this->getProfileURL($p->ID_MEMBER), $itemid);
					}
					$user = '<a href="'. $user_url . '">'.$p->memberName.'</a>';
				} else {
					$user = $p->memberName;
				}

				$table .= "<div class='{$css["postUser"]}'> by $user</div>";
			}

			//post date
			if($showdate) {
				jimport('joomla.utilities.date');
				$JDate =  new JDate($p->posterTime);
				$JDate->setOffset($tz_offset);
				$date = $JDate->toFormat($date_format);
				$table .= "<div class='{$css["postDate"]}'>".$date."</div>";
			}

			//post body
			$text = $this->prepareText($p->body,true);
			$table .= "<div class='{$css["postText"]}'>{$text}</div> \n";
			$table .= "</div>";
		}
		return $table;
	}

	/**
     * Prepares text before saving to db or presentint to joomla article
     * @param string Text to be modified
     * @param $prepareForJoomla boolean to indicate if the text is to be saved to software's db or presented in joomla article
     * @return string Modified text
     */
	function prepareText($text, $prepareForJoomla = false)
	{
		static $bbcode;

		if($prepareForJoomla===false) {
			//first thing is to remove all joomla plugins
			preg_match_all('/\{(.*)\}/U',$text,$matches);

			//find each thread by the id
			foreach($matches[1] AS $plugin) {
				//replace plugin with nothing
				$text = str_replace('{'.$plugin.'}',"",$text);
			}
 			if(!is_array($bbcode)) {
 				$bbcode = array();
 				//pattens to run in begening
				$bbcode[0][] = "#<a.*?href=['|\"](ftp://)(.*?)['|\"].*?>(.*?)</a>#si";
 				$bbcode[1][] = "[ftp=$1$2]$3[/ftp]";

				//pattens to run in end
				$bbcode[2][] = '#<table.*?>(.*?)<\/table>#si';
				$bbcode[3][] = '[table]$1[/table]';

				$bbcode[2][] = '#<tr.*?>(.*?)<\/tr>#si';
				$bbcode[3][] = '[tr]$1[/tr]';

				$bbcode[2][] = '#<td.*?>(.*?)<\/td>#si';
				$bbcode[3][] = '[td]$1[/td]';

				$bbcode[2][] = '#<strong.*?>(.*?)<\/strong>#si';
				$bbcode[3][] = '[b]$1[/b]';

				$bbcode[2][] = '#<(strike|s)>(.*?)<\/\\1>#sim';
				$bbcode[3][] = '[s]$2[/s]';
 			}
 			
			$text = JFusionFunction::parseCode($text,'bbcode',false,$bbcode);
		} else {
			//remove phpbb's bbcode uids
			$text = preg_replace("#\[(.*?):(.*?)]#si","[$1]",$text);
			//decode html entities
			$text = html_entity_decode($text);
			//parse bbcode to html
			$text = JFusionFunction::parseCode($text,'html');
		}

		return $text;
	}
	
    function getThread($threadid)
    {
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT ID_TOPIC AS threadid, ID_BOARD AS forumid, ID_FIRST_MSG AS postid FROM #__topics WHERE ID_TOPIC = $threadid";
		$db->setQuery($query);
		$results = $db->loadObject();
		return $results;
    }
}
