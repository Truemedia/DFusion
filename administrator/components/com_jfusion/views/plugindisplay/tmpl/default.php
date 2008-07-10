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
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'controlpanel.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('PLUGIN_CONFIGURATION'); ?></h2></td></tr></table><br/>
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
	?>
<td><?php echo $record->id; ?></td>
<td><INPUT TYPE=RADIO NAME="jname" VALUE="<?php echo $record->name; ?>"><?php echo $record->name; ?></td>
<td><?php echo $record->description; ?></td>

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



//prepare an array of status messages
$status = array(JText::_('NO_CONFIG'), JText::_('NO_DATABASE'), JText::_('NO_TABLE'), JText::_('GOOD_CONFIG'));

//check to see what the config status is
if ($record->status == '3') { ?>
<td><img src="images/tick.png" border="0" alt="Good Config" /><?php echo $status[$record->status]; ?></td>
<?php } else { ?>
<td><img src="images/publish_x.png" border="0" alt="Wrong Config" /><?php echo $status[$record->status]; ?></td>
<?php }

//if a plugin is properly configured
if ($record->status == '3') {
//output the total number of users for the plugin
$JFusionPlugin = NULL;
$total_users = NULL;
$JFusionPlugin = JFusionFactory::getPlugin($record->name);
$total_users = $JFusionPlugin->getUserCount();

echo "<td> $total_users </td>";
} else {
echo "<td></td>";
}

//if a plugin is properly configured
if ($record->status == '3') {
//check to see if new user registration is allowed
$new_registration = NULL;
$new_registration  = $JFusionPlugin->allowRegistration();

if ($new_registration) { ?>
<td><img src="images/tick.png" border="0" alt="Enabled" /><?php echo JText::_('ENABLED'); ?></td>
<?php } else { ?>
<td><img src="images/publish_x.png" border="0" alt="Disabled" /><?php echo JText::_('DISABLED'); ?></td>
<?php }
} else {
	echo "<td></td>";
}

//output a warning to the administrator if the allowRegistration setting is wrong
if ($new_registration && $record->slave == '1'){
   JError::raiseWarning(0, $record->name . ' ' . JText::_('DISABLE_REGISTRATION'));
}

if (!$new_registration && $record->master == '1'){
   JError::raiseWarning(0, $record->name . ' ' . JText::_('ENABLE_REGISTRATION'));
}


//if a plugin is properly configured
if ($record->status == '3') {
//display the default usergroup
$usergroup = $JFusionPlugin->getDefaultUsergroup();
if ($usergroup) {
	echo "<td>$usergroup</td></tr>";
} else {
	echo '<td><img src="images/publish_x.png" border="0" alt="Disabled" />' . JText::_('MISSING'). ' ' .JText::_('DEFAULT_USERGROUP'). '</td></tr>';
    JError::raiseWarning(0, $record->name . ': ' . JText::_('MISSING'). ' '. JText::_('DEFAULT_USERGROUP'));
}
} else {
echo "<td></td></tr>";
}

}
?>

</tbody></table>

<input type="hidden" name="field_name" value=""><input type="hidden" name="field_value" value=""></form>

<table width="100%"><tr><td><img src="images/tick.png" border="0" alt="Enabled" /> = <?php echo JText::_('ENABLED'); ?> </td>
<td><img src="images/publish_x.png" border="0" alt="Disabled" /> = <?php echo JText::_('DISABLED'); ?> </td>
<td><img src="images/checked_out.png" border="0" alt="Unavailable" /> = <?php echo JText::_('UNAVAILABLE'); ?> </td>
</tr></table></br>

<table class="adminlist" cellspacing="1"><thead><tr><th class="title" >
<?php echo JText::_('LEGEND'); ?>
</th></td></thead><tr><td>
<?php echo JText::_('LEGEND_MASTER'); ?>
</td><td>
<?php echo JText::_('LEGEND_SLAVE'); ?>
</td><td>
<?php echo JText::_('LEGEND_CHECK_ENCRYPTION'); ?>
</td><td>
<?php echo JText::_('LEGEND_DUAL_LOGIN'); ?>
</td><td>
<?php echo JText::_('LEGEND_STATUS'); ?>
</td><td>
<?php echo JText::_('LEGEND_USERS'); ?>
</td><td>
<?php echo JText::_('LEGEND_REGISTRATION'); ?>
</td><td>
<?php echo JText::_('LEGEND_DEFAULT_USERGROUP'); ?>
</td></tr></table>


