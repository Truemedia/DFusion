<?php
/**
* @package JFusion_DOKUWIKI
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

require_once( dirname(__FILE__).'/dokuwiki.php');

/**
 * JFusion Public Class for DOKUWIKI 1.1.x
 * For detailed descriptions on these functions please check the model.abstractpublic.php
 * @package JFusion_DOKUWIKI
 */
class JFusionPublic_dokuwiki extends JFusionPublic {

	function getJname()
	{
		return 'dokuwiki';
	}

	function getRegistrationURL()
	{
		return 'doku.php?do=login';
	}

	function getLostPasswordURL()
	{
		return 'doku.php?do=resendpwd';
	}

/*  function getLostUsernameURL()
	{
		return 'index.php?action=reminder';
	}*/

	function & getBuffer()
	{
		/*
		$do = JRequest::getVar('do');
		$page = JRequest::getVar('page');

		if ( $do['save'] == 'Save' ||
			$do == 'edit' ||
			( $do == 'admin' && $page == 'config' ) ) $needCurl = true;

//		if($needCurl) $buffer = $this->getBufferCurl();
//		else $buffer = $this-> getBufferInclude();*/
 		$buffer = $this-> getBufferInclude();
		return $buffer;
	}

	function & getBufferInclude()
	{
		$joomla_globals = $GLOBALS;
		// We're going to want a few globals... these are all set later.

		global $INFO,$ACT,$ID,$QUERY,$USERNAME,$CLEAR,$QUIET,$USERINFO,$DOKU_PLUGINS,$PARSER_MODES,$TOC,$EVENT_HANDLER,$AUTH,$IMG,$JUMPTO;
		global $HTTP_RAW_POST_DATA,$RANGE,$HIGH,$MSG,$DATE,$PRE,$TEXT,$SUF,$AUTH_ACL,$QUIET,$SUM,$SRC,$IMG,$NS,$IDX,$REV,$INUSE,$NS,$AUTH_ACL;
		global $UTF8_UPPER_TO_LOWER,$UTF8_LOWER_TO_UPPER,$UTF8_LOWER_ACCENTS,$UTF8_UPPER_ACCENTS,$UTF8_ROMANIZATION,$UTF8_SPECIAL_CHARS,$UTF8_SPECIAL_CHARS2;

		global $auth,$plugin_protected,$plugin_types,$conf,$lang,$argv;
		global $cache_revinfo,$cache_wikifn,$cache_cleanid,$cache_authname,$cache_metadata,$tpl_configloaded;
		global $db_host,$db_name,$db_username,$db_password,$db_prefix,$pun_user,$pun_config;

		// Get the path
		$params = JFusionFactory::getParams($this->getJname());
		$source_path = $params->get('source_path');

		$user		 = $params->get('database_user');
		$host		 = $params->get('database_host');
		$database	= $params->get('database_name');

		if (substr($source_path, -1) == DS) {
			$index_file = $source_path .'doku.php';
			if ( JRequest::getVar('jfile') == 'detail.php' ) $index_file = $source_path.'lib'.DS.'exe'.DS.'detail.php';
		} else {
			$index_file = $source_path .DS.'doku.php';
			if ( JRequest::getVar('jfile') == 'detail.php' ) $index_file = $source_path.DS.'lib'.DS.'exe'.DS.'detail.php';
		}

		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'hooks.php');

		if ( ! is_file($index_file) ) {
			JError::raiseWarning(500, 'The path to the DokuWiki index file set in the component preferences does not exist');
			return null;
		}

		//set the current directory to dokuwiki
		chdir($source_path);

		// Get the output
		ob_start();
        define('UTF8_STRLEN', true);
		define('UTF8_CORE', true);
		define('UTF8_CASE', true);
		$rs = include_once($index_file);
		$buffer = ob_get_contents();
		ob_end_clean();

		//change the current directory back to Joomla.
		chdir(JPATH_SITE);


