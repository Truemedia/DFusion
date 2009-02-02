<?php

/**
 * @package JFusion
 * @subpackage Plugin_Search
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

JPlugin::loadLanguage( 'plg_search_jfusion' );

//get the name of each plugin and add to areas
function &plgSearchjfusionAreas()
{
	static $areas = array();

	//get the softwares with search enabled
	$plugins = JFusionFunction::getPlugins('search');
	
	foreach($plugins as $plugin)
	{
		if($plugin->name!="joomla_int") {
			//make sure that search is enabled
			$public =& JFusionFactory::getPublic($plugin->name);
			$searchEnabled = ($public->getSearchQuery() == '') ? false : true;
			if($searchEnabled){
				$areas[$plugin->name] = $plugin->name;
			}
		}
	}

	return $areas;
}


function plgSearchjfusion($text, $phrase = '', $ordering = '', $areas = null )
{
	//no text to search
	if(!$text) return array();

	$searchPlugins = plgSearchjfusionAreas();
	if(is_array($areas)) {
		//jfusion plugins to search
		$searchPlugins = array_intersect( $areas, array_keys( $searchPlugins ));
		if(empty($searchPlugins)) {
			return array();
		}
	}
		
	//get the search plugin parameters
	$plugin =& JPluginHelper::getPlugin('search','jfusion');
	$params = new JParameter( $plugin->params); 

	foreach($searchPlugins AS $key => $jname)
	{
		$linkMode =& $params->get('link_mode_'.$jname,'direct');
		$itemid =& $params->get('itemid_'.$jname,false);
		
		//initialize plugin database
		$db = & JFusionFactory::getDatabase($jname);
		//load jfusion plugin search functions
		$searchMe =& JFusionFactory::getPublic($jname);
		//get the query used to search
		$query = $searchMe->getSearchQuery();

		//only search if the query is not empty
		if($query!="") {
			//assign specific table colums to title and text
			$columns = $searchMe->getSearchQueryColumns();

			//build the query
			if($phrase == 'exact') {
				$where = "(LOWER({$columns->title}) LIKE '%$text%') OR (LOWER({$columns->text}) like '%$text%')";
			} else {
				$words = explode (' ', $text);
				$wheres = array();
				foreach($words as $word) {
					$wheres[] = "(LOWER({$columns->title}) LIKE '%$word%') OR (LOWER({$columns->text}) like '%$word%')";
				}

				if($phrase == 'all') $separator = "AND";
				else $separator = "OR";

				$where = '(' . implode ( ") $separator (", $wheres) . ')';
			}
			
			//pass the where clause into the plugin in case it wants to add something
			$searchMe->getSearchCriteria($where);
			
			$query .= " WHERE $where";
			
			$db->setQuery($query);
			$results = $db->loadObjectList();
			
			//contains the rows returned from plugins
			$rows = array();
			
			//pass results back to the plugin in case they need to be filtered
			$searchMe->filterSearchResults($results);
			
			//load the results
			if(is_array($results)) {
				foreach($results as $result) {
					//add a link
					$href = JFusionFunction::createURL($searchMe->getSearchResultLink($result), $jname, $linkMode,$itemid);
					$result->href = $href;
					//open link in same window
					$result->browsernav = 2;
					//clean up the text such as removing bbcode, etc
					$result->text = $searchMe->cleanUpSearchText($result->text);
					$result->section = $searchMe->cleanUpSearchText($result->section);
					$result->title = $searchMe->cleanUpSearchText($result->title);
				}
				$rows[] = $results;
			} else {
				JError::raiseWarning(500, $db->stderr());
				return null;
			}
		}
	}

	//To hold all the search results
	$searchResults = array();

	//do we have any rows?
	if(count($rows)>0) {
		//merge all the rows into one array
		foreach($rows AS $r) {
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
	}

	return $searchResults;
}