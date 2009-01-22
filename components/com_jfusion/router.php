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
	//detect if url does not include an itemid
	if (isset($query['jname']))	{
		$segments[] = $query['jname'];
		unset($query['jname']);
		$segments[] = $query['view'];
		unset($query['view']);
		$segments[] = $query['jfile'];
		unset($query['jfile']);
	} else {
		//make sure the url starts with the filename
		if (isset($query['jfile']))	{
			$segments[] = $query['jfile'];
			unset($query['jfile']);
		}
	}
	//change all other variables into SEF
	foreach($query as $key => $value){
		if($key != 'option' && $key != 'Itemid'){
			$segments[] = $key .',' . $value;
			unset($query[$key]);
		}
	}

	return $segments;
}

function jfusionParseRoute($segments)
{
	$vars = array();
	if(isset($segments[0])){
		if(strpos($segments[0],'.')){
	    	$vars['jfile'] 		= $segments[0];
	    	unset($segments[0]);
		} else {
		    $vars['jname'] 		= $segments[0];
		    $vars['view'] 		= $segments[1];
	    	$vars['jfile'] 		= $segments[2];
	    	unset($segments[0],$segments[1],$segments[2]);
		}
		//parse all other segments
		if(!empty($segments)){
			foreach ($segments as $segment){
				$parts = explode(',', $segment);
				if(isset($parts[1])){
					$vars[$parts[0]] = $parts[1];
				}
			}
		}
    }
    unset($segments);
	return $vars;
}
