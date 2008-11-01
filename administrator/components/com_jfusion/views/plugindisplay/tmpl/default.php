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
	height: 147px;
	width: 150px;
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
				<img src="components/com_jfusion/images/joomla.png" height="75px" width="75px">
				<span>Joomla Options</span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=plugindisplay" >
				<img src="components/com_jfusion/images/controlpanel.png" height="75px" width="75px">
				<span>Configure Plugins</span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=pluginmanager" >
				<img src="components/com_jfusion/images/manager.png" height="75px" width="75px">
				<span>Manage Plugins</span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=sync" >
				<img src="components/com_jfusion/images/usersync.png" height="75px" width="75px">
				<span>Usersync</span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index.php?option=com_jfusion&task=loginchecker" >
				<img src="components/com_jfusion/images/login_checker.png" height="75px" width="75px">
				<span>Login checker</span>
				</a>
			</div>
	</div>
	<div style="float:left;">
			<div class="icon">
				<a href="index2.php?option=com_jfusion&task=help" >
				<img src="components/com_jfusion/images/help.png" height="75px" width="75px">
				<span>Help</span>
				</a>
			</div>
	</div>
</div>

<td width="45%" valign="top">

<?php

jimport('joomla.html.pane');
$pane =& JPane::getInstance('tabs');
echo $pane->startPane( 'pane' );
echo $pane->startPanel( 'JFusion News', 'panel1' );
echo $this->JFusionNews;
echo $pane->endPanel();
echo $pane->startPanel( 'Test tab', 'panel2' );
echo "This is a test tab";
echo $pane->endPanel();
echo $pane->endPane();


?>
</td></tr></table>




