<?php
/**
* @package JFusion
* @subpackage Views
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

//display the paypal donation button
JFusionFunction::displayDonate();
?>

<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/versioncheck.png" height="75px" width="75px">
<td><h2><?php echo JText::_('VERSION_CHECKER');?></h2></td>
</tr></table><br/>

<style type="text/css">
tr.good0 { background-color: #ecfbf0; }
tr.good1 { background-color: #d9f9e2; }
tr.bad0 { background-color: #f9ded9; }
tr.bad1 { background-color: #f9e5e2; }
table.adminform td {width: 33%;}
</style>

<table class="adminform" cellspacing="1"><thead><tr>
<th class="title" align="left"><?php echo JText::_('SERVER_SOFTWARE'); ?></th>
<th class="title" align="center"><?php echo JText::_('YOUR_VERSION'); ?></th>
<th class="title" align="center"><?php echo JText::_('MINIMUM_VERSION'); ?></th>
</tr></thead><tbody>

<?php
$server_compatible = true;
if (version_compare(phpversion(), $this->JFusionVersion->php[0]->data()) == -1){
	echo '<tr class = "bad0">';
	$server_compatible = false;
} else {
	echo '<tr class = "good0">';
}?>


<td>PHP</td>
<td><?php echo phpversion();?></td>
<td><?php echo $this->JFusionVersion->php[0]->data();?></td></tr>

<?
$version =& new JVersion;
$joomla_version = $version->getShortVersion();
//remove any letters from the version
$joomla_versionclean = ereg_replace("[A-Za-z !]","", $joomla_version);

if (version_compare($joomla_versionclean, $this->JFusionVersion->joomla[0]->data()) == -1){
	echo '<tr class = "bad1">';
	$server_compatible = false;
} else {
	echo '<tr class = "good1">';
}?>

<td>Joomla</td>
<td><?php echo $joomla_version;?></td>
<td><?php echo $this->JFusionVersion->joomla[0]->data();?></td></tr>

<?
$phpinfo = JFusionFunction::phpinfo_array();
$mysql_version = $phpinfo['mysql']['Client API version'];
if (empty($mysql_version) || strpos($mysql_version,'X')){
	//get the version directly from mySQL
	$db = & JFactory::getDBO();
	$query = 'SELECT version();';
	$db->setQuery($query );
	$mysql_version = $db->loadResult();
}

if (version_compare($mysql_version, $this->JFusionVersion->mysql[0]->data()) == -1){
	echo '<tr class = "bad0">';
	$server_compatible = false;
} else {
	echo '<tr class = "good0">';
}?>

<td>MySQL</td>
<td><?php echo $mysql_version;?></td>
<td><?php echo $this->JFusionVersion->mysql[0]->data();?></td></tr>

</table>
<?php
if($server_compatible){
	//output the good news
	?>
<table bgcolor="#d9f9e2" width ="100%"><tr><td>
<img src="components/com_jfusion/images/check_good.png" height="30px" width="30px">
<td><h2><?php echo JText::_('SERVER_UP2DATE'); ?></h2></td><td></td></tr></table>

<?php
} else {
	//output the bad news and automatic upgrade option ?>
<table bgcolor="#f9ded9" width ="100%"><tr><td width="50px"><td>
<img src="components/com_jfusion/images/check_bad.png" height="30px" width="30px">
<td><h2><?php echo JText::_('SERVER_OUTDATED'); ?></h2></td>

<td></td></tr></table>

<?php
}

?>

<br/><br/><table class="adminform" cellspacing="1"><thead><tr>
<th class="title" align="left"><?php echo JText::_('JFUSION_SOFTWARE'); ?></th>
<th class="title" align="center"><?php echo JText::_('YOUR_VERSION'); ?></th>
<th class="title" align="center"><?php echo JText::_('CURRENT_VERSION'); ?></th>
</tr></thead><tbody>

<?php
//check if the JFusion component is installed
$component_xml = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'com_jfusion.xml';
$auth_xml = JPATH_SITE .DS.'plugins'.DS.'authentication'.DS.'jfusion.xml';
$user_xml = JPATH_SITE .DS.'plugins'.DS.'user'.DS.'jfusion.xml';
$search_xml = JPATH_SITE .DS.'plugins'.DS.'search'.DS.'jfusion.xml';
$discussion_xml = JPATH_SITE .DS.'plugins'.DS.'content'.DS.'jfusion.xml';
$activity_xml = JPATH_SITE .DS.'modules'.DS.'mod_jfusion_activity'.DS.'mod_jfusion_activity.xml';
$login_xml = JPATH_SITE .DS.'modules'.DS.'mod_jfusion_login'.DS.'mod_jfusion_login.xml';
$row_count = 0;
$up2date = true;

if (file_exists($component_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($component_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->component[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Component</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->component[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}


if (file_exists($auth_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($auth_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->auth[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Auth Plugin</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->auth[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($user_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($user_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->user[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion User Plugin</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->user[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($search_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($search_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->user[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Search Plugin</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->search[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($discussion_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($discussion_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->user[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Discussion Bot Plugin</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->discussion[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($activity_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($activity_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->activity[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Activity Module</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->activity[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($login_xml)) {
	//get the version number
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($login_xml);
   	$xml    = $parser->document;

	if (version_compare($xml->version[0]->data, $this->JFusionVersion->login[0]->data()) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Login Module</td>
	<td><?php echo $xml->version[0]->data;?></td>
	<td><?php echo $this->JFusionVersion->login[0]->data();?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
echo '</table><br/>';

?>
<table class="adminform" cellspacing="1"><thead><tr>
<th class="title" align="left"><?php echo JText::_('JFUSION_PLUGINS'); ?></th>
<th class="title" align="center"><?php echo JText::_('YOUR_VERSION'); ?></th>
<th class="title" align="center"><?php echo JText::_('CURRENT_VERSION'); ?></th>
</tr></thead><tbody>
<?php
$db = & JFactory::getDBO();
$query = 'SELECT * from #__jfusion';
$db->setQuery($query );
$plugins = $db->loadObjectList();
foreach ($plugins as $plugin) {
	?>
	<?php
    $plugin_xml = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$plugin->name.DS.'jfusion.xml';
    //get the version number
	if(file_exists($plugin_xml) && is_readable($plugin_xml)) {
		$parser = JFactory::getXMLParser('Simple');
    	$xml    = $parser->loadFile($plugin_xml);
   		$xml    = $parser->document;

		if(isset($this->JFusionVersion->{$plugin->name})){
			if (version_compare($xml->version[0]->data, $this->JFusionVersion->{$plugin->name}[0]->data()) == -1){
				echo '<tr class = "bad'.$row_count.'">';
				$up2date = false;
			} else {
				echo '<tr class = "good'.$row_count.'">';
			}
		} else {
			echo '<tr>';
		}
		echo "<td>JFusion {$plugin->name} " . JText::_('PLUGIN') . "</td>";
		echo "<td>{$xml->version[0]->data}</td>\n";
	} else {
		JFusionFunction::raiseWarning(JText::_('ERROR'), JText::_('XML_FILE_MISSING') . ' JFusion ' . $plugin->name . ' ' .JText::_('PLUGIN'), 1);
		echo '<tr class = "bad'.$row_count.'">';
		echo "<td>JFusion {$plugin->name} " . JText::_('PLUGIN') . "</td>";
		echo "<td>".JText::_('UNKNOWN')."</td>\n";
	}
	?>
	<td><?php

	if(isset($this->JFusionVersion->{$plugin->name}))	{
		$plugin_version = $this->JFusionVersion->{$plugin->name};
		if ($plugin_version[0]->data()){
			echo $plugin_version[0]->data();
		} else {
			echo JText::_('UNKNOWN');
		}
	} else {
		echo JText::_('UNKNOWN');
	}

	?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}

echo '</table>';

if($up2date){
	//output the good news
	?>
<table bgcolor="#d9f9e2" width ="100%"><tr><td>
<img src="components/com_jfusion/images/check_good.png" height="30px" width="30px">
<td><h2><?php echo JText::_('JFUSION_UP2DATE'); ?></h2></td><td></td></tr></table>

<?php
} else {
	//output the bad news and automatic upgrade option ?>
<table bgcolor="#f9ded9" width ="100%"><tr><td width="50px"><td>
<img src="components/com_jfusion/images/check_bad.png" height="30px" width="30px">
<td><h2><?php echo JText::_('JFUSION_OUTDATED'); ?></h2></td>

<td>
<script language="javascript" type="text/javascript">
<!--
function confirmSubmit()
{
var agree=confirm("<?php echo JText::_( 'UPGRADE_CONFIRM' ); ?>");
if (agree)
	return true ;
else
	return false ;
}
// -->
</script>

<form enctype="multipart/form-data" action="index.php" method="post" name="adminForm">
	<input type="submit" value="<?php echo JText::_( 'UPGRADE_JFUSION' ); ?>" onCLick="return confirmSubmit();"/>
	<input type="hidden" name="install_url" value="http://jfusion.googlecode.com/svn/branches/jfusion_package.zip" />
	<input type="hidden" name="type" value="" />
	<input type="hidden" name="installtype" value="url" />
	<input type="hidden" name="task" value="doInstall" />
	<input type="hidden" name="option" value="com_installer" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
</td></tr></table>
<br/><br/>

<table bgcolor="#ffffce" width ="100%"><tr><td width="50px"><td>
<img src="components/com_jfusion/images/advanced.png" height="75px" width="75px">
<td><h3><?php echo JText::_('ADVANCED') . ' ' . JText::_('VERSION'). ' ' . JText::_('MANAGEMENT'); ?></h3>
<script language="javascript" type="text/javascript">
<!--
function confirmSubmit2(action)
{
alert(action);
if (action == 'build'){
	var confirm_text = '<?php echo JText::_( 'UPGRADE_CONFIRM' ); ?>';
	var install_url = 'http://jfusion.googlecode.com/svn/trunk/jfusion_package.zip';
} else if (action == 'release'){
	var confirm_text = '<?php echo JText::_( 'UPGRADE_CONFIRM' ); ?>';
	var install_url = 'http://jfusion.googlecode.com/svn/branches/jfusion_package.zip';
} else if (action == 'svn'){
	var confirm_text = '<?php echo JText::_( 'UPGRADE_CONFIRM' ); ?>';
	var install_url = 'http://jfusion.googlecode.com/svn-history/r' + document.adminForm2.svn_build.value + '/trunk/jfusion_package.zip';
}

var agree=confirm(confirm_text);
if (agree){
    document.adminForm2.install_url.value = install_url;
	return true ;
} else {
	return false ;
}
}
// -->
</script>


<form enctype="multipart/form-data" action="index.php" method="post" name="adminForm2">
	<input type="hidden" name="install_url" value="" />
	<input type="hidden" name="type" value="" />
	<input type="hidden" name="installtype" value="url" />
	<input type="hidden" name="task" value="doInstall" />
	<input type="hidden" name="option" value="com_installer" />
	<?php echo JHTML::_( 'form.token' ); ?>
<b><?php echo JText::_('ADVANCED_WARNING');?></b><br/>

<input type="submit" value="<?php echo JText::_('INSTALL') . ' ' . JText::_('LATEST') . ' ' . JText::_('RELEASE') ; ?>" onCLick="return confirmSubmit2('release');"/><br/>
<input type="submit" value="<?php echo JText::_('INSTALL') . ' ' . JText::_('LATEST') . ' SVN Build';?>" onCLick="return confirmSubmit2('build');"/><br/>
SVN build:<input type="text" name="svn_build" size="4"/> <input type="submit" value="<?php echo JText::_('INSTALL') . ' ' . JText::_('SPECIFIC') . ' SVN Build'; ?>" onCLick="return confirmSubmit2('svn');"/><br/>
</td></tr></table>
<br/><br/>


<?php
}

