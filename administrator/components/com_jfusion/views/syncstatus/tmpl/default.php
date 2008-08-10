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

//check to see if there is anything to output
if (!$this->syncdata['slave_data']){
	echo JText::_('SYNC_NODATA');
	return true;
} elseif ($this->syncdata['completed']) {
	//check to see if there were any errors
	if ($this->syncdata['errors']) {
		//redirect to resolve errors
		echo '<h2><a href="index.php?option=com_jfusion&task=syncerror&syncid=' . $this->syncdata['syncid'] . '">' . JText::_('SYNC_CONFLICT') . '</a></h2>';
	} else {
		//inform about the success
		echo '<h2>' . JText::_('SYNC_SUCCESS') . '</h2>';
	}

}

?>

<h2><?php echo JText::_('SYNC_STATUS');?></h2>

<table class="adminlist" cellspacing="1"><thead><tr><th width="50px">
<?php echo JText::_('PLUGIN'). ' ' . JText::_('NAME');
?>
</th><th>
<?php echo JText::_('SYNC_USERS_TODO');
?>
</th><th>
<?php echo JText::_('USERS') . ' ' . JText::_('CREATED');
?>
</th><th>
<?php echo JText::_('USERS') . ' ' . JText::_('UPDATED');
?>
</th><th>
<?php echo JText::_('USERS') . ' ' . JText::_('DELETED');
?>
</th><th>
<?php echo JText::_('USER') . ' ' . JText::_('CONFLICTS');
?>
</th></tr></thead>

<?php foreach ($this->syncdata['slave_data'] as $slave) {
?>
<tr><td>
<?php echo $slave['jname'];?>
</td><td>
<?php echo $slave['total'];?>
</td><td>
<?php echo $slave['created'];?>
</td><td>
<?php echo $slave['updated'];?>
</td><td>
<?php echo $slave['deleted'];?>
</td><td>
<?php echo $slave['error'];?>
</td></tr>

<?php } ?>

</table></form>

