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
		$source_url = $params->get('source_url');

		$doku_rel = preg_replace( '#(\w{0,10}://)(.*?)/(.*?)#is'  , '$3' , $source_url );
		$doku_rel = preg_replace('#//+#','/',"/$doku_rel/");
		define('DOKU_COOKIE', 'DW'.md5($doku_rel));

		if (substr($source_path, -1) == DS) {
			$index_file = $source_path .'doku.php';
			if ( JRequest::getVar('jfile') == 'detail.php' ) $index_file = $source_path.'lib'.DS.'exe'.DS.'detail.php';
//			if ( JRequest::getVar('jfile') == 'fetch.php' ) $index_file = $source_path.'lib'.DS.'exe'.DS.'fetch.php';
//			if ( JRequest::getVar('jfile') == 'feed.php' ) $index_file = $source_path .'feed.php';
		} else {
			$index_file = $source_path .DS.'doku.php';
			if ( JRequest::getVar('jfile') == 'detail.php' ) $index_file = $source_path.DS.'lib'.DS.'exe'.DS.'detail.php';
//			if ( JRequest::getVar('jfile') == 'fetch.php' ) $index_file = $source_path.DS.'lib'.DS.'exe'.DS.'fetch.php';
//			if ( JRequest::getVar('jfile') == 'feed.php' ) $index_file = $source_path .DS.'feed.php';
		}
		if ( JRequest::getVar('media') ) JRequest::setVar('media',str_replace(':', '-', JRequest::getVar('media')));
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
		//change the current directory back to Joomla. 5*60
		chdir(JPATH_SITE);

		$this->setPathWay();

		// Log an error if we could not include the file
		if (!$rs) {
			JError::raiseWarning(500, 'Could not find DokuWiki in the specified directory');
		}
		return $buffer;
	}

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_body, $replace_body;

		$share = Dokuwiki::getInstance();
		$conf = $share->getConf();

		$regex_body		= array();
		$replace_body	= array();

		$regex_body[]	= '#(href)=["|\']/feed.php["|\']#mS';
		$replace_body[]	= '$1="'.$integratedURL.'feed.php"';

		$regex_body[]	= '#href=["|\']/(lib/exe/fetch.php)(.*?)["|\']#mS';
		$replace_body[]	= 'href="'.$integratedURL.'$1$2"';
/*		$regex_body[]	= '#href=["|\']/(_media/)(.*?)["|\']#mS';
		$replace_body[]	= 'href="'.$integratedURL.'$1$2"';
*/
		$regex_body[] = '#href=["|\'](?!\w{0,10}://|\w{0,10}:|\#)/(.*?)["|\']#mSie';
		$replace_body[]	= '\'href="\'.$this->fixUrl("$1").\'"\'';

