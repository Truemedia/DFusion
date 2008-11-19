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

class jfusionViewplugindisplay extends JView {

    function display($tpl = null)
    {
        //prepare the toolbar
        $bar =& new JToolBar('My Toolbar' );
        $bar->appendButton('Standard', 'edit', JText::_('EDIT'), 'plugineditor', false, false );
        $bar->appendButton('Standard', 'help', JText::_('WIZARD'), 'wizard', false, false );
        $toolbar = $bar->render();

        //get the data about the JFusion plugins
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion';
        $db->setQuery($query );
        $rows = $db->loadObjectList();

        if ($rows) {
            //print out results to user
            $this->assignRef('rows', $rows);
            $this->assignRef('toolbar', $toolbar);
            parent::display($tpl);
        } else {
        	//for some reason the Joomla installer did no create the needed tables
        	$sqlfile = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'sql'.DS.'install.jfusion.sql';
        	if (($file_handle = @fopen($sqlfile, 'r')) === FALSE) {
            	JError::raiseWarning(500, JText::_('NO_JFUSION_TABLE'). ' ' . JText::_('NO_SQL_FILE'));
	        } else {
    	        //parse the file line by line to get only the config variables
        	    $sqlquery = fopen($sqlfile, 'r');
        	    $db->setQuery($sqlquery);
        	    if (!$db->query) {
            		JError::raiseWarning(500, JText::_('NO_JFUSION_TABLE') . ': ' . $db->stderr());
        	    } else {
			        //get the data about the JFusion plugins
			        $query = 'SELECT * from #__jfusion';
        			$db->setQuery($query );
        			$rows = $db->loadObjectList();
			        if ($rows) {
            			//print out results to user
            			$this->assignRef('rows', $rows);
            			$this->assignRef('toolbar', $toolbar);
            			parent::display($tpl);
              	    } else {
            			JError::raiseWarning(500, JText::_('NO_JFUSION_TABLE'));
			        }
        	    }
        	}
        }
    }
}




