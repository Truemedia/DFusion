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

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'usersync.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('RESOLVE_CONLFICTS'); ?></h2></td></tr></table><br/>
<h3>
<?php echo JText::_('SYNC_WARNING');
?>
</h3><br/>

<div id="ajax_bar"><b>
<?php echo JText::_('SYNC_STEP1');
?>
</div>
<br/>

<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="synccontroller" />
<input type="hidden" name="syncid" value="<?php echo $this->syncid;?>" />
<table class="adminlist" cellspacing="1"><thead><tr><th width="50px">
<?php echo JText::_('NAME');
?>
</th><th width="50px">
<?php echo JText::_('TYPE');
?>
</th><th width="50px">
<?php echo JText::_('USERS_TOTAL');
?>
</th><th width="200px">
<?php echo JText::_('OPTIONS');
?>
</th></tr></thead>
<tr><td>
<?php echo $this->master_data['jname'];
?>
</td><td>
<?php echo JText::_('MASTER') ?>
<input type="hidden" name="master" value="<?php echo $this->master_data['jname'];
?>" />
</td><td>
<?php echo $this->master_data['total'];
?>
</td><td></td></tr>

<?php foreach($this->slave_data as $slave) {
    ?>
    <tr><td>
    <?php echo $slave['jname'];
    ?>
    <input type="hidden" name="slave[<?php echo $slave['jname'];
    ?>]" value="<?php echo $slave['jname'];
    ?>" />
    </td><td>
    <?php echo JText::_('SLAVE') ?>
    </td><td>
    <?php echo $slave['total'];
    ?>
    <input type="hidden" name="slave[<?php echo $slave['jname'];
    ?>][total]" value="<?php echo $slave['total'];
    ?>" />
    </td><td>
    <?php echo JText::_('SYNC_INTO_MASTER');
    ?><input type="checkbox" name="slave[<?php echo $slave['jname'];
    ?>][sync_into_master]" value="1">
    </td></tr>

<?php }
?>


</table></form>
<br/><br/>

<div id="ajax_bar"><b><?php echo JText::_('SYNC_STEP1_INSTR');
?>
</b>&nbsp;
&nbsp;
&nbsp;
<a id="start" href="#"><?php echo JText::_('START');
?></a>
<span class="border">&nbsp;
</span>
<a id="stop" href="#"><?php echo JText::_('STOP');
?></a>
<div id="aspin"></div>
</div><br/>

<div id="log_res">
</div>
