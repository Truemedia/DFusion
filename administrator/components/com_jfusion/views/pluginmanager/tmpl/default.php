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
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/manager.png" height="75px" width="75px">
<td><h2><?php echo JText::_('PLUGIN_MANAGER'); ?></h2></td></tr></table>
<?php echo JText::_('PLUGIN_MANAGER_INSTR'); ?><br/><br/>

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
<?php
$plugin_xml = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$record->name.DS.'jfusion.xml';

if(file_exists($plugin_xml) && is_readable($plugin_xml))
{
	$parser = JFactory::getXMLParser('Simple');
    $xml    = $parser->loadFile($plugin_xml);
   	$xml    = $parser->document;

?>
<td><?php echo $xml->description[0]->data(); ?></td>
<td><?php echo $xml->version[0]->data(); ?></td>
<td><?php echo $xml->creationDate[0]->data(); ?></td>
<td><?php echo $xml->author[0]->data(); ?></td>
<td><?php echo $xml->authorUrl[0]->data(); ?></td>

<?php
} else {
	JFusionFunction::raiseWarning(JText::_('ERROR'), JText::_('XML_FILE_MISSING') . ' JFusion ' . $plugin->name . ' ' .JText::_('PLUGIN'), 1);
	?>
	<td><?php echo JText::_('UNKNOWN'); ?></td>
	<td><?php echo JText::_('UNKNOWN'); ?></td>
	<td><?php echo JText::_('UNKNOWN'); ?></td>
	<td><?php echo JText::_('UNKNOWN'); ?></td>
	<td><?php echo JText::_('UNKNOWN'); ?></td>
	<?php
	}

	echo "</tr>";
} ?>


</tbody></table><br/><br/><br/>


<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/install.png" height="75px" width="75px">
<td><h2><?php echo JText::_('PLUGIN_INSTALL'); ?></h2></td></tr></table>
<?php echo JText::_('PLUGIN_INSTALL_INSTR'); ?><br/><br/>


<table class="adminform"><tr><td>
	<img src="components/com_jfusion/images/folder_zip.png" height="75px" width="75px">
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
	<img src="components/com_jfusion/images/folder_dir.png" height="75px" width="75px">
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
	<img src="components/com_jfusion/images/folder_url.png" height="75px" width="75px">
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
