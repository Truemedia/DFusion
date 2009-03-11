<?php
/**
* @package JFusion
* @subpackage Modules
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
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
	
	// configuration
	$config['itemid'] = $params->get('itemid');
	$config['avatar'] = $params->get('avatar',false);
	$config['avatar_software'] = $params->get('avatar_software', 'jfusion');
	$config['avatar_height'] = $params->get('avatar_height',80);
	$config['avatar_width'] = $params->get('avatar_width',60);
	$config['avatar_location'] = $params->get('avatar_location','top');
	$config['pmcount'] = $params->get('pmcount',false);
	$config['viewnewmessages'] = $params->get('viewnewmessages',false);
	$config['login_msg'] = $params->get('login_msg');
	$config['alignment'] = $params->get('alignment','center');
	
	if ($params->get('new_window',false)) {
		$config['new_window'] = '_blank';
	} else {
	    $config['new_window'] = '_self';
	}
	
	$pluginParamValue = $params->get('JFusionPlugin');
	$pluginParamValue = unserialize(base64_decode($pluginParamValue));
	$jname = $pluginParamValue['jfusionplugin'];
	
	if(!empty($jname)) {
		$pluginParam = new JParameter('');
		$pluginParam->loadArray($pluginParamValue);
		$view = $pluginParam->get('view', 'auto');

		$output = "";
		if($title = $pluginParam->get('title', NULL)) {
			$output = "<h4>".$pluginParam->get('title', $jname)."</h4>\n";
		}
		if($view == 'auto') {
			$output .= modjfusionUserActivityHelper::renderPluginAuto($jname, $config, $params);
		} else {
			$output .= modjfusionUserActivityHelper::renderPluginMode($jname, $config, $view, $pluginParam);
		}
		echo $output;
	} else {
	    echo JText::_('NO_PLUGIN');
	}
} else {
    echo JText::_('NO_COMPONENT');
}