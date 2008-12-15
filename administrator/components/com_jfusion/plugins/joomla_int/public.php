<?php
/**
* @package JFusion_Joomla_Int
* @version 1.0.7
* @author JFusion development team
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
* JFusion plugin class for the internal Joomla database
* @package JFusion_Joomla_Int
*/
class JFusionPublic_joomla_int extends JFusionPublic
{
    function getJname()
    {
        return 'joomla_int';
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