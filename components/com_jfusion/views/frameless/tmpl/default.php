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

// Set the URL and make sure we have a ? delimiter
//$url	= JRoute::_('index.php?option=com_j2smf');
//$url	= $uri->current();
//$j2smf->setBaseURL($url);

// Make note of the query
//$query	= $uri->getQuery();
//$j2smf->setFullURL($url.'?'.$query);


// Get the bridge
$j2smf	= j2smfBridge::getInstance();

// Load the integration script
$j2smf->loadIntegration();

// Get the output from SMF
$buffer =& $j2smf->getBuffer();
if (! $buffer ) {
    echo "No output from SMF";
    $j2smf->logError("Cannot obtain SMF Output");
    return false;
}

// Do some general parsing
if (function_exists('j2smf_parseBuffer') ) {
    j2smf_parseBuffer($buffer);
}

if (class_exists('tidy') ) {
    // Parse the output using Tidy
    $options	= array('wrap' => 0 );
    $tidy = new tidy();
    $tidy->parseString($buffer, $options);
    $root = $tidy->root();

    // Make sure that we have something
    if (! $root || ! $root->hasChildren()) {
        $j2smf->logError("The document does not appear to be an HTML document");
        return false;
    }

    // Get the important nodes
    $html = $root->child[1];
    $head = $html->child[0];
    $body = $html->child[1];

    // Output the headers
    foreach($head->child as $child ) {
        $name	= $child->name;
        if ($name == 'script' || $name == 'link' ) {
            $j2smf->addHeader($child);
        }
    }

    // Output the body
    foreach($body->child as $child ) {
        echo $child;
    }
} else {
    $pattern	= '#<head>(.*?)</head>\s*<body>(.*)</body>#';
    $pattern	= '#<head[^>]*>(.*)<\/head>\s*<body[^>]*>(.*)<\/body>#si';
    preg_match_all($pattern, $buffer, $data);

    // Check if we found something
    if (count($data) < 3 ) {
        $j2smf->logError("The document does not appear to be an HTML document");
    } else {

        // Get the body information
        if (isset($data[1][0]) ) {
            $j2smf->addHeader($data[1][0]);
        }


        // Get the header information
        if (isset($data[2][0]) ) {
            echo $data[2][0];
        }
    }


    // Add the headers to Joomla
    $document	= JFactory::getDocument();
    $headers	= $j2smf->getAllHeaders();
    foreach($headers as $header ) {
        $document->addCustomTag($header);
    }



}
