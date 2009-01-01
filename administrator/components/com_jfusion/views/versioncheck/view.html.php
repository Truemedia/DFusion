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

jimport('joomla.application.component.view');

/**
* Renders the main admin screen that shows the configuration overview of all integrations
* @package JFusion
*/

class jfusionViewversioncheck extends JView {

    function display($tpl = null)
    {
    	//get the jfusion news
    	ob_start();
		$url = 'http://jfusion.googlecode.com/svn/branches/jfusion_version.xml';
    	if(function_exists('curl_init')){
    		//curl is the preferred function
	        $crl = curl_init();
    	    $timeout = 5;
        	curl_setopt ($crl, CURLOPT_URL,$url);
	        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    	    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        	$JFusionVersionRaw = curl_exec($crl);
	        curl_close($crl);
    	} else {
    		//get the file directly if curl is disabled
		    $JFusionVersionRaw = file_get_contents($url);
		    if (!strpos($JFusionVersionRaw, '<document>')){
		    	//file_get_content is often blocked by hosts, return an error message
				echo JText::_('CURL_DISABLED');
				return;
		    }
    	}

 		$parser = JFactory::getXMLParser('Simple');
	    if ($parser->loadString($JFusionVersionRaw)) {
        	if (isset( $parser->document )) {
            	$JFusionVersion    = $parser->document;
         	} else {
				echo JText::_('CURL_DISABLED');
				return;
         	}
      	}
		unset($parser);
		ob_end_clean();

		$this->assignRef('JFusionVersion', $JFusionVersion);
        parent::display($tpl);
    }
}