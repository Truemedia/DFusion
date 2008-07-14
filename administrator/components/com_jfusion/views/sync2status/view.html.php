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

class jfusionViewsync2status extends JView {

    function display($tpl = null)
    {

        //retrieve the syncid
        $syncid = JRequest::getVar('syncid', '', 'GET');

		//get the syncdata
		$syncdata = JFusionFunction::getSyncdata($syncid);

	    //print out results to user
    	$this->assignRef('syncdata', $syncdata);
    	parent::display($tpl);
    }

}
?>



