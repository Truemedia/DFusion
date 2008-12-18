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

class jfusionViewcpanel extends JView {

    function display($tpl = null)
    {
    	//get the jfusion news
		$url = 'http://www.jfusion.org/jfusion_news.xml';
    	if(function_exists('curl_init')){
    		//curl is the preferred function
	        $crl = curl_init();
    	    $timeout = 5;
        	curl_setopt ($crl, CURLOPT_URL,$url);
	        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    	    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        	$JFusionNewsRaw = curl_exec($crl);
	        curl_close($crl);
    	} else {
    		//get the file directly if curl is disabled
		    $JFusionNewsRaw = file_get_contents($url);
		    if (!strpos($JFusionNewsRaw, '<document>')){
		    	//file_get_content is often blocked by hosts, return an error message
				$JFusionNewsRaw = '<?xml version=\'1.0\' standalone=\'yes\'?>
					<document><item><date></date>
					<title>' . JText::_('CURL_DISABLED') . '</title>
					<link>www.jfusion.org</link><body></body></item></document>';
		    }
    	}


		$JFusionNews = new SimpleXMLElement($JFusionNewsRaw);
		$this->assignRef('JFusionNews', $JFusionNews);

        parent::display($tpl);

    }
}




