<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

/**
* Load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
JFusionFunction::displayDonate();
?>

<style type="text/css">
tr.good0 { background-color: #a6fda6; }
tr.good1 { background-color: #8bfb8b; }
tr.bad0 { background-color: #f9aaaa; }
tr.bad1 { background-color: #fb8b8b; }
</style>



<table class="adminform" cellspacing="1"><thead><tr>
<th class="title" align="left"><?php echo JText::_('SERVER_SOFTWARE'); ?></th>
<th class="title" align="center"><?php echo JText::_('YOUR_VERSION'); ?></th>
<th class="title" align="center"><?php echo JText::_('MINIMUM_VERSION'); ?></th>
</tr></thead><tbody>

<?php
$server_compatible = true;
if (version_compare(phpversion(), $this->JFusionVersion->php) == -1){
	echo '<tr class = "bad0">';
	$server_compatible = false;
} else {
	echo '<tr class = "good0">';
}?>


<td>PHP</td>
<td><?php echo phpversion();?></td>
<td><?php echo $this->JFusionVersion->php;?></td></tr>

<?
$version =& new JVersion;
$joomla_version = $version->getShortVersion();
if (version_compare($joomla_version, $this->JFusionVersion->joomla) == -1){
	echo '<tr class = "bad1">';
	$server_compatible = false;
} else {
	echo '<tr class = "good1">';
}?>

<td>Joomla</td>
<td><?php echo $joomla_version;?></td>
<td><?php echo $this->JFusionVersion->joomla;?></td></tr>

<?
$phpinfo = JFusionFunction::phpinfo_array();
if (version_compare($phpinfo['mysql']['Client API version'], $this->JFusionVersion->mysql) == -1){
	echo '<tr class = "bad0">';
	$server_compatible = false;
} else {
	echo '<tr class = "good0">';
}?>

<td>MySQL</td>
<td><?php echo $phpinfo['mysql']['Client API version'];?></td>
<td><?php echo $this->JFusionVersion->mysql;?></td></tr>

</table>
<?php
if($server_compatible){
	//output the good news
	?>
<table bgcolor="#d9f9e2" width ="100%"><tr><td>
<img src="<?php echo 'components/com_jfusion/images/check_good.png'; ?>" height="30px" width="30px">
<td><h2><? echo JText::_('SERVER_UP2DATE'); ?></h2></td><td></td></tr></table>

<?php
} else {
	//output the bad news and automatic upgrade option ?>
<table bgcolor="#f9ded9" width ="100%"><tr><td width="50px"><td>
<img src="<?php echo 'components/com_jfusion/images/check_bad.png'; ?>" height="30px" width="30px">
<td><h2><? echo JText::_('SERVER_OUTDATED'); ?></h2></td>

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
$activity_xml = JPATH_SITE .DS.'modules'.DS.'mod_jfusion_activity'.DS.'mod_jfusion_activity.xml';
$login_xml = JPATH_SITE .DS.'modules'.DS.'mod_jfusion_login'.DS.'mod_jfusion_login.xml';
$row_count = 0;
$up2date = true;

if (file_exists($component_xml)) {
	//get the version number
	$xml = simplexml_load_file($component_xml);
	if (version_compare($xml->version, $this->JFusionVersion->component) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Component</td>
	<td><?php echo $xml->version;?></td>
	<td><?php echo $this->JFusionVersion->component;?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}


if (file_exists($auth_xml)) {
	//get the version number
	$xml = simplexml_load_file($auth_xml);
	if (version_compare($xml->version, $this->JFusionVersion->auth) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Auth Plugin</td>
	<td><?php echo $xml->version;?></td>
	<td><?php echo $this->JFusionVersion->auth;?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($user_xml)) {
	//get the version number
	$xml = simplexml_load_file($user_xml);
	if (version_compare($xml->version, $this->JFusionVersion->user) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion User Plugin</td>
	<td><?php echo $xml->version;?></td>
	<td><?php echo $this->JFusionVersion->user;?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($activity_xml)) {
	//get the version number
	$xml = simplexml_load_file($activity_xml);
	if (version_compare($xml->version, $this->JFusionVersion->activity) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Activity Module</td>
	<td><?php echo $xml->version;?></td>
	<td><?php echo $this->JFusionVersion->activity;?></td></tr><?php
	unset($xml);
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
}
if (file_exists($login_xml)) {
	//get the version number
	$xml = simplexml_load_file($login_xml);
	if (version_compare($xml->version, $this->JFusionVersion->login) == -1){
		echo '<tr class = "bad'.$row_count.'">';
		$up2date = false;
	} else {
		echo '<tr class = "good'.$row_count.'">';
	}	?>
	<td>JFusion Login Module</td>
	<td><?php echo $xml->version;?></td>
	<td><?php echo $this->JFusionVersion->login;?></td></tr><?php
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

    $plugin_xml = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$plugin->name.DS.'jfusion.xml';
	//get the version number
	$xml = simplexml_load_file($plugin_xml);
	if($this->JFusionVersion->{$plugin->name}){
		if (version_compare($xml->version, $this->JFusionVersion->{$plugin->name}) == -1){
			echo '<tr class = "bad'.$row_count.'">';
			$up2date = false;
		} else {
			echo '<tr class = "good'.$row_count.'">';
		}
	} else {
		echo '<tr>';
	}
	?>
	<td>JFusion <?echo $plugin->name;?> Plugin</td>
	<td><?php echo $xml->version;?></td>
	<td><?php if ($this->JFusionVersion->{$plugin->name}){
		echo $this->JFusionVersion->{$plugin->name};
	} else {
		echo 'unknown';
	}?></td></tr><?php
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
<img src="<?php echo 'components/com_jfusion/images/check_good.png'; ?>" height="30px" width="30px">
<td><h2><? echo JText::_('JFUSION_UP2DATE'); ?></h2></td><td></td></tr></table>

<?php
} else {
	//output the bad news and automatic upgrade option ?>
<table bgcolor="#f9ded9" width ="100%"><tr><td width="50px"><td>
<img src="<?php echo 'components/com_jfusion/images/check_bad.png'; ?>" height="30px" width="30px">
<td><h2><? echo JText::_('JFUSION_OUTDATED'); ?></h2></td>

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
	<input type="hidden" name="install_url" value="http://www.jfusion.org/jfusion_latest_package.zip" />
	<input type="hidden" name="type" value="" />
	<input type="hidden" name="installtype" value="url" />
	<input type="hidden" name="task" value="doInstall" />
	<input type="hidden" name="option" value="com_installer" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
</td></tr></table>

<?php
}

