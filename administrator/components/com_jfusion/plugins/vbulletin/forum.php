<?php
/**
* @package JFusion_vBulletin
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Forum Class for vBulletin
 * For detailed descriptions on these functions please check the model.abstractforum.php
 * @package JFusion_vBulletin
 */
class JFusionForum_vbulletin extends JFusionForum
{
	function JFusionForum_vbulletin()
	{
		//get the params object
	    $this->params = JFusionFactory::getParams($this->getJname());
	}

	function vBulletinInit()
	{
		//only initialize the vb framework if it has not already been done
		if(!defined('VB_AREA'))
		{
			//get the params object
	        $this->params = JFusionFactory::getParams($this->getJname());

			//load the vbulletin framework
			define('VB_AREA','External');
			define('SKIP_SESSIONCREATE', 1);
			define('SKIP_USERINFO', 1);
			define('CWD', $this->params->get('source_path'));

			if(file_exists(CWD))
			{
				require_once(CWD.'/includes/init.php');

				//force into global scope
				$GLOBALS["vbulletin"] =& $vbulletin;
				$GLOBALS["db"] =& $vbulletin->db;

				return true;
			}
			else
			{
				JError::raiseWarning(500, JText::_('SOURCE_PATH_NOT_FOUND'));
				return false;
			}
		}
		else
		{
			return true;
		}
	}

	function checkThreadExists($contentitem)
	{
	    $status = array();
        $status['debug'] = array();
        $status['error'] = array();

		//check to see if a valid $content object was passed on
		if(!is_object($contentitem)){
			$status['error'][] = JText::_('NO_CONTENT_DATA_FOUND');
			return $status;
		}

		$forumid = $this->getDefaultForum($contentitem);

		//see if the thread exists
		$existingthread = $this->getThread($contentitem->id);

		//set the timezone to UTC
		date_default_timezone_set('UTC');

		//datetime post was last updated
		$postModified = $existingthread->modified;

		//datetime content was last updated
		$contentModified = strtotime($contentitem->modified);

		if(!empty($existingthread))
		{
			//check to make sure the thread still exists in the software
			$jdb = & JFusionFactory::getDatabase($this->getJname());
			$query = "SELECT COUNT(*) FROM #__thread WHERE threadid = {$existingthread->threadid} AND firstpostid = {$existingthread->postid}";
			$jdb->setQuery($query);
			if($jdb->loadResult()==0)
			{
				//the thread no longer exists in the software!!  recreate it
				$this->createThread($contentitem, $forumid, $status);
            	if (empty($status['error'])) {
                	$status['action'] = 'created';
            	}
            	return $status;
			}
			elseif($contentModified > $postModified)
			{
				//update the post if the content has been updated
				$this->updateThread($existingthread->threadid, $existingthread->postid, $contentitem, $status);
				if (empty($status['error'])) {
                	$status['action'] = 'updated';
            	}
            	return $status;
			}
		}
	    else
	    {
	    	//thread does not exist; create it
            $this->createThread($contentitem, $forumid, $status);
            if (empty($status['error'])) {
                $status['action'] = 'created';
            }
            return $status;
        }
	}

	function getDefaultForum($contentitem)
	{
		//content section/category
		$sectionid = $contentitem->sectionid;
		$catid = $contentitem->catid;

		//default forum to create post in
		$forumid = $this->params->get("default_forum");

		//determine default forum
		$sections = $this->params->get("pair_sections");
		$sectionPairs = empty($sections) ? false :  explode(";",$sections);

		$categories = $this->params->get("pair_categories");
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

    function &getThread($contentid)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT threadid,postid,modified FROM #__jfusion_forum_plugin WHERE contentid = ' . $contentid;
        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;
    }

    function getJname()
    {
        return 'vbulletin';
    }

	function createThread($contentitem, $forumid, &$status)
	{
		//initialize vb framework
		if(!$this->vBulletinInit()) return null;

		//TODO create error notices if required params are empty

		$userid = $this->params->get("default_userid");
		$firstPost = $this->params->get("first_post");

		//strip title of all html characters
		$title = trim(strip_tags($contentitem->title));

		//set what should be posted as the first post
		if($firstPost=="articleLink")
		{
			//create link
			$forumText = $this->params->get("first_post_link_text");
			$text = $this->prepareText(jFusionFunction::createJoomlaArticleURL($contentitem->id,$forumText));
		}
		else
		{
			//prepare the text for posting
			$text = $this->prepareText($contentitem->text);
		}

		require_once (CWD . "/includes/functions.php");

		$threaddm =& datamanager_init('Thread_FirstPost', $GLOBALS["vbulletin"], ERRTYPE_SILENT, 'threadpost');
		$foruminfo = fetch_foruminfo($forumid);
		$threaddm->set_info('forum', $foruminfo);
		$threaddm->set('forumid', $foruminfo['forumid']);
		$threaddm->set('userid', $userid);
		$threaddm->set('title', $title);
		$threaddm->set('pagetext',$text);
		$threaddm->set('allowsmilie', 1);
		$threaddm->set('ipaddress', $_SERVER["REMOTE_ADDR"]);
		$threaddm->set('visible', 1);
		$threaddm->set('dateline', time());
		$threaddm->pre_save();
		if(!empty($threaddm->errors)){
			$status["errors"] = array_merge($status["errors"], $threaddm->errors);
		} else {
			$threadid = $threaddm->save();
			$postid = $threaddm->fetch_field('firstpostid');

			//save the threadid to the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $threadid, $postid, $this->getJname());
		}
	}

