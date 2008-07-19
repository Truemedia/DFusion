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

if ($this->syncdata['slave_data']){
	echo JText::_('SYNC_STATUS');
	return true;
}

?>

<h2><?php echo JText::_('SYNC_STATUS');?></h2>

<table class="adminlist" cellspacing="1"><thead><tr><th width="50px">
<?php echo JText::_('NAME');
?>
</th><th width="50px">
<?php echo JText::_('TOTAL');
?>
</th><th width="50px">
<?php echo JText::_('CREATED');
?>
</th><th width="200px">
<?php echo JText::_('UPDATED');
?>
</th><th>
<?php echo JText::_('DELETED');
?>
</th><th>
<?php echo JText::_('ERROR');
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