<?php

/**
 * @package JFusion_vBulletin
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

global $name, $baseURL, $fullURL, $integratedURL, $vbsefmode;
/**
 * JFusion Public Class for vBulletin
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * @package JFusion_vBulletin
 */
class JFusionPublic_vbulletin extends JFusionPublic{

	function getJname()
	{
		return 'vbulletin';
	}

	function getRegistrationURL()
	{
		return 'register.php';
	}

	function getLostPasswordURL()
	{
		return 'login.php?do=lostpw';
	}

	function getLostUsernameURL()
	{
		return 'login.php?do=lostpw';
	}

    
   /************************************************
	 * Functions For JFusion Who's Online Module
	 ***********************************************/

	/**
	 * Returns a query to find online users
	 * Make sure the columns are in this order: userid, username, name (of user)
	 */
	function getOnlineUserQuery()
	{
		return "SELECT DISTINCT u.userid, u.username AS username, u.username AS name FROM #__user AS u INNER JOIN #__session AS s ON u.userid = s.userid WHERE s.userid != 0";
	}
	
	/**
	 * Returns number of guests
	 * @return int
	 */
	function getNumberOnlineGuests()
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__session WHERE userid = 0";
		$db->setQuery($query);
		return $db->loadResult();
	}
	
	/**
	 * Returns number of logged in users
	 * @return int
	 */
	function getNumberOnlineMembers()
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__session WHERE userid != 0";
		$db->setQuery($query);
		return $db->loadResult();
	}
	
	/************************************************
	* Functions For Frameless View
	************************************************/

	function & getBuffer($jPluginParam)
	{
		global $vbsefmode, $vbJname, $vbsefenabled;
		//define('_JFUSION_DEBUG',1);		
		
		//check to make sure the frameless hook is installed
		$db =& JFusionFactory::getDatabase($this->getJname());
		$q = "SELECT active FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Frameless Integration Plugin'";
		$db->setQuery($q);
		$active = $db->loadResult();
		if($active!='1') {
			JError::raiseWarning(500, JText::_('VB_FRAMELESS_HOOK_NOT_INSTALLED'));
			return null;
		}
		//have to clear this as it shows up in some text boxes
		unset($q);
		
		// Get some params
		$params = JFusionFactory::getParams($this->getJname());
		$vbsefmode = $params->get('sefmode',0);
		$source_path = $params->get('source_path');
		$source_url = $params->get('source_url');
    	$config =& JFactory::getConfig();
		$vbsefenabled = $config->getValue('config.sef');

		//get the jname to be used in the hook file
		$vbJname = $this->getJname();
				
		//get the filename
		$jfile = JRequest::getVar('jfile');

		if(!$jfile) {
			//use the default index.php
			$jfile = 'index.php';
		}

		//combine the path and filename
		if (substr($source_path, -1) == DS) {
			$index_file = $source_path . $jfile;
		} else {
			$index_file = $source_path . DS . $jfile;
		}

		if ( ! is_file($index_file) ) {
			JError::raiseWarning(500, 'The path to the requested does not exist');
			return null;
		}

		//set the current directory to vBulletin
		chdir($source_path);

		// Get the output
		ob_start();

		//aaaahhh; basically everything global in vbulletin must be declared here for it to work  ;-{
		//did not include specific globals in admincp
		$vbGlobals = array(
			'_CALENDARHOLIDAYS'
			,'_CALENDAROPTIONS'
			,'_TEMPLATEQUERIES'
			,'ad_location'
			,'albumids'
			,'allday'
			,'altbgclass'
			,'attachementids'
			,'badwords'
			,'bb_view_cache'
			,'bgclass'
			,'birthdaycache'
			,'cache_postids'
			,'calendarcache'
			,'calendarids'
			,'calendarinfo'
			,'calmod'
			,'checked'
			,'checked'
			,'cmodcache'
			,'colspan'
			,'copyrightyear'
			,'count'
			,'counters'
			,'cpnav'
			,'curforumid'
			,'curpostid'
			,'curpostidkey'
			,'currentdepth'
			,'customfields'
			,'datastore_fetch'
			,'date1'
			,'date2'
			,'datenow'
			,'day'
			,'days'
			,'daysprune'
			,'db'
			,'defaultselected'
			,'DEVDEBUG'
			,'disablesmiliesoption'
			,'display'
			,'dotthreads'
			,'doublemonth'
			,'doublemonth1'
			,'doublemonth2'
			,'eastercache'
			,'editor_css'
			,'eventcache'
			,'eventdate'
			,'eventids'
			,'faqcache'
			,'faqjumpbits'
			,'faqlinks'
			,'faqparent'
			,'folder'
			,'folderid'
			,'foldernames'
			,'folderselect'
			,'footer'
			,'foruminfo'
			,'forumjump'
			,'forumpermissioncache'
			,'forumperms'
			,'forumrules'
			,'forumshown'
			,'frmjmpsel'
			,'gobutton'
			,'goodwords'
			,'header'
			,'headinclude'
			,'holiday'
			,'html_allowed'
			,'hybridposts'
			,'ifaqcache'
			,'ignore'
			,'imodcache'
			,'imodecache'
			,'inforum'
			,'infractionids'
			,'ipclass'
			,'ipostarray'
			,'istyles'
			,'jumpforumbits'
			,'jumpforumtitle'
			,'langaugecount'
			,'laspostinfo'
			,'lastpostarray'
			,'limitlower'
			,'limitupper'
			,'links'
			,'message'
			,'messagearea'
			,'messagecounters'
			,'messageid'
			,'mod'
			,'month'
			,'months'
			,'monthselected'
			,'morereplies'
			,'navclass'
			,'newthreads'
			,'notifications_menubits'
			,'notifications_total'
			,'onload'
			,'optionselected'
			,'p'
			,'p_two_linebreak'
			,'pagestarttime'
			,'pagetitle'
			,'parent_postids'
			,'parentassoc'
			,'parentoptions'
			,'parents'
			,'pda'
			,'period'
			,'permissions'
			,'permscache'
			,'perpage'
			,'phrasegroups'
			,'phrasequery'
			,'pictureids'
			,'pmbox'
			,'pmids'
			,'post'
			,'postarray'
			,'postattache'
			,'postids'
			,'postinfo'
			,'postorder'
			,'postparent'
			,'postusername'
			,'previewpost'			
			,'project_forums'
			,'project_types'
			,'querystring'
			,'querytime'
			,'rate'
			,'ratescore'
			,'recurcriteria'
			,'reminder'
			,'replyscore'
			,'searchforumids'
			,'searchids'
			,'searchthread'
			,'searchthreadid'
			,'searchtype'
			,'selectedicon'
			,'selectedone'
			,'serveroffset'
			,'show'
			,'smilebox'
			,'socialgroups'
			,'spacer_close'
			,'spacer_open'
			,'strikes'
			,'style'
			,'stylecount'
			,'stylevar'
			,'subscribecounters'
			,'subscriptioncache'
			,'template_hook'
			,'templateassoc'
			,'tempusagecache'
			,'threadedmode'
			,'threadids'
			,'threadinfo'
			,'time1'
			,'time2'
			,'timediff'
			,'timenow'
			,'timerange'
			,'timezone'
			,'titlecolor'
			,'titleonly'
			,'today'
			,'usecategories'
			,'usercache'
			,'userids'
			,'vbcollapse'
			,'vBeditTemplate'
			,'vboptions'
			,'vbphrase'
			,'vbulletin'
			,'viewscore'
			,'wol_album'
			,'wol_attachement'
			,'wol_calendar'
			,'wol_event'
			,'wol_inf'
			,'wol_pm'
			,'wol_post'
			,'wol_search'
			,'wol_socialgroup'
			,'wol_thread'
			,'wol_user'
			,'year'
			);

			foreach($vbGlobals as $g)
			{
				//global the variable
				global $$g;
			}

			if(defined('_JFUSION_DEBUG')) {
				$_SESSION["jfvbdebug"] = array();
			}
			
			try {
				include_once($index_file);
			}
			catch(Exception $e) {
				$buffer = ob_get_contents() ;
				ob_end_clean();
			}

			//change the current directory back to Joomla.
			chdir(JPATH_SITE);
			
			return $buffer;
	}

	function parseBody(&$buffer, $localBaseURL, $localFullURL, $localIntegratedURL)
	{
		global $name, $baseURL, $fullURL, $integratedURL, $vbsefmode, $vbsefenabled;
		$name = $this->getJname();
		$baseURL = $localBaseURL;
		$fullURL = $localFullURL;
		$integratedURL = $localIntegratedURL;
		$params = JFusionFactory::getParams($name);
		$vbsefmode = $params->get('sefmode',0);
    	$config =& JFactory::getConfig();
		$vbsefenabled = $config->getValue('config.sef');
		
		//fix for form actions
		//cannot use preg_replace here because it adds unneeded slashes which messes up JS
		$action_search	= '#action="(.*?)"(.*?)>#mS';
		$buffer = preg_replace_callback($action_search,'fixAction',$buffer);

		//fix for the rest of the urls
		$url_search = '#href="(.*?)"(.*?)>#mS';
		$buffer = preg_replace_callback($url_search,'fixURL',$buffer);

		//fix for the rest of the urls
		$url_search = '#<href="(.*?)"(.*?)>#mS';
		$buffer = preg_replace_callback($url_search,'fixURL',$buffer);

		//convert relative links from images and js files into absolute links
		$include_search = '#(src="|background="|url\(\'|window.open\(\'?)(?!http)(.*?)("|\'?\)|\')#mS';
		$buffer = preg_replace_callback($include_search, 'fixInclude', $buffer);
		
		//we need to fix the cron.php file
		$buffer = preg_replace('#src="(.*)cron.php(.*)>#mS','src="'.$integratedURL.'cron.php$2>',$buffer);

		//we need to wrap the body in a div to prevent some CSS clashes
		$buffer = "<div id = 'framelessVb'>\n$buffer\n</div>";

		if(defined('_JFUSION_DEBUG')) {
			$buffer .= "<pre><code>" . htmlentities(print_r($_SESSION["jfvbdebug"],true) ). "</code></pre>";
			$buffer .= "<pre><code>" . htmlentities(print_r($GLOBALS['vbulletin'],true) ). "</code></pre>";
		}
	}

	function parseHeader(&$buffer, $localBaseURL, $localFullURL, $localIntegratedURL)
	{
		global $name, $baseURL, $fullURL, $integratedURL, $vbsefmode, $vbsefenabled;
		$name = $this->getJname();
		$baseURL = $localBaseURL;
		$fullURL = $localFullURL;
		$integratedURL = $localIntegratedURL;
		$params = JFusionFactory::getParams($name);
		$vbsefmode = $params->get('sefmode',0);
    	$config =& JFactory::getConfig();
		$vbsefenabled = $config->getValue('config.sef');
		
		$js  = "<script type=\"text/javascript\">\n";
		$js .= "var vbSourceURL = '$integratedURL';\n";
		$js .= "</script>\n";
		
		//we need to find and change the call to vb's yahoo connection file to our own customized one
		//that adds the source url to the ajax calls
		$yuiURL = JFusionFunction::getJoomlaURL().'administrator'.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname();
		$buffer = preg_replace('#\<script type="text\/javascript" src="(.*?)(connection-min.js|connection.js)\?v=(.*?)"\>#mS',"$js <script type=\"text/javascript\" src=\"$yuiURL/yui/connection/connection-min.js?v=$3\">",$buffer);

		//convert relative links into absolute links
		$url_search = '#(src="|background="|href="|url\("|url\(\'?)(?!http)(.*?)("\)|\'?\)|")#mS';
		$buffer = preg_replace_callback($url_search, 'fixInclude', $buffer);

		$start = '<style type="text/css" id="vbulletin_css">';
		$end = '</style>';

		$buffer = " ".$buffer;
		$ini = strpos($buffer,$start);
		if ($ini !== false) {
			$ini += strlen($start);
			$len = strpos($buffer,$end,$ini) - $ini;
			$css = substr($buffer,$ini,$len);
			$newCss = fixCSS($css);
			$buffer = str_replace($css,$newCss,$buffer);
		}
	}
	
	function getPathWay()
	{
		$mainframe = &JFactory::getApplication('site');
		$db =& JFusionFactory::getDatabase($this->getJname());
		$pathway = array();
		
		//let's get the jfile
		$jfile = JRequest::getVar('jfile');

		//we are viewing a forum
		if(JRequest::getVar('f',false)!==false) {
			$fid = JRequest::getVar('f');
			$query = "SELECT title, parentlist, parentid from #__forum WHERE forumid = $fid";
			$db->setQuery($query);
			$forum = $db->loadObject();
			
			if($forum->parentid!='-1') {
				$parents = array_reverse(explode(',',$forum->parentlist));
				foreach($parents as $p){
					if($p!="-1") {
						$query = "SELECT title from #__forum WHERE forumid = $p";
						$db->setQuery($query);
						$title = $db->loadResult();
						$crumb = new stdClass();
						$crumb->title = $title;
						$crumb->url = "forumdisplay.php?f=$p";
						$pathway[] = $crumb;						
					}
				}
			} else {
				$crumb = new stdClass();
				$crumb->title = $forum->title;
				$crumb->url = "forumdisplay.php?f=$fid";
				$pathway[] = $crumb;
			}
		} elseif (JRequest::getVar('t',false)!==false) {
			$tid = JRequest::getVar('t');
			$query = "SELECT t.title AS thread, f.title AS forum, f.forumid, f.parentid, f.parentlist FROM #__thread AS t JOIN #__forum AS f ON t.forumid = f.forumid WHERE t.threadid = $tid";
			$db->setQuery($query);
			$result = $db->loadObject();
			
			if($result->parentid!='-1') {
				$parents = array_reverse(explode(',',$result->parentlist));
				foreach($parents as $p){
					if($p!="-1") {
						$query = "SELECT title from #__forum WHERE forumid = $p";
						$db->setQuery($query);
						$title = $db->loadResult();
						$crumb = new stdClass();
						$crumb->title = $title;
						$crumb->url = "forumdisplay.php?f=$p";
						$pathway[] = $crumb;						
					}
				}
			} else {
				$crumb = new stdClass();
				$crumb->title = $result->forum;
				$crumb->url = "forumdisplay.php?f=$result->forumid";
				$pathway[] = $crumb;
			}

			$crumb = new stdClass();
			$crumb->title = $result->thread;
			$crumb->url = "showthread.php?t=$tid";
			$pathway[] = $crumb;
		} elseif(JRequest::getVar('p',false)!==false) {
			$pid = JRequest::getVar('p');
			$query = "SELECT t.title AS thread, f.title AS forum, f.forumid, f.parentid, f.parentlist FROM #__thread AS t JOIN #__forum AS f JOIN #__post AS p ON t.forumid = f.forumid AND t.threadid = p.threadid WHERE p.postid = $pid";
			$db->setQuery($query);
			$result = $db->loadObject();
			
			if($result->parentid!='-1') {
				$parents = array_reverse(explode(',',$result->parentlist));
				foreach($parents as $p){
					if($p!="-1") {
						$query = "SELECT title from #__forum WHERE forumid = $p";
						$db->setQuery($query);
						$title = $db->loadResult();
						$crumb = new stdClass();
						$crumb->title = $title;
						$crumb->url = "forumdisplay.php?f=$p";
						$pathway[] = $crumb;						
					}
				}
			} else {
				$crumb = new stdClass();
				$crumb->title = $result->forum;
				$crumb->url = "forumdisplay.php?f=$result->forumid";
				$pathway[] = $crumb;
			}

			$crumb = new stdClass();
			$crumb->title = $result->thread;
			$crumb->url = "showthread.php?t=$tid";
			$pathway[] = $crumb;			
		} elseif (JRequest::getVar('u',false)!==false) {
			if($jfile=="member.php") {
				// we are viewing a member's profile
				$uid = JRequest::getVar('u');
				$crumb = new stdClass();
				$crumb->title = 'Members List';
				$crumb->url = 'memberslist.php';
				$pathway[] = $crumb;
				
				$query = "SELECT username FROM #__user WHERE userid = $uid";
				$db->setQuery($query);
				$username = $db->loadResult();
				$crumb = new stdClass();
				$crumb->title = "$username's Profile";
				$crumb->url = "member.php?u=$uid";
				$pathway[] = $crumb;				
			}
		} elseif($jfile=="search.php") {
			$crumb = new stdClass();
			$crumb->title = "Search";
			$crumb->url = "search.php";
			$pathway[] = $crumb;

			if(JRequest::getVar('do',false) !== false) {
				$do = JRequest::getVar('do');
				if($do=="getnew") {
					$crumb = new stdClass();
					$crumb->title = "New Posts";
					$crumb->url = "search.php?do=getnew";
					$pathway[] = $crumb;					
				} elseif($do=="getdaily") {
					$crumb = new stdClass();
					$crumb->title = "Today's Posts";
					$crumb->url = "search.php?do=getdaily";
					$pathway[] = $crumb;					
				}
			}
		} elseif($jfile=="private.php") {
			$crumb = new stdClass();
			$crumb->title = "User Control Panel";
			$crumb->url = "usercp.php";
			$pathway[] = $crumb;	
			
			$crumb = new stdClass();
			$crumb->title = "Private Messages";
			$crumb->url = "private.php";
			$pathway[] = $crumb;	
		} elseif($jfile=="usercp.php") {
			$crumb = new stdClass();
			$crumb->title = "User Control Panel";
			$crumb->url = "usercp.php";
			$pathway[] = $crumb;				
		} elseif($jfile=="profile.php") {
			$crumb = new stdClass();
			$crumb->title = "User Control Panel";
			$crumb->url = "usercp.php";
			$pathway[] = $crumb;	
			
			if(JRequest::getVar('do',false) !== false) {
				$crumb = new stdClass();
				$crumb->title = "Your Profile";
				$crumb->url = "profile.php?do=editprofile";
				$pathway[] = $crumb;	
			}
		} elseif($jfile=="moderation.php") {
			$crumb = new stdClass();
			$crumb->title = "User Control Panel";
			$crumb->url = "usercp.php";
			$pathway[] = $crumb;	
			
			if(JRequest::getVar('do',false) !== false) {
				$crumb = new stdClass();
				$crumb->title = "Moderator Tasks";
				$crumb->url = "moderation.php";
				$pathway[] = $crumb;	
			}			
		} elseif($jfile=="memberlist.php") {
			$crumb = new stdClass();
			$crumb->title = "Members List";
			$crumb->url = "memberslist.php";
			$pathway[] = $crumb;
		}

		return $pathway;
	}	


	/************************************************
	 * For JFusion Search Plugin
	 ***********************************************/

	function cleanUpSearchText($text)
	{	
		$text = JFusionFunction::parseCode($text,'html');
		return $text;
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
		//need to return threadid, postid, title, text, created, section
		$query = 'SELECT p.threadid, p.postid, f.forumid, CASE WHEN p.title = "" THEN CONCAT("Re: ",t.title) ELSE p.title END AS title, p.pagetext AS text,
					FROM_UNIXTIME(p.dateline, "%Y-%m-%d %h:%i:%s") AS created,
					CONCAT_WS( "/", f.title_clean, t.title ) AS section
					FROM #__post AS p
					INNER JOIN #__thread AS t ON p.threadid = t.threadid
					INNER JOIN #__forum AS f on f.forumid = t.forumid';
		return $query;
	}

	function getSearchCriteria(&$where)
	{
		$where .= " AND p.visible = 1";
	}
	
	function filterSearchResults(&$results)
	{
		$plugin =& JFusionFactory::getForum($this->getJname());
		$plugin->filterForumList($results, 'forumid'); 
	}
	
	function getSearchResultLink($post)
	{
		$forum = JFusionFactory::getForum($this->getJname());
		return $forum->getPostURL($post->threadid,$post->postid);
	}
}

