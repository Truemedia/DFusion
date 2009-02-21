<?php
/**
* @package JFusion
* @subpackage Modules
* @author JFusion development team
* @copyright Copyright (C) 2009 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );
require_once(dirname(__FILE__).DS.'helper.php');

//check if the JFusion component is installed
$model_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php';
$factory_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php';
if (file_exists($model_file) && file_exists($factory_file)) {
	
/**
* require the JFusion libraries
*/
require_once($model_file);
require_once($factory_file);

$pluginParamValue = $params->get('JFusionPluginParam');
$pluginParamValue = unserialize(base64_decode($pluginParamValue));

if(is_array($pluginParamValue)) {
	$outputAll = array();
	foreach($pluginParamValue as $jname => $value) {
		$pluginParam = new JParameter('');
		$pluginParam->loadArray($value);
		$view = $pluginParam->get('view', 'auto');
		
		// configuration
		$config = array();
		$config['showmode'] = intval($pluginParam->get('showmode'));
		$config['link_mode'] = $pluginParam->get('link_mode','direct');
		$config['name'] = $pluginParam->get('name');
		$config['userlink'] = intval($pluginParam->get('userlink'),false);
		$config['userlink_software'] = $pluginParam->get('userlink_software',false);
		$config['userlink_custom'] = $pluginParam->get('userlink_custom',false);
		$config['view'] = $pluginParam->get('link_mode');
		$config['itemid'] = $pluginParam->get('itemid');
		$config['avatar'] = $pluginParam->get('avatar',false);
		$config['avatar_software'] = $pluginParam->get('avatar_software','jfusion');

		$output = "";
		if($title = $pluginParam->get('title', NULL)) {
			$output = "<h4>".$pluginParam->get('title', $jname)."</h4>\n";
		}
		if($view == 'auto') {
			$output .= modjfusionWhosOnlineHelper::renderPluginAuto($jname, $config, $params);
		} else {
			$output .= modjfusionWhosOnlineHelper::renderPluginMode($jname, $config, $view, $pluginParam);
		}
		$outputAll[] = $output;
	}
	
	//Output each List
	foreach($outputAll as $value) {
		echo $value;
	}
} else {
    echo JText::_('NO_PLUGIN');
}
} else {
    echo JText::_('NO_COMPONENT');
}