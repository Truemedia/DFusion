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
 * JFusion Public Class for phpBB3
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * @package JFusion_phpBB3
 */
class JFusionPublic_phpbb3 extends JFusionPublic{

    function getJname()
    {
        return 'phpbb3';
    }

    function getRegistrationURL()
    {
        return 'ucp.php?mode=register';
    }

    function getLostPasswordURL()
    {
        return 'ucp.php?mode=sendpassword';
    }

    function getLostUsernameURL()
    {
        return 'ucp.php?mode=sendpassword';
    }

    /************************************************
	 * Functions For JFusion Who's Online Module
	 ***********************************************/

	function getOnlineUserQuery()
	{
		//get a unix time from 5 mintues ago
		date_default_timezone_set('UTC');
		$active = strtotime("-5 minutes",time());

		$query = "SELECT DISTINCT u.user_id AS userid, u.username, u.username AS name FROM #__users AS u INNER JOIN #__sessions AS s ON u.user_id = s.session_user_id WHERE s.session_user_id != 1 AND s.session_time > $active";
		return $query;
	}

	function getNumberOnlineGuests()
	{
		//get a unix time from 5 mintues ago
		date_default_timezone_set('UTC');
		$active = strtotime("-5 minutes",time());

		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__sessions WHERE session_user_id = 1 AND session_time > $active";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}

	function getNumberOnlineMembers()
	{
		//get a unix time from 5 mintues ago
		date_default_timezone_set('UTC');
		$active = strtotime("-5 minutes",time());

		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__sessions WHERE session_user_id != 1 AND session_time > $active";

		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}

    /************************************************
	 * Functions For Frameless Integration
	 ***********************************************/

    function & getBuffer($jPluginParam)
    {
        // Get the path
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');

        //Allow for admin redirects in the hooks
        global $jfusion_source_url;
        $jfusion_source_url = $params->get('source_url');

        //get the filename
        $jfile = JRequest::getVar('jfile');

        //redirect directly to admincp if needed
        if ($jfile == 'adm/index.php') {
            $url ="Location: " . $params->get('source_url') . 'adm/index.php?' . $_SERVER['QUERY_STRING'] ;
            header($url);
        }

		//redirect for file download requests
        if ($jfile == 'file.php') {
            $url ="Location: " . $params->get('source_url') . 'download/file.php?' . $_SERVER['QUERY_STRING'] ;
            header($url);
        }

        //add check for thread subscriptions
        $subscribe = JRequest::getVar('e');
        if($subscribe){
            $jfile = 'viewtopic.php';
            $_GET['p'] = $subscribe;
            $_REQUEST['p'] = $subscribe;
            $_POST['p'] = $subscribe;
        }

        //add a mode for quicktools


        //add check for search function
        $submit = JRequest::getVar('submit');
        if($submit == 'Search'){
            $jfile = 'search.php';
        }

        //add check for quick mod tools function
        $quickmod = JRequest::getVar('quickmod');
        if($quickmod == 1){
            $jfile = 'mcp.php';
			$_GET['mode'] = 'quickmod';
            $_REQUEST['mode'] = 'quickmod';
            $_POST['mode'] = 'quickmod';
        }

        if (!$jfile) {
            //use the default index.php
            $jfile = 'index.php';
        }

		//allow for fix action urls for ucp.php
		if ($jfile == 'ucp.php'){
			global $jfusion_file;
			$jfusion_file = 'ucp.php';
		}

        //combine the path and filename
        if (substr($source_path, -1) == DS) {
            $index_file = $source_path . basename($jfile);
        } else {
            $index_file = $source_path . DS . basename($jfile);
        }

        if (! is_file($index_file) ) {
            JError::raiseWarning(500, 'The path to the requested does not exist');
            $result = false;
            return $result;
        }

        //set the current directory to phpBB3
        chdir($source_path);

        /* set scope for variables required later */
        global $phpbb_root_path, $phpEx, $db, $config, $user, $auth, $cache, $template, $phpbb_hook, $module, $mode;

        //define the phpBB3 hooks
        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $this->getJname().DS.'hooks.php');

        // Get the output
        ob_start();
        try {
            define('UTF8_STRLEN', true);
            define('UTF8_CORE', true);
            define('UTF8_CASE', true);

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



    function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_body, $replace_body;

        if (! $regex_body || ! $replace_body ) {
            // Define our preg arrays
            $regex_body		= array();
            $replace_body	= array();

            //convert relative links from images into absolute links
	        $regex_body[]	= '#(src="|background="|url\(\'?)./(.*?)("|\'?\))#mS';
            $replace_body[]	= '$1'.$integratedURL.'$2$3';

			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
			$params = JFusionFactory::getParams('joomla_int');
			$sefmode = $params->get('sefmode');
			if($sefmode == 1){
		        //some urls such as PM related ones have items appended to it after the url has been parsed by append_sid()
		        $regex_body[]	= '#href="(.*?)'.JURI::base().'(.*?)"(.*?)>#me';
        	    $replace_body[]	= '$this->fixURL("$1" . "'.JURI::base().'"."$2","$3")';
			}

			//fix for form actions
	        $regex_body[]	= '#action="(.*?)"(.*?)>#me';
			$uri		= JURI::getInstance();
			$indexURL	= JURI::base() .'index.php';
            $replace_body[]	= '$this->fixAction("$1","$2","' . $indexURL .'")';
        }

		global $JFusionRedirectParse;
		if($JFusionRedirectParse == true){
	        $regex_body[]	= '#<a href\=\"(.*?)\"\>Return to the previous page\<\/a\>\<\/p\>#me';
   	        $replace_body[]	= '$this->fixRedirectURL("$1")';
		}

        $buffer = preg_replace($regex_body, $replace_body, $buffer);
    }

    function fixURL($url, $extra){
  		//check to see if the URL is in SEF
		if (strpos($url,',')){
			//else do a regex to fix the SEF url
        	$url = preg_replace('#(/&amp\;|/\?\|?|&amp;)(.*?)\=#mS', '/$2,', $url);
        	$url = preg_replace('#(\?)(.*?)\=#mS', '/$2,', $url);
		}

		//return the string unchanged
		return 'href="' . $url . '"' . $extra . '>';

    }

      function fixAction($url, $extra, $baseURL){
      	$url = htmlspecialchars_decode($url);
        $config =& JFactory::getConfig();
        $sef = $config->getValue( 'config.sef' );
        $Itemid = JRequest::getVar('Itemid');

     	//check to see if the URL is in SEF
      	if($sef == 1) {
			$parts = preg_split('/\/\&|\/|&|\?/', $url);
			foreach ($parts as $part){
				$vars =preg_split('/,|=/', $part);
				if(isset($vars[1])){
					$url_variables[$vars[0]] = $vars[1];
				} elseif (strpos($part,'.php')){
					$jfile = $part;
				}
			}
			$baseURL = substr(JURI::base(),0,-1) . JRoute::_('index.php?option=com_jfusion&Itemid='.$Itemid.'&jfile='.$jfile);
			$replacement = 'action="'.$baseURL . '"' . $extra . '>';
	    } else {
	      	$url_details = parse_url($url);
    	  	$url_variables = array();
      		parse_str($url_details['query'], $url_variables);
   	     	$jfile = basename($url_details['path']);
	      	//set the correct action and close the form tag
			$replacement = 'action="'.$baseURL . '"' . $extra . '>';
      		$replacement .= '<input type="hidden" name="jfile" value="'. $jfile . '">';
      		$replacement .= '<input type="hidden" name="Itemid" value="'.$Itemid . '">';
    	  	$replacement .= '<input type="hidden" name="option" value="com_jfusion">';
		}
        unset($url_variables['option'],$url_variables['jfile'],$url_variables['Itemid']);

		//added unset sid as a test
		unset($url_variables['sid']);

		//add any other variables
		if(is_array($url_variables)){
			 foreach ($url_variables as $key => $value){
      			$replacement .=  '<input type="hidden" name="'. $key .'" value="'.$value . '">';
      		}
		}
      	return $replacement;
      }

    function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_header, $replace_header;

        if (! $regex_header || ! $replace_header ) {
            // Define our preg arrays
            $regex_header		= array();
            $replace_header	= array();

            //convert relative links into absolute links
           $regex_header[]	= '#(href="|src=")./(.*?")#mS';
           $replace_header[]	= '$1'.$integratedURL.'$2"';

           //fix for URL redirects
           $regex_header[]	= '#<meta http-equiv="refresh" content="(.*?)"(.*?)>#me';
		   $replace_header[]	= '$this->fixRedirect("$1")';

        }
        $buffer = preg_replace($regex_header, $replace_header, $buffer);
    }

