<?php
/**
* @package JFusion
* @subpackage Elements
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
* Require the Jfusion plugin factory
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');


/**
* Defines the forum select list for JFusion forum plugins
* @package JFusion
*/
class JElementJFusionSearchParams extends JElement
{
    var $_name = "JFusionSearchParams";

    function fetchElement($name, $value, &$node, $control_name)
    {
    	//load the language
    	JPlugin::loadLanguage( 'plg_search_jfusion' );
     	$db = & JFactory::getDBO();
        $query = 'SELECT params FROM #__plugins  WHERE element = \'jfusion\' and folder = \'search\'';
        $db->setQuery( $query );
        $params = $db->loadResult();      	
		$parametersInstance = new JParameter($params);		
		//load custom plugin parameter
		$jPluginParam = new JParameter('');
		$jPluginParamRaw = unserialize(base64_decode($parametersInstance->get('JFusionPluginParam')));
		$output = "";

		if(is_array($jPluginParamRaw)) {
			
			$linkModes = array(
				0 => array('value' => 'wrapper', 'text' => JText::_("LINK_MODE_0")),
				1 => array('value' => 'direct', 'text' => JText::_("LINK_MODE_1")),
				2 => array('value' => 'frameless', 'text' => JText::_("LINK_MODE_2")));
			
			foreach($jPluginParamRaw as $jname => $value) {
		
				$jPluginParam->loadArray($value);
				$jname = $jPluginParam->get('jfusionplugin');
		
				if (JFusionFunction::validPlugin($jname)) {
					$output .= "<b>".$jname . "</b><br />\n";
					
					//link mode
					$output .= JText::_("LINK_MODE")."<br />";
					$selectedValue = $parametersInstance->get('link_mode_'.$jname);
					$output .= JHTML::_('select.genericlist', $linkModes, $control_name.'[link_mode_'.$jname.']', '','value', 'text', $selectedValue);
					$output .= "<br />\n";
					
					//itemid
					$output .= JText::_("ITEMID")."<br />";
			        $query = 'SELECT id, name FROM #__menu WHERE type = \'component\' AND link LIKE \'%com_jfusion%\' ORDER BY name';
			        $db->setQuery( $query );
			        $rows = $db->loadObjectList();

			        if(!empty($rows)) {
			        	$selectedValue = $parametersInstance->get('itemid_'.$jname);			        	
						$output .= JHTML::_('select.genericlist', $rows, $control_name.'[itemid_'.$jname.']', '','id', 'name', $selectedValue);
			        } else {
			           $output .= JText::_('NO_MENU_ITEMS');
			        }

			        $output .= "<br />\n";
		        } else {
		            $output .= $jname . ": " . JText::_('NO_VALID_PLUGIN') . "<br />";
		        }
			}
		} else {
			$output .= JText::_('NO_PLUGIN_SELECT');
		}
		
		return $output;
    }
}