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

	function & getBuffer()
	{
     	JError::raiseWarning(500, 'Frameless integration is not yet implemented for vBulletin.');
		return null;

		// Get the path
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');
        $source_url = $params->get('source_url');

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

		//constants needed in vbulletin hooks
		define('_JFUSION_JNAME', $this->getJname());
		define('_JFUSION_SOURCE_PATH', $source_path);
		define('_JFUSION_SOURCE_URL', $source_url);

		try {
            include_once($index_file);
        }
        catch(Exception $e) {
            $buffer = ob_get_contents() ;
            ob_end_clean();
        }

		global $mainframe;
		$mainframe->setPageTitle('');

		//change the current directory back to Joomla.
		chdir(JPATH_SITE);

		return $buffer;
	}

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		//fix for form actions
		//cannot use preg_replace here because it adds unneeded slashes which messes up JS
		$action_search	= '#action="(.*?)"(.*?)>#mS';
        $buffer = preg_replace_callback($action_search,'fixAction',$buffer);

        //fix for the rest of the urls
        $url_search = '#href="(.*?)"(.*?)>#mS';
        $buffer = preg_replace_callback($url_search,'fixURL',$buffer);

        //convert relative links from images and js files into absolute links
	    $buffer = preg_replace('#(src="|background="|url\(\'?)(.*?)("|\'?\))#mS', '$1'.$integratedURL.'$2$3', $buffer);

        //we need to fix the cron.php file
		$buffer = preg_replace('#src="(.*)cron.php(.*)>#mS','src="'.$integratedURL.'cron.php$2>',$buffer);

        //we need to wrap the body in a div to prevent some CSS clashes
        $buffer = "<div id = 'framelessVb'>\n$buffer\n</div>";
	}

	function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_header, $replace_header;

		if ( ! $regex_header || ! $replace_header )
		{
			// Define our preg arrays
			$regex_header		= array();
			$replace_header	= array();

			//convert relative links into absolute links
			$regex_header[]	= '#(href=|src=|url\()("./|"/|"|\'./|\'/|\')(.*?)("|\')#mS';
			$replace_header[]	= '$1$2'.$integratedURL.'$3$4';


		   //fix for URL redirects
           $regex_header[]	= '#<meta http-equiv="refresh" content="(.*?)"(.*?)>#me';
		   $replace_header[]	= '$this->fixRedirect("$1")';
		}

		$buffer = preg_replace($regex_header, $replace_header, $buffer);

		//now we need to do a little CSS cleanup to optimize frameless
		$buffer = preg_replace("!\b(body)\b!",'#framelessVb',$buffer);
		$buffer = str_replace("td, th, p, li", "#framelessVb td, #framelessVb th, #framelessVb p, #framelessVb li",$buffer);
		$buffer = str_replace("td.thead, th.thead, div.thead","#framelessVb td.thead, #framelessVb th.thead, #framelessVb div.thead",$buffer);
	}

     function fixRedirect($url){
      	//split up the timeout from url
		$parts = explode(';url=', $url);

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
			$redirect_url = $source_url . 'index.php?' . $uri->getQuery();
	    }

      	//reconstruct the redirect meta tag
       return '<meta http-equiv="refresh" content="'.$parts[0] . ';url=' . $redirect_url .'">';
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

	function getSearchResultLink($post)
	{
		$forum = JFusionFactory::getForum($this->getJname);
		return $forum->getPostURL($post->threadid,$post->postid);
	}

}


function fixAction($matches)
{
	//die("<pre>".print_r($matches,true)."</pre>");
	$url = $matches[1];
	$extra = $matches[2];
	$uri		= JURI::getInstance();
	$baseURL	= JURI::base() .'index.php';
	$url = htmlspecialchars_decode($url);
	$url_details = parse_url($url);
	$url_variables = array();
	parse_str($url_details['query'], $url_variables);

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

	return $replacement;
}


function fixURL($matches)
{
	$url = $matches[1];
	$extra = $matches[2];

	//Clean the url and the params first
	$url  = str_replace( '&amp;', '&', $url );

	//we need to make some exceptions

	//url is already parsed
	if(strpos($url, 'jfile=')!==false) {
		$url = preg_replace('#.*jfile=(.*?\.php).*#mS', '$1', $url);
		$replacement = 'href="'.$url . '"' . $extra . '>';
        return $replacement;
	}

	//absolute url
	if(strpos($url,'http')!==false) {
		return "href=\"$url\" $extra>";
	}

	//js function
	if($url=="#") {
		return "href=\"#\" $extra>";
	}

	//admincp, mocp, or archive
    if (strpos($url,'admincp')!==false || strpos($url,'modcp')!==false || strpos($url,'archive')!==false) {
		return 'href="' . _JFUSION_SOURCE_URL . $url . "\" $extra>";
    }

	//Create the URL
	$uri = new JURI($url);

	//get the query
	$query = $uri->getQuery(true);
	$uri->setQuery($query);

	if(strpos($url, '#') !== false)
	{
		$fragment = $uri->getFragment($url);
		$uri->setFragment($fragment);
	}

	$filename = $uri->getPath();
	$break = explode('/', $filename);
	$view = $break[count($break) - 1];

	//set the jfile param if needed
	if(!empty($view)){
		$uri->setVar('jfile', $view);
	}

	//set the jfusion references for Joomla
	$Itemid = JRequest::getVar('Itemid');

	if ($Itemid){
		$uri->setVar('Itemid', $Itemid);
	}

	$uri->setVar('option', 'com_jfusion');

	$url = 'index.php'.$uri->toString(array('query', 'fragment'));

	$url = urldecode(JRoute::_($url, true));

	//set the correct url and close the a tag
	$replacement = 'href="'.$url . '"' . $extra . '>';

	return $replacement;
}