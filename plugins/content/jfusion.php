<?php
/**
* @package JFusion
* @subpackage Plugin_Discussbot
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Load the JFusion framework
*/
jimport('joomla.plugin.plugin');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

JPlugin::loadLanguage( 'plg_content_jfusion', JPATH_ADMINISTRATOR );

class plgContentJfusion extends JPlugin
{
	var $css = array();
	var $params = false;
	var $jname = '';
	/**
	* Constructor
	*
	* For php4 compatability we must not use the __constructor as a constructor for
	* plugins because func_get_args ( void ) returns a copy of all passed arguments
	* NOT references. This causes problems with cross-referencing necessary for the
	* observer design pattern.
	*/
    function plgContentJfusion(& $subject, $config)
    {
        parent::__construct($subject, $config);
    }

    function onPrepareContent(& $contentitem, $options)
    {   	        
		//prevent any output by the plugins (this could prevent cookies from being passed to the header)
		ob_start();

    	//retrieve plugin software for discussion bot
		if($this->params===false) {
			$jPlugin =& JPluginHelper::getPlugin('content','jfusion');
        	$this->params = new JParameter( $jPlugin->params);	
		}
        $jname =& $this->params->get('jname',false);

        //check to see if a valid $content object was passed on
		if(!is_object($contentitem)){
			JFusionFunction::raiseWarning($jname . ' ' . JText::_('DISCUSSBOT_ERROR'), JText::_('NO_CONTENT_DATA_FOUND'), 1);
			return $status;
		}
		
		//only use the discussion bot if a category and section id are set	 and we have a jfusion plugin		
		if($jname!==false && isset($contentitem->catid) && isset($contentitem->sectionid)) {
			//get the jfusion forum object
			$JFusionPlugin =& JFusionFactory::getForum($jname);
			//get the Joomla user object
			$JoomlaUser =& JFactory::getUser();
				
			//first process any submitted quick replies
			if(!$JoomlaUser->guest && JRequest::getVar('jfusionForm'.$contentitem->id, false, 'POST')!==false && $this->params->get("enable_quickreply",false))	{
				//create the post if a threadid is found otherwise return an error
				if(JRequest::getVar('threadid',false, 'POST')!==false){
					//retrieve the userid from forum software
					$JFusionUser = JFusionFactory::getUser($jname);
					$userinfo = $JFusionUser->getUser($JoomlaUser->username);
					$postedThread = new stdClass();
					$postedThread->threadid = JRequest::getVar('threadid',0, 'POST');
					$postedThread->forumid = JRequest::getVar('forumid',0, 'POST');
					$postedThread->postid = JRequest::getVar('postid',0, 'POST');
					$status = $JFusionPlugin->createPost($this->params, $postedThread, $contentitem, $userinfo);
					if($status['error']){
						JFusionFunction::raiseWarning($jname . ' ' . JText::_('DISCUSSBOT_ERROR'), $status['error'],1);
					}
				} else {
					JFusionFunction::raiseWarning($jname . ' ' . JText::_('DISCUSSBOT_ERROR'), JText::_('THREADID_NOT_FOUND'),1);
				}
			}
		
			//determine what mode we are to operate in
			$mode = ($this->params->get("auto_create")) ? "auto" : "manual";
			
			//create the thread if set to auto generate but only if the content is published and a user has been set
			if($mode=="auto") {
				//get the existing thread information
				$existingthread = $this->getThread($contentitem->id, $jname);
				$userid =& $this->params->get("default_userid");
				if($contentitem->state && !empty($userid)) {
					//generate the thread/post if article meets criteria
					$generate = $this->checkIfForumSet($contentitem);
					//get the default forum id
					$forumid = $JFusionPlugin->getDefaultForum($this->params, $contentitem);

					if($generate && !empty($forumid)) {
					    $status = $JFusionPlugin->checkThreadExists($this->params, $contentitem, $existingthread, $forumid);
					    if ($status['error']) {
				    	    JFusionFunction::raiseWarning($plugin->name . ' ' .JText::_('FORUM') . ' ' .JText::_('UPDATE'), $status['error'],1);
				    	} else {
				    		//get the updated thread info
				    		$existingthread = $existingthread = $this->getThread($contentitem->id, $jname);		
				    	}
					}
									
					$content = $this->getContent($contentitem, $existingthread, $jname);
					$contentitem->text .= $content;
				}			
		    } 
		    
    		//find any {jfusion_discuss...} to manually plug
			preg_match_all('/\{jfusion_discuss (.*)\}/U',$contentitem->text,$matches);
			
			foreach($matches[1] AS $id)
			{
				//get the existing thread information
				$existingthread = $JFusionPlugin->getThread($id);
				if(!empty($existingthread)){
					$content = $this->getContent($contentitem, $existingthread, $jname);
					$contentitem->text = str_replace("{jfusion_discuss $id}",$content,$contentitem->text);
				} else {
					$contentitem->text = str_replace("{jfusion_discuss $id}",JText::_("THREADID_NOT_FOUND"),$contentitem->text);
				}
			}
			
			ob_end_clean();
			$result = true;
			return $result;
		}
    }

