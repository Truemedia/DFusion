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
		return 'main.php?g2_view=core.UserAdmin&g2_subView=register.UserSelfRegistration';
	}

	function getLostPasswordURL()
	{
		return 'main.php?g2_view=core.UserAdmin&g2_subView=core.UserRecoverPassword';
	}

	function getLostUsernameURL()
	{
		return '';
	}

	function & getBuffer($jPluginParam)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		G2BridgeCore::loadGallery2Api(true);
		global $gallery;
		
		$album = $jPluginParam->get('album');
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
	    	G2BridgeCore::setVar("sidebar", $g2data["sidebarBlocksHtml"]);
		}
		
	    G2BridgeCore::setPathway();
	    
		$buffer = "<html><head>".$g2data['headHtml']."</head><body>".
		$g2data['bodyHtml']."</body></html>";
		return $buffer;
	}


	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL) {}
	function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL) {}

	
}