function fixAction($matches)
{
	$url = $matches[1];
	$extra = $matches[2];

	if(defined('_JFUSION_DEBUG')) {
		$debug = array();
		$debug['original'] = $matches[0];
		$debug['url'] = $url;
		$debug['extra'] = $extra;
		$debug['function'] = 'fixAction';
	}

	$uri		= JURI::getInstance();
	$baseURL	= JURI::base() .'index.php';
	$url = htmlspecialchars_decode($url);
	$url_details = parse_url($url);
	$url_variables = array();
	parse_str($url_details['query'], $url_variables);

	if(defined('_JFUSION_DEBUG')) {
		$debug['url_variables'] = $url_variables;
	}

	//set the correct action and close the form tag
	$replacement = 'action=\''.$baseURL . '\'' . $extra . '>';

	//add which file is being referred to
	if ($url_variables['jfile']){
		//use the action file that was in jfile variable
		$jfile = $url_variables['jfile'];
		unset($url_variables['jfile']);
	} else {
		//use the action file from the action URL itself
		$jfile = basename($url_details['path']);
	}

	$replacement .= '<input type="hidden" name="jfile" value="'. $jfile . '">';

	//add a reference to JFusion
	$replacement .= '<input type="hidden" name="option" value="com_jfusion">';
	unset($url_variables['option']);

	//add a reference to the itemid if set
	$Itemid = JRequest::getVar('Itemid');
	if ($Itemid){
		$replacement .=  '<input type="hidden" name="Itemid" value="'.$Itemid . '">';
	}
	unset($url_variables['Itemid']);

	//add any other variables
	foreach ($url_variables as $key => $value){
		$replacement .=  '<input type="hidden" name="'. $key .'" value="'.$value . '">';
	}

	if(defined('_JFUSION_DEBUG')) {
		$debug['parsed']= $replacement;
		$_SESSION['jfvbdebug'][] = $debug;
	}

	return $replacement;
}


