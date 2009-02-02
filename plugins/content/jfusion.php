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

JPlugin::loadLanguage( 'plg_content_jfusion' );

class plgContentJfusion extends JPlugin
{
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
    	JPlugin::loadLanguage( 'plg_content_jfusion' );
    	
		//prevent any output by the plugins (this could prevent cookies from being passed to the header)
		ob_start();

    	//retrieve plugin software for discussion bot
        $jPlugin =& JPluginHelper::getPlugin('content','jfusion');
		$params = new JParameter( $jPlugin->params);
		$jname =& $params->get('jname',false);
		
		if($jname!==false) {
			//get the jfusion forum object
			$JFusionPlugin =& JFusionFactory::getForum($jname);
			//get the Joomla user object
			$JoomlaUser =& JFactory::getUser();
			
			//first process any submitted quick replies
			if(!$JoomlaUser->guest && JRequest::getVar('jfusionForm'.$contentitem->id, false, 'POST')!==false && $params->get("enable_quickreply",false))	{
				//get the threadid from the lookup table
				$db =& JFactory::getDBO();
				$query = 'SELECT threadid, postid FROM #__jfusion_forum_plugin WHERE contentid = ' . $contentitem->id . ' AND jname = ' . $db->Quote($jname);
				$db->setQuery($query);
				$ids  = $db->loadAssoc();
				$threadid = $ids["threadid"];

				//create the post if a threadid is found otherwise return an error
				if(!empty($threadid)){
					//retrieve the userid from forum software
					$JFusionUser = JFusionFactory::getUser($jname);
					$userinfo = $JFusionUser->getUser($JoomlaUser->username);
				
					$status = $JFusionPlugin->createPost($params, $ids, $contentitem, $userinfo);
					if($status['error']){
						JFusionFunction::raiseWarning($jname . ' ' . JText::_('DISCUSSBOT_ERROR'), $status['error'],1);
					}
				} else {
					JFusionFunction::raiseWarning($jname . ' ' . JText::_('DISCUSSBOT_ERROR'), JText::_('THREADID_NOT_FOUND'),1);
				}
			}
			
			//setup parameters
			$linkMode =& $params->get("link_mode");
			$linkText =& $params->get("link_text");
			$itemid =& $params->get("itemid",false);
			$auto =& $params->get("auto_create");
			$noPostMsg =& $params->get("noPostMsg");
			$css = array();
			$css['threadLink'] = $params->get("cssClassThreadLink");
			$css['postArea'] = $params->get("cssClassPostArea");
			$css['postHeader'] = $params->get("cssClassPostHeader");
			$css['postBody'] = $params->get("cssClassPostBody");
			$css['postTitle'] = $params->get("cssClassPostTitle");
			$css['noPostMsg'] = $params->get("cssClassNoPostMsg");
			$css['postUser'] = $params->get("cssClassPostUser");
			$css['userAvatar'] = $params->get("cssClassUserAvatar");
			$css['postDate'] = $params->get("cssClassPostDate");
			$css['postText'] = $params->get("cssClassPostText");
			$css['quickReply'] = $params->get("cssClassQuickReply");
			$css['quickReplyHeader'] = $params->get("cssClassQuickReplyHeader");
			$this->loadDbCss($css);
			
			//create the thread if set to auto generate but only if the content is published
			if($auto && $contentitem->state) {
				//generate the thread/post if article meets criteria
				$generate = $this->checkIfForumSet($params, $contentitem);
				if($generate) {
				    $status = $JFusionPlugin->checkThreadExists($params, $contentitem);
				    if ($status['error']) {
				        JFusionFunction::raiseWarning($plugin->name . ' ' .JText::_('FORUM') . ' ' .JText::_('UPDATE'), $status['error'],1);
				    }
				}
		    }
	
			//get the ID of the thread
			$existingthread =& $JFusionPlugin->getThread($contentitem->id);
			$threadid =& $existingthread->threadid;		
	    
			$urlstring_pre = JFusionFunction::createURL($JFusionPlugin->getThreadURL($threadid), $jname, $linkMode, $itemid);
			$urlstring = '<div class="'.$css['threadLink'].'"><a href="'. $urlstring_pre . '" target="' . $new_window . '">' . $linkText . '</a></div>';
	
			//add link to content
			$contentitem->text = $contentitem->text . $urlstring;
			//prepare quick reply box if enabled
			if($params->get("enable_quickreply") && !$JoomlaUser->guest) {
				$replyForm .= "<div class='{$css["quickReplyHeader"]}'>{$params->get("quick_reply_header")}</div>\n";
				$replyForm .= "<form name='jfusionQuickReply{$contentitem->id}' method=post action='".$_SERVER["REQUEST_URI"]."'>\n";
				$replyForm .= "<input type=hidden name='jfusionForm{$contentitem->id}' value='1'>\n";
			} else {
				$replyForm = false;
			}
			
			//add posts to content if enabled
			if($params->get("show_posts")) {
				$tableOfPosts  = "<div class='{$css["postArea"]}'> \n";
			
				if($replyForm && $params->get("quickreply_location")=="above") {
					$tableOfPosts .= "<div class='{$css["quickReply"]}'>\n". $replyForm . $JFusionPlugin->createQuickReply()."</form>\n</div>\n";
				}
			
				//get the posts
				$posts = $JFusionPlugin->getPosts($params, $existingthread->threadid,$existingthread->postid);
				if(!empty($posts)){			
					$tableOfPosts .= $JFusionPlugin->createPostTable($params, $existingthread, $posts, $css);
				} elseif(!empty($noPostMsg)) {
					$tableOfPosts .= "<div class='{$css["noPostMsg"]}'> {$noPostMsg} </div>\n";
				}
				
				if($replyForm && $params->get("quickreply_location")=="below"){
					$tableOfPosts .= "<div class='{$css["quickReply"]}'>\n". $replyForm . $JFusionPlugin->createQuickReply()."</form>\n</div>\n";
				}
				$tableOfPosts .= "</div> \n";
				$contentitem->text = $contentitem->text . $tableOfPosts;
			} elseif($replyForm){
				$quickReply = "<div class='{$css["quickReply"]}'>\n". $replyForm . $JFusionPlugin->createQuickReply()."</form>\n</div>\n";
				$contentitem->text = $contentitem->text . $quickReply;
			}
	
			//find any {jfusion_discuss...} to manually plug
			preg_match_all('/\{jfusion_discuss (.*)\}/U',$contentitem->text,$matches);
		
			//find each thread by the id
			foreach($matches[1] AS $id)
			{
				//create the url string
				$urlstring_pre = JFusionFunction::createURL($JFusionPlugin->getThreadURL($id), $jname, $linkMode,$itemid);
				$urlstring = '<div class="'.$css["threadLink"].'"><a href="'. $urlstring_pre . '" target="' . $new_window . '">' . $linkText . '</a></div>';
				
				//replace plugin with link
				$contentitem->text = str_replace("{jfusion_discuss $id}",$urlstring,$contentitem->text);
			}
	
			ob_end_clean();
			$result = true;
			return $result;
		}
    }
    
    function loadDbCss(&$css)
    {
		$empty = false;
		foreach ($css AS $k => $v){
			if(empty($v)){
				$empty = true;
				$css[$k] = "jfDb" . ucfirst($k);
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

	function checkIfForumSet(&$params, &$contentitem)
	{
    	$forumid = $params->get("default_forum",false);
		$sectionPairs = $params->get("pair_sections",false);
		$categoryPairs = $params->get("pair_categories",false);
	
		//first we need to check to see if we at least one forum to work with
		if($forumid || $sectionPairs || $categoryPairs) {
	    	//check to see if there are sections/categories that are specifically included/excluded
	    	$sections =& $params->get("include_sections");
			$includeSections = empty($sections) ? false : explode(",",$sections);
	
			$categories =& $params->get("include_categories");
			$includeCategories = empty($categories) ? false : explode(",",$categories);
	
			$sections =& $params->get("exclude_sections");
			$excludeSections = empty($sections) ? false : explode(",",$sections);
	
			$categories =& $params->get("exclude_categories");
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
}
?>