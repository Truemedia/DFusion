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
		$cpanel = 'http://jfusion.googlecode.com/svn/branches/jfusion_cpanel.xml';

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
        	curl_setopt ($crl, CURLOPT_URL,$cpanel);
	        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    	    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        	$JFusionCpanelRaw = curl_exec($crl);
	        curl_close($crl);

    	} else {
    		//get the file directly if curl is disabled
		    $JFusionCpanelRaw = file_get_contents($cpanel);
		    if (!strpos($JFusionCpanelRaw, '<document>')){
		    	//file_get_content is often blocked by hosts, return an error message
				$JFusionNewsRaw = $curl_disabled;
		    }
    	}

 		$parser = JFactory::getXMLParser('Simple');
	    if ($parser->loadString($JFusionCpanelRaw)) {
        	if (isset( $parser->document )) {
            	$JFusionCpanel    = $parser->document;
         	} else {
				unset($parser);
 				$parser = JFactory::getXMLParser('Simple');
				$parser->loadString($curl_disabled);
            	$JFusionCpanel    = $parser->document;
         	}
      	}
		unset($parser);

      	//end the outputbuffer
      	ob_end_clean();

		//pass on the variables to the view
		$this->assignRef('JFusionCpanel', $JFusionCpanel);
        parent::display($tpl);
    }
}
