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
<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/help.png" height="75px" width="75px">
<td><h2><?php echo JText::_('HELP_SCREEN'); ?></h2></td></tr></table><br/>

<h1><b>
<?php echo JText::_('HELP_WARNING'); ?>
</b></h1><br/><br/><h3>
<?php echo JText::_('HELP_CAN_DO'); ?>
</h3><font size="2"><ul>
<li><?php echo JText::_('HELP_CAN_DO_1'); ?>
<li><?php echo JText::_('HELP_CAN_DO_2'); ?>
</ul></font><br/><br/><h3>
<?php echo JText::_('HELP_CANT_DO'); ?>
</h3><font size="2"><ul>
<li><?php echo JText::_('HELP_CANT_DO_1'); ?>
<li><?php echo JText::_('HELP_CANT_DO_2'); ?>
</ul></font><br/><br/><h3>
<?php echo JText::_('HELP_ACTION'); ?>
</h3><font size="2">
<?php echo JText::_('HELP_ACTION_TEXT'); ?>
</font><br/><br/><h3>
<?php echo JText::_('HELP_INSTALL'); ?>
</h3><font size="2"><ul>
<li><?php echo JText::_('HELP_INSTALL_TEXT'); ?>
<li><?php echo JText::_('HELP_INSTALL_1'); ?>
<li><?php echo JText::_('HELP_INSTALL_2'); ?>
<li><?php echo JText::_('HELP_INSTALL_3'); ?>
<li><?php echo JText::_('HELP_INSTALL_4'); ?>
<li><?php echo JText::_('HELP_INSTALL_5'); ?>
<li><?php echo JText::_('HELP_INSTALL_6'); ?>
</ul></font><br/><br/><h3>
<?php echo JText::_('HELP_VISUAL'); ?>
</h3><font size="2">
<?php echo JText::_('HELP_VISUAL_TEXT'); ?>
<br/><ul>
<li><?php echo JText::_('HELP_VISUAL_1'); ?>
<li><?php echo JText::_('HELP_VISUAL_2'); ?>
<li><?php echo JText::_('HELP_VISUAL_3'); ?>
</ul></font><br/><br/><h3>
<?php echo JText::_('HELP_SUPPORT'); ?>
</h3><font size="2">
<?php echo JText::_('HELP_SUPPORT_TEXT'); ?>
</font><br/><br/><h3>
<?php echo JText::_('HELP_BUGS'); ?>
</h3><font size="2">
<?php echo JText::_('HELP_BUGS_TEXT'); ?>
</font><br/><br/><br/>