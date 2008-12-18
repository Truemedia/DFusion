<?php
/**
* @package JFusion_Joomla_Int
* @author JFusion development team
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
* JFusion Public Class for the internal Joomla database
* For detailed descriptions on these functions please check the model.abstractapublic.php
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