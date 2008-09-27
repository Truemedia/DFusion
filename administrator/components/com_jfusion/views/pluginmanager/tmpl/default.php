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


<script language="javascript" type="text/javascript">
<!--
function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}


function submitbutton(pressbutton)
{
    var form = document.adminForm;

    if (pressbutton == 'copy_plugin') {
        //see if a plugin was selected
        if (getCheckedValue(document.adminForm.jname)) {
            var new_jname = prompt('Please type in the name to use for the copied plugin. This name must not already be in use.');
            document.adminForm.new_jname.value = new_jname;
        } else {
            alert('Please select a plugin to copy first');
            exit;
        }

    }

    submitform(pressbutton);
    return;
}


function setCheckedValue(radioObj, newValue)
{
    if (!radioObj) {
        return;
    }
    var radioLength = radioObj.length;
    if (radioLength == undefined) {
        radioObj.checked = (radioObj.value == newValue.toString());
        return;
    }
    for (var i = 0; i < radioLength; i++) {
        radioObj[i].checked = false;
        if (radioObj[i].value == newValue.toString()) {
            radioObj[i].checked = true;
        }
    }
}

function submitbutton3(pressbutton)
{
    var form = document.adminForm;

    // do field validation
    if (form.install_directory.value == "") {
        alert("<?php echo JText::_( 'NO_DIRECTORY'); ?>" );
    } else {
        form.installtype.value = 'folder';
        form.submit();
    }
}

function submitbutton4(pressbutton)
{
    var form = document.adminForm;

    // do field validation
    if (form.install_url.value == "" || form.install_url.value == "http://") {
        alert("<?php echo JText::_( 'NO_URL'); ?>" );
    } else {
        form.installtype.value = 'url';
        form.submit();
    }
}

//-->
</script>
<form method="post" action="index2.php" name="adminForm" enctype="multipart/form-data">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="install_plugin" />
<input type="hidden" name="new_jname" value="" />

<?php echo $this->toolbar; ?>

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'manager.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('PLUGIN_MANAGER'); ?></h2></td></tr></table>
<? echo JText::_('PLUGIN_MANAGER_INSTR'); ?><br/><br/>

<table class="adminlist" cellspacing="1"><thead><tr>
<th class="title" width="20px"><?php echo JText::_('ID'); ?></th>
<th class="title" ><?php echo JText::_('NAME'); ?></th>
<th class="title" align="center"><?php echo JText::_('DESCRIPTION'); ?></th>
<th class="title" align="center"><?php echo JText::_('VERSION'); ?></th>
<th class="title" align="center"><?php echo JText::_('DATE'); ?></th>
<th class="title" align="center"><?php echo JText::_('AUTHOR'); ?></th>
<th class="title" align="center"><?php echo JText::_('CONTACT_DETAILS'); ?></th>
</tr></thead><tbody>

<?php $row_count = 0;
foreach ($this->rows as $record) {
echo '<tr class="row' . $row_count .'">';
if ($row_count == 1){
	$row_count = 0;
}	else {
	$row_count = 1;
}
	?>


<td><?php echo $record->id; ?></td>
<td><INPUT TYPE=RADIO NAME="jname" VALUE="<?php echo $record->name; ?>"><?php echo $record->name; ?></td>
<td><?php echo $record->description; ?></td>
<td><?php echo $record->version; ?></td>
<td><?php echo $record->date; ?></td>
<td><?php echo $record->author; ?></td>
<td><?php echo $record->support; ?></td>
</tr>

<?php } ?>


</tbody></table><br/><br/><br/>


<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'install.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('PLUGIN_INSTALL'); ?></h2></td></tr></table>
<? echo JText::_('PLUGIN_INSTALL_INSTR'); ?><br/><br/>


<table class="adminform"><tr><td>
	<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'folder_zip.png'; ?>" height="75px" width="75px">
</td><td>
	<table><tr><th colspan="2">
	<?php echo JText::_( 'UPLOAD_PACKAGE' ); ?>
	</th></tr><tr><td width="120"><label for="install_package">
	<?php echo JText::_( 'PACKAGE_FILE' ); ?>
	:</label></td><td>
	<input class="input_box" id="install_package" name="install_package" type="file" size="57" />
	<input class="button" type="button" value="<?php echo JText::_( 'UPLOAD_FILE' ); ?> &amp; <?php echo JText::_( 'INSTALL' ); ?>" onclick="submitbutton()" />
	</td></tr></table>
</td></tr></table>

<table class="adminform"><tr><td>
	<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'folder_dir.png'; ?>" height="75px" width="75px">
</td><td>
	<table><tr><th colspan="2">
	<?php echo JText::_( 'INSTALL_FROM_DIRECTORY' ); ?>
	</th></tr><tr><td width="120"><label for="install_directory">
	<?php echo JText::_( 'INSTALL_DIRECTORY' ); ?>
	:</label></td><td>
	<input type="text" id="install_directory" name="install_directory" class="input_box" size="70" value="" />
	<input type="button" class="button" value="<?php echo JText::_( 'INSTALL' ); ?>" onclick="submitbutton3()" />
	</td></tr></table>
</td</tr></table>

<table class="adminform"><tr><td>
	<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'folder_url.png'; ?>" height="75px" width="75px">
</td><td>
	<table><tr><th colspan="2">
	<?php echo JText::_( 'INSTALL_FROM_URL' ); ?>
	</th></tr><tr><td width="120"><label for="install_url">
	<?php echo JText::_( 'INSTALL_URL' ); ?>
	:</label></td><td>
	<input type="text" id="install_url" name="install_url" class="input_box" size="70" value="http://" />
	<input type="button" class="button" value="<?php echo JText::_( 'INSTALL' ); ?>" onclick="submitbutton4()" />
	</td></tr></table>
</td></tr></table>

<input type="hidden" name="type" value="" />
<input type="hidden" name="installtype" value="upload" />
</form>