      function fixRedirect($url){

		//get some base vars
    	$params = JFusionFactory::getParams('joomla_int');
		$sefmode = $params->get('sefmode');
		$redirect = JRequest::getVar('redirect');
        $Itemid = JRequest::getVar('Itemid');

      	//split up the timeout from url
		$parts = explode(';url=', $url);

		//allow for redirects on login
		if($redirect){
			$parts[1] = $redirect;
		}

		if($sefmode == 0){
		    $url_parts = explode('?', $parts[1],2);
		    //get the filename
		    $jfile = basename($url_parts[0]);
		    //parse all other vars
			$var_parts = preg_split('/\/\&|\/|&|\?/', $url_parts[1]);
			$url_variables = array();
			foreach ($var_parts as $part){
				$vars =preg_split('/,|=/', $part);
				if(isset($vars[1])){
					$url_variables[] = $vars[0] .'='.$vars[1];
				}
			}
			$redirectURL = substr(JURI::base(),0,-1) . JRoute::_('index.php?option=com_jfusion&Itemid='.$Itemid.'&jfile='.$jfile.'&'.implode('&',$url_variables));

			//trigger redirect parsing in the body
			global $JFusionRedirectParse;
			$JFusionRedirectParse = true;
        	return '<meta http-equiv="refresh" content="'.$parts[0].';url=' . $redirectURL .'">';
		}
      }

