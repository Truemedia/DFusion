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
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'login_checker.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('LOGIN_CHECKER'); ?></h2></td></tr></table><br/>

<br><font size="2">
<?php echo JText::_('LOGIN_CHECKER_TEXT'); ?>
</font><br><br>
<?php echo JText::_('USERNAME'); ?>
<input type="text" name="check_username"><br>
<?php echo JText::_('PASSWORD'); ?>
<input type="password" name="check_password">
</form>

