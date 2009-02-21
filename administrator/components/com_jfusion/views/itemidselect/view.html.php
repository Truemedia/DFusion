<?php
/**
 * @version		$Id: view.php 11213 2008-10-25 12:43:11Z pasamio $
 * @package		Joomla
 * @subpackage	Content
 * @copyright	Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class jfusionViewitemidselect extends JView
{
	function display()
	{
		global $mainframe;

		// Initialize variables
		$db			= &JFactory::getDBO();
		JHTML::_('behavior.modal');

		$document	= & JFactory::getDocument();
		$document->setTitle('Plugin Selection');
		$template = $mainframe->getTemplate();
		$document->addStyleSheet("templates/$template/css/general.css");

		JHTML::_('behavior.tooltip');
		?>
			<table class="adminlist" cellspacing="1">
			<thead>
				<tr>
					<th width="10">
						<?php echo JText::_( 'ITEMID' ); ?>
					</th>
					<th class="title">
						<?php echo JText::_( 'MENU' ); ?>
					</th>
					<th class="title">
						<?php echo JText::_( 'TITLE' ); ?>
					</th>
					<th class="title">
						<?php echo JText::_( 'ALIAS' ); ?>
					</th>
					<th class="title">
						<?php echo JText::_( 'JFUSION' ) . ' ' . JText::_( 'PLUGIN' ); ?>
					</th>
					<th width="7%">
						<?php echo JText::_( 'HELP_VISUAL' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
			<?php

			//get a list of jfusion menuitems
			$db = & JFactory::getDBO();
			$query = 'SELECT id, menutype, name, alias, params FROM #__menu WHERE link = \'index.php?option=com_jfusion\'';
	        $db->setQuery($query);
    	    $rows = $db->loadObjectList();

			$row_count = 0;
			foreach ($rows as $row) {
				echo '<tr class="row' . $row_count .'">';
				if ($row_count == 1){
					$row_count = 0;
				}	else {
				$row_count = 1;
				}

				?>
				<tr class="<?php echo "row$row_count"; ?>">
					<td>
						<a style="cursor: pointer;" onclick="window.parent.jSelectItemid('<?php echo $row->id; ?>');">
							<?php echo htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8'); ?></a>
					</td>
					<td>
						<?php echo $row->menutype; ?>
					</td>
						<td>
							<?php echo $row->name; ?>
						</td>
					<td>
						<?php echo $row->alias; ?>
					</td>
					<td>
						<?php //get the integration method
				        $registry = new JRegistry();
				        $registry->loadINI($row->params);
	    				$params = $registry->toObject();

						echo $params->visual_integration; ?>
					</td>
					<td>
						<?php //get the plugin name
						$jPluginParam = new JParameter('');
						$jPluginParam->loadArray(unserialize(base64_decode($params->JFusionPluginParam)));
						echo $jPluginParam->get('jfusionplugin');?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
			</table>
		<?php
	}
}