	function updateThread($threadid,$postid,$contentitem,&$status)
	{
		//initialize the vb framework
		if(!$this->vBulletinInit()) return null;

		$firstPost = $this->params->get("first_post");

		//strip title of all html characters
		$title = trim(strip_tags($contentitem->title));

		//set what should be posted as the first post
		if($firstPost=="articleLink")
		{
			//create link
			$forumText = $this->params->get("first_post_link_text");
			$text = $this->prepareText(jFusionFunction::createJoomlaArticleURL($contentitem->id,$forumText));
		}
		else
		{
			//prepare the text for posting
			$text = $this->prepareText($contentitem->text);
		}

		require_once (CWD . "/includes/functions.php");

		$threadinfo = verify_id('thread', $threadid, 0, 1);
		$foruminfo = fetch_foruminfo($threadinfo['forumid'], false);
		$postinfo = array();
		$postinfo['postid'] = $postid;
    	$postinfo['threadid'] =$threadinfo['threadid'];
    	$postinfo['ipaddress'] = $_SERVER["REMOTE_ADDR"];
		$postinfo['dateline'] = time();

		$postdm =& datamanager_init('Post', $GLOBALS["vbulletin"], ERRTYPE_SILENT, 'threadpost');
		$postdm->set_existing($postinfo);
		$postdm->set_info('forum', $foruminfo);
		$postdm->set_info('thread', $threadinfo);
		$postdm->setr('pagetext', $text);
		$postdm->setr('title',$title);
		$postdm->pre_save();
		if(!empty($postdm->errors)){
			$status["errors"] = array_merge($status["errors"], $postdm->errors);
		} else {
			$postdm->save();

			//update the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $threadid, $postid, $this->getJname());
		}
	}

	function prepareText($text)
	{
		//first thing is to remove all joomla plugins
		preg_match_all('/\{(.*)\}/U',$text,$matches);

		//find each thread by the id
		foreach($matches[1] AS $plugin)
		{
			//replace plugin with nothing
			$text = str_replace("{$plugin}","",$text);
		}

		//for vbulletin we need to convert html to bbcode
		$allowhtml = false;
		$p_two_linebreak = true;

		require_once(CWD . '/includes/functions_wysiwyg.php');
		$parsed_text = convert_wysiwyg_html_to_bbcode($text, $allowhtml, $p_two_linebreak);
		return $parsed_text;
	}

	function getPosts($threadid,$postid)
	{
		//set the query
		$limit_posts = $this->params->get("limit_posts");
		$limit = empty($limit_posts) || trim($limit_posts)==0 ? "" :  "LIMIT 0,$limit_posts";
		$sort = $this->params->get("sort_posts");
		$body_limit = $this->params->get("body_limit");
		$bodyLimit = empty($body_limit) || trim($body_limit)==0 ? "a.pagetext" : "left(a.pagetext, $body_limit)";

		$where = "WHERE a.threadid = {$threadid} AND a.postid != {$postid}";
		$query = "SELECT a.postid , a.username, a.userid, a.title, a.dateline, $bodyLimit, a.threadid FROM `#__post` as a INNER JOIN `#__thread` as b ON a.threadid = b.threadid $where ORDER BY a.dateline $sort $limit";

		$jdb = & JFusionFactory::getDatabase($this->getJname());
		$jdb->setQuery($query);
		$posts = $jdb->loadObjectList();

		return $posts;
	}

