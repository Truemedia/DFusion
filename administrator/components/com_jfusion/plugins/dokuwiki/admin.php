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
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');

require_once( dirname(__FILE__).'/dokuwiki.php');

/**
 * JFusion plugin class for punBB
 * @package JFusion_punBB
 */
class JFusionAdmin_dokuwiki extends JFusionAdmin {

    function getJname()
    {
        return 'dokuwiki';
    }

    function checkConfig($jname)
    {
        $status = array();

		$params = JFusionFactory::getParams($this->getJname());
		$share = Dokuwiki::getInstance();

        $source_path = $params->get('source_path');
		$config = $share->getConf($source_path);
        if (is_array($config)) {
            $status['config'] = 1;
			$status['message'] = JText::_('GOOD_CONFIG');
            return $status;
        } else {
            $status['config'] = 0;
			$status['message'] = JText::_('WIZARD_FAILURE');
            return $status;
        }
    }

    function setupFromPath($Path)
    {
        $share = Dokuwiki::getInstance();
         //try to open the file
         if ($config = $share->getConf($Path) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $Path " . JText::_('WIZARD_MANUAL'));
            return false;
         } else {
            $params = array();
            $params['cookie_name'] = $config['cookie_name'];
			$params['cookie_path'] = $config['cookie_path'];
			$params['cookie_domain'] = $config['cookie_domain'];
			$params['cookie_seed'] = $config['cookie_seed'];
			$params['cookie_secure'] = $config['cookie_secure'];
            $params['source_path'] = $Path;
            return $params;
        }
    }

    function getAvatar($userid)
    {
        return 0;
    }

    function getUserList()
    {
        $share = Dokuwiki::getInstance();
        $conf = $share->getConf();
        return $share->getUserList();
	}

    function getUserCount()
    {
        $share = Dokuwiki::getInstance();
        $userlist = $this->getUserList();
        return $share->auth->getUserCount();
    }

    function getUsergroupList()
    {
        $default_group = new stdClass;
        $default_group->name = $default_group->id = JFusionAdmin_dokuwiki::getDefaultUsergroup();
        $UsergroupList[] = $default_group;
        return $UsergroupList;
    }

    function getDefaultUsergroup()
    {
        $share = Dokuwiki::getInstance();
        return $share->getDefaultUsergroup();
    }

    function allowRegistration()
    {
        $share = Dokuwiki::getInstance();
        $conf = $share->getConf();
        if (strpos($conf['disableactions'], 'register') !== false) {
            return false;
        } else {
            return true;
        }
    }
}