      function fixRedirectURL($url){

		//get some base vars
    	$params = JFusionFactory::getParams('joomla_int');
		$sefmode = $params->get('sefmode');
		$redirect = JRequest::getVar('redirect');
        $Itemid = JRequest::getVar('Itemid');

		if($sefmode == 0){
		    $url_parts = explode('?', $url,2);
		    //get the filename
		    $jfile = basename($url_parts[0]);
		    //parse all other vars
			$var_parts = preg_split('/\/\&|\/|&|\?/', $url_parts[1]);
			$url_variables = array();
			foreach ($var_parts as $part){
				$vars =preg_split('/,|=/', $part);
				if(isset($vars[1])){
					$url_variables[] = $vars[0] .'='.$vars[1];
				}
			}
			$redirectURL = substr(JURI::base(),0,-1) . JRoute::_('index.php?option=com_jfusion&Itemid='.$Itemid.'&jfile='.$jfile.'&'.implode('&',$url_variables));
        	return '<a href="' . $redirectURL . '">Return to the previous page</a></p>';
		}
      }

	/************************************************
	 * For JFusion Search Plugin
	 ***********************************************/

	function cleanUpSearchText($text)
	{
		//remove phpbb's bbcode uids
		$text = preg_replace("#\[(.*?):(.*?)]#si","[$1]",$text);
		$text = JFusionFunction::parseCode($text,'html');
		return $text;
	}

	function getSearchQueryColumns()
	{
		$columns = new stdClass();
		$columns->title = "p.post_subject";
		$columns->text = "p.post_text";
		return $columns;
	}

	function getSearchQuery()
	{
		//need to return threadid, postid, title, text, created, section
		$query = 'SELECT p.topic_id, p.post_id, p.forum_id, CASE WHEN p.post_subject = "" THEN CONCAT("Re: ",t.topic_title) ELSE p.post_subject END AS title, p.post_text AS text,
					FROM_UNIXTIME(p.post_time, "%Y-%m-%d %h:%i:%s") AS created,
					CONCAT_WS( "/", f.forum_name, t.topic_title ) AS section
					FROM #__posts AS p
					INNER JOIN #__topics AS t ON t.topic_id = p.topic_id
					INNER JOIN #__forums AS f on f.forum_id = p.forum_id';
		return $query;
	}

	function getSearchCriteria(&$where)
	{
		$where .= " AND p.post_approved = 1";
	}

	function filterSearchResults(&$results)
	{

	}

	function getSearchResultLink($post)
	{
		$forum = JFusionFactory::getForum($this->getJname());
		return $forum->getPostURL($post->topic_id,$post->post_id);
	}
}