function fixURL($matches)
{
	global $name, $baseURL, $integratedURL, $vbsefmode, $vbsefenabled;

	$url = $matches[1];
	$extra = $matches[2];

	
	
	if(defined('_JFUSION_DEBUG')) {
		$debug = array();
		$debug['original'] = $matches[0];
		$debug['url']= $url;
		$debug['extra'] = $parts;
		$debug['function'] = 'fixURL';
	}

	//Clean the url
	$url = str_replace( '&amp;', '&', $url );
	if((string) strpos($url, '#') === (string)0 && strlen($url) != 1) {
		$url = $_SERVER['REQUEST_URI'].$url;
	}
	
	//we need to make some exceptions
	//absolute url, already parsed URL, JS function, or jumpto
	if(strpos($url,'http')!==false || strpos($url,$_SERVER['REQUEST_URI'])!==false || ((string) strpos($url, '#') === (string)0 && strlen($url) == 1)) {
		$replacement = "href=\"$url\" $extra>";
		if(defined('_JFUSION_DEBUG')) {
			$debug['parsed'] = $replacement;
		}
		return $replacement;
	}
	
	//admincp, mocp, archive, or printthread
	if (strpos($url,'admincp')!==false || strpos($url,'modcp')!==false || strpos($url,'archive')!==false || strpos($url,'printthread.php')!==false) {
		$replacement = 'href="' . $integratedURL . $url . "\" $extra>";
		if(defined('_JFUSION_DEBUG')) {
			$debug['parsed'] = $replacement;
		}
		return $replacement;
	}
	

	//if the plugin is set as a slave, find the master and replace register/lost password urls
	if (strpos($url,'register.php')!==false) {
		$master = JFusionFunction::getMaster();
		if(!empty($master) && $master->name!=$name) {
			$master =& JFusionFactory::getPublic($master->name);
			$url =  $master->getRegistrationURL();
		}
	}

	if (strpos($url,'login.php?do=lostpw')!==false) {
		$master = JFusionFunction::getMaster();
		if(!empty($master) && $master->name!=$name) {
			$master =& JFusionFactory::getPublic($master->name);
			$url =  $master->getLostPasswordURL();
		}
	}

	
	if (empty($vbsefenabled)){
		//non sef URls
		$url = str_replace('?', '&amp;', $url);
		$url = $baseURL . '&amp;jfile=' .$url;
	} else {
		if ($vbsefmode) {
			$url =  JFusionFunction::routeURL($url, JRequest::getVar('Itemid'));
		} else {
			//we can just append both variables
			$url = $baseURL . $url;
		}
	}

	//set the correct url and close the a tag
	$replacement = 'href="'.$url . '"' . $extra . '>';

	if(defined('_JFUSION_DEBUG')) {
		$debug['parsed'] = $replacement;
		$_SESSION["jfvbdebug"][] = $debug;
	}

	return $replacement;
}

