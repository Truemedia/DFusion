<?php defined('_JEXEC') or die('Restricted access'); ?>
<h1>Select Plugin Single</h1>
<form
	action="index.php?option=com_jfusion&task=advancedparamsubmit&tmpl=component"
	method="post" name="adminForm" id="adminForm">
<table class="paramlist admintable" width="100%" cellspacing="1">
	<tbody>
		<tr>
			<td class="paramlist_key">JFusion Plugin</td>
			<td class="paramlist_value"><?php echo $this->output; ?></td>
		</tr>
		<tr style="padding: 0px; margin: 0px;">
			<td colspan="2" style="padding: 0px; margin: 0px;"><?php 
			if ($this->comp && ($params = $this->comp->render('params'))) :
				echo $params;
			endif;
			?></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" value="Speichern" /></td>
		</tr>
	</tbody>
</table>
</form>
