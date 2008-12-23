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

class jfusionViewsyncerror extends JView {

    function display($tpl = null)
    {
		//Load usersync library
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

		//check to see if the sync has already started
	    $syncid = JRequest::getVar('syncid');
    	$syncdata = JFusionUsersync::getSyncdata($syncid);

	    $this->assignRef('syncdata', $syncdata);
	    parent::display($tpl);
    }
}

