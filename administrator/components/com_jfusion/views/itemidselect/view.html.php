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

jimport('joomla.application.component.view');
/**
* Renders the a screen that allows the user to choose a JFusion integration method
* @package JFusion
*/
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
        echo '<h3>' . JText::_('SELECT_INTEGRATED_VIEW' ) . '</h3>';
        ?>
        <table class="adminlist" cellspacing="1">
        <thead>
        <tr>
        <th width="10">
        <?php echo JText::_('ITEMID' );
        ?>
        </th>
        <th class="title">
        <?php echo JText::_('MENU' );
        ?>
        </th>
        <th class="title">
        <?php echo JText::_('TITLE' );
        ?>
        </th>
        <th class="title">
        <?php echo JText::_('ALIAS' );
        ?>
        </th>
        <th class="title">
        <?php echo JText::_('JFUSION' ) . ' ' . JText::_('PLUGIN' );
        ?>
        </th>
        <th width="7%">
        <?php echo JText::_('HELP_VISUAL' );
        ?>
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
        foreach($rows as $row) {
            echo '<tr class="row' . $row_count .'">';
            if ($row_count == 1) {
                $row_count = 0;
            } else {
                $row_count = 1;
            }

            ?>
            <tr class="<?php echo "row$row_count"; ?>">
            <td>
            <a style="cursor: pointer;" onclick="window.parent.jSelectItemid('<?php echo $row->id; ?>');">
            <?php echo htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8');
            ?></a>
            </td>
            <td>
            <?php echo $row->menutype;
            ?>
            </td>
            <td>
            <?php echo $row->name;
            ?>
            </td>
            <td>
            <?php echo $row->alias;
            ?>
            </td>

            <td>
            <?php //get the plugin name
            $params = new JParameter($row->params);
            $jPluginParam = unserialize(base64_decode($params->get('JFusionPluginParam')));
            if (is_array($jPluginParam)) {
                echo $jPluginParam['jfusionplugin'];
            } else {
                echo JText::_('NO_PLUGIN_SELECTED');
            }
            ?>
            </td>
            <td>
            <?php
            //get the integration method
            echo $params->get('visual_integration');
            ?>
            </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
        </table>
        <?php
        //get a list of direct links for jfusion plugins
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE status = 1';
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        echo '<br/><h3>' . JText::_('SELECT_DIRECT_VIEW' ) . '</h3>';
        ?>
        <table class="adminlist" cellspacing="1">
        <thead>
        <tr>
        <th class="title">
        <?php echo JText::_('NAME' );
        ?>
        </th>
        <th class="title">
        <?php echo JText::_('DESCRIPTION' );
        ?>
        </th>
        <th class="title">
        <?php echo JText::_('URL' );
        ?>
        </th>
        </tr>
        </thead>
        <tbody>
        <?php

        $row_count = 0;
        foreach($rows as $row) {
            echo '<tr class="row' . $row_count .'">';
            if ($row_count == 1) {
                $row_count = 0;
            } else {
                $row_count = 1;
            }

            ?>
            <tr class="<?php echo "row$row_count"; ?>">
            <td>
            <a style="cursor: pointer;" onclick="window.parent.jSelectItemid('<?php echo $row->name; ?>');">
            <?php echo htmlspecialchars($row->name, ENT_QUOTES, 'UTF-8');
            ?></a>
            </td>
            <td>
            <?php
            $JFusionParam = JFusionFactory::getParams($row->name);
            $description = $JFusionParam->get('description');
            echo $description;
            ?>
            </td>
            <td>
            <?php
            $source_url = $JFusionParam->get('source_url');
            echo $source_url;
            ?>
            </td>
            </tr> <?php

        }
    }
}
