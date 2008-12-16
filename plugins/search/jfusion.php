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

	//jfusion plugins to search
	$searchPlugins = array_intersect( $areas, array_keys( $jFusionPlugins ));

	//get the search plugin parameters
	$plugin =& JPluginHelper::getPlugin('search','jfusion');
	$params = new JParameter( $plugin->params);
	$linkMode = $params->get('link_mode','direct');

	foreach($searchPlugins AS $key => $jname)
	{
		//initialize plugin database
		$db = & JFusionFactory::getDatabase($jname);
		//load jfusion plugin search functions
		$searchMe = JFusionFactory::getPublic($jname);
		//get the query used to search
		$query = $searchMe->getSearchQuery();
		//assign specific table colums to title and text
		$columns = $searchMe->getSearchQueryColumns();

		//build the query
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

		//contains the rows returned from plugins
		$rows = array();

		//load the results
		if($results = $db->loadObjectList())
		{
			foreach($results as $result)
			{
				//add a link
				$href = JFusionFunction::createURL($searchMe->getSearchResultLink($result), $jname, $linkMode);
				$result->href = $href;
				//open link in same window
				$result->browsernav = 2;
				//clean up the text such as removing bbcode, etc
				$result->text = $searchMe->cleanUpSearchText($result->text);
			}
			$rows[] = $results;
		}
		else
		{
			JError::raiseWarning(500, $db->stderr());
			return null;
		}

	}

	//To hold all the search results
	$searchResults = array();

	//merge all the rows into one array
	foreach($rows AS $r)
	{
		$searchResults = array_merge($searchResults,$r);
	}

	//sort the results
	jimport('joomla.utilities.array');

	switch ($ordering){
		case 'oldest':
			JArrayHelper::sortObjects($searchResults, 'created');
			break;
		case 'alpha':
			JArrayHelper::sortObjects($searchResults, 'title');
			break;
		case 'newest':
		default:
			JArrayHelper::sortObjects($searchResults, 'created', -1);
			break;
	}

	return $searchResults;
}