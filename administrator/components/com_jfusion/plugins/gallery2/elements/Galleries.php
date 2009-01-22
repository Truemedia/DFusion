<?php
/**
 * @package JFusion
 * @subpackage Elements
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


/**
 * Defines the forum select list for JFusion forum plugins
 * @package JFusion
 */
class JElementGalleries extends JElement
{
	var $_name = "Galleries";

	function fetchElement($name, $value, &$node, $control_name)
	{
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.
		DS.'gallery2'.DS.'gallery2.php');
		jFusion_g2BridgeCore::loadGallery2Api(true);

		list($ret, $tree) = GalleryCoreApi::fetchAlbumTree();

		$output = array();
		if(!empty($tree))
		{
			list ($ret, $items) = GalleryCoreApi::loadEntitiesById(
			GalleryUtilities::arrayKeysRecursive($tree));
			foreach ($items as $item) {
				$title = $item->getTitle() ? $item->getTitle() : $item->getPathComponent();
				$title = preg_replace('/\r\n/', ' ', $title);
				$titles[$item->getId()] = $title;
			}
			if($ret)
			{
				return "<div>Couldn't query Gallery-Tree</div>";
			}
			$output[] = array("id"   => -1,
	                          "name" => "Default Album",
	                          "disp" => "Default Album");
				
			$this->buildTree($tree, $titles, $output,
	                       ".&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", true);
		}
		return JHTML::_('select.genericlist', $output, $control_name.'['.$name.']', null,
                'id', 'disp', $value);
	}

	function buildTree($tree, $titles, &$ar, $limiter='', $sub=false)
	{
		foreach($tree as $tItemID => $tItemArray)
		{
			$name = htmlspecialchars($titles[$tItemID], ENT_QUOTES);

			$ar[] = array("id"   => $tItemID,
	                      "name" => $name,
	                      "disp" => $limiter . ($sub? "<sup>L</sup>&nbsp;" : "") . $name);

			$this->buildTree($tItemArray, $titles, $ar,
			$limiter . ".&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", true);
		}
	}
}



