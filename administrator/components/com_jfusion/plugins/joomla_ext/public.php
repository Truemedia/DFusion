<?php
/**
 * @package JFusion_Joomla_Ext
 * @version 1.1.0-001
 * @author JFusion development team -- Henk Wevers
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

/**
 * JFusion plugin class for an external Joomla database
 * @package JFusion_Joomla_Ext
 */
class JFusionPublic_joomla_ext extends JFusionPublic
{
    function getJname()
    {
    	return 'joomla_ext';
    }

    function getRegistrationURL()
    {
        return JFusionJplugin::getRegistrationURL();
    }

    function getLostPasswordURL()
    {
        return JFusionJplugin::getLostPasswordURL();
    }

    function getLostUsernameURL()
    {
        return JFusionJplugin::getLostUsernameURL();
    }
 }