    function getContent(&$contentitem, &$existingthread, $jname)
    {
    	//if $existingthread is empty return nothing
    	if(empty($existingthread)) {
    		return '';
    	}
    	
		//setup parameters
		$JFusionPlugin =& JFusionFactory::getForum($jname);
		$linkMode =& $this->params->get("link_mode");
		$linkText =& $this->params->get("link_text");
		$linkTarget =& $this->params->get('link_target','_parent');
		$itemid =& $this->params->get("itemid",false);
		$noPostMsg =& $this->params->get("no_posts_msg");
		$mustLoginMsg =& $this->params->get("must_login_msg");
		$JoomlaUser =& JFactory::getUser();
		
		//load CSS
		if(empty($this->css)) {
			$this->loadDbCss();
		}	

		$view = JRequest::getVar('view');

		$content = "<div style='float:none; display:block;'>";
		
		if($view=="article" || $this->params->get('always_show_link',false)) {
			$numPosts = $JFusionPlugin->getReplyCount($existingthread);
			$post = ($numPosts==1) ? "POST" : "POSTS";
			
			$threadid =& $existingthread->threadid;		
			$urlstring_pre = JFusionFunction::createURL($JFusionPlugin->getThreadURL($threadid), $jname, $linkMode, $itemid);
			$content .= '<div class="'.$this->css['threadLink'].'"><a href="'. $urlstring_pre . '" target="' . $linkTarget . '">' . $linkText . '</a> ['.$numPosts.' '.JText::_($post).']</div>';
		}

		//let's only show quick replies and posts on the article view
		if($view=="article") {							
			//prepare quick reply box if enabled
			
			if($this->params->get("enable_quickreply")){
				$show = (!$JoomlaUser->guest) ? "form" : "message"; 
				$replyForm  = "<div class='{$this->css["quickReplyHeader"]}'>{$this->params->get("quick_reply_header")}</div>\n";
				$replyForm .= "<div class='{$this->css["quickReply"]}'>\n";	
			} else {
				$show = false;
			}
			
			if($show=="form") {
				$replyForm .= "<form name='jfusionQuickReply{$contentitem->id}' method=post action='".$_SERVER["REQUEST_URI"]."'>\n";
				$replyForm .= "<input type=hidden name='jfusionForm{$contentitem->id}' value='1'>\n";
				$replyForm .= "<input type=hidden name='threadid' value='{$existingthread->threadid}'>\n";
				$replyForm .= "<input type=hidden name='forumid' value='{$existingthread->forumid}'>\n";
				$replyForm .= "<input type=hidden name='postid' value='{$existingthread->postid}'>\n";
				$replyForm .= $JFusionPlugin->createQuickReply()."</form>\n";
				$replyForm .= "</div>\n";
			} elseif($show=="message") {
				$replyForm .= $mustLoginMsg;
				$replyForm .= "</div>\n";
			}
			
			//add posts to content if enabled
			if($this->params->get("show_posts")) {
				//get the posts
				$posts = $JFusionPlugin->getPosts($this->params, $existingthread);
			
				$content  .= "<div class='{$this->css["postArea"]}'> \n";
			
				if($show!==false && $this->params->get("quickreply_location")=="above") {
					$content .= $replyForm;
				}
			
				if(!empty($posts)){			
					$content .= $JFusionPlugin->createPostTable($this->params, $existingthread, $posts, $this->css);
				} elseif(!empty($noPostMsg)) {
					$content .= "<div class='{$this->css["noPostMsg"]}'> {$noPostMsg} </div>\n";
				}
				
				if($show!==false && $this->params->get("quickreply_location")=="below"){
					$content .= $replyForm;
				}
				
				$content .= "</div> \n";
			} elseif($show!==false){
				$content .= $replyForm;
			}
		}
		$content .= "</div>";
		return $content;
    }
    
