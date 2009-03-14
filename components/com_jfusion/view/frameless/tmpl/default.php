<?php
/**
* @package JFusion
* @subpackage Views
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

//initialise some vars
$application = JFactory::getApplication();
$uri		= JURI::getInstance();

// declare Data object
$data = new stdClass();
$data->buffer = null;
$data->header = null;
$data->body = null;
$data->baseURL = null;
$data->fullURL = null;
$data->integratedURL = null;
$data->jPluginParam = $this->jPluginParam;
$data->Itemid = JRequest::getVar('Itemid');

//Get the base URL to the specific JFusion plugin
$data->baseURL = JFusionFunction::getPluginURL($data->Itemid);

//Get the full current URL
$query	= $uri->getQuery();
$url	= $uri->current();
$data->fullURL = $url.'?'.$query;

//Get the integrated URL
$JFusionParam = JFusionFactory::getParams($this->jname);
$data->integratedURL =$JFusionParam->get('source_url');

// Get the output from the JFusion plugin
$JFusionPlugin = JFusionFactory::getPublic($this->jname);

//backup Joomla's globals
$joomla_globals = $GLOBALS;
//get the buffer
$JFusionPlugin->getBuffer($data);
//restore Joomla's globals
$GLOBALS = $joomla_globals;
//reset the global $Itemid so that modules are not repeated
global $Itemid;
$Itemid = $data->Itemid;
//reset Itemid so that it can be obtained via getVar
JRequest::setVar('Itemid',$data->Itemid);

//clear the page title
if(!empty($data->buffer)) {
	global $mainframe;
	$mainframe->setPageTitle('');
}

//check to see if the Joomla database is still connnected incase the plugin messed it up
JFusionFunction::reconnectJoomlaDb();

if ($data->buffer === 0){
	JError::raiseWarning(500, JText::_('NO_FRAMELESS'));
    $result = false;
    return $result;
}

if (! $data->buffer ) {
	JError::raiseWarning(500, JText::_('NO_BUFFER'));
    $result = false;
    return $result;
}

//we set the backtrack_limit to twice the buffer length just in case!
$backtrack_limit = ini_get('pcre.backtrack_limit');
ini_set('pcre.backtrack_limit',strlen($data->buffer)*2);

$pattern	= '#<head[^>]*>(.*)<\/head>.*?<body[^>]*>(.*)<\/body>#si';
preg_match($pattern, $data->buffer, $temp);
$data->header = $temp[1];
$data->body = $temp[2];
unset($temp,$data->buffer);

// Check if we found something
if (!strlen($data->header) || !strlen($data->body)) {
	if(!empty($data->buffer)){
		//non html output, return without parsing
	   	die($data->buffer);
	} else {
		//no output returned
   		JError::raiseWarning(500, JText::_('NO_HTML'));
	}
} else {
	// Add the header information
    if (isset($data->header) ) {
	    $document	= JFactory::getDocument();
		$regex_header = array();
		$replace_header = array();

	    //change the page title
		$pattern = '#<title>(.*?)<\/title>#Si';
		preg_match($pattern, $data->header, $page_title);
		$mainframe->setPageTitle(html_entity_decode( $page_title[1], ENT_QUOTES, "utf-8" ));
		$regex_header[]	= $pattern;
		$replace_header[] = '';

		//set meta data to that of softwares
		$meta = array('keywords','description','robots');

		foreach($meta as $m) {
			$pattern = '#<meta name=["|\']'.$m.'["|\'](.*?)content=["|\'](.*?)["|\'](.*?)>#Si';
			if (preg_match($pattern, $data->header, $page_meta)){
				if($page_meta[2]) {
					$document->setMetaData( $m, $page_meta[2] );
				}
			$regex_header[]	= $pattern;
		    $replace_header[] = '';
			}
		}

		$pattern = '#<meta name=["|\']generator["|\'](.*?)content=["|\'](.*?)["|\'](.*?)>#Si';
    	if(preg_match($pattern, $data->header, $page_generator)) {
    		if($page_generator[2]) {
        		$document->setGenerator( $document->getGenerator().', '. $page_generator[2]);
        	}
			$regex_header[]	= $pattern;
			$replace_header[] = '';
    	}

    	//use Joomla's default
    	$regex_header[]	= '#<meta http-equiv=["|\']Content-Type["|\'](.*?)>#Si';
		$replace_header[] = '';

    	//remove above set meta data from software's header
		$data->header = preg_replace($regex_header, $replace_header, $data->header);

		$JFusionPlugin->parseHeader($data);
    	$document->addCustomTag($data->header);

		$pathway = $JFusionPlugin->getPathWay();
		if ( is_array($pathway) ) {
			$breadcrumbs = & $mainframe->getPathWay();
    		foreach($pathway as $path) $breadcrumbs->addItem($path->title, substr(JFusionFunction::getJoomlaURL(),0,-1).JFusionFunction::routeURL($path->url, JRequest::getVar('Itemid')));
    	}
	}

	// Output the body
   	if (isset($data->body) ) {
    	// parse the URL's'
        $JFusionPlugin->parseBody($data);
        echo $data->body;
	}

	//set the base href
	$document->setBase($data->baseURL);

	//restore the backtrack_limit
	ini_set('pcre.backtrack_limit',$backtrack_limit);
}