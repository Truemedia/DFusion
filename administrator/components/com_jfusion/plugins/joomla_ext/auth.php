<?php

/**
 * @package JFusion_Joomla_Ext
 * @version 1.1.0-b001
 * @author JFusion development team
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the Abstract Auth Class
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractauth.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php'); 
/**
 * @package JFusion_Joomla_Ext
 */
class JFusionAuth_joomla_ext extends JFusionAuth{

    function generateEncryptedPassword($userinfo)
    {
       return  JFusionJplugin::generateEncryptedPassword($userinfo);
    }

}
