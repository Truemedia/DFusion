<?php

/**
* @package JFusion_vBulletin
* @version 1.1.0-001
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');


/**
* JFusion plugin class for vBulletin 3.6.8
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
	}

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_body, $replace_body;

		if ( ! $regex_body || ! $replace_body )
		{
			// Define our preg arrayshttp://www.jfusion.org/administrator/index.php?option=com_extplorer#
			$regex_body		= array();
			$replace_body	= array();

			//convert relative links with query into absolute links
			$regex_body[]	= '#href="./(.*)\?(.*)"#mS';
			$replace_body[]	= 'href="'.$baseURL.'&jfile=$1&$2"';

			//convert relative links without query into absolute links
			$regex_body[]	= '#href="./(.*)"#mS';
			$replace_body[]	= 'href="'.$baseURL.'&jfile=$1"';

			//convert relative links from images into absolute links
			$regex_body[]	= '#(src="|url\()./(.*)("|\))#mS';
			$replace_body[]	= '$1'.$integratedURL.'$2$3"';

			//convert links to the same page with anchors
			$regex_body[]	= '#href="\#(.*?)"#';
			$replace_body[]	= 'href="'.$fullURL.'&#$1"';

			//update site URLs to the new Joomla URLS
			$regex_body[]	= "#$integratedURL(.*)\?(.*)\"#mS";
			$replace_body[]	= $baseURL . '&jfile=$1&$2"';

			//convert action URLs inside forms to absolute URLs
			//$regex_body[]	= '#action="(.*)"#mS';
			//$replace_body[]	= 'action="'.$integratedURL.'/"';

		}

		$buffer = preg_replace($regex_body, $replace_body, $buffer);
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
			$regex_header[]	= '#(href|src)=("./|"/)(.*?)"#mS';
			$replace_header[]	= 'href="'.$integratedURL.'$3"';

			//$regex_header[]	= '#(href|src)="(.*)"#mS';
			//$replace_header[]	= 'href="'.$integratedURL.'$2"';

		}

		$buffer = preg_replace($regex_header, $replace_header, $buffer);
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

