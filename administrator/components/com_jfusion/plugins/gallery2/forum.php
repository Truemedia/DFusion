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
 * JFusion Forum Class for phpBB3
 * For detailed descriptions on these functions please check the model.abstractforum.php
 * @package JFusion_phpBB3
 */
class JFusionForum_gallery2 extends JFusionForum
{

	function getJname()
	{
		return 'gallery2';
	}

	function renderActivityModule($config, $view, $pluginParam)
	{
		switch($view) {
			case 'image_block':
				return $this->renderImageBlock($config, $view, $pluginParam);
				break;
			case 'sidebar':
				return $this->renderSideBar($config, $view, $pluginParam);
				break;
			default:
				return JText::_('NOT IMPLEMENTED YET');
		}
	}

	function renderImageBlock($config, $view, $pluginParam) {
		
		//Load Parameters
		$align = $pluginParam->get( 'g2_align' );
		$block = $pluginParam->get( 'g2_block' );
		$header = $pluginParam->get( 'g2_header' );
		$title = $pluginParam->get( 'g2_title' );
		$date = $pluginParam->get( 'g2_date' );
		$views = $pluginParam->get( 'g2_views' );
		$owner = $pluginParam->get( 'g2_owner' );
		$itemId = (int) $pluginParam->get( 'g2_itemId' );
		$max_size = (int) $pluginParam->get( 'g2_maxSize' );
		$link_target = $pluginParam->get( 'g2_link_target' );
		$frame = $pluginParam->get( 'g2_frame' );
		$strip_anchor = $pluginParam->get( 'g2_strip_anchor' );
		$count = (int) $pluginParam->get( 'g2_count' );
		
		/* Create the show array */
		$array['show'] = array();
		if ($title == 1) {
			$array['show'][] = 'title';
		}
		if ($date == 1) {
			$array['show'][] = 'date';
		}
		if ($views == 1) {
			$array['show'][] = 'views';
		}
		if ($owner == 1) {
			$array['show'][] = 'owner';
		}
		if ($header == 1) {
			$array['show'][] = 'heading';
		}
		$array['show'] = (count($array['show']) > 0) ? implode('|', $array['show']) : 'none';

		/* add itemId if set */
		if(!empty($itemId) && $itemId != -1) {
			$array['itemId'] = $itemId;
		}

		/* set the rest */
		$array['blocks']	 = $block;
		if(!empty($max_size)){
			$array['maxSize'] = $max_size;
		}
		$array['linkTarget'] = $link_target;
		
		if($config['debug'] && $frame == 'none') {
			/* Load the module list */
	        list ($ret, $moduleStatus) = GalleryCoreApi::fetchPluginStatus('module');
	        if ($ret) {
	            return JTEXT::_('ERROR_LOADING_GALLERY_MODULES');
	        }
	
	        if (!isset($moduleStatus['imageframe']) || empty($moduleStatus['imageframe']['active'])) {
	            return JTEXT::_('ERROR_IMAGEFRAME_NOT_READY');
	        }
		}
		$array['itemFrame']  = $frame;

		$content = '<div align="'.$align.'">';

		if($block=="specificItem" && empty($itemId)){
			$content .= '<strong>Error</strong><br />You have selected no "itemid" and this must be done if you select "Specific Picture"';
		} else {
			//Initialize the Framework
			require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
			             DS.'gallery2'.DS.'gallery2.php');
			if(!jFusion_g2BridgeCore::loadGallery2Api(true)) {
				$content .= '<strong>Error</strong><br />Can\'t initialise G2Bridge.';
			} else {
				if(isset($config['itemid']) && $config['itemid'] != 150) {
					global $gallery;
					$params = JFusionFactory::getParams('gallery2');
					$source_url = $params->get('source_url');
					$urlGenerator = new GalleryUrlGenerator();
					$urlGenerator->init(jFusion_g2BridgeCore::getEmbedUri($config['itemid']), $source_url, null);
					$gallery->setUrlGenerator($urlGenerator);
				}
				list ($ret, $imageBlockHtml, $headContent) = GalleryEmbed::getImageBlock($array);
				if ($ret) {
					if($ret->getErrorCode() == 4194305) {
						$content .= '<strong>Error</strong><br />You need to install the Gallery2 Plugin "imageblock".';
					} else {
						$content .= "<h2>Fatal G2 error</h2> Here's the error from G2:<br />" .$ret->getAsHtml();
					}
				}
				$content .= ($strip_anchor == 1) ? strip_tags($imageBlockHtml, '<img><table><tr><td><div><h3>') : $imageBlockHtml;
				$document	= & JFactory::getDocument();
				$document->addCustomTag($headContent);
				/* finish Gallery 2 */
				GalleryEmbed::done();
			}
		}

		$content .= '</div>';
		return $content;
	}
	
	function renderSideBar($config, $view, $pluginParam) {
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		$g2sidebar = jFusion_g2BridgeCore::getVar("sidebar", -1);
		if ($g2sidebar != -1) {
			return '<div id="gsSidebar" class="gcBorder1"> '.implode('', $g2sidebar).'</div>';
		} else {
			return 'Sidebar isn\'t initialisies. Maybe there is a Problem with the Bridge';
	    }
	}

	/**
	 * Returns the Profile Url in Gallery2
	 * This Link requires Modules:members enabled in gallery2
	 * 
	 * @return string
	 * @see Gallery2:Modules:members
	 */
    function getProfileURL($uid)
    {
        return 'main.php?g2_view=members.MembersProfile&g2_userId=$uid';
    }
}


