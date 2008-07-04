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

class jfusionViewplugineditor extends JView {

    function display($tpl = null)
    {
    $bar =& new JToolBar('My Toolbar' );
    $bar->appendButton('Standard', 'save', JText::_('SAVE'), 'saveconfig', false, false );
    $bar->appendButton('Standard', 'cancel', JText::_('CANCEL'), 'plugindisplay', false, false );
    $toolbar = $bar->render();

    //print out results to user
    $this->assignRef('toolbar', $toolbar);
    parent::display($tpl);

    }

}
?>


