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

<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/usersync.png" height="75px" width="75px">
<td><h2><? echo JText::_('RESOLVE_CONLFICTS'); ?></h2></td></tr></table><br/>
<br/>


<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="syncerror" />


<select name="default_value" default="0">
<option value="0"><?php echo JText::_('IGNORE')?></option>
<option value="1"><?php echo JText::_('UPDATE'). ' ' . JText::_('MASTER'). ' ' . JText::_('USER')?></option>
<option value="2"><?php echo JText::_('UPDATE'). ' ' . JText::_('SLAVE'). ' ' . JText::_('USER')?></option>
<option value="3"><?php echo JText::_('DELETE'). ' ' . JText::_('MASTER'). ' ' . JText::_('USER')?></option>
<option value="4"><?php echo JText::_('DELETE'). ' ' . JText::_('SLAVE'). ' ' . JText::_('USER')?></option>
</select>

<script language="javascript" type="text/javascript">
<!--
function applyAll() {
var default_value = document.forms['adminForm'].elements['default_value'].selectedIndex;
	for(i=0; i<document.adminForm.elements.length; i++){
		if(document.adminForm.elements[i].type=="select-one"){
			document.adminForm.elements[i].selectedIndex = default_value;
		}
	}

}
//-->
</script>
<a href="javascript:void(0);"  onclick="applyAll();">start</a>



<table class="adminlist" cellspacing="1"><thead><tr>
<th class="title" width="20px"><?php echo JText::_('ID'); ?></th>
<th class="title" align="center"><?php echo JText::_('MASTER'); ?></th>
<th class="title" align="center"><?php echo JText::_('SLAVE'); ?></th>
<th class="title" align="center"><?php echo JText::_('ACTION'); ?></th>
</tr></thead><tbody>

<?php $row_count = 0;

for ($i=0; $i<count($this->syncdata['errors']); $i++) {
$error =  $this->syncdata['errors'][$i];

	echo '<tr class="row' . $row_count .'">';
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
?>
<td><?php echo $i; ?>
<input type="hidden" name="syncerror[<?php echo $i; ?>][master_jname]" value="<?php echo $error['master']['jname']?>" />
<input type="hidden" name="syncerror[<?php echo $i; ?>][slave_jname]" value="<?php echo $error['slave']['jname']?>" />
<input type="hidden" name="syncerror[<?php echo $i; ?>][master_username]" value="<?php echo $error['master']['username']?>" />
<input type="hidden" name="syncerror[<?php echo $i; ?>][slave_username]" value="<?php echo $error['slave']['username']?>" />


</td>
<td><?php echo $error['master']['jname'] . ' ' . JText::_('USERNAME') . ': ' . $error['master']['username'] . JText::_('WITH') . ' ' . JText::_('EMAIL') .': ' . $error['master']['email'] ; ?></td>
<td><?php echo $error['slave']['jname'] . ' ' . JText::_('USERNAME') . ': ' . $error['slave']['username'] . JText::_('WITH') . ' ' . JText::_('EMAIL') .': ' . $error['slave']['email'] ; ?></td>
<td><select name="syncerror[<?php echo $i; ?>][action]" default="0">
<option value="0"><?php echo JText::_('IGNORE')?></option>
<option value="1"><?php echo JText::_('UPDATE'). ' ' . JText::_('MASTER'). ' ' . JText::_('USER')?></option>
<option value="2"><?php echo JText::_('UPDATE'). ' ' . JText::_('SLAVE'). ' ' . JText::_('USER')?></option>
<option value="3"><?php echo JText::_('DELETE'). ' ' . JText::_('MASTER'). ' ' . JText::_('USER')?></option>
<option value="4"><?php echo JText::_('DELETE'). ' ' . JText::_('SLAVE'). ' ' . JText::_('USER')?></option>
</select></td></tr>

<?php } ?>
</table>
The conflict function does not work at the moment. We are working on adding this feature to the JFusion plugins. My apologies for the inconvience.
<input type="submit" value="<? echo JText::_('RESOLVE_CONLFICTS'); ?>">
</form>