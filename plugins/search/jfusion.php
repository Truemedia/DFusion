<?php

/**
 * @package JFusion
 * @subpackage Plugin_Search
 * @version 1.1.0
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');

$mainframe->registerEvent( 'onSearch', 'plgSearchjfusion' );
$mainframe->registerEvent( 'onSearchAreas', 'plgSearchjfusionAreas');

JPlugin::loadLanguage( 'com_jfusion' );

//get the name of each plugin and add to areas
function &plgSearchjfusionAreas()
{
	static $areas = array();

	//get the softwares
	$master = JFusionFunction::getMaster();
	$slaves = JFusionFunction::getSlaves();

	if($master->name!="joomla_int") $areas[$master->name] = $master->description;

	foreach($slaves as $slave)
	{
		if($slave->name!="joomla_int") {
			$areas[$slave->name] = $slave->description;
		}
	}

	return $areas;
}


function plgSearchjfusion($text, $phrase = '', $ordering = '', $areas = null )
{
	//no text to search
	if(!$text) return array();

	$jFusionPlugins = plgSearchjfusionAreas();

	if(is_array($areas))
	{
		if(!array_intersect( $areas, array_keys( $jFusionPlugins)))
		{
			return array();
		}
	}

	//plugins to search
	$searchPlugins = array_intersect( $areas, array_keys( $jFusionPlugins ));

	//contains the rows returned from plugins
	$rows = array();

	//will contain combined results from all the rows
	$results = array();

	$plugin =& JPluginHelper::getPlugin('search','jfusion');
	$params = new JParameter( $plugin->params);
	$linkMode = $params->get('link_mode','direct');

	foreach($searchPlugins AS $key => $jname)
	{
		$db = & JFusionFactory::getDatabase($jname);
		$searchMe = JFusionFactory::getForum($jname);
		$forum = JFusionFactory::getForum($jname);

		$query = $searchMe->getSearchQuery();
		$columns = $searchMe->getSearchQueryColumns();

		if($phrase == 'exact')
		{
			$where = "(LOWER({$columns->title}) LIKE '%$text%') OR (LOWER({$columns->text}) like '%$text%')";
		}
		else
		{
			$words = explode (' ', $text);
			$wheres = array();
			foreach($words as $word)
			{
				$wheres[] = "(LOWER({$columns->title}) LIKE '%$word%') OR (LOWER({$columns->text}) like '%$word%')";
			}

			if($phrase == 'all') $separator = "AND";
			else $separator = "OR";

			$where = '(' . implode ( ") $separator (", $wheres) . ')';
		}

		$query .= " WHERE $where";
		$db->setQuery($query);
		if($posts = $db->loadObjectList())
		{
			foreach($posts as $post)
			{
				$href = JFusionFunction::createURL($forum->getPostURL($post->threadid,$post->postid), $jname, $linkMode);
				$post->href = $href;
				$post->browsernav = 2;
				$post->text = $searchMe->cleanUpSearchText($post->text);
			}
			$rows[] = $posts;
		}
		else die($db->stderr());

	}

	//merge all the rows into one array
	foreach($rows AS $r)
	{
		$results = array_merge($results,$r);
	}

	//sort the posts
	jimport('joomla.utilities.array');

	switch ($ordering){
		case 'oldest':
			JArrayHelper::sortObjects($results, 'created');
			break;
		case 'alpha':
			JArrayHelper::sortObjects($results, 'title');
			break;
		case 'newest':
		default:
			JArrayHelper::sortObjects($results, 'created', -1);
			break;
	}

	return $results;
}