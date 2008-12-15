<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
jimport('joomla.application.component.view');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');

class jfusionViewframeless extends JView {

    function display($tpl = null)
    {
        $application = JFactory::getApplication();
        $uri		= JURI::getInstance();

        //Get the base URL and make sure we have a ? delimiter
        $Itemid = JRequest::getVar('Itemid');
        if ($Itemid) {
            $baseURL	= JURI::base() .'index.php?option=com_jfusion&Itemid=' . $Itemid;
        } else {
            $baseURL	= JRoute::_('index.php?option=com_jfusion&view=frameless&jname='.$this->jname);
        }



        //Get the full URL, making note of the query
        $query	= $uri->getQuery();
        $url	= $uri->current();
        $fullURL = $url.'?'.$query;

        //Get the integrated URL
        $JFusionParam = JFusionfactory::getParams($this->jname);
        $integratedURL =$JFusionParam->get('source_url');

        // Get the output from the JFusion plugin
        $JFusionPlugin = JFusionfactory::getPublic($this->jname);

        //Get the output buffer
        $buffer =& $JFusionPlugin->getBuffer();

        if (! $buffer ) {
            JError::raiseWarning(500, JText::_('NO_BUFFER'));
            return false;
        }

        if (class_exists('tidy') ) {
            // Parse the output using Tidy
            $options	= array('wrap' => 0 );
            $tidy = new tidy();
            $tidy->parseString($buffer, $options);
            $root = $tidy->root();

            // Make sure that we have something
            if (! $root || ! $root->hasChildren()) {
                JError::raiseWarning(500, JText::_('NO_HTML'));
                return false;
            }

            // Get the important nodes
            $html = $root->child[1];
            $head = $html->child[0];
            $body = $html->child[1];

            //change the page title
			$pattern = '#<title>(.*?)<\/title>#';
			preg_match($pattern, $head, $page_title);
			global $mainframe;
			$mainframe->setPageTitle($page_title[1]);

            // Output the headers
            $document	= JFactory::getDocument();
            foreach($head->child as $child ) {
                $name	= $child->name;
                if ($name == 'script' || $name == 'link' ) {
                    $JFusionPlugin->parseHeader($child, $baseURL, $fullURL, $integratedURL);
                    $document->addCustomTag($child);
                }
            }

            // Output the body
            foreach($body->child as $child ) {
                // parse the URL's'
                $JFusionPlugin->parseBody($child, $baseURL, $fullURL, $integratedURL);
                echo $child;
            }
        } else {
            $pattern	= '#<head>(.*?)</head>\s*<body>(.*)</body>#';
            $pattern	= '#<head[^>]*>(.*)<\/head>\s*<body[^>]*>(.*)<\/body>#si';
            preg_match_all($pattern, $buffer, $data);

            // Check if we found something
            if (count($data) < 3 ) {
                JError::raiseWarning(500, JText::_('NO_HTML'));
            } else {

                // Add the header information

                if (isset($data[1][0]) ) {
                    $document	= JFactory::getDocument();
                    $JFusionPlugin->parseHeader($data[1][0], $baseURL, $fullURL, $integratedURL);
                    $document->addCustomTag($data[1][0]);

		            //change the page title
					$pattern = '#<title>(.*?)<\/title>#';
					preg_match($pattern, $data[1][0], $page_title);
					global $mainframe;
					$mainframe->setPageTitle($page_title[1]);


                }

                // Output the body
                if (isset($data[2][0]) ) {
                    // 	parse the URL's'
                    $JFusionPlugin->parseBody($data[2][0], $baseURL, $fullURL, $integratedURL);
                    echo $data[2][0];
                }
            }
        }
    }
}
