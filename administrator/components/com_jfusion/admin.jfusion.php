<?php
/**
 * @package JFusion
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );


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

$controller->execute( $task );

// Redirect if set by the controller
$controller->redirect();


