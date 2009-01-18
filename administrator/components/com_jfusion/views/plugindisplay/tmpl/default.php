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
<style type="text/css"> .icon-32-addusers{
background-image: url(templates/khepri/images/toolbar/icon-32-adduser.png);
background-repeat: no-repeat;
} </style>

<script language="javascript" type="text/javascript">
<!--
function submitbutton(pressbutton) {
var form = document.adminForm;
submitform(pressbutton);
return;
}

function setCheckedValue(radioObj, newValue) {
if(!radioObj)
return;
var radioLength = radioObj.length;
if(radioLength == undefined) {
radioObj.checked = (radioObj.value == newValue.toString());
return;
}
for(var i = 0; i < radioLength; i++) {
radioObj[i].checked = false;
if(radioObj[i].value == newValue.toString()) {
radioObj[i].checked = true;
}
}
}

//-->
</script>
<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="" />



<?php echo $this->toolbar; ?>
<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/controlpanel.png" height="75px" width="75px">
<td><h2><?php echo JText::_('PLUGIN_CONFIGURATION'); ?></h2></td></tr></table><br/>

<table class="adminlist" cellspacing="1"><thead><tr>
<th class="title" width="20px"><?php echo JText::_('ID'); ?></th>
<th class="title" align="center"><?php echo JText::_('NAME'); ?></th>
<th class="title" align="center"><?php echo JText::_('DESCRIPTION'); ?></th>
<th class="title" width="40px" align="center"><?php echo JText::_('MASTER'); ?></th>
<th class="title" width="40px" align="center"><?php echo JText::_('SLAVE'); ?></th>
<th class="title" width="40px" align="center"><?php echo JText::_('CHECK_ENCRYPTION'); ?></th>
<th class="title" width="40px" align="center"><?php echo JText::_('DUAL_LOGIN'); ?></th>
<th class="title" align="center"><?php echo JText::_('STATUS'); ?></th>
<th class="title" align="center"><?php echo JText::_('USERS'); ?></th>
<th class="title" align="center"><?php echo JText::_('REGISTRATION'); ?></th>
<th class="title" align="center"><?php echo JText::_('DEFAULT_USERGROUP'); ?></th>
</tr></thead><tbody>

<?php $row_count = 0;
foreach ($this->rows as $record) {
echo '<tr class="row' . $row_count .'">';
if ($row_count == 1){
	$row_count = 0;
}	else {
	$row_count = 1;
}

$JFusionPlugin = NULL;
$JFusionPlugin = JFusionFactory::getAdmin($record->name);

	?>
<td><?php echo $record->id; ?></td>
<td><INPUT TYPE=RADIO NAME="jname" VALUE="<?php echo $record->name; ?>"><?php echo $record->name; ?></td>
<td><?php $JFusionParam = JFusionFactory::getParams($record->name);
$description = $JFusionParam->get('description');
if($description){
	echo $description;
} else {
	$plugin_xml = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$record->name.DS.'jfusion.xml';
	if(file_exists($plugin_xml) && is_readable($plugin_xml)) {
		$xml = simplexml_load_file($plugin_xml);
		echo $xml->description;
	} else {
		echo "";
	}
}?></td>

<?php //check to see if module is a master
if ($record->master =='1') {?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'master'; document.adminForm.field_value.value = '0';submitbutton('changesetting')" title="Disable Plugin"><img src="images/tick.png" border="0" alt="Enabled" /></a></td>
<?php } elseif ($record->master =='0') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'master'; document.adminForm.field_value.value = '1';submitbutton('changesetting')" title="Enable Plugin"><img src="images/publish_x.png" border="0" alt="Disabled" /></a></td>
<?php } else { ?>
<td><img src="images/checked_out.png" border="0" alt="Unavailable" /></td>
<?php }

//check to see if module is s slave
if ($record->slave =='1') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'slave'; document.adminForm.field_value.value = '0';submitbutton('changesetting')" title="Disable Plugin"><img src="images/tick.png" border="0" alt="Enabled" /></a></td>
<?php } elseif ($record->slave =='0') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'slave'; document.adminForm.field_value.value = '1';submitbutton('changesetting')" title="Enable Plugin"><img src="images/publish_x.png" border="0" alt="Disabled" /></a></td>
<?php } else { ?>
<td><img src="images/checked_out.png" border="0" alt="Unavailable" /></td>
<?php }

//check to see if password encryption is enabled
if ($record->check_encryption == '1') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'check_encryption'; document.adminForm.field_value.value = '0';submitbutton('changesetting')" title="Disable Encryption"><img src="images/tick.png" border="0" alt="Enabled" /></a></td>
<?php } elseif ($record->check_encryption == '0') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'check_encryption'; document.adminForm.field_value.value = '1';submitbutton('changesetting')" title="Enable Encryption"><img src="images/publish_x.png" border="0" alt="Disabled" /></a></td>
<?php } else { ?>
<td><img src="images/checked_out.png" border="0" alt="Unavailable" /></td>
<?php }

//check to see if dual login is enabled
if ($record->dual_login =='1') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'dual_login'; document.adminForm.field_value.value = '0';submitbutton('changesetting')" title="Disable Dual Login"><img src="images/tick.png" border="0" alt="Enabled" /></a></td>
<?php } elseif ($record->dual_login =='0') { ?>
<td><a href="javascript:void(0);" onclick="setCheckedValue(document.adminForm.jname, '<?php echo $record->name; ?>'); document.adminForm.field_name.value = 'dual_login'; document.adminForm.field_value.value = '1';submitbutton('changesetting')" title="Enable Dual Login"><img src="images/publish_x.png" border="0" alt="Disabled" /></a></td>
<?php } else { ?>
<td><img src="images/checked_out.png" border="0" alt="Unavailable" /></td>
<?php }


