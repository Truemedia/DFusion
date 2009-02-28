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

//display the paypal donation button
JFusionFunction::displayDonate();

//allow for AJAX popups
JHTML::_('behavior.modal', 'a.modal');

?>
<script language="javascript" type="text/javascript">
<!--
checked=false;
function applyAll() {
	 if (checked == false)
          {
           checked = true;
          }
        else
          {
          checked = false;
          }
	for(i=0; i<document.adminForm.elements.length; i++){
		if(document.adminForm.elements[i].type=="checkbox"){
			document.adminForm.elements[i].checked = checked;
		}
		}
}
//-->
</script>

<?php echo $this->toolbar; ?>

<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="syncstatus" />
<input type="hidden" name="syncid" value="<?php echo $this->syncid;?>" />

<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/synchistory.png" height="75px" width="75px">
<td><h2><?php echo JText::_('SYNC_HISTORY'); ?></h2></td></tr></table><br/>

<table class="adminlist" cellspacing="1"><thead><tr>
<th class="title" width="20px">
<input type='checkbox' onclick='applyAll();'>
</th>
<th class="title" width="20px"><?php echo JText::_('ID'); ?></th>
<th class="title" ><?php echo JText::_('ACTION'); ?></th>
<th class="title" align="center"><?php echo JText::_('START_TIME'); ?></th>
<th class="title" align="center"><?php echo JText::_('END_TIME'); ?></th>
<th class="title" align="center"><?php echo JText::_('TOTAL_TIME'); ?></th>
<th class="title" align="center"><?php echo JText::_('ERRORS'); ?></th>
<th class="title" align="center"><?php echo JText::_('DETAILS'); ?></th>
</tr></thead><tbody>

<?php

define('INT_SECOND', 1);
define('INT_MINUTE', 60);
define('INT_HOUR', 3600);
define('INT_DAY', 86400);
define('INT_WEEK', 604800);

function get_formatted_timediff($then, $now = false)
{
    $now      = (!$now) ? time() : $now;
    $timediff = ($now - $then);
    $weeks    = (int) intval($timediff / INT_WEEK);
    $timediff = (int) intval($timediff - (INT_WEEK * $weeks));
    $days     = (int) intval($timediff / INT_DAY);
    $timediff = (int) intval($timediff - (INT_DAY * $days));
    $hours    = (int) intval($timediff / INT_HOUR);
    $timediff = (int) intval($timediff - (INT_HOUR * $hours));
    $mins     = (int) intval($timediff / INT_MINUTE);
    $timediff = (int) intval($timediff - (INT_MINUTE * $mins));
    $sec      = (int) intval($timediff / INT_SECOND);
    $timediff = (int) intval($timediff - ($sec * INT_SECOND));

    $str = '';
    if ( $weeks )
    {
        $str .= intval($weeks);
        $str .= ($weeks > 1) ? ' weeks' : ' week';
    }

    if ( $days )
    {
        $str .= ($str) ? ', ' : '';
        $str .= intval($days);
        $str .= ($days > 1) ? ' days' : ' day';
    }

    if ( $hours )
    {
        $str .= ($str) ? ', ' : '';
        $str .= intval($hours);
        $str .= ($hours > 1) ? ' hours' : ' hour';
    }

    if ( $mins )
    {
        $str .= ($str) ? ', ' : '';
        $str .= intval($mins);
        $str .= ($mins > 1) ? ' minutes' : ' minute';
    }

    if ( $sec )
    {
        $str .= ($str) ? ', ' : '';
        $str .= intval($sec);
        $str .= ($sec > 1) ? ' seconds' : ' second';
    }

    if ( !$weeks && !$days && !$hours && !$mins && !$sec )
    {
        $str .= '0 seconds ';
    }

    return $str;
}


$row_count = 0;

if(empty($rows)){
	$rows = array();
    JError::raiseWarning(500, JText::_('NO_USERSYNC_DATA'));
}

foreach ($this->rows as $record) {
echo '<tr class="row' . $row_count .'">';
if ($row_count == 1){
	$row_count = 0;
}	else {
	$row_count = 1;
}

$syncdata = unserialize(base64_decode($record->syncdata));
?>

<td><input type="checkbox" name="syncid[<?php echo $record->syncid; ?>]" /></td>
<td><?php echo $record->syncid; ?></td>
<td><?php echo $record->action; ?></td>
<td><?php echo date("d/m/y : H:i:s", $record->time_start) ; ?></td>
<?php if($record->time_end){?>
<td><?php echo date("d/m/y : H:i:s", $record->time_end) ; ?></td>
<td><?php echo get_formatted_timediff($record->time_start, $record->time_end); ?></td>
<?php } else {?>
<td></td>
<td><?php echo JText::_('SYNC_NOT_FINISHED'); ?></td>
<?php } ?>

<?php
//get the total errors
$total_error = 0;
if(is_array($syncdata['slave_data'])){
	foreach ($syncdata['slave_data'] as $slave) {
		$total_error = $total_error + $slave['error'];
	}
}
echo '<td>' . $total_error . '</td>';?>

<td><a class="modal" rel="{handler: 'iframe', size: {x: 650, y: 375}}" href="index.php?option=com_jfusion&task=syncstatus&syncid=<?php echo $record->syncid; ?>"><?php echo JText::_('CLICK_FOR_MORE_DETAILS'); ?></a></td>
</tr>

<?php } ?>

</tbody></table></form><br/><br/><br/>