		// Log an error if we could not include the file
		if (!$rs) {
			JError::raiseWarning(500, 'Could not find DokuWiki in the specified directory');
		}

		//restore the joomla globals like nothing happened
		$GLOBALS = $joomla_globals;
		//check to see if the Joomla database is still connnected
		jimport('joomla.database.database');
		jimport( 'joomla.database.table' );
		$db = & JFactory::getDBO();
		$conf =& JFactory::getConfig();

		$j_host		 = $conf->getValue('config.host');
		$j_user		 = $conf->getValue('config.user');
		$j_password	 = $conf->getValue('config.password');
		$j_database		= $conf->getValue('config.db');

		if ( $user == $j_user || $database != $j_database || $host != $j_host ) {
			if (!($db->_resource = @mysql_connect( $j_host, $j_user, $j_password, true ))) {
			  $db->_errorNum = 2;
			  $db->_errorMsg = 'Could not connect to MySQL';
			  die ('could not reconnect to the Joomla database');
			}
			// select the database
			$db->select($j_database);
		}
		return $buffer;
	}

/*	function & getBufferCurl()
	{
		$joomla_globals = $GLOBALS;

		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'hooks.php');
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'helper.php');
		// Get the path
		$params = JFusionFactory::getParams($this->getJname());
		$share = Dokuwiki::getInstance();
		$user = & JFactory::getUser();
		$conf = $share->getConf();

		$source_path = $params->get('source_path');
		if (substr($source_path, -1) == DS) {
			$index_file = $source_path .'doku.php';
		} else {
			$index_file = $source_path .DS.'doku.php';
		}
		if ( !is_file($index_file) ) {
			JError::raiseWarning(500, 'The path to the doku index file set in the component preferences does not exist');
			return null;
		}

		$source_url = $params->get('source_url');
		if (substr($source_url, -1) == DS) {
			$url = $source_url .'doku.php';
			if ( JRequest::getVar('file') == 'detail.php' ) $url = $source_url.'lib/exe/detail.php';
		} else {
			$url = $source_url .DS.'doku.php';
			if ( JRequest::getVar('file') == 'detail.php' ) $url = $source_url.DS.'lib/exe/detail.php';
		}

		if ( $conf['userewrite'] == 2 ) {
			$home = explode( '/' , $_SERVER['PATH_INFO']);
			$home = $home[count($home)-1];
			$url = $url.'/'.$home;
		}

		if ( stristr(JRequest::getVar('do'), 'login') ) header('Location: '.JURI::base().'index.php?option=com_user&view=login');
		$buffer = JFusionCurlHelper::getBuffer($this->getJname(),$url,$_GET,$_POST);

		// Log an error if we could not include the file
		if (!$buffer) {
			JError::raiseWarning(500, 'Could not access dokuwiki in the specified directory');
		}
		//restore the joomla globals like nothing happened
		$GLOBALS = $joomla_globals;

		//check to see if the Joomla database is still connnected
		$db = & JFactory::getDBO();
		if (!is_resource($db->_resource)) {
			//joomla connection needs to be re-established
			jimport('joomla.database.database');
			jimport( 'joomla.database.table' );
			$conf =& JFactory::getConfig();

			$host		 = $conf->getValue('config.host');
			$user		 = $conf->getValue('config.user');
			$password	 = $conf->getValue('config.password');
			$database	= $conf->getValue('config.db');
			$prefix	 = $conf->getValue('config.dbprefix');

			// connect to the server
			if (!($db->_resource = @mysql_connect( $host, $user, $password, true ))) {
				$db->_errorNum = 2;
				$db->_errorMsg = 'Could not connect to MySQL';
				die ('could not reconnect to the Joomla database');
			}
			// select the database
			$db->select($database);
		}
		return $buffer;
	}*/

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_body, $replace_body;

		$share = Dokuwiki::getInstance();
		$conf = $share->getConf();

		$regex_body		= array();
		$replace_body	= array();

		$url_r = array( 'feed.php', 'fetch.php');
		$this->replaceUrl($buffer,'doku.php',$url_r,$conf['userewrite'] );

		$this->replaceForm($buffer,'doku.php',null,$conf['userewrite']);

		$regex_body[] = '#(src)=["|\'][./|/](.*?)["|\']#mS';
		$replace_body[]	= '$1="'.$integratedURL.'$2"';

		$regex_body[]	= '#(href)=["|\']/feed.php["|\']#mS';
		$replace_body[]	= '$1="'.$integratedURL.'feed.php"';

		$regex_body[]	= '#href=["|\']/(lib/exe/fetch.php)(.*?)["|\']#mS';
		$replace_body[]	= 'href="'.$integratedURL.'$1$2"';
		$regex_body[]	= '#href=["|\']/(_media/)(.*?)["|\']#mS';
		$replace_body[]	= 'href="'.$integratedURL.'$1$2"';

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
			$regex_header[]	= '#(href|src)=["|\'][./|/](.*?)["|\']#mS';
			$replace_header[] = 'href="'.$integratedURL.'$2"';
		}
		$buffer = preg_replace($regex_header, $replace_header, $buffer);
	}

	function replaceUrl( &$data , $default='index.php' , $url_r=array(), $sef=false ) {
		if(!$data || ( !is_array($url_r) && $url_r ) ) return false;
		if (!$url_r) $url_r=array();
		$pattern = '#<a(.*?)href=["|\'](.*?)["|\'](.*?)>#mSsi';

		preg_match_all($pattern, $data, $links,$file);
		$count = 0;
		foreach ( $links[2] as $key => $value ) {
			if ( strstr( substr($value, 0, 3) , '/' ) ) {
				$path_parts = pathinfo($value);

				$uri = new JURI();

				$addlink = false;
				unset( $id,$media,$other,$file);

				switch ($sef) {
				    case 0:
						if ( strpos($path_parts['basename'],'?') === false ) $id = $path_parts['basename'];
						else list( $file , $other ) = explode( '?' , $path_parts['basename'] );

						if ( array_search($file, $url_r) === false ) {
							$addlink = true;
							if ( strpos($file ,$default) === false) $uri->setVar('jfile', $file);
							parse_str( $other , $arg );
						}
				        break;
				    case 1:
				    	if ( strpos($path_parts['dirname'],'_media') !== false ) break;
						if ( strpos($path_parts['dirname'],'_detail') !== false ) {
							$addlink = true;
							$uri->setVar('jfile', 'detail.php');

							list( $media , $id ) = explode( '?' , $path_parts['basename'] );
							parse_str( $id , $arg );
							$uri->setVar('media', $media);
						} else {
							if ( strpos($path_parts['basename'],'?') === false ) $id = $path_parts['basename'];
							else list( $id , $other ) = explode( '?' , $path_parts['basename'] );

							if ( array_search($id, $url_r) === false ) {
								$addlink = true;
								parse_str( $other , $arg );
								$arg['id'] = $id;
							}
						}

				        break;
				    case 2:
						$start = strpos($value,'.php')+4;
						$file = substr($value, 0, $start ); // returns "d"
						$file = explode('/' , $file );
						$file = $file[count($file)-1];
						if ( array_search($file, $url_r) === false ) {
							$addlink = true;
							$start = substr($value, $start+1 ); // returns "d"
							if ( $file == 'detail.php' ) {
								$uri->setVar('jfile', 'detail.php');
								list( $media , $id ) = explode( '?' , $start );
								parse_str( $id , $arg );
								$uri->setVar('media', $media);
							} else {
								if ( strpos($start,'?') === false )	$id = $start;
								else list( $id , $other ) = explode( '?' , $start );
								parse_str( $other , $arg );
								$arg['id'] = $id;
								if ( strpos($file ,$default) === false) $uri->setVar('jfile', $file);
							}
						}
				        break;
				}

				if ( $addlink ) {
					$count++;
					foreach ( $arg as $arg_key => $arg_value ) {
						if ($arg_value) {
							if ( strpos($arg_value,'#') === false ) {
								$uri->setVar($arg_key, $arg_value);
							} else {
								list( $v , $f ) = explode( '#' , $arg_value );
								$uri->setVar($arg_key, $v);
								$uri->setFragment($f);
							}
						}
					}

					$jfile = $uri->getVar('jfile');
					if(empty($jfile)){
						$uri->setVar('jfile', 'doku.php');
					}
					$value = JRoute::_('index.php?'.$uri->getQuery());
					$data = str_replace($links[0][$key], '<a'.$links[1][$key].'href="'.$value.'"'.$links[3][$key].'>', $data);
				 }
			}
		}
		return $count;
	}

	function replaceForm( &$data , $default='index.php', $url_r=array(), $sef=false ) {
		if(!$data || ( !is_array($url_r) && $url_r ) ) return false;
		if (!$url_r) $url_r=array();
		$pattern = '#<form(.*?)action=["|\'](.*?)["|\'](.*?)>(.*?)</form>#mSsi';
		$getData = '';
		if ( !$sef ) {
			if (JRequest::getVar('Itemid')) $getData .= '<input name="Itemid" value="'.JRequest::getVar('Itemid').'" type="hidden">';
			if (JRequest::getVar('option')) $getData .= '<input name="option" value="'.JRequest::getVar('option').'" type="hidden">';
			if (JRequest::getVar('jname')) $getData .= '<input name="jname" value="'.JRequest::getVar('jname').'" type="hidden">';
			if (JRequest::getVar('view')) $getData .= '<input name="view" value="'.JRequest::getVar('view').'" type="hidden">';
		}

		preg_match_all($pattern, $data, $links);
		$count = 0;
		foreach ( $links[2] as $key => $value ) {
			$method = '#method=["|\']post["|\']#mS';
			$is_get = true;
			if ( strstr( substr($value, 0, 3) , '/' ) ) {
				if ( preg_match($method,$links[1][$key]) || preg_match($method,$links[3][$key]) ) $is_get = false;
				$path_parts = pathinfo($value);

				$uri = new JURI();

				$addlink = false;
				unset( $id,$media,$other,$file);

				switch ($sef) {
				    case 0:
						if ( strpos($path_parts['basename'],'?') === false ) $id = $path_parts['basename'];
						else list( $file , $other ) = explode( '?' , $path_parts['basename'] );

						if ( array_search($file, $url_r) === false ) {
							$addlink = true;
							if ( strpos($file ,$default) === false) $uri->setVar('jfile', $file);
							parse_str( $other , $arg );
						}
				        break;
				    case 1:
				    	if ( strpos($path_parts['dirname'],'_media') !== false ) break;
						if ( strpos($path_parts['dirname'],'_detail') !== false ) {
							$addlink = true;
							$uri->setVar('jfile', 'detail.php');

							list( $media , $id ) = explode( '?' , $path_parts['basename'] );
							parse_str( $id , $arg );
							$uri->setVar('media', $media);
						} else {
							if ( strpos($path_parts['basename'],'?') === false ) $id = $path_parts['basename'];
							else list( $id , $other ) = explode( '?' , $path_parts['basename'] );

							if ( array_search($id, $url_r) === false ) {
								$addlink = true;
								parse_str( $other , $arg );
								$arg['id'] = $id;
							}
						}

				        break;
				    case 2:
						$start = strpos($value,'.php')+4;
						$file = substr($value, 0, $start ); // returns "d"
						$file = explode('/' , $file );
						$file = $file[count($file)-1];
						if ( array_search($file, $url_r) === false ) {
							$addlink = true;
							$start = substr($value, $start+1 ); // returns "d"
							if ( $file == 'detail.php' ) {
								$uri->setVar('jfile', 'detail.php');
								list( $media , $id ) = explode( '?' , $start );
								parse_str( $id , $arg );
								$uri->setVar('media', $media);
							} else {
								if ( strpos($start,'?') === false )	$id = $start;
								else list( $id , $other ) = explode( '?' , $start );
								parse_str( $other , $arg );
								$arg['id'] = $id;
								if ( strpos($file ,$default) === false) $uri->setVar('jfile', $file);
							}
						}
				        break;
				}

				if ( $addlink ) {
					$count++;
					foreach ( $arg as $arg_key => $arg_value ) {
						if ($arg_value) {
							if ( strpos($arg_value,'#') === false ) {
								$uri->setVar($arg_key, $arg_value);
							} else {
								list( $v , $f ) = explode( '#' , $arg_value );
								$uri->setVar($arg_key, $v);
								$uri->setFragment($f);
							}
						}
					}

					$value = JRoute::_('index.php?'.$uri->getQuery());
					if( $is_get && substr($value, -1) != DS ) $links[4][$key] = $getData.$links[4][$key];
					$data = str_replace($links[0][$key], '<form'.$links[1][$key].'action="'.$value.'"'.$links[3][$key].'>'.$links[4][$key].'</form>', $data);
				 }
			}
		}
		return $count;
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

    /**
     * Retrieves the search results to be displayed.  Placed here so that plugins that do not use the database can retrieve and return results
     * @param $text string text to be searched
     * @param $phrase string how the search should be performed exact, all, or any
     * @param $pluginParam custom plugin parameters in search.xml
     * @param $linkMode what mode to use when creating the URL
     * @param $itemid what menu item to use when creating the URL
     * @return array of results as objects
     * Each result should include:
     * $result->title = title of the post/article
     * $result->section = (optional) section of  the post/article (shows underneath the title; example is Forum Name / Thread Name)
     * $result->text = text body of the post/article
     * $result->href = link to the content (without this, joomla will not display a title)
     * $result->browsernav = 1 opens link in a new window, 2 opens in the same window
     * $result->created = (optional) date when the content was created
     */
	function getSearchResults(&$text, &$phrase, &$pluginParam, $linkMode, $itemid)
	{
		global $rootFolder;

		$params = JFusionFactory::getParams($this->getJname());
		$rootFolder = $params->get('source_path');

		if (substr($rootFolder, -1) == DS) {
			define(DOKU_INC, $rootFolder);
		} else {
			define(DOKU_INC, $rootFolder.'/');
		}


		require_once('doku_search.php');

//		require_once(DOKU_INC.'inc'.DS.'fulltext.php');
//		require_once(DOKU_INC.'inc'.DS.'common.php');



		$results = ft_pageSearch($text, &$highlights);

		//pass results back to the plugin in case they need to be filtered
		$this->filterSearchResults($results);

	 	$rows = array();
	 	$pos = 0;
		foreach($results as $key => $index)
		{
			$rows[$pos]->title = JText::_( $key );
			$rows[$pos]->text = $this->getPage($rootFolder,$key);

			$rows[$pos]->href  = JFusionFunction::createURL($this->getSearchResultLink($key), $this->getJname(), $linkMode,$itemid);
			$rows[$pos]->section = JText::_( $key );
			$pos++;
	   	}
		return $rows;
	}

	function getPage($path,$page)
	{
		$file = $path.DS.'data'.DS.'pages'.DS.str_replace(":", DS, $page).'.txt';
		$text = '';
		if (file_exists($file)) {
			$handle = fopen($file, "r");
   			while (!feof($handle)) {
       			$text .= fgets($handle, 4096);
   			}
		    fclose($handle);
		}
		return $text?$text:"Please, follow the given link to get the DokuWiki article where we found one or more keyword(s).";"Please, follow the given link to get the DokuWiki article where we found one or more keyword(s).";
	}

	function filterSearchResults(&$results)
	{

	}

	function getSearchResultLink($post)
	{
		return "doku.php?id=" . $post;
	}
}














