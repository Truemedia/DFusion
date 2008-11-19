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

//see if we need to create SQL tables
$db =& JFactory::getDBO();
$table_list = $db->getTableList();
$table_prefix = $db->getPrefix();

if (array_search($table_prefix . 'jfusion',$table_list) == false) {
$batch_query = "CREATE TABLE #__jfusion (
  id int(11) NOT NULL auto_increment,
  name varchar(50) NOT NULL,
  description varchar(150) NOT NULL,
  version varchar(50),
  date varchar(50),
  author varchar(50),
  support varchar(50),
  params text,
  master tinyint(4) NOT NULL,
  slave tinyint(4) NOT NULL,
  status tinyint(4) NOT NULL,
  dual_login tinyint(4) NOT NULL,
  check_encryption tinyint(4) NOT NULL,
  activity tinyint(4) NOT NULL,
  PRIMARY KEY  (id)
);

INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status, check_encryption, activity)
VALUES ('joomla_int', 'Current Joomla Installation', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/support/',  0, 0,  0, 3,  0, 0);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('joomla_ext', 'External Joomla Installation', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/support/', 0, 3, 3, 0,  0, 0);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('vbulletin', 'vBulletin 3.7.x', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/phpbb3/', 0,  0, 0, 0,  0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('phpbb3', 'phpBB3','1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/support/', 0, 0, 0, 0, 0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('smf', 'SMF 1.1.x', '1.00', '25th May 2008', 'JFusion development team', 'www.jfusion.org/support/', 0, 0, 0, 0,  0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('mybb', 'myBB 1.4.1','1.01','07th September 2008',  'JFusion development team', 'www.jfusion.org/support/',  0,  0, 0, 0,  0, 1);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('magento', 'magento','1.01','07th September 2008',  'JFusion development team', 'www.jfusion.org/support/',  0, 0, 0, 0,  0, 3);
INSERT INTO #__jfusion  (name ,description, version, date, author, support, params,  slave, dual_login, status,  check_encryption, activity)
VALUES ('moodle', 'moodle','1.01','07th September 2008',  'JFusion development team', 'www.jfusion.org/support/',  0,  0, 0, 0,  0, 3);
";
	$db->setQuery($batch_query);
	if (!$db->queryBatch()){
		echo $db->stderr() . '<br/>';
	}
}
if (array_search($table_prefix . 'jfusion_users',$table_list) == false) {
	$query = 'CREATE TABLE #__jfusion_users (
	id int(11) NOT NULL,
	username varchar(50),
	PRIMARY KEY (username)
) DEFAULT CHARACTER SET utf8;';
	$db->setQuery($query);
	if (!$db->query()){
		echo $db->stderr() . '<br/>';
	}
}
if (array_search($table_prefix . 'jfusion_users_plugin',$table_list) == false) {
	$query = 'CREATE TABLE #__jfusion_users_plugin (
	autoid int(11) NOT NULL auto_increment,
	id int(11) NOT NULL,
	username varchar(50),
	userid int(11) NOT NULL,
    jname varchar(50) NOT NULL,
	PRIMARY KEY (autoid)
) DEFAULT CHARACTER SET utf8;';
	$db->setQuery($query);
	if (!$db->query()){
		echo $db->stderr() . '<br/>';
	}
}

if (array_search($table_prefix . 'jfusion_sync',$table_list) == false) {
	$query = 'DROP TABLE IF EXISTS #__jfusion_sync;
CREATE TABLE #__jfusion_sync (
  syncid varchar(10),
  action varchar(255),
  syncdata text,
  time_start int(8),
  time_end int(8),
  PRIMARY KEY  (syncid)
);';
	$db->setQuery($query);
	if (!$db->query()){
		echo $db->stderr() . '<br/>';
	}
}


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
