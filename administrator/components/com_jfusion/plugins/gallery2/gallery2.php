<?php

/**
 * @package JFusion_Gallery2
 * @version 1.0.0
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion plugin class for Gallery2
 * @package JFusion_Gallery2
 */
class jFusion_g2BridgeCore {
	static $loadedGallery = false;
	static $registry = array();

	function loadGallery2Api($fullInit, $itemId = NULL) {
		if(self::$loadedGallery) {
			return true;
		}
		$params = JFusionFactory::getParams('gallery2');
		$source_url = $params->get('source_url');
		$source_path = $params->get('source_path');
		if (substr($source_path, -1) == DS) {
			$index_file = $source_path .'embed.php';
		} else {
			$index_file = $source_path .DS.'embed.php';
		}

		if(substr($source_url, 0, 1) == '/') {
			$uri =& JURI::getInstance();
			$base = $uri->toString( array('scheme', 'host', 'port'));
			$source_url = $base . $source_url;
		}

		$initParams["g2Uri"] = $source_url;
		$initParams["embedUri"] = jFusion_g2BridgeCore::getEmbedUri($itemId);
		$initParams["loginRedirect"] = JRoute::_("index.php?option=com_user&view=login");
		$initParams["fullInit"] = $fullInit;

		if (!is_file($index_file)) {
			JError::raiseWarning(500, 'The path to the Gallery2 embed file set in the component preferences does not exist');
			return false;
		}

		require_once($index_file);
		$ret = GalleryEmbed::init($initParams);
		if($ret)
		{
			JError::raiseWarning(500, 'Error while initialising Gallery2 API');
			return false;
		}

		$ret = GalleryCoreApi::setPluginParameter('module', 'core', 'cookie.path', '/');
		if ($ret) {
			JError::raiseWarning(500, 'Error while setting cookie path');
			return false;
		}
		
		if($fullInit) {
			$user = &JFactory::getUser();
			if($user->id != 0) {
				$userPlugin = JFusionFactory::getUser('gallery2');
				$g2_user = $userPlugin->getUser($user->username);
				$userPlugin->createSession($g2_user, NULL, false);
			} else {
				GalleryEmbed::logout();
			}
		}
		
		self::$loadedGallery = true;
		return true;
	}

	function getEmbedUri( $itemId = NULL )
	{
		global $mainframe;
		$router = $mainframe->getRouter();
		$id = JRequest::getVar( 'Itemid', -1 );
		if($itemId !== NULL) {
			$id = $itemId;
		}
		//Create Gallery Embed Path
		$path = 'index.php?option=com_jfusion';
		if($id > 0) {
			$path .= '&Itemid='.$id;
		} else {
			$path .= '&view=frameless&jname=gallery2';
		}
		$uri = JRoute::_($path);

		$uri = JRoute::_($path);
		if($router->getMode() == JROUTER_MODE_SEF) {
			if($mainframe->getCfg('sef_suffix')) {
				$uri = str_replace(".html","",$uri);
			}
			if(!strpos($uri,"?")) {
				$uri .= "/";
			}
		}
		return $uri;
	}

	static function setVar($key, $value) {
		self::$registry[$key] = $value;
	}

	static function getVar($key, $default=null)
	{
		if (isset(self::$registry[$key])) {
			return self::$registry[$key];
		}
		return $default;
	}

	function setPathway(){
		global $mainframe, $gallery;

		$urlGenerator = $gallery->getUrlGenerator();

		$itemId = (int) GalleryUtilities::getRequestVariables('itemId');
		$userId = $gallery->getActiveUserId();

		/* fetch parent sequence for current itemId or Root */
		if ($itemId) {
			list ($ret, $parentSequence) = GalleryCoreApi::fetchParentSequence($itemId);
			if ($ret) {
				return $ret;
			}
		} else {
			list ($ret, $rootId) = GalleryCoreApi::getPluginParameter('module', 'core', 'id.rootAlbum');
			if ($ret) {
				return $ret;
			}
			$parentSequence = array($rootId);
		}

		/* Add current item at the end */
		$parentSequence[] = $itemId;
		/* shift first parent off, as Joomla adds menu name already.*/
		array_shift($parentSequence);

		/* study permissions */
		if(sizeof($parentSequence) > 0 && $parentSequence[0] != 0){
			$ret = GalleryCoreApi::studyPermissions($parentSequence);
			if ($ret) {
				return $ret;
			}
				
			/* load the Entities */
			list ($ret, $list) = GalleryCoreApi::loadEntitiesById($parentSequence);
			if ($ret) {
				return $ret;
			}
			foreach ($list as $it) {
				$entities[$it->getId()] = $it;
			}
		}

		$breadcrumbs = & $mainframe->getPathWay();
		$document = & JFactory::getDocument();

		/* check permissions and push */
		$i = 1;
		$limit = count($parentSequence);
		foreach ($parentSequence as $id) {
			list ($ret, $canSee) =
			GalleryCoreApi::hasItemPermission($id, 'core.view', $userId);
			if ($ret) {
				return $ret;
			}
			if ($canSee) {
				/* push them into pathway */
				$urlParams = array('view' => 'core.ShowItem', 'itemId' => $id);

				$title = $entities[$id]->getTitle()
				? $entities[$id]->getTitle() : $entities[$id]->getPathComponent();
				$title = preg_replace('/\r\n/', ' ', $title);

				$url = $urlGenerator->generateUrl($urlParams);
				if($i < $limit) {
					$breadcrumbs->addItem($title, $url);
				} else {
					$breadcrumbs->addItem($title, '');
					/* description */
					$document->setMetaData( 'description', $entities[$id]->getSummary());
					/* keywords */
					$document->setMetaData( 'keywords', $entities[$id]->getKeywords());
				}
			}
			$i++;
		}
		return null;
	}

}

?>
