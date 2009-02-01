<?php

/**
 * @package JFusion_punBB
 * @version 1.0.7
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

require_once( dirname(__FILE__).'/dokuwiki.php');

/**
 * @package JFusion_punBB
 */
class JFusionAuth_dokuwiki extends JFusionAuth {

    function generateEncryptedPassword($userinfo)
    {
        $share = Dokuwiki::getInstance();
        return $share->auth->cryptPassword($userinfo->password_clear);
    }
}
