<?php

/**
* @package JFusion_vBulletin
* @version 1.0.9
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractforum.php');

/**
* JFusion plugin class for vBulletin 3.6.8
* @package JFusion_vBulletin
*/

class JFusionForum_vbulletin extends JFusionForum
{
	function JFusionForum_vbulletin()
	{
		//get the params object
        $this->params = JFusionFactory::getParams($this->getJname());

		//load the vbulletin framework
		define('VB_AREA','External');
		define('SKIP_SESSIONCREATE', 1);
		define('SKIP_USERINFO', 1);
		define('CWD', $this->params->get('source_path'));
		require_once(CWD.'/includes/init.php');

		//force into global scope
		$GLOBALS["vbulletin"] = $vbulletin;
		$GLOBALS["db"] = $vbulletin->db;
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
		$posts = $jdb->loadRowList();

		return $posts;
	}

	function createPostTable($existingthread)
	{
		//get required params
		defined('_DATE_FORMAT_LC2') or define('_DATE_FORMAT_LC2','%A, %d %B %Y %H:%M');
		$date_format = $this->params->get('custom_date', _DATE_FORMAT_LC2);
		$tz_offset = intval($this->params->get('tz_offset'));
		$showdate = intval($this->params->get('show_date'));
		$showuser = intval($this->params->get('show_user'));
		$userlink = intval($this->params->get('user_link'));
		$linkMode = $this->params->get("link_mode");
		$jname = $this->getJname();
		$forum = JFusionFactory::getForum($jname);
		$header = $this->params->get("post_header");
		if($showdate && $showuser) $colspan = 2;
		else $colspan = 1;

		//get the posts
		$posts = $this->getPosts($existingthread->threadid,$existingthread->postid);


		$table .= "<div 'jfusionpostarea'> \n";
		$table  = "<div class='jfusionpostheader'>$header</div>\n";

		for ($i=0; $i<count($posts); $i++)
		{
			$p = &$posts[$i];

			$table .= "<div class = 'jfusionposttable'> \n";

			$urlstring_pre = JFusionfunction::createURL($forum->getPostURL($p[6],$p[0]), $jname, $linkMode);
			$title = '<a href="'. $urlstring_pre . '">'. $p[3] .'</a>';
			$table .= "<div><span class='jfusionposttitle'>{$title}</span> \n";

			//process user info
			if ($showuser)
			{
				if ($userlink)
				{
					$user_url = JFusionfunction::createURL($forum->getProfileURL($p[2]), $jname, $linkMode);
					$user = '<a href="'. $user_url . '">'.$p[1].'</a>';
				}
				else
				{
					$user = $p["username"];
				}

				$table .= " by <span class='jfusionpostuser'>$user</span>";
			}

			$table .= "</div>";

			//process date info
			if($showdate) $table .= "<div class='jfusionpostdate'>".strftime($date_format, $tz_offset * 3600 + ($p[4])) . "</div>";

			$table .= "<div class='jfusionpostbody'>{$p[5]}</div> \n";
			$table .= "</div>";
		}

		$table .= "</div> \n";

		return $table;
	}

	function getSearchQueryColumns()
	{
		$columns = new stdClass();
		$columns->title = "p.title";
		$columns->text = "p.pagetext";
		return $columns;
	}

	function getSearchQuery()
	{
		//need to return threadid, postid, title, text, created
		$query = 'SELECT p.threadid, p.postid, p.title, p.pagetext AS text,
					FROM_UNIXTIME(p.dateline, "%Y-%m-%d %h:%i:%s") AS created,
					CONCAT_WS( "/", f.title, t.title ) AS section
					FROM #__post AS p
					INNER JOIN #__thread AS t ON p.threadid = t.threadid
					INNER JOIN #__forum AS f on f.forumid = t.forumid';
		return $query;
	}

	function cleanUpSearchText($text)
	{
		$pagetext = str_replace("[QUOTE]","", $text);
		$pagetext = str_replace("[/QUOTE]","", $pagetext);
		$pagetext = str_replace("[URL=","<a href=", $pagetext);
		$pagetext = str_replace("[/URL]","</a>", $pagetext);
		$pagetext = str_replace("]",">", $pagetext);
		$pagetext = str_replace("[B>","<br /><br /><b>", $pagetext);
		$pagetext = str_replace("[/B>","</b>", $pagetext);
		$pagetext = str_replace("[IMG>","<img src=", $pagetext);
		$pagetext = str_replace("[/IMG>","></img>", $pagetext);
		$pagetext = str_replace("[br />","<br />", $pagetext);

		return $pagetext;
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

    function getQuery($usedforums, $result_order, $result_limit)
    {
        if ($usedforums) {
            $where = ' WHERE forumid IN (' . $usedforums .')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid " . $where . " ORDER BY a.lastpost  ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.lastpostid = b.postid " . $where . " ORDER BY a.lastpost  ".$result_order." LIMIT 0,".$result_limit.";"),
        1 => array(0 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid " . $where . " ORDER BY a.dateline  ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.lastpostid = b.postid " . $where . " ORDER BY a.dateline  ".$result_order." LIMIT 0,".$result_limit.";"),
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