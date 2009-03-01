<?php
/**
 * @package JFusion
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

//check to see if PHP5 is used
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
    //die if php4 is used
    die( JText::_('PHP_VERSION_OUTDATED'). PHP_VERSION . '<br/><br/>' . JText::_('PHP_VERSION_UPGRADE'));
}

/**
 * Require the base controller
 */
require_once( JPATH_COMPONENT.DS.'controllers'.DS.'controller.jfusion.php' );

// Require specific controller if requested
if($controller = JRequest::getWord('controller')) {
    $path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
    if (file_exists($path)) {
        require_once $path;
    } else {
        $controller = '';
    }
}

// Create the controller
$classname    = 'JFusionController'.$controller;
$controller   = new $classname( );

// Perform the Request task
$task = JRequest::getVar( 'task');
if (!$task) {$task = 'cpanel';}

$tasklist = $controller->getTasks();
if(in_array($task,$tasklist)){
	//excute the task
	$controller->execute($task);
} else {
	//run the task as a view
    JRequest::setVar('view', $task);
	$controller->display();
}

// Redirect if set by the controller
$controller->redirect();