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
 * JFusion Public Class for an external Joomla database
 * For detailed descriptions on these functions please check the model.abstractpublic.php
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
    
    /************************************************
	 * Functions For JFusion Who's Online Module
	 ***********************************************/

	function getOnlineUserQuery()
	{
		return JFusionJplugin::getOnlineUserQuery();
	}

	function getNumberOnlineGuests()
	{
		return JFusionJplugin::getNumberOnlineGuests();
	}

	function getNumberOnlineMembers()
	{
		return JFusionJplugin::getNumberOnlineMembers();
	}    
 }