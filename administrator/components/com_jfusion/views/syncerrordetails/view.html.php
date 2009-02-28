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

class jfusionViewsyncerrordetails extends JView {

    function display($tpl = null)
    {


	/**
	* 	Load usersync library
	*/
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

	//Load debug library
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');

	//check to see if the sync has already started
    $syncid = JRequest::getVar('syncid');
    $syncdata = JFusionUsersync::getSyncdata($syncid);
	$syncerror = JFusionUsersync::getErrorData($syncid);

    $this->assignRef('syncdata', $syncdata);
    $this->assignRef('syncid', $syncid);
	$this->assignRef('syncerror', $syncerror);
    parent::display($tpl);
    }
}

