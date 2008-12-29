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
		$news = 'http://jfusion.googlecode.com/svn/branches/jfusion_news.xml';
		$team = 'http://jfusion.googlecode.com/svn/branches/jfusion_team.xml';

		//define the standard message when the XML is not found
	    $curl_disabled = '<?xml version=\'1.0\' standalone=\'yes\'?>
					<document><item><date></date>
					<title>' . JText::_('CURL_DISABLED') . '</title>
					<link>www.jfusion.org</link><body></body></item></document>';

		//prevent any output during parsing
		ob_start();

    	//get the jfusion news
    	if(function_exists('curl_init')){
    		//curl is the preferred function

	        $crl = curl_init();
    	    $timeout = 5;
        	curl_setopt ($crl, CURLOPT_URL,$news);
	        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    	    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        	$JFusionNewsRaw = curl_exec($crl);
	        curl_close($crl);

	        $crl = curl_init();
    	    $timeout = 5;
        	curl_setopt ($crl, CURLOPT_URL,$team);
	        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    	    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        	$JFusionTeamRaw = curl_exec($crl);
	        curl_close($crl);


    	} else {
    		//get the file directly if curl is disabled
		    $JFusionNewsRaw = file_get_contents($news);
		    if (!strpos($JFusionNewsRaw, '<document>')){
		    	//file_get_content is often blocked by hosts, return an error message
				$JFusionNewsRaw = $curl_disabled;
		    }
    		//get the file directly if curl is disabled
		    $JFusionTeamRaw = file_get_contents($team);
		    if (!strpos($JFusionTeamRaw, '<document>')){
		    	//file_get_content is often blocked by hosts, return an error message
				$JFusionTeamRaw = $curl_disabled;
		    }
    	}

 		$parser = JFactory::getXMLParser('Simple');
	    if ($parser->loadString($JFusionTeamRaw)) {
        	if (isset( $parser->document )) {
            	$JFusionTeam    = $parser->document;
         	} else {
				unset($parser);
 				$parser = JFactory::getXMLParser('Simple');
				$parser->loadString($curl_disabled);
            	$JFusionTeam    = $parser->document;
         	}
      	}
		unset($parser);

 		$parser = JFactory::getXMLParser('Simple');
	    if ($parser->loadString($JFusionNewsRaw)) {
        	if (isset( $parser->document )) {
            	$JFusionNews    = $parser->document;
         	} else {
				unset($parser);
 				$parser = JFactory::getXMLParser('Simple');
				$parser->loadString($curl_disabled);
            	$JFusionNews    = $parser->document;
         	}
      	}

      	//end the outputbuffer
      	ob_end_clean();

		//pass on the variables to the view
		$this->assignRef('JFusionNews', $JFusionNews);
		$this->assignRef('JFusionTeam', $JFusionTeam);
        parent::display($tpl);
    }
}
