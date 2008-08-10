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
?>

<table class="adminform"><tr>
<td><a href="index.php?option=com_jfusion&task=syncmaster"><?php echo JText::_('SYNC_MASTER');?></a></td>
<td><a href="index.php?option=com_jfusion&task=syncslave"><?php echo JText::_('SYNC_SLAVE');?></a></td>
<td><a href="index.php?option=com_jfusion&task=synchistory"><?php echo JText::_('SYNC_HISTORY');?></a></td>
</tr></table></br><br/>

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'usersync.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('RESOLVE_CONLFICTS'); ?></h2></td></tr></table><br/>
<br/>

<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="syncerror" />

<table class="adminlist" cellspacing="1"><thead><tr>
<th class="title" width="20px"><?php echo JText::_('ID'); ?></th>
<th class="title" align="center"><?php echo JText::_('MASTER'); ?></th>
<th class="title" align="center"><?php echo JText::_('SLAVE'); ?></th>
<th class="title" align="center"><?php echo JText::_('IGNORE'); ?></th>
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
<td><input type="radio" name="syncerror[<?php echo $i; ?>][action]" value="master"><?php echo JText::_('DELETE') . ' ' . $error['master']['jname'] . ' ' . JText::_('USERNAME') . ': ' . $error['master']['username'] . JText::_('WITH') . ' ' . JText::_('EMAIL') .': ' . $error['master']['email'] ; ?></td>
<td><input type="radio" name="syncerror[<?php echo $i; ?>][action]" value="slave"><?php echo JText::_('DELETE') . ' ' . $error['slave']['jname'] . ' ' . JText::_('USERNAME') . ': ' . $error['slave']['username'] . JText::_('WITH') . ' ' . JText::_('EMAIL') .': ' . $error['slave']['email'] ; ?></td>
<td><input type="radio" name="syncerror[<?php echo $i; ?>][action]" value="ignore" checked="checked"><?php echo JText::_('IGNORE') . ' ' . JText::_('USERSYNC') . ' ' . JText::_('ERROR'); ?></td>
</tr>
<?php } ?>
</table>
The conflict function does not work correctly for email conflicts. I am working on a fix. My apologies for the inconvience.
<input type="submit" value="<? echo JText::_('RESOLVE_CONLFICTS'); ?>">
</form>