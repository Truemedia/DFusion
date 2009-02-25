<?php

/**
* @package JFusion_Moodle
* @version 1.1.0-b001
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');

/**
 * JFusion plugin class for Gallery2
 * @package JFusion_Gallery2
 */
class JFusionPublic_gallery2 extends JFusionPublic{

    function getJname(){
        return 'gallery2';
    }


	function getRegistrationURL()
	{
		return '?g2_view=core.UserAdmin&g2_subView=register.UserSelfRegistration';
	}

	function getLostPasswordURL()
	{
		return '?g2_view=core.UserAdmin&g2_subView=core.UserRecoverPassword';
	}
/*
	function getLostUsernameURL()
	{
		return '';
	}*/

	function & getBuffer($jPluginParam)
	{
		//Handle PHP based Gallery Rewrite
		$segments = JRequest::getVar('jFusion_Route');
		if(!empty($segments)) {
			$path_info = '/'.implode('/', unserialize($segments));
			$path_info = str_replace(':','-',$path_info);
			$_SERVER['PATH_INFO'] = $path_info;
		}
		
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(true);
		global $gallery;
		
		$album = $jPluginParam->get('album', -1);
		if($album != -1) {
			$gallery->setConfig('defaultAlbumId', $album);
			$gallery->setConfig('breadcrumbRootId', $album);
		}
		
		//Check displaying Sidebar
		GalleryCapabilities::set('showSidebarBlocks', ($jPluginParam->get("dispSideBar") == 1));
		
		// Start the Embed Handler
		ob_start();
		//$ret = $gallery->setActiveUser($userinfo);
		$g2data = GalleryEmbed::handleRequest();
		$output = ob_get_contents();
		ob_end_clean();
		// Handle File Output
		if ($output) {
			if (preg_match('%<h2>\s(?<head>.*)\s</h2>%', $output, $match1) &&
			preg_match('%<p class="giDescription">\s(?<desc>.*)\s</p>%', $output, $match2))
			{
				echo "<pre>";
				var_dump($match1);
				var_dump($match2);
				echo "</pre>";
				if(isset($match1["head"]) && isset($match2["desc"])) {
					JError::raiseError(500, $match1["head"], $match2["desc"]);
				} else {
					JError::raiseError(500, JText::_('Gallery2 Internal Error'));
				}
			} else {
				print $output;
				exit();
			}
		}
		
	    /* Register Sidebare for Module Usage */
		if(isset($g2data["sidebarBlocksHtml"])) {
	    	jFusion_g2BridgeCore::setVar("sidebar", $g2data["sidebarBlocksHtml"]);
		}
		
	    jFusion_g2BridgeCore::setPathway();
	    
		$buffer = "<html><head>".$g2data['headHtml']."</head><body>".
		$g2data['bodyHtml']."</body></html>";
		return $buffer;
	}

	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL) {}
	function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL) {}

	function getSearchResults(&$text, &$phrase, &$pluginParam, $linkMode, $itemid) 
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(true, $itemid);
		global $gallery;
		
		$params = JFusionFactory::getParams('gallery2');
		$source_url = $params->get('source_url');
		$urlGenerator = $gallery->getUrlGenerator();
		
		/* start preparing */
		$text = trim( $text );
		if ( $text == '' ) {
			return array();
		}
		
		//Limitation so prevent overheads -1 = unlimited
		$limit = -1;
		list(, $result['GalleryCoreSearch']) = GalleryEmbed::search($text, 'GalleryCoreSearch', 0, $limit);
		
		foreach ($result as $section => $resultArray) {
			if($resultArray['count'] == 0){
				continue;
			}
		
			foreach($resultArray['results'] as $array){
				$info = new stdClass();
				$info->href = $urlGenerator->generateUrl(array('view' => 'core.ShowItem', 'itemId' => $array['itemId']));
				list ($ret, $item) = GalleryCoreApi::loadEntitiesById($array['itemId']);
				$info->title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
				$info->title = preg_replace('/\r\n/', ' ', $info->title);
				$info->section = $section;

				$info->created = $item->getcreationTimestamp();

				$description = $item->getdescription();

				$info->text = empty($description) ? $item->getSummary() : $description;
				$info->browsernav = 2;

				$item->getparentId();
				if($item->getparentId() != 0){
					list ($ret, $parent) = GalleryCoreApi::loadEntitiesById($item->getparentId());
					$parent = $parent->getTitle() ? $parent->getTitle() : $parent->getPathComponent();
					$info->section = preg_replace('/\r\n/', ' ', $parent);
					if(strpos(strtolower($info->section), 'gallery') !== 0){
						$info->section = 'Gallery/'.$info->section;
					}
				}

				list(,$views) = GalleryCoreApi::fetchItemViewCount($array['itemId']);

				$return[] = $info;
				
			}
		}
		return $return;
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
		//get a unix time from 5 mintues ago
		date_default_timezone_set('UTC');
		$now = time();
		$active = strtotime("-5 minutes",$now);
		$query = "SELECT DISTINCT u.g_id AS userid, u.g_userName as username, u.g_fullName AS name  ".
		         "FROM #__User AS u INNER JOIN #__SessionMap AS s ON s.g_userId = u.g_id ". 
		         "WHERE s.g_modificationTimestamp > $active";
		return $query;
	}
	
	/**
	 * Returns number of members
	 * @return int
	 */
	function getNumberOnlineMembers()
	{
		//get a unix time from 5 mintues ago
		date_default_timezone_set('UTC');
		$now = time();
		$active = strtotime("-5 minutes",$now);
		
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__SessionMap s ".
		         "WHERE g_modificationTimestamp > $active AND s.g_userId != 5";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}
	
	/**
	 * Returns number of guests
	 * @return int
	 */
	function getNumberOnlineGuests()
	{
		//get a unix time from 5 mintues ago
		date_default_timezone_set('UTC');
		$now = time();
		$active = strtotime("-5 minutes",$now);
		
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT COUNT(*) FROM #__SessionMap s ".
		         "WHERE g_modificationTimestamp > $active AND s.g_userId = 5";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}
}
