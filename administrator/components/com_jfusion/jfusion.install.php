<?php

/**
* @package JFusion
* @subpackage Install
* @version 1.0.8
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

//Install Package Manager
jimport('joomla.installer.helper');
$basedir = dirname(__FILE__);

//set the filenames
$module_login = $basedir . DS . 'packages' . DS . 'jfusion_mod_login.zip';
$module_activity = $basedir . DS . 'packages' . DS . 'jfusion_mod_activity.zip';
$plugin_user = $basedir . DS . 'packages' . DS . 'jfusion_plugin_user.zip';
$plugin_auth = $basedir . DS . 'packages' . DS . 'jfusion_plugin_auth.zip';

echo 'Installing or Upgrading JFusion Login Module <br />';
$package = JInstallerHelper::unpack($module_login);
$tmpInstaller = new JInstaller();
if(!$tmpInstaller->install($package['dir'])) {
	JError::raiseWarning(100,JText::_('Automated').' '.JText::_('Install').': '.JText::_('There was an error installing an extension:') . basename($file));
}

echo 'Installing or Upgrading JFusion Activity Module <br />';
$package = JInstallerHelper::unpack($module_activity);
$tmpInstaller = new JInstaller();
if(!$tmpInstaller->install($package['dir'])) {
	JError::raiseWarning(100,JText::_('Automated').' '.JText::_('Install').': '.JText::_('There was an error installing an extension:') . basename($file));
}

echo 'Installing or Upgrading JFusion Authentication Plugin <br />';
$package = JInstallerHelper::unpack($plugin_auth);
$tmpInstaller = new JInstaller();
if(!$tmpInstaller->install($package['dir'])) {
	JError::raiseWarning(100,JText::_('Automated').' '.JText::_('Install').': '.JText::_('There was an error installing an extension:') . basename($file));
}

echo 'Installing or Upgrading JFusion User Plugin <br />';
$package = JInstallerHelper::unpack($plugin_user);
$tmpInstaller = new JInstaller();
if(!$tmpInstaller->install($package['dir'])) {
	JError::raiseWarning(100,JText::_('Automated').' '.JText::_('Install').': '.JText::_('There was an error installing an extension:') . basename($file));
}
