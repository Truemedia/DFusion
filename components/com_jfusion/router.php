<?php
/**
* @package JFusion
* @subpackage Router
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined( '_JEXEC' ) or die( 'Restricted access' );

function jfusionBuildRoute(&$query)
{
	$segments = array();
	//make sure the url starts with the filename
	if (isset($query['jfile']))	{
		$segments[] = $query['jfile'];
		unset($query['jfile']);
	}

	//change all other variables into SEF
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
	$params = JFusionFactory::getParams('joomla_int');
	$sefmode = $params->get('sefmode');
	if($sefmode == 1){
		foreach($query as $key => $value){
			if($key != 'option' && $key != 'Itemid'){
				if(is_array($value)){
					foreach($value as $array_key => $array_value){
						$segments[] = $key .'['.$array_key.'],' . $array_value;
						unset($query[$key]);
					}
				} else {
					$segments[] = $key .',' . $value;
					unset($query[$key]);
				}
			}
		}
	}

	return $segments;
}

function jfusionParseRoute($segments)
{
	$vars = array();
	$vars['jFusion_Route'] = serialize($segments);

	if(isset($segments[0])){
		if(!strpos($segments[0],',') & !strpos($segments[0],'&')){
	    	$vars['jfile'] 		= $segments[0];
	    	unset($segments[0]);
		}

		//parse all other segments
		if(!empty($segments)){
			foreach ($segments as $segment){
				$parts = explode(',', $segment);
				if(isset($parts[1])){
					//check for an array
					if(strpos($parts[0],'[')){
						//prepare the variable
						$array_parts = explode('[', $parts[0]);
						$array_index = substr_replace($array_parts[1],"",-1);
						//set the variable
						if(empty($vars[$array_parts[0]])){
							$vars[$array_parts[0]] = array();
						}
						$vars[$array_parts[0]][$array_index]=$parts[1];
					} else {
						$vars[$parts[0]] = $parts[1];
					}
				}
			}
		}
    }
    unset($segments);
	return $vars;
}
