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
<style type="text/css">
/** cpanel settings **/
#cpanel div.icon {
	text-align: center;
	margin-right: 5px;
	float: left;
	margin-bottom: 5px;
}
#cpanel div.icon a {
	display: block;
	float: left;
	border: 1px solid #f0f0f0;
	height: 97px;
	width: 100px;
	color: #666;
	vertical-align: middle;
	text-decoration: none;
}

#cpanel div.icon a:hover {
	border-left: 1px solid #eee;
	border-top: 1px solid #eee;
	border-right: 1px solid #ccc;
	border-bottom: 1px solid #ccc;
	background-color: #CCE6FF;
	border: 1px solid #3300FF;
	color: #0B55C4;
}

#cpanel img  { padding: 10px 0; margin: 0 auto; }
#cpanel span { display: block; text-align: center; }
</style>

<table class="adminform"><tr><td width="55%" valign="top">

<div id="cpanel">
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=plugineditor&jname=joomla_int" >
				<img src="components/com_jfusion/images/joomla.png" height="50px" width="50px">
				<span><?php echo JText::_('JOOMLA_OPTIONS'); ?></span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=plugindisplay" >
				<img src="components/com_jfusion/images/controlpanel.png" height="50px" width="50px">
				<span><?php echo JText::_('CONFIGURE_PLUGINS'); ?></span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=pluginmanager" >
				<img src="components/com_jfusion/images/manager.png" height="50px" width="50px">
				<span><?php echo JText::_('MANAGE_PLUGINS'); ?></span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=syncoptions" >
				<img src="components/com_jfusion/images/syncmaster.png" height="50px" width="50px">
				<span><?php echo JText::_('NEW_USER_SYNC'); ?></span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=synchistory" >
				<img src="components/com_jfusion/images/synchistory.png" height="50px" width="50px">
				<span><?php echo JText::_('USER_SYNC_HISTORY'); ?></span>
				</a>
			</div>
	</div>

	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=loginchecker" >
				<img src="components/com_jfusion/images/login_checker.png" height="50px" width="50px">
				<span><?php echo JText::_('CP_LOGIN_CHECKER'); ?></span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index2.php?option=com_jfusion&task=versioncheck" >
				<img src="components/com_jfusion/images/versioncheck.png" height="50px" width="50px">
				<span><?php echo JText::_('VERSION_CHECK'); ?></span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index2.php?option=com_jfusion&task=help" >
				<img src="components/com_jfusion/images/help.png" height="50px" width="50px">
				<span><?php echo JText::_('CP_HELP'); ?></span>
				</a>
			</div>
	</div>

</div>

<td width="45%" valign="top">

<?php
//check to see if JFusion is enabled
$plugin_user = JFusionFunction::isPluginInstalled('jfusion', 'user', 1);
$plugin_auth = JFusionFunction::isPluginInstalled('jfusion', 'authentication', 1);

if ($plugin_user && $plugin_auth){

?>
<table bgcolor="#d9f9e2" width ="100%"><tr><td width="50px"><td>
<img src="components/com_jfusion/images/check_good.png" height="30px" width="30px">
<td><h2><?php echo JText::_('PLUGINS_ENABLED'); ?></h2></td><td><a href="index.php?option=com_jfusion&task=disableplugins" onCLick="return confirm('<?php echo JText::_('PLUGINS_DISABLE_CONFIRM');?>')"><?php echo JText::_('PLUGINS_DISABLE');?></a></td></tr></table>
<?php
} else {
?>
<table bgcolor="#f9ded9" width ="100%"><tr><td width="50px"><td>
<img src="components/com_jfusion/images/check_bad.png" height="30px" width="30px">
<td><h2><?php echo JText::_('PLUGINS_DISABLED'); ?></h2></td><td><a href="index.php?option=com_jfusion&task=enableplugins" onCLick="return confirm('<?php echo JText::_('PLUGINS_ENABLE_CONFIRM');?>')"><?php echo JText::_('PLUGINS_ENABLE');?></a></td></tr></table>
<?php
}

jimport('joomla.html.pane');
$pane =& JPane::getInstance('tabs');
echo $pane->startPane( 'pane' );

foreach ($this->JFusionCpanel->item as $item) {
	echo $pane->startPanel( $item->title[0]->data(), $item->title[0]->data());
	echo $item->body[0]->data();
	echo $pane->endPanel();
}

?>
</td></tr></table>




