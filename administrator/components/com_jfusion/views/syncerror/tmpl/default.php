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

?>
<script type="text/javascript" src="<?php echo 'components/com_jfusion/js/moodalbox.js'; ?>"></script>
<link rel="stylesheet" href="<?php echo 'components/com_jfusion/css/moodalbox.css'; ?>" type="text/css" media="screen" />
<style type="text/css">
#ajax_bar {
    background-color: #e4ecf2;
    border: 1px solid #d6d6d6;
    border-left-color: #e4e4e4;
    border-top-color: #e4e4e4;
    margin-top: 0pt auto;
    height: 20px;
    padding: 3px 5px;
    vertical-align: center;
}
</style>

<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/usersync.png" height="75px" width="75px">
<td><h2><?php echo JText::_('RESOLVE_CONLFICTS'); ?></h2></td></tr></table><br/>
<br/>
<font size="2"><?php echo JText::_('CONFLICT_INSTRUCTION'); ?></font><br/>
<h3><?php echo JText::_('EMAIL') . ' ' . JText::_('CONFLICTS'); ?></h3>
<font size="2"><?php echo JText::_('CONFLICTS_EMAIL'); ?></font><br/>
<h3><?php echo JText::_('USERNAME') . ' ' . JText::_('CONFLICTS'); ?></h3>
<font size="2"><?php echo JText::_('CONFLICTS_USERNAME'); ?></font><br/>
<h3><?php echo JText::_('USERSYNC') . ' ' . JText::_('ERROR'); ?></h3>
<font size="2"><?php echo JText::_('CONFLICTS_ERROR'); ?></font><br/><br/>


<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="syncerror" />
<input type="hidden" name="syncid" value="<?php echo $this->syncid;?>" />

<div id="ajax_bar"><?php echo JText::_('APPLY_ACTION_ALL_CONFLICTS'); ?>
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
<a href="javascript:void(0);"  onclick="applyAll();"><?php echo JText::_('APPLY'); ?></a>
</div>


<table class="adminlist" cellspacing="1"><thead><tr>
<th class="title" width="20px"><?php echo JText::_('ID'); ?></th>
<th class="title" align="center"><?php echo JText::_('TYPE'); ?></th>
<th class="title" align="center"><?php echo JText::_('PLUGIN'). ' '. JText::_('NAME'). ': '. JText::_('USERID') . ' / '. JText::_('USERNAME') . ' / '. JText::_('EMAIL'); ?></th>
<th class="title" align="center"><?php echo JText::_('CONFLICT'); ?></th>
<th class="title" align="center"><?php echo JText::_('DETAILS'); ?></th>
<th class="title" align="center"><?php echo JText::_('ACTION'); ?></th>
</tr></thead><tbody>

<?php $row_count = 0;
if(!isset($this->syncdata['errors'])) $this->syncdata['errors'] = array();
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
<input type="hidden" name="syncerror[<?php echo $i; ?>][user_jname]" value="<?php echo $error['user']['jname']?>" />
<input type="hidden" name="syncerror[<?php echo $i; ?>][conflict_jname]" value="<?php echo $error['conflict']['jname']?>" />
<input type="hidden" name="syncerror[<?php echo $i; ?>][user_username]" value="<?php echo $error['user']['userlist']->username ?>" />
<input type="hidden" name="syncerror[<?php echo $i; ?>][conflict_username]" value="<?php echo $error['conflict']['userinfo']->username?>" />
</td>
<td>
<?php
//check to see what sort of an error it is
if (empty($error['conflict']['userinfo'])){
    $error_type = 'Error';
} elseif ($error['user']['userinfo']->username != $error['conflict']['userinfo']->username){
    $error_type = 'Username';
} elseif ($error['user']['userinfo']->email != $error['conflict']['userinfo']->email){
    $error_type = 'Email';
} else {
    $error_type = 'Error';
}
echo $error_type; ?>
</td>
<td><?php echo $error['user']['jname'] . ': ' . $error['user']['userlist']->userid . ' / ' . $error['user']['userlist']->username  .' / ' . $error['user']['userlist']->email ;
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');
//debug::show($error, 'Info on Error');
 ?></td>
<td>
<?php
if ($error_type != 'Error'){
	echo $error['conflict']['jname'] . ': ' . $error['conflict']['userinfo']->userid .' / ' . $error['conflict']['userinfo']->username .' / ' . $error['conflict']['userinfo']->email ;
}
?>
</td>
<td>
<a href="index.php?option=com_jfusion&task=syncerrordetails&syncid=<?php echo $this->syncdata['syncid'];?>&errorid=<?php echo $i;?>" rel="moodalbox"><?php echo JText::_('DETAILS'); ?></a>
</td><td>
<?php
if ($error_type != 'Error'){ ?>
<select name="syncerror[<?php echo $i; ?>][action]" default="0">
<option value="0"><?php echo JText::_('IGNORE')?></option>
<option value="1"><?php echo JText::_('UPDATE'). ' ' . $error['user']['jname']. ' ' . JText::_('USER')?></option>
<option value="2"><?php echo JText::_('UPDATE'). ' ' . $error['conflict']['jname']. ' ' . JText::_('USER')?></option>
<option value="3"><?php echo JText::_('DELETE'). ' ' . $error['user']['jname']. ' ' . JText::_('USER')?></option>
<option value="4"><?php echo JText::_('DELETE'). ' ' . $error['conflict']['jname']. ' ' . JText::_('USER')?></option>
?>
</select>
<?php }
echo '</td></tr>';
}

//close the table and render submit button
echo '</table><input type="submit" value="' .JText::_('RESOLVE_CONLFICTS') . '"></form>';