function fixInclude($matches)
{
	global $integratedURL;
	$pre = $matches[1];
	$url = $matches[2];
	$post = $matches[3];

	$replacement = $pre . $integratedURL . $url . $post;

	if(defined('_JFUSION_DEBUG')) {
		$debug = array();
		$debug['original'] = $matches[0];
		$debug['pre'] = $pre;
		$debug['url'] = $url;
		$debug['post'] = $post;
		$debug['function'] = 'fixInclude';
		$debug['replacement'] = $replacement;
		$_SESSION['jfvbdebug'][] = $debug;
	}

	return $replacement;
}


function fixRedirect($matches)
{
	$url = $matches[1];

	//split up the timeout from url
	$parts = explode(';url=', $url);

	if(defined('_JFUSION_DEBUG')) {
		$debug = array();
		$debug['original'] = $matches[0];
		$debug['url']= $url;
		$debug['extra'] = $parts;
		$debug['function'] = 'fixRedirect';
	}

	//get the correct URL to joomla
	$params = JFusionFactory::getParams('joomla_int');
	$source_url = $params->get('source_url');

	//check to see if the URL is in SEF
	if (strpos($parts[1],'index.php/')){
		//fix inaccuracies in the phpBB3 SEF url generation code
		$parts[1] = preg_replace('#(/&amp\;|/\?|&amp;)(.*?)\=#mS', '/$2,', $parts[1]);
		$query = explode('index.php/', $parts[1]);
		$redirect_url = $source_url . 'index.php/' . $query[1];
	} else {
		//parse the non-SEF URL
		$uri = new JURI($parts[1]);
		//set the URL with the jFusion params to correct any domain mistakes
		//set the jfusion references for Joomla
		$Itemid = JRequest::getVar('Itemid');
		if ($Itemid){
			$uri->setVar('Itemid', $Itemid);
		}
		$uri->setVar('option', 'com_jfusion');

		//set the URL with the jFusion params to correct any domain mistakes
		$redirect_url = $source_url . 'index.php?' . $uri->getQuery();
	}

	//reconstruct the redirect meta tag
	$replacement = '<meta http-equiv="refresh" content="'.$parts[0] . ';url=' . $redirect_url .'">';
	if(defined('_JFUSION_DEBUG')) {
		$debug['parsed'] = $replacement;
		$_SESSION["jfvbdebug"][] = $debug;
	}

	return $replacement;
}