//		$regex_body[] = '#href=["|\'][./|/](.*?)["|\']#mSie';
//		$replace_body[]	= 'href="'.$integratedURL.'$1"';

		$this->replaceForm($buffer);

		$regex_body[] = '#(src)=["|\'][./|/](.*?)["|\']#mS';
		$replace_body[]	= '$1="'.$integratedURL.'$2"';

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

	function fixUrl($q='')
	{
		$q = urldecode($q);
		$q = str_replace(':', ';', $q);
		if ( strpos($q,'_detail/') === 0 || strpos($q,'lib/exe/detail.php') === 0 ) {
			if( strpos($q,'_detail/') === 0 ) {
				$q = substr($q,strlen('_detail/'));
			} else {
				$q = substr($q,strlen('lib/exe/detail.php'));
			}

			if ( strpos($q,'?') === 0 ) {
				$url = JFusionFunction::routeURL('detail.php'.$q, JRequest::getVar('Itemid'));
			} else {
				$this->trimUrl($q);
				$url = JFusionFunction::routeURL('detail.php?media='.$q, JRequest::getVar('Itemid'));
			}
		} else if ( strpos($q,'_media/') === 0 || strpos($q,'lib/exe/fetch.php') === 0 ) {
			if( strpos($q,'_media/') === 0 ) {
				$q = substr($q,strlen('_media/'));
			} else {
				$q = substr($q,strlen('lib/exe/fetch.php'));
			}

			if ( strpos($q,'?') === 0 ) {
				$url = JFusionFunction::routeURL('fetch.php'.$q, JRequest::getVar('Itemid'));
			} else {
				$this->trimUrl($q);
				$url = JFusionFunction::routeURL('fetch.php?media='.$q, JRequest::getVar('Itemid'));
			}
		} else if ( strpos($q,'doku.php') === 0 ) {
			$q = substr($q,strlen('doku.php'));
			if ( strpos($q,'?') === 0 ) {
				$url = JFusionFunction::routeURL('doku.php'.$q, JRequest::getVar('Itemid'));
			} else {
				$this->trimUrl($q);
				if ( strlen($q) ) $url = JFusionFunction::routeURL('doku.php?id='.$q, JRequest::getVar('Itemid'));
				else $url = JFusionFunction::routeURL('doku.php', JRequest::getVar('Itemid'));
			}
		} else {
			$this->trimUrl($q);
			if ( strlen($q) ) $url = JFusionFunction::routeURL('doku.php?id='.$q, JRequest::getVar('Itemid'));
			else $url = JFusionFunction::routeURL('', JRequest::getVar('Itemid'));
		}
		if ( $url ) return substr(JURI::base(),0,-1).$url;
		return $q;
	}

	function trimUrl( &$url ) {
		$url = ltrim( $url , '/' );
		$order   = array('/','?');
		$replace = array(';','&');
		$url = str_replace($order, $replace, $url);
	}

	function replaceForm( &$data ) {
		$pattern = '#<form(.*?)action=["|\']/(.*?)["|\'](.*?)>(.*?)</form>#mSsi';
		$getData = '';
		if (JRequest::getVar('Itemid')) $getData .= '<input name="Itemid" value="'.JRequest::getVar('Itemid').'" type="hidden">';
		if (JRequest::getVar('option')) $getData .= '<input name="option" value="'.JRequest::getVar('option').'" type="hidden">';
		if (JRequest::getVar('jname')) $getData .= '<input name="jname" value="'.JRequest::getVar('jname').'" type="hidden">';
		if (JRequest::getVar('view')) $getData .= '<input name="view" value="'.JRequest::getVar('view').'" type="hidden">';

		preg_match_all($pattern, $data, $links);

		foreach ( $links[2] as $key => $value ) {
			$method = '#method=["|\']post["|\']#mS';
			$is_get = true;
			if ( preg_match($method,$links[1][$key]) || preg_match($method,$links[3][$key]) ) $is_get = false;
			$value = $this->fixUrl($links[2][$key]);

			if( $is_get && substr($value, -1) != DS ) $links[4][$key] = $getData.$links[4][$key];
			$data = str_replace($links[0][$key], '<form'.$links[1][$key].'action="'.$value.'"'.$links[3][$key].'>'.$links[4][$key].'</form>', $data);
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

	function setPathWay()
	{
		$mainframe = &JFactory::getApplication('site');
		$breadcrumbs = & $mainframe->getPathWay();

		if ( JRequest::getVar('id') ) {
			$bread = explode(';', JRequest::getVar('id'));
			$url = '';
			$breads = array();
			$i=0;
			foreach($bread as $key ) {
				if ($url) $url .= ';'.$key;
				else $url = $key;

				$breads[$i]['text'] = $key;
				$breads[$i]['url'] = $url;
				$i++;
			}

			if ( JRequest::getVar('media') || JRequest::getVar('do') ) {
				if ( JRequest::getVar('media') ) $add = JRequest::getVar('media');
				else  $add = JRequest::getVar('do');
				$breads[count($breads)-1]['text'] = $breads[count($breads)-1]['text']. ' ( '.$add.' )';
			}
			foreach($breads as $key ) $breadcrumbs->addItem($key['text'], substr(JURI::base(),0,-1).JFusionFunction::routeURL('doku.php?id='.$key['url'], JRequest::getVar('Itemid')));
		}
	}
}