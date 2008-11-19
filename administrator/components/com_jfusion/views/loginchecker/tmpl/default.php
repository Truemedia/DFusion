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
<input type="hidden" name="task" value="logincheckerresult" />

<?php echo $this->toolbar; ?>


<table><tr><td width="100px">
<img src="<?php echo 'components/com_jfusion/images/jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components/com_jfusion/images/login_checker.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('LOGIN_CHECKER'); ?></h2></td></tr></table><br/>

<font size="2"><?php echo JText::_('LOGIN_CHECKER_TEXT'); ?></font>
<br/><br/>
<table class="adminlist" cellspacing="1"><thead><tr><th colspan="2" class="title" >
<? echo JText::_('LOGIN_CHECKER'); ?>
</th></td></thead><tr><td>
<?php echo JText::_('USERNAME'); ?>
</td><td>
<input type="text" name="check_username">
</td></tr><tr><td>
<?php echo JText::_('PASSWORD'); ?>
</td><td>
<input type="password" name="check_password">
</td></tr></table>
</form>