function fixCSS($css)
{
	if(defined('_JFUSION_DEBUG')) {
		$debug = array();
		$debug['function'] = 'fixCSS';
		$debug['original'] = $css;
	}

	//remove comments
	//trim off top to the body selector
	$ini = strpos($css,'body');
	$len = strlen($css);
	$css = substr($css,$ini,$len);

	$css = preg_replace('#\/\*(.*?)\*\/#mS','',$css);

	//strip newlines
	$css = str_replace("\r\n","",$css);

	//break up the CSS into styles
	$elements = explode('}',$css);
	//unset the last one as it is empty
	unset($elements[count($elements)-1]);

	//rewrite css
	foreach($elements as $k => $v) {
		//breakup each element into selectors and properties
		$element = explode("{", $v);
		//breakup the selectors
		$selectors = explode(",",$element[0]);
		foreach($selectors as $sk => $sv){
			//add vb framless container
			if($sv == 'body'){
				$selectors[$sk] = '#framelessVb';
			} elseif(strpos($sv,'wysiwyg')===false) {
				$selectors[$sk] = "#framelessVb $sv";
			}
		}

		//reconstruct the element
		$elements[$k] = implode(', ', $selectors) . ' {' . $element[1] . '}';
	}

	//reconstruct the css
	$css = implode("\n",$elements);

	if(defined('_JFUSION_DEBUG')) {
		$debug['parsed'] = $css;
		$_SESSION["jfvbdebug"] = $debug;
	}

	return $css;
}