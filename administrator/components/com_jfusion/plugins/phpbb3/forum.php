<?php

/**
 * @package JFusion_phpBB3
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Forum Class for phpBB3
 * For detailed descriptions on these functions please check the model.abstractforum.php
 * @package JFusion_phpBB3
 */
class JFusionForum_phpbb3 extends JFusionForum
{
	var $joomlaGlobals;
	
    function getJname()
    {
        return 'phpbb3';
    }

    function getThreadURL($threadid)
    {
        return 'viewtopic.php?t=' . $threadid;
    }

    function getPostURL($threadid, $postid)
    {
        return 'viewtopic.php?p='.$postid.'#p' . $postid;
    }

    function getProfileURL($uid)
    {
        return 'memberlist.php?mode=viewprofile&u='.$uid;
    }

    function getPrivateMessageURL()
    {
        return 'ucp.php?i=pm&folder=inbox';
    }

    function getViewNewMessagesURL()
    {
        return 'search.php?search_id=newposts';
    }


    function getAvatar($puser_id)
    {
        if ($puser_id) {

            $dbparams = JFusionFactory::getParams($this->getJname());
            $db = JFusionFactory::getDatabase($this->getJname());

            $db->setQuery('SELECT user_avatar, user_avatar_type FROM #__users WHERE user_id='.$puser_id);
            $db->query();
            $result = $db->loadObject();

            if (!empty($result)) {
                if ($result->user_avatar_type == 1) {
                    // AVATAR_UPLOAD
                    $url = $dbparams->get('source_url').'download/file.php?avatar='.$result->user_avatar;
                } else if ($result->user_avatar_type == 3) {
                    // AVATAR_GALLERY
                    $db->setQuery("SELECT config_value FROM #__config WHERE config_name='avatar_gallery_path'");
                    $db->query();
                    $path = $db->loadResult();
                    if (!empty($path)) {
                        $url = $dbparams->get('source_url').$path.'/'.$result->user_avatar;
                    } else {
                        $url = '';
                    }
                } else if ($result->user_avatar_type == 2) {
                    // AVATAR REMOTE URL
                    $url = $result->user_avatar;
                } else {
                    $url = '';
                }
                return $url;
            }
        }
        return 0;
    }

