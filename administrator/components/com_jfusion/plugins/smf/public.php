<?php

/**
* @package JFusion_SMF
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Public Class for SMF 1.1.x
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * @package JFusion_SMF
 */
class JFusionPublic_smf extends JFusionPublic{

	function getJname()
	{
		return 'smf';
	}

	function getRegistrationURL()
	{
		return 'index.php?action=register';
	}

	function getLostPasswordURL()
	{
		return 'index.php?action=reminder';
	}

	function getLostUsernameURL()
	{
		return 'index.php?action=reminder';
	}

	function & getBuffer()
	{
		$joomla_globals = $GLOBALS;
		// We're going to want a few globals... these are all set later.
		global $time_start, $maintenance, $msubject, $mmessage, $mbname, $language;
		global $boardurl, $boarddir, $sourcedir, $webmaster_email, $cookiename;
		global $db_connection, $db_server, $db_name, $db_user, $db_prefix, $db_persist, $db_error_send, $db_last_error;
		global $modSettings, $context, $sc, $user_info, $topic, $board, $txt;
		global $scripturl;

		// Required to avoid a warning about a license violation even though this is not the case
		global $forum_version;

		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'hooks.php');

		// Get the path
		$params = JFusionFactory::getParams($this->getJname());
		$source_path = $params->get('source_path');

		if (substr($source_path, -1) == DS) {
			$index_file = $source_path .'index.php';
		} else {
			$index_file = $source_path .DS.'index.php';
		}

		if ( ! is_file($index_file) ) {
			JError::raiseWarning(500, 'The path to the SMF index file set in the component preferences does not exist');
			return null;
		}

		//set the current directory to SMF
		chdir($source_path);

		// Get the output
		ob_start();
		$rs = include_once($index_file);
		$buffer = ob_get_contents();
		ob_end_clean();

		//change the current directory back to Joomla.
		chdir(JPATH_SITE);

		// Log an error if we could not include the file
		if (!$rs) {
			JError::raiseWarning(500, 'Could not find SMF in the specified directory');
		}

		if ( preg_match('#<\?xml(.*?)\?>#si', $buffer) ){
        	die($buffer);
		}

		$document = JFactory::getDocument();
		$document->addScript(JURI::base().DS.'administrator'.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'smf'.DS.'js'.DS.'script.js');
		return $buffer;
	}

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		$regex_body		= array();
		$replace_body	= array();

		//convert relative links with query into absolute links
		$regex_body[]	= '#'.$integratedURL.'index.php\?(.*?)"#mSis';
		$replace_body[]	=  $baseURL . '&$1"';

		//convert relative links without query into absolute links
		$regex_body[]	= '#'.$integratedURL.'index.php"#mS';
		$replace_body[]	= $baseURL. '"';;

		//convert links to the same page with anchors
		$regex_body[]	= '#'.$integratedURL.'index.php\#(.*?)"#mSis';
		$replace_body[]	= $baseURL.'#$1"';

//	    $regex_body[]	= '#\#(.*?)#mSis';
//		$replace_body[]	= $fullURL.'#$1';

		//Jump Related fix
		$regex_body[]	= '#<select name="jumpto" id="jumpto".*?">(.*?)</select>#mSsie';
		$replace_body[]	= '$this->fixJump("$1")';

		$regex_body[] = '#<input (.*?) window.location.href = \'(.*?)\' \+ this.form.jumpto.options(.*?)>#mSsi';
		$replace_body[] = '<input $1 window.location.href = jf_scripturl + this.form.jumpto.options$3>';

		//todo: Fix quickreply ( Quote )
//		$regex_body[]	= '#<a (.*?) onclick="doQuote(.*?)>#mSsi';
//		$replace_body[]	= '<a $1>';

		$regex_body[]	= '#<a (.*?) onclick="doQuote(.*?)>#mSsi';
		$replace_body[]	= '<a $1 onclick="jfusion_doQuote$2>';

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

			//convert relative links into absolute links
			$regex_header[]	= '#(href|src)=("./|"/)(.*?)"#mS';
			$replace_header[]	= 'href="'.$integratedURL.'$3"';

			$regex_header[] = '#var smf_scripturl = ["|\'](.*?)["|\'];#mS';
			$replace_header[] = 'var smf_scripturl = "$1"; var jf_scripturl = "'.$baseURL.'";';
		}
		$buffer = preg_replace($regex_header, $replace_header, $buffer);
	}

	function fixJump($content)
	{
	  $find = '#<option value="[?](.*?)">(.*?)</option>#mSsi';
	  $replace = '<option value="&$1">$2</option>';

	  $content = preg_replace($find, $replace, $content);

	  return '<select name="jumpto" id="jumpto" onchange="if (this.selectedIndex > 0 && this.options[this.selectedIndex].value && this.options[this.selectedIndex].value.length) window.location.href = jf_scripturl + this.options[this.selectedIndex].value;">'.$content.'</select>';
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
		$columns->title = "p.subject";
		$columns->text = "p.body";
		return $columns;
	}

	function getSearchQuery()
	{
		//need to return threadid, postid, title, text, created, section
		$query = 'SELECT p.ID_TOPIC, p.ID_MSG, p.ID_BOARD, CASE WHEN p.subject = "" THEN CONCAT("Re: ",fp.subject) ELSE p.subject END AS title, p.body AS text,
					FROM_UNIXTIME(p.posterTime, "%Y-%m-%d %h:%i:%s") AS created,
					CONCAT_WS( "/", f.name, fp.subject ) AS section
					FROM #__messages AS p
					INNER JOIN #__topics AS t ON t.ID_TOPIC = p.ID_TOPIC
					INNER JOIN #__messages AS fp ON fp.ID_MSG = t.ID_FIRST_MSG
					INNER JOIN #__boards AS f on f.ID_BOARD = p.ID_BOARD';
		return $query;
	}

	function filterSearchResults(&$results)
	{

	}

	function getSearchResultLink($post)
	{
		$forum = JFusionFactory::getForum($this->getJname());
		return $forum->getPostURL($post->ID_TOPIC,$post->ID_MSG);
	}
}










