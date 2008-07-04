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

class jfusionViewWrapper extends JView {

    function display($tpl = null)
    {

        //get the forum url
        $wrap = urldecode(JRequest::getVar('wrap', '', 'get'));
        $jname = urldecode(JRequest::getVar('jname', '', 'get'));

        //get the URL to the forum
        $params = JFusionFactory::getParams($jname);
        $source_url = $params->get('source_url');

        //check to see if url starts with http
        $test_url = substr($source_url, 0, 4);
        if ($test_url == 'http') {
        } else {
            $source_url = 'http://' . $source_url;
        }

        //check for trailing slash
        if (substr($source_url, -1) == '/') {
            $url = $source_url . $wrap;
        } else {
            $url = $source_url . '/'. $wrap;
        }
        ;


        //print out results to user
        $this->assignRef('url', $url);
        $this->assignRef('params', $params);
        parent::display($tpl);
    }

}
?>