    function getPrivateMessageCounts($puser_id)
    {

        if ($puser_id) {

            // read pm counts
            $db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT COUNT(msg_id)
			FROM #__privmsgs_to
			WHERE pm_unread = 1
			AND folder_id <> -2
			AND user_id = '.$puser_id);
            $unreadCount = $db->loadResult();

            // read total pm count
            $db->setQuery('SELECT COUNT(msg_id)
			FROM #__privmsgs_to
			WHERE folder_id NOT IN (-1, -2)
			AND user_id = '.$puser_id);
            $totalCount = $db->loadResult();

            return array('unread' => $unreadCount, 'total' => $totalCount);
        }
        return array('unread' => 0, 'total' => 0);
    }

    function getQuery($usedforums, $result_order, $result_limit, $display_limit)
    {

        //set a WHERE query if a forum list was passed through
        if ($usedforums) {
            $where = ' WHERE a.forum_id IN (' . $usedforums .')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.topic_id , a.topic_first_poster_name, a.topic_poster, a.topic_title, a.topic_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts`  as b ON a.topic_first_post_id = b.post_id " . $where . " ORDER BY a.topic_last_post_time ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT b.post_id , a.topic_last_poster_name, a.topic_last_poster_id, a.topic_last_post_subject, a.topic_last_post_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.topic_last_post_id = b.post_id " . $where . " ORDER BY a.topic_last_post_time ".$result_order." LIMIT 0,".$result_limit.";"),
        1 => array(0 => "SELECT a.topic_id , a.topic_first_poster_name, a.topic_poster, a.topic_title, a.topic_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts`  as b ON a.topic_first_post_id = b.post_id " . $where . " ORDER BY a.topic_time ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT b.post_id , a.topic_last_poster_name, a.topic_last_poster_id, a.topic_last_post_subject, a.topic_last_post_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.topic_last_post_id = b.post_id " . $where . " ORDER BY a.topic_time ".$result_order." LIMIT 0,".$result_limit.";"),
        2 => array(0 => "SELECT a.post_id , b.username, a.poster_id, a.post_subject, a.post_time, left(a.post_text, $display_limit), a.topic_id  FROM `#__posts` as a INNER JOIN #__users as b ON a.poster_id = b.user_id " . $where . " ORDER BY a.post_time ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.post_id , b.username, a.poster_id, a.post_subject, a.post_time, left(a.post_text, $display_limit), a.topic_id  FROM `#__posts` as a INNER JOIN #__users as b ON a.poster_id = b.user_id " . $where . " ORDER BY a.post_time ".$result_order." LIMIT 0,".$result_limit.";")
        );
        return $query;
    }

    function getForumList()
    {
        //get the connection to the db

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT forum_id as id, forum_name as name FROM #__forums
					WHERE forum_type = 1 ORDER BY left_id';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }


    /************************************************
	 * Functions For JFusion Discussion Bot Plugin
	 ***********************************************/
    
    /**
     * Checks to see if a thread already exists for the content item and calls the appropriate function
     * @param object with discussion bot parameters 
     * @param object $contentitem object containing content information
     * @return array Returns status of actions with errors if any
     */
	function checkThreadExists(&$dbparams, &$contentitem, &$existingthread, $forumid)
	{
		$status = array();
		$status['debug'] = array();
        $status['error'] = array();

		//set the timezone to UTC
		date_default_timezone_set('UTC');
		
		if(!empty($existingthread))
		{	
			//datetime post was last updated
			$postModified = $existingthread->modified;
			//datetime content was last updated
			$contentModified = strtotime($contentitem->modified);
		
			//check to make sure the thread still exists in the software
			$jdb = & JFusionFactory::getDatabase($this->getJname());
			$query = "SELECT COUNT(*) FROM #__topics WHERE forum_id = {$existingthread->forumid} AND topic_id = {$existingthread->threadid} AND topic_first_post_id = {$existingthread->postid}";
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
			elseif($contentModified > $postModified)
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
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT topic_id AS threadid, forum_id AS forumid, topic_first_post_id AS postid FROM #__topics WHERE topic_id = $threadid";
		$db->setQuery($query);
		$results = $db->loadObject();
		return $results;
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
			$text = $this->prepareText($contentitem->introtext . $contentitem->fulltext);
		}
		
		//the user information
		$query = "SELECT username, username_clean, user_colour, user_permissions FROM #__users WHERE user_id = '$userid'";
		$jdb->setQuery($query);		
		$phpbbUser = $jdb->loadObject();
		
		$current_time = time();
	
		$topic_row = new stdClass();
		$topic_row->topic_poster = $userid;
		$topic_row->topic_time = $current_time;
		$topic_row->forum_id = $forumid;
		$topic_row->icon_id = false;
		$topic_row->topic_approved	= 1;
		$topic_row->topic_title = $subject;
		$topic_row->topic_first_poster_name	= $phpbbUser->username;
		$topic_row->topic_first_poster_colour = $phpbbUser->user_permissions;
		$topic_row->topic_type = 0;
		$topic_row->topic_time_limit = 0;
		$topic_row->topic_attachment = 0;	
		
		if(!$jdb->insertObject('#__topics', $topic_row, 'topic_id' )){
			$status['error'] = $jdb->stderr();
			return;
		}
		$topicid = $jdb->insertid();

		$message_parser = '';
		$this->phpbbInit($text, $message_parser);	
		
		$post_row = new stdClass();
		$post_row->forum_id			= $forumid;
		$post_row->topic_id 		= $topicid;
		$post_row->poster_id		= $userid;
		$post_row->icon_id			= 0;
		$post_row->poster_ip		= $_SERVER["REMOTE_ADDR"];
		$post_row->post_time		= $current_time;
		$post_row->post_approved	= 1;
		$post_row->enable_bbcode	= 1;
		$post_row->enable_smilies	= 1;
		$post_row->enable_magic_url	= 1;
		$post_row->enable_sig		= 1;
		$post_row->post_username	= $phpbbUser->username_clean;
		$post_row->post_subject		= $subject;
		$post_row->post_text		= $message_parser->message;
		$post_row->post_checksum	= md5($message_parser->message);
		$post_row->post_attachment	= 0;
		$post_row->bbcode_bitfield	= $message_parser->bbcode_bitfield;
		$post_row->bbcode_uid		= $message_parser->bbcode_uid;
		$post_row->post_postcount	= 1;
		$post_row->post_edit_locked	= 0;

		if(!$jdb->insertObject('#__posts', $post_row, 'post_id')) {
			$status['error'] = $jdb->stderr();
			return;
		}
		$postid = $jdb->insertid();			
	
		$topic_row = new stdClass();
		$topic_row->topic_first_post_id			= $postid;
		$topic_row->topic_last_post_id			= $postid;
		$topic_row->topic_last_post_time		= $current_time;
		$topic_row->topic_last_poster_id		= (int) $userid;
		$topic_row->topic_last_poster_name		= $phpbbUser->username_clean;
		$topic_row->topic_last_poster_colour	= $phpbbUser->user_colour;
		$topic_row->topic_last_post_subject		= (string) $subject;
		$topic_row->topic_id					= $topicid;
		if(!$jdb->updateObject('#__topics', $topic_row, 'topic_id' )) {
			$status['error'] = $jdb->stderr();
			return;
		}
		
		$forum_stats = new stdClass();
		$forum_stats->forum_last_post_id 		=  $postid;
		$forum_stats->forum_last_post_subject	= $jdb->Quote($subject);
		$forum_stats->forum_last_post_time 	=  $current_time;
		$forum_stats->forum_last_poster_id 	=  (int) $userid;
		$forum_stats->forum_last_poster_name 	=  $phpbbUser->username_clean;
		$forum_stats->forum_last_poster_colour = $phpbbUser->user_colour;
		$forum_stats->forum_id 				= $forumid;
		$query = "SELECT forum_topics, forum_topics_real, forum_posts FROM #__forums WHERE forum_id = $forumid";
		$jdb->setQuery($query);
		$num = $jdb->loadObject();
		$forum_stats->forum_topics = $num->forum_topics + 1;
		$forum_stats->forum_topics_real = $num->forum_topics_real + 1;
		$forum_stats->forum_posts = $num->forum_posts + 1;
		if(!$jdb->updateObject('#__forums', $forum_stats, 'forum_id' )) {
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
			$text = $this->prepareText($contentitem->introtext . $contentitem->fulltext);
		}
		
		$message_parser = '';
		$this->phpbbInit($text, $message_parser);	

		$current_time = time();
		$userid = $dbparams->get('default_user');
		
		$query = "SELECT post_edit_count FROM #__posts WHERE post_id = $postid";
		$jdb->setQuery($query);
		$count = $jdb->loadResult();
		
		$post_row = new stdClass();
		$post_row->post_subject		= $subject;
		$post_row->post_text		= $message_parser->message;
		$post_row->post_checksum	= md5($message_parser->message);
		$post_row->bbcode_bitfield	= $message_parser->bbcode_bitfield;
		$post_row->bbcode_uid		= $message_parser->bbcode_uid;
		$post_row->post_edit_time 	= $current_time;
		$post_row->post_edit_reason = 'JFusion Discussion Bot '. JText::_('UPDATE');
		$post_row->post_edit_user	= $userid;
		$post_row->post_edit_count	= $count + 1;
		$post_row->post_id 			= $postid;
		if(!$jdb->updateObject('#__posts', $post_row, 'post_id')) {
			$status['error'] = $jdb->stderr();
		} else {		
			//update the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $forumid, $threadid, $postid, $this->getJname());
		}
	}

	/**
	 * Creates a post from the quick reply
	 * @param object with discussion bot parameters
	 * @param $ids array with thread id ($ids["threadid"]) and first post id ($ids["postid"]) 
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
	
			$message_parser = '';
			$this->phpbbInit($text, $message_parser);	

			//get some topic information
			$query = "SELECT topic_title, topic_replies, topic_replies_real FROM #__topics WHERE topic_id = {$ids['threadid']}";
			$jdb->setQuery($query);
			$topic = $jdb->loadObject();			
			//the user information
			$query = "SELECT username, user_colour, user_permissions FROM #__users WHERE user_id = '$userid'";
			$jdb->setQuery($query);		
			$phpbbUser = $jdb->loadObject();
			
			$current_time = time();

			$post_row = new stdClass();
			$post_row->forum_id			= $ids->forumid;
			$post_row->topic_id 		= $ids->threadid;
			$post_row->poster_id		= $userid;
			$post_row->icon_id			= 0;
			$post_row->poster_ip		= $_SERVER["REMOTE_ADDR"];
			$post_row->post_time		= $current_time;
			$post_row->post_approved	= 1;
			$post_row->enable_bbcode	= 1;
			$post_row->enable_smilies	= 1;
			$post_row->enable_magic_url	= 1;
			$post_row->enable_sig		= 1;
			$post_row->post_username	= $phpbbUser->username;
			$post_row->post_subject		= "Re: {$topic->topic_title}";
			$post_row->post_text		= $message_parser->message;
			$post_row->post_checksum	= md5($message_parser->message);
			$post_row->post_attachment	= 0;
			$post_row->bbcode_bitfield	= $message_parser->bbcode_bitfield;
			$post_row->bbcode_uid		= $mssage_parser->bbcode_uid;
			$post_row->post_postcount	= 1;
			$post_row->post_edit_locked	= 0;
						
			if(!$jdb->insertObject('#__posts', $post_row, 'post_id')) {
				$status['error'] = $jdb->stderr();
				return $status;
			}
			$postid = $jdb->insertid();			
			
			$topic_row = new stdClass();
			$topic_row->topic_last_post_id			= $postid;
			$topic_row->topic_last_post_time		= $current_time;
			$topic_row->topic_last_poster_id		= (int) $userid;
			$topic_row->topic_last_poster_name		= $phpbbUser->username;
			$topic_row->topic_last_poster_colour	= $phpbbUser->user_colour;
			$topic_row->topic_last_post_subject		= '';
			$topic_row->topic_replies				= $topic->topic_replies + 1;
			$topic_row->topic_replies_real 			= $topic->topic_replies_real + 1;
			$topic_row->topic_id					= $ids->threadid;
			if(!$jdb->updateObject('#__topics', $topic_row, 'topic_id' )) {
				$status['error'] = $jdb->stderr();
				return $status;
			}

			$query = "SELECT forum_posts FROM #__forums WHERE forum_id = {$ids->forumid}";
			$jdb->setQuery($query);
			$num = $jdb->loadObject();
			
			$forum_stats = new stdClass();
			$forum_stats->forum_last_post_id 		= $postid;
			$forum_stats->forum_last_post_subject	= '';
			$forum_stats->forum_last_post_time 		= $current_time;
			$forum_stats->forum_last_poster_id 		= (int) $userid;
			$forum_stats->forum_last_poster_name 	= $phpbbUser->username;
			$forum_stats->forum_last_poster_colour 	= $phpbbUser->user_colour;
			$forum_stats->forum_posts				= $num->forum_posts + 1;
			$forum_stats->forum_id 					= $ids->forumid;
			$query = "SELECT forum_topics, forum_topics_real, forum_posts FROM #__forums WHERE forum_id = {$ids->forumid}";
			$jdb->setQuery($query);
			$num = $jdb->loadObject();
			$forum_stats->forum_topics = $num->forum_topics + 1;
			$forum_stats->forum_topics_real = $num->forum_topics_real + 1;
			$forum_stats->forum_posts = $num->forum_posts + 1;
			if(!$jdb->updateObject('#__forums', $forum_stats, 'forum_id' )) {
				$status['error'] = $jdb->stderr();
				return $status;
			}		
		}
		return $status;	
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
		
			$text = JFusionFunction::parseCode($text,'bbcode');	
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
					$avatarSrc = $this->getAvatar($p->user_id);
                } else {
                	$avatarSrc = JFusionFunction::getAltAvatar($avatar_software,$p->user_id,true);
                }
    	            
				if($avatarSrc) {
					$size = getimagesize($avatar);
					$w = $size[0];
					$h = $size[1];
					if($size[0]>60) {
						$scale = min(60/$w, 80/$h);
						$w = floor($scale*$w);
						$h = floor($scale*$h);
					}
					$avatar = "<div class='{$css["userAvatar"]}'><img height='$h' width='$w' src='$avatarSrc'></div>";
				} else {
					$avatar = "";
				}
			} else {
				$avatar = "";
			}
			$table .= $avatar;

			//post title
			$urlstring_pre = JFusionFunction::routeURL($this->getPostURL($p->topic_id,$p->post_id), $itemid);
			$title = '<a href="'. $urlstring_pre . '">'. $p->post_subject .'</a>';
			$table .= "<div class = '{$css["postTitle"]}'>{$title}</div>\n";

			//user info
			if ($showuser) {
				if ($userlink) {
					if(!empty($link_software) && $link_software != 'jfusion' && $link_software!='custom') {
						$user_url = JFusionFunction::getAltProfileURL($link_software,$p->username);
					} elseif ($link_software=='custom' && !empty($userlink_custom)) {
						$userlookup = JFusionFunction::lookupUser($this->getJname(),$p->user_id,false);
						$user_url = $userlink_custom.$userlookup->id;
						
					} else {
						$user_url = false;
					}
					
					if($user_url === false) {
						$user_url = JFusionFunction::routeURL($this->getProfileURL($p->user_id), $itemid);
					}
					$user = '<a href="'. $user_url . '">'.$p->username.'</a>';
				} else {
					$user = $p->username;
				}
				
				$table .= "<div class='{$css["postUser"]}'> by $user</div>";
			}

			//post date
			if($showdate){
				jimport('joomla.utilities.date');
				$JDate =  new JDate($p->post_time);
				$JDate->setOffset($tz_offset);
				$date = $JDate->toFormat($date_format);
				$table .= "<div class='{$css["postDate"]}'>".$date."</div>";
			} 

			//post body
			$table .= "<div class='{$css["postText"]}'>{$this->prepareText($p->post_text,true)}</div> \n";
			$table .= "</div>";
		}

		return $table;
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
		$body_limit = $dbparams->get("body_limit");
		$bodyLimit = (empty($body_limit) || trim($body_limit)==0) ? "p.post_text" : "left(p.post_text, $body_limit) AS post_text";

		$where = "WHERE p.topic_id = {$threadid} AND p.post_id != {$postid} AND p.post_approved = 1";
		$query = "SELECT p.post_id , u.username, u.user_id, p.post_subject, p.post_time, $bodyLimit, p.topic_id FROM `#__posts` as p INNER JOIN `#__users` as u ON p.poster_id = u.user_id $where ORDER BY p.post_time $sort $limit";

		$jdb = & JFusionFactory::getDatabase($this->getJname());
		$jdb->setQuery($query);
		$posts = $jdb->loadObjectList();

		return $posts;
	}

	function getReplyCount(&$existingthread)
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) AS c FROM #__posts WHERE topic_id = {$existingthread->threadid} AND post_id != {$existingthread->postid}";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}
	
	//needed to parse the bbcode for phpbb
	function phpbbInit(&$text, &$message_parser)
	{
		$this->joomlaGlobals = $GLOBALS;
		if(!class_exists('parse_message')) {
			$params = JFusionFactory::getParams($this->getJname());

			$source_path = $params->get('source_path');

	        define('IN_PHPBB',true);
			global $phpbb_root_path, $phpEx, $db;
			$phpbb_root_path = $source_path .'/';
			$phpEx = "php";
			
			if (!function_exists('utf8_clean_string')) {
				//load the filtering functions for phpBB3
				global $jname;
				$jname = $this->getJname();		
				require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'discussionbot_clean.php');
			}
		
			include_once($source_path . '/config.php');
			include_once($source_path . '/includes/constants.php');
			include_once($source_path . '/includes/db/dbal.php');
			include_once($source_path . '/includes/db/mysql.php');

			$db = new dbal_mysql();
			$db->sql_connect($dbhost, $dbuser, $dbpasswd, $dbname, $dbport, false, true);	
				
			include_once($source_path . '/includes/bbcode.php');
			include_once($source_path . '/includes/functions.php');
			include_once($source_path . '/includes/functions_content.php');
			include_once($source_path . '/includes/message_parser.php');
										
		}

		$message_parser = new parse_message($text);
		$message_parser->parse(1, 1, 1);
		$text = $message_parser->message;

		$GLOBALS = $this->joomlaGlobals;
		JFusionFunction::reconnectJoomlaDb();
	}
}