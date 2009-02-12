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
	$searchplugin =& JPluginHelper::getPlugin('search','jfusion');
	$params = new JParameter( $searchplugin->params);
	$enabledPlugins = unserialize(base64_decode($params->get('JFusionPluginParam')));
	
	foreach($plugins as $plugin)
	{
		if(array_key_exists($plugin->name,$enabledPlugins)) {
			if($plugin->name!="joomla_int") {
				//make sure that search is enabled
				$public =& JFusionFactory::getPublic($plugin->name);		
				$title = (!empty($enabledPlugins[$plugin->name]['title'])) ? $enabledPlugins[$plugin->name]['title'] : $plugin->name; 
				$areas[$plugin->name] = $title;
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
	$pluginParamValue = $params->get('JFusionPluginParam');
	$pluginParamValue = unserialize(base64_decode($pluginParamValue));
		
	//To hold all the search results
	$searchResults = array();
	
	foreach($searchPlugins AS $key => $jname)
	{
		$linkMode =& $params->get('link_mode_'.$jname,'direct');
		$itemid =& $params->get('itemid_'.$jname,false);
		$searchMe =& JFusionFactory::getPublic($jname);
		if(is_array($pluginParamValue)){
			$pluginParam = new JParameter('');
			$pluginParam->loadArray($pluginParamValue[$jname]);
		} else {
			$pluginParam = '';
		}
		
		$results = $searchMe->getSearchResults($text,$phrase,$pluginParam, $linkMode, $itemid);

		$searchResults = array_merge($searchResults,$results);	
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