	function createPostTable(&$existingthread, &$css)
	{
		//get required params
		defined('_DATE_FORMAT_LC2') or define('_DATE_FORMAT_LC2','%A, %d %B %Y %H:%M');
		$date_format = $this->params->get('custom_date', _DATE_FORMAT_LC2);
		$tz_offset = intval($this->params->get('tz_offset'));
		$showdate = intval($this->params->get('show_date'));
		$showuser = intval($this->params->get('show_user'));
		$showavatar = $this->params->get("show_avatar");
		$userlink = intval($this->params->get('user_link'));
		$linkMode = $this->params->get("link_mode");
		$jname = $this->getJname();
		$forum = JFusionFactory::getForum($jname);
		$header = $this->params->get("post_header");

		if($showdate && $showuser) $colspan = 2;
		else $colspan = 1;

		//get the posts
		$posts = $this->getPosts($existingthread->threadid,$existingthread->postid);


		$table .= "<div class='{$css["postArea"]}'> \n";
		$table  = "<div class='{$css["postHeader"]}'>$header</div>\n";

		for ($i=0; $i<count($posts); $i++)
		{
			$p = &$posts[$i];

			$table .= "<div class = '{$css["postBody"]}'> \n";

			//avatar
			if($showavatar){
				$avatarSrc = $this->getAvatar($p->userid);
				if($avatarSrc) {
					$avatar = "<div class='{$css["userAvatar"]}'><img src='$avatarSrc'></div>";
				} else {
					$avatar = "";
				}
			} else {
				$avatar = "";
			}
			$table .= $avatar;

			//post title
			$urlstring_pre = JFusionfunction::createURL($forum->getPostURL($p->threadid,$p->postid), $jname, $linkMode);
			$title = '<a href="'. $urlstring_pre . '">'. $p->title .'</a>';
			$table .= "<div class = '{$css["postTitle"]}'>{$title}</div>\n";

			//user info
			if ($showuser)
			{
				if ($userlink) {
					$user_url = JFusionfunction::createURL($forum->getProfileURL($p->userid), $jname, $linkMode);
					$user = '<a href="'. $user_url . '">'.$p->username.'</a>';
				} else {
					$user = $p->username;
				}

				$table .= "<div class='{$css["postUser"]}'> by $user</div>";
			}

			//post date
			if($showdate) $table .= "<div class='{$css["postDate"]}'>".strftime($date_format, $tz_offset * 3600 + ($p->dateline)) . "</div>";

			//post body
			$table .= "<div class='{$css["postText"]}'>{$p->pagetext}</div> \n";
			$table .= "</div>";
		}

		$table .= "</div> \n";

		return $table;
	}

    function getThreadURL($threadid)
    {
        return  'showthread.php?t=' . $threadid;

    }

    function getPostURL($threadid, $postid)
    {
        return  'showthread.php?p='.$postid.'#post' . $postid;
    }

    function getProfileURL($uid)
    {
        return  'member.php?u='.$uid;
    }

	function getPrivateMessageCounts($userid)
    {
        // initialise some objects
        $jdb = & JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT pmtotal,pmunread FROM #__user WHERE userid = '.$userid;
        $jdb->setQuery($query);
        $vbPMData = $jdb->loadObject();

        $pmcount['total'] = $vbPMData->pmtotal;
        $pmcount['unread'] = $vbPMData->pmunread;

        return $pmcount;
    }

    function getPrivateMessageURL()
    {
        return 'private.php';
    }

   function getViewNewMessagesURL()
   {
      return 'search.php?do=getnew';
   }
   
   function getAvatar($userid)
   {
        if ($userid) {
            $url = $this->params->get('source_url').'image.php?u='.$userid .'&amp;dateline='. time() ;
            return $url;

        } else {
            return 0;
        }
    }

        function getQuery($usedforums, $result_order, $result_limit, $display_limit)
        {
            if ($usedforums) {
                $where = ' WHERE forumid IN (' . $usedforums .')';
            } else {
                $where = '';
            }

            $query = array(0 => array(0 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $display_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid " . $where . " ORDER BY a.lastpost  ".$result_order." LIMIT 0,".$result_limit.";",
            1 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $display_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.lastpostid = b.postid " . $where . " ORDER BY a.lastpost  ".$result_order." LIMIT 0,".$result_limit.";"),
            1 => array(0 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $display_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid " . $where . " ORDER BY a.dateline  ".$result_order." LIMIT 0,".$result_limit.";",
            1 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $display_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.lastpostid = b.postid " . $where . " ORDER BY a.dateline  ".$result_order." LIMIT 0,".$result_limit.";"),
            2 => array(0 => "SELECT a.postid , a.username, a.userid, a.title, a.dateline, a.pagetext, a.threadid FROM `#__post` as a INNER JOIN `#__thread` as b ON a.threadid = b.threadid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";",
            1 => "SELECT a.postid , a.username, a.userid, a.title, a.dateline, a.pagetext, a.threadid FROM `#__post` as a INNER JOIN `#__thread` as b ON a.threadid = b.threadid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";")
            );

            return $query;
        }


    function getForumList()
    {
        //get the connection to the db

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT forumid as id, title_clean as name FROM #__forum ORDER BY forumid';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }
}
?>