    function loadDbCss()
    {   	
    	$this->css = array();
		$this->css['threadLink'] = $this->params->get("cssClassThreadLink");
		$this->css['postArea'] = $this->params->get("cssClassPostArea");
		$this->css['postHeader'] = $this->params->get("cssClassPostHeader");
		$this->css['postBody'] = $this->params->get("cssClassPostBody");
		$this->css['postTitle'] = $this->params->get("cssClassPostTitle");
		$this->css['noPostMsg'] = $this->params->get("cssClassNoPostMsg");
		$this->css['postUser'] = $this->params->get("cssClassPostUser");
		$this->css['userAvatar'] = $this->params->get("cssClassUserAvatar");
		$this->css['postDate'] = $this->params->get("cssClassPostDate");
		$this->css['postText'] = $this->params->get("cssClassPostText");
		$this->css['quickReply'] = $this->params->get("cssClassQuickReply");
		$this->css['quickReplyHeader'] = $this->params->get("cssClassQuickReplyHeader");
				
		$empty = false;
		foreach ($this->css AS $k => $v){
			if(empty($v)){
				$empty = true;
				$this->css[$k] = "jfDb" . ucfirst($k);
			}
		}

		if($empty) {
			$defaultCSS = "
				<style type=\"text/css\">

				.jfDbThreadLink{
					font-size:12px;
					margin:5px;
				}
				.jfDbPostArea{
					width:100%;
					margin:10px;

				}
				.jfDbPostHeader{
					font-size:11px;
					color:#000000;
					font-weight:bold;
				}
				.jfDbPostBody{
					border:1px solid #eee;
					min-height: 90px;
				}
				.jfDbPostTitle{
					color:#000000;
					float:left;
					margin-right:4px;
				}
				.jfDbNoPostMsg{
					font-weight:bold;
				}
				.jfDbPostUser{

				}
				.jfDbUserAvatar{
					float:left;
					margin-right:5px;
				}
				.jfDbPostDate{
					font-size:9px;
				}
				.jfDbPostText{
					margin-top:5px;
				}

				.jfDbQuickReply textarea{
					width:100%;
					height: 100px;
				}

				.jfDbQuickReply .inputbox{
					width:100%;
				}
				
				.jfDbQuickReplyHeader{
					font-weight:bold;
				}
				</style>";
			$document =& JFactory::getDocument();
			$document->addCustomTag($defaultCSS);
		}
	}

	function checkIfForumSet(&$contentitem)
	{
    	$forumid = $this->params->get("default_forum",false);
		$sectionPairs = $this->params->get("pair_sections",false);
		$categoryPairs = $this->params->get("pair_categories",false);
	
		//first we need to check to see if we at least one forum to work with
		if($forumid || $sectionPairs || $categoryPairs) {
	    	//check to see if there are sections/categories that are specifically included/excluded
	    	$sections =& $this->params->get("include_sections");
			$includeSections = empty($sections) ? false : explode(",",$sections);
	
			$categories =& $this->params->get("include_categories");
			$includeCategories = empty($categories) ? false : explode(",",$categories);
	
			$sections =& $this->params->get("exclude_sections");
			$excludeSections = empty($sections) ? false : explode(",",$sections);
	
			$categories =& $this->params->get("exclude_categories");
			$excludeCategories = empty($categories) ? false : explode(",",$categories);
	
			//section and category id of content
			$secid =& $contentitem->sectionid;
			$catid =& $contentitem->catid;
	
			//there are section stipulations on what articles to include
			if($includeSections) {
				if($includeCategories) {
					//there are both specific sections and categories to include
					//check to see if this article is not in the selected sections and categories
					if(!in_array($secid,$includeSections) && !in_array($catid,$includeCategories)) $generate = false;
				} elseif($excludeCategories) {
					//exclude this article if it is in one of the excluded categories
					if(in_array($catid,$excludeCategories)) $generate = false;
				} elseif(in_array($secid,$includeSections)) {
					//there are only specific sections to include with no category stipulations
					$generate = true;
				} else  {
					//this article is not in one of the sections to include
					$generate = false;
				}
			} elseif($includeCategories) {
				//there are category stipulations on what articles to include but no section stipulations
		        //check to see if this article is not in the selected categories
				if(!in_array($catid,$includeCategories)) $generate = false;
			} elseif($excludeSections) {
			    //there are section stipulations on what articles to exclude
				//check to see if this article is in the excluded sections
				if(in_array($secid,$excludeSections)) $generate = false;
			} elseif($excludeCategories) {
				//there are category stipulations on what articles to exclude but no exclude stipulations on section
				//check to see if this article is in the excluded categories
				if(in_array($catid,$excludeCategories)) $generate = false;
			} elseif($forumid!==false) {
				$generate = true;
			} else {
				$generate = false;
			}
		} else {
			$generate = false;
		}	

		return $generate;
	}
	
	function getThread($contentid,$jname) {
		$db =& JFactory::getDBO();
        $query = "SELECT * FROM #__jfusion_forum_plugin WHERE contentid = '$contentid' AND jname = '$jname'";
        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;
	}
}
?>