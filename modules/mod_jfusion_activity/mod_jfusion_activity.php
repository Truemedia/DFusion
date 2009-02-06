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

defined('_DATE_FORMAT_LC2') or define('_DATE_FORMAT_LC2','%A, %d %B %Y %H:%M');
defined('LAT') or define('LAT', 0);
defined('LCT') or define('LCT', 1);
defined('LCP') or define('LCP', 2);
defined('LINKTHREAD') or define('LINKTHREAD', 0);
defined('LINKPOST') or define('LINKPOST', 1);

// configuration
$config['mode'] = intval($params->get('mode'));
$config['linktype'] = intval($params->get('linktype'));
$config['display_body'] = intval($params->get('display_body'));
$config['lxt_type'] = intval($params->get('linktype'));
$config['forum_mode'] = $params->get('forum_mode', 0);
//$config['selected_forums'] = $params->get('selected_forums');
$config['display_limit'] = intval($params->get('display_limit'));
$config['display_limit_subject'] = intval($params->get('display_limit_subject'));
$config['result_limit'] = intval($params->get('result_limit'));
$config['date_format'] = $params->get('custom_date', _DATE_FORMAT_LC2);
$config['tz_offset'] = intval($params->get('tz_offset'));
$config['result_order'] = (intval($params->get('result_order'))) ? "DESC" : "ASC";
$config['showdate'] = intval($params->get('showdate'));
$config['showuser'] = intval($params->get('showuser'));
$config['userlink'] = intval($params->get('userlink'),false);
$config['userlink_software'] = $params->get('userlink_software',false);
$config['userlink_custom'] = $params->get('userlink_custom',false);
$config['view'] = $params->get('link_mode');
$config['debug'] = $params->get('debug');
$config['itemid'] = $params->get('itemid');

if ($params->get('new_window')) {
	$config['new_window'] = '_blank';
} else {
    $config['new_window'] = '_self';
}

$pluginParamValue = $params->get('JFusionPluginParam');
$pluginParamValue = unserialize(base64_decode($pluginParamValue));

if(is_array($pluginParamValue)) {
	$outputAll = array();
	foreach($pluginParamValue as $jname => $value) {
		$pluginParam = new JParameter('');
		$pluginParam->loadArray($value);
		$view = $pluginParam->get('view', 'auto');
		
		$output = "";
		if($title = $pluginParam->get('title', NULL)) {
			$output = "<h4>".$pluginParam->get('title', $jname)."</h4>\n";
		}
		if($view == 'auto') {
			$output .= modjfusionActivityHelper::renderPluginAuto($jname, $config, $params);
		} else {
			$output .= modjfusionActivityHelper::renderPluginMode($jname, $config, $view, $pluginParam);
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

