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

//-->
</script>
<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="" />



<?php echo $this->toolbar; ?>

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'editor.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('PLUGIN_EDITOR'); ?></h2></td></tr></table><br/>

<?php echo $this->parameters; ?>

<input type="hidden" name="jname" value="<?php echo $this->jname; ?>">

</form>

