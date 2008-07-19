<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access'); ?>

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
<input type="hidden" name="task" value="wizardresult" />

<?php echo $this->toolbar; ?>

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'wizard.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('SETUP_WIZARD'); ?></h2></td></tr></table>

<br/><br/><font size="2">
<?php echo JText::_('WIZARD_INSTR'); ?>
</font><br><br><br><table width="100%" class="paramlist admintable" cellspacing="1">
<tr width="100%"><td class="paramlist_key">
<?php echo JText::_('WIZARD_PATH'); ?>
</td><td class="paramlist_value"><input type="text" name="params[source_path]" id="paramssource_path" value="<?php echo JPATH_ROOT; ?>" class="text_area" size="50" /></td></tr></table>
<br><font size="2">
<?php echo JText::_('WIZARD_INSTR2'); ?>
</font><br><input type=hidden name=jname value="<?php echo $this->jname; ?>"></form>
