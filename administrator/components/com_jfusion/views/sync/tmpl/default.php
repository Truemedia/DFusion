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

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'usersync.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('USERSYNC'); ?></h2></td></tr></table><br/><br/>

<font size="2"><?php echo JText::_('SYNC_INSTR');?></font>
<br/><br/>

<table class="adminform"><tr><td>
<a href="index.php?option=com_jfusion&task=syncoptions"><img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'syncslave.png'; ?>" height="75px" width="75px"></a>
</td><td>
<a href="index.php?option=com_jfusion&task=syncoptions"><?php echo JText::_('SYNC_OPTIONS_INSTR');?></a>
</td></tr></table></br>

<table class="adminform"><tr><td>
<a href="index.php?option=com_jfusion&task=synchistory"><img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'synchistory.png'; ?>" height="75px" width="75px"></a>
</td><td>
<a href="index.php?option=com_jfusion&task=synchistory"><?php echo JText::_('SYNC_HISTORY_INSTR');?></a>
</td></tr></table></br>

