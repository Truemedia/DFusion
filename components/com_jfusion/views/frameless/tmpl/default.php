<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

/**
* load the frameless model
*/
require_once(JPATH_SITE .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.frameless.php');

$application = JFactory::getApplication();
$uri		= JURI::getInstance();
$frameless = new frameless();

// Set the URL and make sure we have a ? delimiter
$url	= JRoute::_('index.php?option=com_jfusion&task=frameless&jname=smf');
$frameless->setBaseURL($url);

// Make note of the query
$query	= $uri->getQuery();
$frameless->setFullURL($url.'?'.$query);


// Get the output from the JFusion plugin
$JFusionPlugin = JFusionfactory::getPlugin($this->jname);

//Get the output buffer
$buffer =& $JFusionPlugin->getBuffer();


if (! $buffer ) {
	JError::raiseWarning(500, JText::_('NO_BUFFER'));
    return false;
}

// Do some general parsing
$frameless->parseBuffer($buffer);

if ( class_exists('tidy') ) {
	// Parse the output using Tidy
	$options	= array( 'wrap' => 0 );
	$tidy = new tidy();
	$tidy->parseString($buffer, $options);
	$root = $tidy->root();

	// Make sure that we have something
	if( ! $root || ! $root->hasChildren()) {
		JError::raiseWarning(500, JText::_('NO_HTML'));
		return false;
	}

	// Get the important nodes
	$html = $root->child[1];
	$head = $html->child[0];
	$body = $html->child[1];

	// Output the headers
	foreach ( $head->child as $child ) {
		$name	= $child->name;
		if ( $name == 'script' || $name == 'link' ) {
			$frameless->addHeader($child);
		}
	}

	// Output the body
	foreach ( $body->child as $child ) {
		echo $child;
	}
} else {
	$pattern	= '#<head>(.*?)</head>\s*<body>(.*)</body>#';
	$pattern	= '#<head[^>]*>(.*)<\/head>\s*<body[^>]*>(.*)<\/body>#si';
	preg_match_all($pattern, $buffer, $data);

	// Check if we found something
	if ( count($data) < 3 ) {
		JError::raiseWarning(500, JText::_('NO_HTML'));
	} else {

		// Get the body information
		if ( isset($data[1][0]) ) {
			$frameless->addHeader($data[1][0]);
		}


		// Get the header information
		if ( isset($data[2][0]) ) {
			echo $data[2][0];
		}
	}
}


    // Add the headers to Joomla
    $document	= JFactory::getDocument();
    $headers	= $frameless->getAllHeaders();
    foreach($headers as $header ) {
        $document->addCustomTag($header);
    }
