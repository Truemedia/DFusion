<?php
/**
 * @package JFusion
 * @subpackage Controller
 * @version 1.0.7
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Load the JFusion framework
 */
jimport('joomla.application.component.controller');



/**
 * JFusion Component Controller
 * @package JFusion
 */

class JFusionControllerFrontEnd extends JController
{

/**
 * Displays the integrated software in an iFrame
 */
function wrapper()
{
	JRequest::setVar('view', 'wrapper');
	parent::display();
}


}

