<?php defined('_JEXEC') or die('Restricted access'); ?>
<h1>Select Plugin Multi</h1>
<form
	action="index.php?option=com_jfusion&task=advancedparamsubmit&tmpl=component&multiselect=1"
	method="post" name="adminForm" id="adminForm">
<?php 
	if (isset($this->error)) echo $this->error;
?>
<table class="paramlist admintable" width="100%" cellspacing="1">
	<tbody>
		<tr>
			<td class="paramlist_key">JFusion Plugin</td>
			<td class="paramlist_value"><?php echo $this->output; ?></td>
		</tr>
		<tr style="padding: 0px; margin: 0px;">
			<td colspan="2" style="padding: 0px; margin: 0px;"><?php 
			foreach($this->comp as $key => $value) {
				echo '<fieldset class="adminform">';
				echo '<legend>'.$key.'</legend>';
				echo '<table width="100%" class="paramlist admintable" cellspacing="1">';
				echo '<tr><td width="40%" class="paramlist_key">';
				echo '<span class="editlinktip">Title</span></td>';
				echo '<td class="paramlist_value"><input type="text" name="params['.$key.'][title]" value="'.$value['params']->get('title', '').'"/></td>';
				echo '</tr></table>';
				if ($value['params'] && ($params = $value['params']->render('params['.$key.']'))) :
					echo $params;
				endif;
				echo '<input type="button" name="remove" value="Remove" onclick="jPluginRemove(this, \''.$key.'\');" />';
				echo '<input type="hidden" name="params['.$key.'][jfusionplugin]" value="'.
				     $value["jfusionplugin"].'" />';
				echo "</fieldset>";
			}
			?></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" value="Speichern" /></td>
		</tr>
	</tbody>
</table>
<input type="hidden" name="jfusion_task" value="" />
<input type="hidden" name="jfusion_value" value="" />
</form>
