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
	var $joomla_globals;
		
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

    function getJname()
    {
        return 'vbulletin';
    }

	function checkThreadExists(&$dbparams, &$contentitem, &$existingthread, $forumid)
	{
	    $status = array();
        $status['debug'] = array();
        $status['error'] = array();

		//set the timezone to UTC
		date_default_timezone_set('UTC');

		//backup Joomla's global scope
		$this->backupGlobals();
		
		if(!empty($existingthread))
		{			
			//datetime post was last updated
			$postModified = $existingthread->modified;
			//datetime content was last updated
			$contentModified = strtotime($contentitem->modified);
			
			//check to make sure the thread still exists in the software
			$jdb = & JFusionFactory::getDatabase($this->getJname());
			$query = "SELECT COUNT(*) FROM #__thread WHERE forumid = {$existingthread->forumid} AND threadid = {$existingthread->threadid} AND firstpostid = {$existingthread->postid}";
			$jdb->setQuery($query);
			if($jdb->loadResult()==0)
			{
				//the thread no longer exists in the software!!  recreate it
				$this->createThread($dbparams, $contentitem, $forumid, $status);
            	if (empty($status['error'])) {
                	$status['action'] = 'created';
            	}
            	
                //restore Joomla's global scope
				$this->restoreGlobals();
				            	
            	return $status;
			}
			elseif($contentModified > $postModified)
			{
				//update the post if the content has been updated
				$this->updateThread($dbparams, $existingthread, $contentitem, $status);
				if (empty($status['error'])) {
                	$status['action'] = 'updated';
            	}
            	
                //restore Joomla's global scope
				$this->restoreGlobals();
				            	
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
            
			//restore Joomla's global scope
			$this->restoreGlobals();
			            
            return $status;
        }
	}
	
    function getThread($threadid)
    {
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT threadid, forumid, firstpostid AS postid FROM #__thread WHERE threadid = $threadid";
		$db->setQuery($query);
		$results = $db->loadObject();
		return $results;
    }
    	
	function createThread(&$dbparams, &$contentitem, $forumid, &$status)
	{
		//initialize vb framework
		if(!$this->vBulletinInit()) return null;

		//TODO create error notices if required params are empty

		$userid = $dbparams->get("default_userid");
		$firstPost = $dbparams->get("first_post");

		//strip title of all html characters
		$title = trim(strip_tags($contentitem->title));

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

		require_once (CWD . "/includes/functions.php");

		$threaddm =& datamanager_init('Thread_FirstPost', $GLOBALS["vbulletin"], ERRTYPE_SILENT, 'threadpost');
		$foruminfo = fetch_foruminfo($forumid);
		$threaddm->set_info('forum', $foruminfo);
		$threaddm->set('forumid', $foruminfo['forumid']);
		$threaddm->set('userid', $userid);
		$threaddm->set('title', $title);
		$threaddm->set('pagetext',trim($text));
		$threaddm->set('allowsmilie', 1);
		$threaddm->set('ipaddress', $_SERVER["REMOTE_ADDR"]);
		$threaddm->set('visible', 1);
		$threaddm->set('dateline', time());
		$threaddm->pre_save();
		if(!empty($threaddm->errors)){
			$status["error"] = array_merge($status["error"], $threaddm->errors);
		} else {
			$threadid = $threaddm->save();
			$postid = $threaddm->fetch_field('firstpostid');

			//save the threadid to the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $forumid, $threadid, $postid, $this->getJname());
		}
	}

	function createPost(&$dbparams, &$ids, &$contentitem, &$userinfo)
	{
		$text = JRequest::getVar('quickReply', false, 'POST');

		if(!empty($text)) {
			//backup Joomla's global scope
			$this->backupGlobals();			
			
			$text = $this->prepareText($text);
			
			$status = array();
			$status["error"] = array();
			
			//initialize the vb framework
			if(!$this->vBulletinInit()) return null;

			require_once (CWD . "/includes/functions.php");
			
			$threadinfo = verify_id('thread', $ids->threadid, 0, 1);
			$foruminfo = fetch_foruminfo($threadinfo['forumid'], false);
			$postinfo = array();
	    	$postinfo['threadid'] = $threadinfo['threadid'];
	    	$postinfo['ipaddress'] = $_SERVER["REMOTE_ADDR"];
			$postinfo['dateline'] = time();

			$postdm =& datamanager_init('Post', $GLOBALS["vbulletin"], ERRTYPE_SILENT, 'threadpost');
			$postdm->set_info('forum', $foruminfo);
			$postdm->set_info('thread', $threadinfo);
			$userinfo = $this->convertUserData($userinfo);
			$postdm->set_info('user',$userinfo);			
			$postdm->setr('userid', $userinfo['userid']);
			$postdm->setr('parentid', $ids->postid);
			$postdm->setr('threadid', $ids->threadid);
			$postdm->setr('pagetext', $text);
			$postdm->set('title', "Re: {$this->prepareText($threadinfo['title'])}");
			
			$postdm->set('visible', 1);
			$postdm->set('allowsmilie', 1);
			
			$postdm->pre_save();
			if(!empty($postdm->errors)){
				$status["error"] = array_merge($status["error"], $postdm->errors);
			} else {
				$id = $postdm->save();	
			}

			//restore Joomla's global scope
			$this->restoreGlobals();
			
			return $status;
		}
	}
	
	function updateThread( &$dbparams, &$existingthread, &$contentitem, &$status)
	{
		//initialize the vb framework
		if(!$this->vBulletinInit()) return null;

		$forumid =& $existingthread->forumid;
		$threadid =& $existingthread->threadid;
		$postid =& $existingthread->postid;
		
		$firstPost = $dbparams->get("first_post");

		//strip title of all html characters
		$title = trim(strip_tags($contentitem->title));

		//set what should be posted as the first post
		if($firstPost=="articleLink") {
			//create link
			$forumText = $dbparams->get("first_post_link_text");
			$text = $this->prepareText(jFusionFunction::createJoomlaArticleURL($contentitem,$forumText));
		} else 	{
			//prepare the text for posting
			$text = $this->prepareText($contentitem->introtext.$contentitem->fulltext);
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
			$status["error"] = array_merge($status["error"], $postdm->errors);
		} else {
			$postdm->save();

			//update the lookup table
			JFusionFunction::updateForumLookup($contentitem->id, $forumid, $threadid, $postid, $this->getJname());
		}
	}

	function prepareText($text,$prepareForJoomla=false)
	{
		if($prepareForJoomla===false) {
			//first thing is to remove all joomla plugins
			preg_match_all('/\{(.*)\}/U',$text,$matches);
	
			//find each thread by the id
			foreach($matches[1] AS $plugin) {
				//replace plugin with nothing
				$text = str_replace('{'.$plugin.'}',"",$text);
			}
			$text = html_entity_decode($text);
			$text = JFusionFunction::parseCode($text,'bbcode',true);
		} else {
			$text = JFusionFunction::parseCode($text,'html');
		}
		return $text;			
	}

	function getPosts(&$dbparams, &$existingthread)
	{
		$threadid =& $existingthread->threadid;
		$postid =& $existingthread->postid;
		
		//set the query
		$limit_posts = $dbparams->get("limit_posts");
		$limit = empty($limit_posts) || trim($limit_posts)==0 ? "" :  "LIMIT 0,$limit_posts";
		$sort = $dbparams->get("sort_posts");
		$body_limit = $dbparams->get("body_limit");
		$bodyLimit = (empty($body_limit) || trim($body_limit)==0) ? "a.pagetext" : "left(a.pagetext, $body_limit) AS pagetext";

		$where = "WHERE a.threadid = {$threadid} AND a.postid != {$postid} AND a.visible = 1";
		$query = "SELECT a.postid , a.username, a.userid, a.title, a.dateline, $bodyLimit, a.threadid FROM `#__post` as a INNER JOIN `#__thread` as b ON a.threadid = b.threadid $where ORDER BY a.dateline $sort $limit";

		$jdb = & JFusionFactory::getDatabase($this->getJname());
		$jdb->setQuery($query);
		$posts = $jdb->loadObjectList();
		return $posts;
	}

	function getReplyCount(&$existingthread)
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT replycount FROM #__thread WHERE threadid = {$existingthread->threadid}";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}
	
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
					$avatarSrc = $this->getAvatar($p->userid);
                } else {
                	$avatarSrc = JFusionFunction::getAltAvatar($avatar_software,$p->userid,true,$this->getJname(),$p->username);
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
			$urlstring_pre = JFusionFunction::routeURL($this->getPostURL($p->threadid,$p->postid), $itemid);
			$title = '<a href="'. $urlstring_pre . '">'. $p->title .'</a>';
			$table .= "<div class = '{$css["postTitle"]}'>{$title}</div>\n";

			//user info
			if ($showuser)
			{
				if ($userlink) {
					if(!empty($link_software) && $link_software != 'jfusion' && $link_software!='custom') {
						$user_url = JFusionFunction::getAltProfileURL($link_software,$p->username);
					} elseif ($link_software=='custom' && !empty($userlink_custom)) {
						$userlookup = JFusionFunction::lookupUser($this->getJname(),$p->userid,false, $p->username);
						$user_url =  $userlink_custom.$userlookup->id;
					} else {
						$user_url = false;
					}
					
					if($user_url === false) {
						$user_url = JFusionFunction::routeURL($this->getProfileURL($p->userid), $itemid);
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
				$JDate =  new JDate($p->dateline);
				$JDate->setOffset($tz_offset);
				$date = $JDate->toFormat($date_format);
				$table .= "<div class='{$css["postDate"]}'>".$date."</div>";			
			} 

			//post body
			$text = $this->prepareText($p->pagetext,true);
			$table .= "<div class='{$css["postText"]}'>{$text}</div> \n";
			$table .= "</div>";
		}

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

	function getActivityQuery($usedforums, $result_order, $result_limit, $display_limit)
	{	
		$where = (!empty($usedforums)) ? ' WHERE a.forumid IN (' . $usedforums .')' : '';
		$end = $result_order." LIMIT 0,".$result_limit;
		
		$query = array(
			LAT => "SELECT a.threadid, a.lastpostid AS postid, b.username, b.userid, a.title AS subject, b.dateline, a.forumid FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid $where ORDER BY a.lastpost $end",
			LCT => "SELECT a.threadid, b.postid, b.username, b.userid, a.title AS subject, b.dateline, left(b.pagetext, $display_limit) AS body, a.forumid FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid $where ORDER BY a.dateline $end", 
			LCP => "SELECT threadid, postid, username, userid, title AS subject, dateline, left(pagetext, $display_limit) AS body, forumid FROM `#__post` " . str_replace('a.forumid','forumid',$where) . " ORDER BY dateline $end"
		);
		
		return $query;
	}

    function getForumList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT forumid as id, title_clean as name FROM #__forum ORDER BY forumid';
        $db->setQuery($query );
        $results = $db->loadObjectList();
               
        return $results;
    }
    
    function filterForumList(&$results, $idKey='forumid') 
    {
    	//get the joomla user
    	$JoomlaUser =& JFactory::getUser();
    	
    	//get the vb user
    	if(!$JoomlaUser->guest) {
    		$user = JFusionFunction::lookupUser($this->getJname(), $JoomlaUser->id);
    		$userid = $user->userid;
    	} else {
    		$userid = 0;
    	}
   
    	//get the usergroup permissions
   		$db =& JFusionFactory::getDatabase($this->getJname());
   		if($userid!=0) {
   			$query = "SELECT u.usergroupid AS gid, g.forumpermissions AS perms FROM #__user AS u INNER JOIN #__usergroup AS g ON u.usergroupid = g.usergroupid WHERE u.userid = '$userid'";
   		} else {
   			$query = "SELECT usergroupid AS gid, forumpermissions AS perms FROM #__usergroup WHERE usergroupid = '1'";
   		}
   		$db->setQuery($query);
   		$groupPerms = $db->loadObject();
    	//used to store permissions to prevent multiple calls to the db for the same result
    	$forumPerms = array();
    	if(is_array($results)) {
    		foreach($results as $k => $r) {
   				$forumid = $r->$idKey;

    			if(!array_key_exists($forumid,$forumPerms)) {
    				$query = "SELECT forumpermissions FROM #__forumpermission WHERE usergroupid = '{$groupPerms->gid}' AND forumid = '{$forumid}'";
    				$db->setQuery($query);
    				$result = $db->loadResult();
    			} else {
    				$result = $forumPerms[$forumid];
    			}
    			
    			if($result) {
    				//forum has set permissions so use these to compare
    				$forumPerms[$forumid] = $result;
    				
    				//can this user view threads of this forum
    				if(!($result & 524288) || !($result & 1)) {
    					//remove the row as the usergroup does not have permission to view this specific forum
    					unset($results[$k]);
    				}
    			} else {
    				//the forum does not have set permission so default to checking the user group permissions
    				if(!($groupPerms->perms & 524288) || !($groupPerms->perms & 1)) {
    					//remove the row as the usergrup does not have permission to view forums
    					unset($results[$k]);
    				}
    			}
    		}   	
    	}
    }
    
    //convert the existinguser variable into something vbulletin understands
    function convertUserData($existinguser)
    {
    	$userinfo = array(
    		'userid' => $existinguser->userid,
    		'username' => $existinguser->username,
   			'email' => $existinguser->email,
    		'password' => $existinguser->password
    	);

    	return $userinfo;
    }
    
   //backs up joomla's global scope
    function backupGlobals()
    {
    	$this->joomla_globals = $GLOBALS;
    }

    //restore joomla's global scope
    function restoreGlobals()
    {
    	if(is_array($this->joomla_globals)) {
    		$GLOBALS = $this->joomla_globals;
    		$this->joomla_globals = "";
    	}
    }
}
?>