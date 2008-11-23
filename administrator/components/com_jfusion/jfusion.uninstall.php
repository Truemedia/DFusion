<?php

/**
* @package JFusion
* @subpackage Install
* @version 1.0.8
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * Get the extension id
 * Grabbed this from the JPackageMan installer class with modification
 */
function _uninstallPlugin($type, $id, $group, $description) {
	$db		=& JFactory::getDBO();
	$result = $id;
	switch($type) {
		case 'plugin':
			$db->setQuery("SELECT id FROM #__plugins WHERE folder = '$group' AND element = '$id'");
			$result = $db->loadResult();
			break;
		case 'module':
			$db->setQuery("SELECT id FROM #__modules WHERE module = '$id'");
			$result = $db->loadResult();
			break;
	}
	if ($result){
		$tmpinstaller = new JInstaller();
		$installer_result = $tmpinstaller->uninstall($type, $result, 0 );
		if(!$result) { ?>
			<table bgcolor="#f9ded9" width ="100%"><tr style="height:30px">
			<td><font size="2"><b><?php echo JText::_('UNINSTALL') . ' ' . $description . ' '. JText::_('FAILED'); ?></b></font></td></tr></table>
		<?php } else { ?>
			<table bgcolor="#d9f9e2" width ="100%"><tr style="height:30px">
			<td><font size="2"><b><?php echo JText::_('UNINSTALL') . ' ' . $description . ' '. JText::_('SUCCESS'); ?></b></font></td></tr></table>
		<?php }
	}
}

echo '<h2>JFusion Uninstall</h2><br/>';

//uninstall the JFusion Modules
_uninstallPlugin('module','mod_jfusion_login', '', 'JFusion Login module');
_uninstallPlugin('module','mod_jfusion_activity', '', 'JFusion Activity module');

//restore the normal login behaviour
$db =& JFactory::getDBO();
$db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'joomla\' and folder = \'authentication\'');
$db->Query();
$db->setQuery('UPDATE #__plugins SET published = 1 WHERE element =\'joomla\' and folder = \'user\'');
$db->Query();
?>
			<table bgcolor="#d9f9e2" width ="100%"><tr style="height:30px"><td width="50px">
			<img src="templates/khepri/images/toolbar/icon-32-apply.png" height="20px" width="20px">
			<td><font size="2"><b>Restored normal Joomla login behaviour</b></font></td></tr></table>
<?php

//uninstall the JFusion plugins
_uninstallPlugin('plugin','jfusion', 'user', 'JFusion User Plugin');
_uninstallPlugin('plugin','jfusion', 'authentication', 'JFusion Authentication Plugin');




