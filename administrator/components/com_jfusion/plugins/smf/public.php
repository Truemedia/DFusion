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
		if (JRequest::getVar('action')=='register') {
			$master = JFusionFunction::getMaster();
			if( $master->name != $this->getJname() ) {
				$JFusionMaster = JFusionFactory::getPublic($master->name);
				$params = JFusionFactory::getParams($master->name);
				$source_url = $params->get('source_url');
				$source_url = rtrim ( $source_url , '/' );
				var_dump($JFusionMaster->getRegistrationURL());
				header('Location: '.$source_url.JRoute::_($JFusionMaster->getRegistrationURL()));
				die();
			}
		}
		// We're going to want a few globals... these are all set later.
		global $time_start, $maintenance, $msubject, $mmessage, $mbname, $language;
		global $boardurl, $boarddir, $sourcedir, $webmaster_email, $cookiename;
		global $db_connection, $db_server, $db_name, $db_user, $db_prefix, $db_persist, $db_error_send, $db_last_error;
		global $modSettings, $context, $sc, $user_info, $topic, $board, $txt;
		global $scripturl;

		// Required to avoid a warning about a license violation even though this is not the case
		global $forum_version;

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
		$document = JFactory::getDocument();
		$document->addScript(JFusionFunction::getJoomlaURL().DS.'administrator'.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'smf'.DS.'js'.DS.'script.js');
		return $buffer;
	}

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		$regex_body		= array();
		$replace_body	= array();

		$regex_body[]	= '#"'.$integratedURL.'index.php(.*?)"#Sise';
		$replace_body[] = '\'"\'.$this->fixUrl("index.php$1","'.$baseURL.'").\'"\'';

		//Jump Related fix
		$regex_body[]	= '#<select name="jumpto" id="jumpto".*?">(.*?)</select>#mSsie';
		$replace_body[]	= '$this->fixJump("$1")';

		$regex_body[] = '#<input (.*?) window.location.href = \'(.*?)\' \+ this.form.jumpto.options(.*?)>#mSsi';
		$replace_body[] = '<input $1 window.location.href = jf_scripturl + this.form.jumpto.options$3>';

		if ( substr( $baseURL,-1 ) == '/' ) {
			$regex_body[] = '#href="(.*?)action=logout(.*?)"#mSsi';
			$replace_body[] = 'href="$1action=logout$2&option=com_jfusion&Itemid='.JRequest::getVar('Itemid').'"';
		}

		//fix for form actions
		$regex_body[]	= '#action="(.*?)"(.*?)>#me';
		$replace_body[]	= '$this->fixAction("$1","$2","' . $baseURL .'")';

		$regex_body[]	= '#<a (.*?) onclick="doQuote(.*?)>#mSsi';
		$replace_body[]	= '<a $1 onclick="jfusion_doQuote$2>';

		$buffer = preg_replace($regex_body, $replace_body, $buffer);
	}

	function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_header, $replace_header;

		if ( ! $regex_header || ! $replace_header )
		{
			$params = JFusionFactory::getParams('joomla_int');
			$joomla_url = $params->get('source_url');

			$baseURLnoSef = 'index.php?option=com_jfusion&Itemid=' . JRequest::getVar('Itemid');
			if (substr($joomla_url, -1) == '/') $baseURLnoSef = $joomla_url . $baseURLnoSef;
			else $baseURLnoSef = $joomla_url . '/' . $baseURLnoSef;

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
			$replace_header[] = 'var smf_scripturl = "$1"; var jf_scripturl = "'.$baseURLnoSef.'";';
		}
		$buffer = preg_replace($regex_header, $replace_header, $buffer);
	}

	function fixUrl($q='',$baseURL)
	{
        //SMF uses semi-colons to seperate vars as well. Convert these to normal ampersands
        $q = str_replace(';','&',$q);
        if (substr($baseURL, -1) != '/'){
			//non sef URls
			$q = str_replace('?', 	'&amp;', $q);
			$url = $baseURL . '&amp;jfile=' .$q;
        } else {
			$params = JFusionFactory::getParams($this->getJname());
			$sefmode = $params->get('sefmode');
			if ($sefmode==1) {
				$url =  JFusionFunction::routeURL($q, JRequest::getVar('Itemid'));
			} else {
				//we can just append both variables
				$url = $baseURL . $q;
			}
		}
		return $url;
	}

	function fixUrl($q='',$baseURL)
	{
        //SMF uses semi-colons to seperate vars as well. Convert these to normal ampersands
        $q = str_replace(';','&',$q);
        if (substr($baseURL, -1) != '/'){
			//non sef URls
			$q = str_replace('?', 	'&amp;', $q);
			$url = $baseURL . '&amp;jfile=' .$q;
        } else {
			$params = JFusionFactory::getParams($this->getJname());
			$sefmode = $params->get('sefmode');
			if ($sefmode==1) {
				$url =  JFusionFunction::routeURL($q, JRequest::getVar('Itemid'));
			} else {
				//we can just append both variables
				$url = $baseURL . $q;
			}
		}
		return $url;
	}

	function fixAction($url, $extra, $baseURL)
	{
		//JError::raiseWarning(500, $url);
		$url = htmlspecialchars_decode($url);
		$Itemid = JRequest::getVar('Itemid');

		if (substr($baseURL, -1) != '/'){
			//non-SEF mode
		  	$url_details = parse_url($url);
		  	$url_variables = array();
			parse_str($url_details['query'], $url_variables);
		 	$jfile = basename($url_details['path']);
		  	//set the correct action and close the form tag
			$replacement = 'action="'.$baseURL . '"' . $extra . '>';
			$replacement .= '<input type="hidden" name="jfile" value="'. $jfile . '">';
			$replacement .= '<input type="hidden" name="Itemid" value="'.$Itemid . '">';
		  	$replacement .= '<input type="hidden" name="option" value="com_jfusion">';
		} else {
			//check to see what SEF mode is selected
		    $params = JFusionFactory::getParams($this->getJname());
		    $sefmode = $params->get('sefmode');
		    if ($sefmode==1) {
		    	//extensive SEF parsing was selected
			  	$url =  JFusionFunction::routeURL($url, $Itemid);
				$replacement = 'action="'.$url . '"' . $extra . '>';
				return $replacement;
			} else {
				//simple SEF mode
		      	$url_details = parse_url($url);
			  	$url_variables = array();
				parse_str($url_details['query'], $url_variables);
		 		$jfile = basename($url_details['path']);
				$replacement = 'action="'.$baseURL . $jfile.'"' . $extra . '>';
			}
		}
		unset($url_variables['option'],$url_variables['jfile'],$url_variables['Itemid']);

		//add any other variables
		if(is_array($url_variables)){
	 		foreach ($url_variables as $key => $value){
				$replacement .=  '<input type="hidden" name="'. $key .'" value="'.$value . '">';
  			}
		}
  		return $replacement;
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

   /************************************************
	 * Functions For JFusion Who's Online Module
	 ***********************************************/

	/**
	 * Returns a query to find online users
	 * Make sure the columns are in this order: userid, username, name (of user)
	 */
	function getOnlineUserQuery()
	{
		return "SELECT DISTINCT u.ID_MEMBER, u.memberName AS username, u.realName AS name FROM #__members AS u INNER JOIN #__log_online AS s ON u.ID_MEMBER = s.ID_MEMBER WHERE s.ID_MEMBER != 0";
	}

	/**
	 * Returns number of guests
	 * @return int
	 */
	function getNumberOnlineGuests()
	{
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__log_online WHERE ID_MEMBER = 0";
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
		$query = "SELECT COUNT(*) FROM #__log_online WHERE ID_MEMBER != 0";
		$db->setQuery($query);
		return $db->loadResult();
	}
}