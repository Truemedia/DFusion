<?php

/**
* @package JFusion
* @subpackage Install
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

//find out where we are
$basedir = dirname(__FILE__);

//load language file
$lang =& JFactory::getLanguage();
$lang->load('com_jfusion', JPATH_BASE);


//output some info to the user
?>
<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/manager.png" height="75px" width="75px">
<td><h2><?php echo JText::_('JFUSION') .' '. ' 1.1.2 Pre-Release '. JText::_('INSTALLATION'); ?></h2></td></tr></table>
<h3><?php echo JText::_('STARTING') . ' ' . JText::_('INSTALLATION') . ' ...' ?></h3>

<?php

/**
* @package JFusion
* @subpackage Install
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

//see if we need to create SQL tables
$db =& JFactory::getDBO();
$table_list = $db->getTableList();
$table_prefix = $db->getPrefix();

//create the jfusion table if it does not exist already
if (array_search($table_prefix . 'jfusion',$table_list) == false) {

	$batch_query = "CREATE TABLE #__jfusion (
	  id int(11) NOT NULL auto_increment,
	  name varchar(50) NOT NULL,
	  params text,
	  master tinyint(4) NOT NULL,
	  slave tinyint(4) NOT NULL,
	  status tinyint(4) NOT NULL,
	  dual_login tinyint(4) NOT NULL,
	  check_encryption tinyint(4) NOT NULL,
	  activity tinyint(4) NOT NULL,
	  search tinyint(4) NOT NULL DEFAULT 0,
	  discussion tinyint(4) NOT NULL DEFAULT 0,
	  plugin_files LONGBLOB,
	  original_name varchar(50) NULL,
	  PRIMARY KEY  (id)
	);

	INSERT INTO #__jfusion  (name , params,  slave, dual_login, status, check_encryption, activity, search, discussion) VALUES
	('joomla_int', 0, 0, 0, 3, 0, 0, 0, 0),
	('joomla_ext', 0, 3, 3, 0, 0, 0, 0, 0),
	('vbulletin',  0, 0, 0, 0, 0, 1, 1, 1),
	('phpbb3', 0, 0, 0, 0, 0, 1, 1, 1),
	('dokuwiki', 0, 0, 0, 0, 0, 1, 0, 0),
	('smf', 0, 0, 0, 0, 0, 1, 1, 1),
	('mybb', 0, 0, 0, 0, 0, 1, 0, 0),
	('magento', 0, 0, 0, 0, 0, 3, 0, 0),
	('moodle', 0, 0, 0, 0, 0, 3, 0, 0),
	('gallery2', 0, 0, 0, 0, 0, 1, 1, 0);
	";
	$db->setQuery($batch_query);
	if (!$db->queryBatch()){
		echo $db->stderr() . '<br/>';
	}
} else {
	//list of default plugins
	$defaultPlugins = array('joomla_int','joomla_ext','vbulletin','phpbb3','smf','mybb','magento','moodle','gallery2','dokuwiki');

	//make sure default plugins are installed
	$query = "SELECT name FROM #__jfusion";
	$db->setQuery($query);
	$installedPlugins = $db->loadResultArray();
	$pluginSql = array();
	foreach($defaultPlugins as $plugin){
		if(!in_array($plugin,$installedPlugins)){
			if($plugin=='joomla_int') {
				$pluginSql[] = "('joomla_int', 0, 0,  0, 3,  0, 0, 0, 0)";
			} elseif ($plugin=='joomla_ext') {
				$pluginSql[] = "('joomla_ext',  0, 3, 3, 0,  0, 0, 0, 0)";
			} elseif ($plugin=='vbulletin') {
				$pluginSql[] = "('vbulletin',  0,  0, 0, 0,  0, 1, 1, 1)";
			} elseif ($plugin=='phpbb3') {
				$pluginSql[] = "('phpbb3', 0, 0, 0, 0, 0, 1, 1, 1)";
			} elseif ($plugin=='smf') {
				$pluginSql[] = "('smf', 0, 0, 0, 0,  0, 1, 1, 1)";
			} elseif ($plugin=='mybb') {
				$pluginSql[] = "('mybb', 0,  0, 0, 0,  0, 1, 0, 0)";
			} elseif ($plugin=='magento') {
				$pluginSql[] = "('magento', 0, 0, 0, 0,  0, 3, 0, 0)";
			} elseif ($plugin=='moodle') {
				$pluginSql[] = "('moodle', 0,  0, 0, 0,  0, 3, 0, 0)";
			} elseif ($plugin=='gallery2') {
				$pluginSql[] = "('gallery2', 0, 0, 0, 0, 0, 1, 1, 0)";
			} elseif ($plugin=='dokuwiki') {
				$pluginSql[] = "('dokuwiki', 0, 0, 0, 0, 0, 1, 0, 0)";
			}
		}
	}

	//make sure that the slave capabilties of the joomla_ext plugin is enabled
	$query = 'SELECT slave FROM #__jfusion WHERE name = \'joomla_ext\'';
	$db->setQuery($query);
	if ($db->loadResult() != 3) {
		$query = 'UPDATE #__jfusion SET slave = 3 WHERE name = \'joomla_ext\'';
 		$db->Execute($query);
	}

	//if pre 1.1.0 Beta Patch 2 columns exist, drop them
	//see if the columns exists
	$query = "SHOW COLUMNS FROM #__jfusion";
	$db->setQuery($query);
	$columns = $db->loadResultArray();

	//check to see if the description column exists, if it does remove all pre 1.1.0 Beta Patch 2 columns
	if(in_array('description',$columns)){
		$query = "ALTER TABLE #__jfusion DROP COLUMN version, DROP COLUMN description, DROP COLUMN date, DROP COLUMN author, DROP COLUMN support";
			$db->setQuery($query);
		if(!$db->query()) {
			echo $db->stderr() . '<br/>';
		}
	}

	//add the plugin_files and original columns if it does not exist
	if(!in_array('plugin_files',$columns)){
		//add the column
		$query = "ALTER TABLE #__jfusion
					ADD COLUMN plugin_files LONGBLOB,
					ADD COLUMN original_name varchar(50) NULL";
		$db->setQuery($query);
		if(!$db->query()) {
			echo $db->stderr() . '<br/>';
		}
	}

	//add the search and discussion columns if upgrading from 1.1.1 Beta or earlier
	if(!in_array('search',$columns)) {
		$query = "ALTER TABLE #__jfusion
					ADD COLUMN search tinyint(4) NOT NULL DEFAULT 0,
	  				ADD COLUMN discussion tinyint(4) NOT NULL DEFAULT 0";
		$db->setQuery($query);
		if(!$db->query()) {
			echo $db->stderr() . '<br/>';
		}
	}

	//add the forumid colum to #__jfusion_forum_plugin if upgrading from 1.1.1 Beta or earlier
	$query = "SHOW COLUMNS FROM #__jfusion_forum_plugin";
	$db->setQuery($query);
	$columns = $db->loadResultArray();
	if(!in_array('forumid',$columns)) {
		$query = "ALTER TABLE #__jfusion_forum_plugin
				  ADD COLUMN forumid int(11) NOT NULL";
		$db->setQuery($query);
		if(!$db->query()) {
			echo $db->stderr() . '<br/>';
		}		
	}
	//insert of missing plugins
	//#chris: moved after table modification to prevent errors
	if(count($pluginSql)>0){
		$query = "INSERT INTO #__jfusion  (name, params,  slave, dual_login, status,  check_encryption, activity, search, discussion) VALUES " . implode(', ',$pluginSql);
		$db->setQuery($query);
		if(!$db->query()) {
			echo $db->stderr() . '<br/>';
		}
	}

	//update plugins with search and discuss bot capabilities
	$query = "UPDATE #__jfusion SET search = 1, discussion = 1 WHERE name IN ('vbulletin','phpbb3','smf')";
	$db->setQuery($query);
	if(!$db->query()) {
		echo $db->stderr() . '<br/>';
	}

	//restore deleted plugins if possible and applicable

	//get a list of installed plugins
	$query = "SELECT name, original_name, plugin_files FROM #__jfusion";
	$db->setQuery($query);
	$installedPlugins = $db->loadObjectList();
	//jfusion plugin directory
	$pluginDir = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins';
	//stores the plugins that are to be removed from the database during the upgrade process
	$uninstallPlugin = array();
	//stores the reason why the plugin had to be unsinstalled
	$uninstallReason = array();
	//stores plugin names of plugins that was attempted to be restored
	$restorePlugins = array();
	//require the model.install.php file to recreate copied plugins
	require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'models'.DS. 'model.install.php');
	$model = new JFusionModelInstaller();	
	

	foreach($installedPlugins as $plugin) {
		//attempt to restore missing plugins
		if(!file_exists($pluginDir.DS.$plugin->name)){
			//restore files for custom/copied plugins if available
			$restorePlugins[] = $plugin->name;
			$config =& JFactory::getConfig();
			$tmpDir = $config->getValue('config.tmp_path');

			//check to see if this is a copy of a default plugin
			if(in_array($plugin->original_name,$defaultPlugins)) {
				//recreate the copy and update the database
				if(!$model->copy($plugin->original_name, $plugin->name, true)){
					//the original plugin could not be copied so uninstall the plugin
					$uninstallPlugin[] = $plugin->name;
					$uninstallReason[$plugin->name] = JText::_('UPGRADE_CREATINGCOPY_FAILED');
				}
			} elseif(!empty($plugin->plugin_files)) {
				//save the compressed file to the tmp dir
				$gzfile = $tmpDir.DS.$plugin->name.'.tgz';
				if(@JFile::write($gzfile,$plugin->plugin_files)){
					//decompress the file
					if (!@JArchive::extract($gzfile, $tmpDir)) {
						//decompression failed
						$uninstallPlugin[] = $plugin->name;
						$uninstallReason[$plugin->name] = JText::_('UPGRADE_DECOMPRESS_FAILED');
						//remove the file
						unlink($gzfile);
					} else {
						$tarfile = $tmpDir.DS.$plugin->name.'.tar';						
						if (!@JArchive::extract($tarfile, $pluginDir.DS.$plugin->name)) {
							//decompression failed
							$uninstallPlugin[] = $plugin->name;
							$uninstallReason[$plugin->name] = JText::_('UPGRADE_DECOMPRESS_FAILED');
							//remove the files
							unlink($gzfile);
							unlink($tarfile);						
						} else {
							//extra check to make sure the files were decompressed to prevent possible fatal errors
							if(!file_exists($pluginDir.DS.$plugin->name)) {
								$uninstallPlugin[] = $plugin->name;
								$uninstallReason[$plugin->name] = JText::_('UPGRADE_DECOMPRESS_FAILED');
							}
							//remove the files
							unlink($gzfile);
							unlink($tarfile);
						}
					}
				} else {
					//the compressed file was not able to be written to the tmp dir so remove it
					$uninstallPlugin[] = $plugin->name;
					$uninstallReason[$plugin->name] = JText::_('UPGRADE_WRITEFILE_FAILED');
				}
			} else {
				//the backup file was missing so remove plugin
				$uninstallPlugin[] = $plugin->name;
				$uninstallReason[$plugin->name] = JText::_('UPGRADE_NO_BACKUP');
			}
		}
	}

	//remove bad plugin entries from the table
	if(count($uninstallPlugin) > 0){
		$query = "DELETE FROM #__jfusion WHERE name IN ('" . implode("', '", $uninstallPlugin) . "')";
		$db->setQuery($query);
		if(!$db->query()) {
			echo $db->stderr() . '<br/>';
		}
	}

	foreach($restorePlugins as $plugin) {
		if(!in_array($plugin,$uninstallPlugin)) {?>
		<table bgcolor="#d9f9e2" width ="100%"><tr style="height:30px"><td width="50px">
		<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px"></td>
		<td><font size="2"><b><?php echo JText::_('RESTORED') . ' ' . $plugin . ' ' . JText::_('SUCCESS'); ?></b></font></td></tr></table>
	<?php } else { ?>
		<table bgcolor="#f9ded9" width ="100%"><tr style="height:30px"><td width="50px">
		<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px"></td>
		<td><font size="2"><b><?php echo JText::_('ERROR') . ' ' . JText::_('RESTORING') . ' ' . $plugin . '. ' . JText::_('UPGRADE_CUSTOM_PLUGIN_FAILED') . ': ' . $uninstallReason[$plugin]; ?></b></font></td></tr></table>
	<?php }
	}
}

//create the jfusion_users table if it does not exist already
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

//create the jfusion_user_plugin table if it does not exist already
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

//create the jfusion_forum_plugin table if it does not exist already
if (array_search($table_prefix . '#__jfusion_forum_plugin',$table_list) == false) {
	$query = 'CREATE TABLE IF NOT EXISTS #__jfusion_forum_plugin (
  contentid int(11) NOT NULL,
  forumid int(11) NOT NULL,
  threadid int(11) NOT NULL,
  postid int(11) NOT NULL,
  jname varchar(255) NOT NULL,
  modified int(11) NOT NULL default 0
) CHARSET=utf8;';
	$db->setQuery($query);
	if (!$db->query()){
		echo $db->stderr() . '<br/>';
	}
} else {
	//add the forum id column
	$query = "SHOW COLUMNS FROM #__jfusion_forum_plugin";
	$db->setQuery($query);
	$columns = $db->loadResultArray();

	if(!in_array('forumid',$columns)) {
		$query = "ALTER TABLE #__jfusion_forum_plugin ADD COLUMN forumid int(11) NOT NULL";
		$db->setQuery($query);
		if (!$db->query()){
			echo $db->stderr() . '<br/>';
		}
	}
}


//create the jfusion_sync table if it does not exist already
if (array_search($table_prefix . 'jfusion_sync',$table_list) == false) {
	$query = 'CREATE TABLE #__jfusion_sync (
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

?>

<table bgcolor="#d9f9e2" width ="100%"><tr><td width="50px">
<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px"></td>
<td><font size="2"><b><?php echo JText::_('INSTALLED') . ' ' . JText::_('JFUSION') . ' ' . JText::_('COMPONENT');?> </b></font></td></tr></table>

<?php
//install the JFusion packages
jimport('joomla.installer.helper');
$packages['Login Module'] = $basedir . DS . 'packages' . DS . 'jfusion_mod_login.zip';
$packages['Activity Module'] = $basedir . DS . 'packages' . DS . 'jfusion_mod_activity.zip';
$packages['Whos Online Module'] = $basedir . DS . 'packages' . DS . 'jfusion_mod_whosonline.zip';
$packages['User Plugin'] = $basedir . DS . 'packages' . DS . 'jfusion_plugin_user.zip';
$packages['Authentication Plugin'] = $basedir . DS . 'packages' . DS . 'jfusion_plugin_auth.zip';
$packages['Search Plugin'] = $basedir . DS . 'packages' . DS . 'jfusion_plugin_search.zip';
$packages['Discussion Bot'] = $basedir . DS . 'packages' . DS . 'jfusion_plugin_content.zip';

foreach ($packages as $name => $filename){
	$package = JInstallerHelper::unpack($filename);
	$tmpInstaller = new JInstaller();
	if($tmpInstaller->install($package['dir'])) {?>
		<table bgcolor="#d9f9e2" width ="100%"><tr style="height:30px"><td width="50px">
		<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px"></td>
		<td><font size="2"><b><?php echo JText::_('INSTALLED') . ' ' . JText::_('JFUSION') . ' ' . $name; ?></b></font></td></tr></table>
	<?php } else { ?>
		<table bgcolor="#f9ded9" width ="100%"><tr style="height:30px"><td width="50px">
		<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px"></td>
		<td><font size="2"><b><?php echo JText::_('ERROR') .' ' . JText::_('INSTALLING') . '' . JText::_('JFUSION') . ' ' . $name; ?></b></font></td></tr></table>
	<?php }
	unset ($package, $tmpInstaller);
}

echo '<br/>' . JText::_('INSTALLATION_INSTRUCTIONS') . '<br/><br/>';

//cleanup the packages directory
$package_dir = $basedir . DS . 'packages';
JFolder::delete($package_dir);

//Make sure the status field in jos_jfusion has got either 0 or 1
$query = 'SELECT status FROM #__jfusion WHERE status = 3';
$db->setQuery($query);
if ($db->loadResult()) {
	$query = 'UPDATE #__jfusion SET status = 0 WHERE status <> 3';
	$db->Execute($query);

	$query = 'UPDATE #__jfusion SET status = 1 WHERE status = 3';
	$db->Execute($query);
}