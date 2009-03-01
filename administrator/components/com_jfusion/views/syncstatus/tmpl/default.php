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

//add CSS
?>
<link rel="stylesheet" href="templates/khepri/css/general.css" type="text/css" />
<?php


//check to see if there is anything to output
if (!$this->syncdata['slave_data']){
	echo JText::_('SYNC_NODATA');
    return;
} elseif (!empty($this->syncdata['completed'])) {
	echo '<br/>';
	//check to see if there were any errors
	if (!empty($this->syncdata['errors'])) {
		//redirect to resolve errors
		echo '<h2><a href="index.php?option=com_jfusion&task=syncerror&syncid=' . $this->syncdata['syncid'] . '">' . JText::_('SYNC_CONFLICT') . '</a></h2>';
	} else {
		//inform about the success
		echo '<h2>' . JText::_('SYNC_SUCCESS') . '</h2>';
	}

} else {
	echo '<br/>';
}

?>

<h2><?php echo JText::_('SYNC_STATUS');?></h2>

<table class="adminlist" cellspacing="1"><thead><tr><th width="50px">
<?php echo JText::_('PLUGIN'). ' ' . JText::_('NAME');
?>
</th><th align="center" class="title">
<?php echo JText::_('SYNC_USERS_TODO');
?>
</th><th align="center" class="title">
<?php echo JText::_('USERS') . ' ' . JText::_('UNCHANGED');
?>
</th><th align="center" class="title">
<?php echo JText::_('USERS') . ' ' . JText::_('UPDATED');
?>
</th><th align="center" class="title">
<?php echo JText::_('USERS') . ' ' . JText::_('CREATED');
?>
</th><th align="center" class="title">
<?php echo JText::_('USERS') . ' ' . JText::_('DELETED');
?>
</th><th align="center" class="title">
<?php echo JText::_('USER') . ' ' . JText::_('CONFLICTS');
?>
</th></tr></thead>
<?php
$row_count = 0;

foreach ($this->syncdata['slave_data'] as $slave) {
	echo '<tr class="row' . $row_count .'">';
	if ($row_count == 1){
		$row_count = 0;
	}	else {
		$row_count = 1;
	}
?>
<td><?php echo $slave['jname'];?></td>
<td><?php echo $slave['total'];?></td>
<td><?php echo $slave['unchanged'];?></td>
<td><?php echo $slave['updated'];?></td>
<td><?php echo $slave['created'];?></td>
<td><?php echo $slave['deleted'];?></td>
<td><?php echo $slave['error'];?></td></tr>

<?php } ?>
</table></form>