//added check for database configuration to prevent error after moving sites
if ($record->slave == 1 || $record->master == 1) {
	$config_status =  $JFusionPlugin->checkConfig($record->name);

	//do a check to see if the status field is correct
	if ($config_status['config'] != $record->status){
	    //Save this error for the integration
        $jdb =& JFactory::getDBO();
        $query = 'UPDATE #__jfusion SET status = '. $config_status['config']. ' WHERE name =' . $jdb->quote($record->name);
        $jdb->setQuery($query );
        $jdb->query();
	}
} else {
	$config_status = array();
	$config_status['config'] = 0;
	$config_status['message'] = JText::_('NO_CONFIG');
}

//check to see what the config status is
if ($config_status['config'] == 1) {

echo '<td><img src="images/tick.png" border="0" alt="Good Config" />' . $config_status['message'] .'</td>';

//output the total number of users for the plugin
$total_users = NULL;
$total_users = $JFusionPlugin->getUserCount();

echo '<td>' .$total_users . '</td>';

//check to see if new user registration is allowed
$new_registration = NULL;
$new_registration  = $JFusionPlugin->allowRegistration();

if ($new_registration) {
echo '<td><img src="images/tick.png" border="0" alt="Enabled" />' . JText::_('ENABLED') . '</td>';
} else {
echo '<td><img src="images/publish_x.png" border="0" alt="Disabled" />' .JText::_('DISABLED') . '</td>';
}

//output a warning to the administrator if the allowRegistration setting is wrong
if ($new_registration && $record->slave == '1'){
   JError::raiseWarning(0, $record->name . ' ' . JText::_('DISABLE_REGISTRATION'));
}

if (!$new_registration && $record->master == '1'){
   JError::raiseWarning(0, $record->name . ' ' . JText::_('ENABLE_REGISTRATION'));
}

//display the default usergroup
$usergroup = $JFusionPlugin->getDefaultUsergroup();
if ($usergroup) {
	echo "<td>$usergroup</td></tr>";
} else {
	echo '<td><img src="images/publish_x.png" border="0" alt="Disabled" />' . JText::_('MISSING'). ' ' .JText::_('DEFAULT_USERGROUP'). '</td></tr>';
    JError::raiseWarning(0, $record->name . ': ' . JText::_('MISSING'). ' '. JText::_('DEFAULT_USERGROUP'));
}

} else {
if (!empty($record->status)){
	echo '<td><img src="images/tick.png" border="0" alt="Good Config" />' .JText::_('GOOD_CONFIG') . '</td>';
} else {
	echo '<td><img src="images/publish_x.png" border="0" alt="Wrong Config" />' .JText::_('NO_CONFIG') . '</td>';
}

echo '<td></td><td></td><td></td></tr>';
}

}

?>
</tbody></table><br/><br/>

<?php echo JText::_('PLUGIN_CONFIG_INSTR'); ?>

<input type="hidden" name="field_name" value=""><input type="hidden" name="field_value" value=""></form>

<table width="100%"><tr><td><img src="images/tick.png" border="0" alt="Enabled" /> = <?php echo JText::_('ENABLED'); ?> </td>
<td><img src="images/publish_x.png" border="0" alt="Disabled" /> = <?php echo JText::_('DISABLED'); ?> </td>
<td><img src="images/checked_out.png" border="0" alt="Unavailable" /> = <?php echo JText::_('UNAVAILABLE'); ?> </td>
</tr></table></br>
<br/><br/><br/>
<table class="adminlist" cellspacing="1"><thead><tr><th colspan="2" class="title" >
<?php echo JText::_('LEGEND'); ?>
</th></td></thead><tr><td>
<?php echo JText::_('MASTER'); ?>
</td><td>
<?php echo JText::_('LEGEND_MASTER'); ?>
</td></tr><tr><td>
<?php echo JText::_('SLAVE'); ?>
</td><td>
<?php echo JText::_('LEGEND_SLAVE'); ?>
</td></tr><tr><td>
<?php echo JText::_('CHECK_ENCRYPTION'); ?>
</td><td>
<?php echo JText::_('LEGEND_CHECK_ENCRYPTION'); ?>
</td></tr><tr><td>
<?php echo JText::_('DUAL_LOGIN'); ?>
</td><td>
<?php echo JText::_('LEGEND_DUAL_LOGIN'); ?>
</td></tr><tr><td>
<?php echo JText::_('STATUS'); ?>
</td><td>
<?php echo JText::_('LEGEND_STATUS'); ?>
</td></tr><tr><td>
<?php echo JText::_('USERS'); ?>
</td><td>
<?php echo JText::_('LEGEND_USERS'); ?>
</td></tr><tr><td>
<?php echo JText::_('REGISTRATION'); ?>
</td><td>
<?php echo JText::_('LEGEND_REGISTRATION'); ?>
</td></tr><tr><td>
<?php echo JText::_('DEFAULT_USERGROUP'); ?>
</td><td>
<?php echo JText::_('LEGEND_DEFAULT_USERGROUP'); ?>
</td></tr></table>


