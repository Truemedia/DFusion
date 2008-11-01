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

jimport('joomla.application.component.view');

/**
* Renders the main admin screen that shows the configuration overview of all integrations
* @package JFusion
*/

class jfusionViewcpanel extends JView {

    function display($tpl = null)
    {

    	//get the jfusion news
        $crl = curl_init();
        $timeout = 5;
        curl_setopt ($crl, CURLOPT_URL,'http://www.jfusion.org/jfusion_news.txt');
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $JFusionNewsRaw = curl_exec($crl);
        curl_close($crl);

		//clean up the string
		$tagsArray = array();
		$tagsArray[] = '<br/>';
		$tagsArray[] = '</b>';
		$tagsArray[] = '<b>';
		$safeHtmlFilter = &JFilterInput::getInstance($tagsArray, $attrArray = array(), 0, 0);
       	$JFusionNews =  $safeHtmlFilter->clean($JFusionNewsRaw, 'string');
		$this->assignRef('JFusionNews', $JFusionNews);

        parent::display($tpl);

    }
}




