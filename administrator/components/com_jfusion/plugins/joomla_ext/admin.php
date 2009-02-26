<?php
/**
 * @package JFusion_Joomla_Ext
 * @author JFusion development team -- Henk Wevers
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the common Joomla JFusion plugin functions
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

/**
 * JFusion Admin Class for an external Joomla database.
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_Joomla_Ext
 */
class JFusionAdmin_joomla_ext extends JFusionAdmin
{
    function getJname()
    {
    	return 'joomla_ext';
    }

    function getTablename()
    {
        return JFusionJplugin::getTablename();
    }

    function getUserList(){
        return JFusionJplugin::getUserList($this->getJname());
    }

    function getUserCount(){
        return JFusionJplugin::getUserCount($this->getJname());
    }

    function getUsergroupList(){
        return JFusionJplugin::getUsergroupList($this->getJname());
    }

    function getDefaultUsergroup(){
        return JFusionJplugin::getDefaultUsergroup($this->getJname());
    }

    function setupFromPath($path)
    {
        return JFusionJplugin::setupFromPath($path);
    }

    function allowRegistration()
    {
        return JFusionJplugin::allowRegistration($this->getJname());
    }
    function debugConfig($jname)
    {
    	//get registration status
		$JFusionPlugin = JFusionFactory::getAdmin($jname);
		$new_registration  = $JFusionPlugin->allowRegistration();

        //get the data about the JFusion plugins
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query );
        $plugin = $db->loadObject();

		//output a warning to the administrator if the allowRegistration setting is wrong
		if ($new_registration && $plugin->slave == '1'){
   			JError::raiseNotice(0, $jname . ': ' . JText::_('DISABLE_REGISTRATION'));
		}
		if (!$new_registration && $plugin->master == '1'){
   			JError::raiseNotice(0, $jname . ': ' . JText::_('ENABLE_REGISTRATION'));
		}
    }
 }