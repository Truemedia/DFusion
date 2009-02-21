<?php
/**
 * @version		$Id: album.php 164 2008-03-03 14:26:18Z Sil3nt $
 * @package		Joomla
 * @copyright	Copyright (C) 2005 - 2007 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class JElementJFusionAdvancedParam extends JElement
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'JFusionAdvancedParam';

	function fetchElement($name, $value, &$node, $control_name)
	{
		global $mainframe;

		$db			=& JFactory::getDBO();
		$doc 		=& JFactory::getDocument();
		$fieldName	= $control_name.'['.$name.']';
		$configfile = $node->attributes("configfile");
		$multiselect = $node->attributes("multiselect");

		$js = "
		function jAdvancedParamSet(title, base64) {
			var link = 'index.php?option=com_jfusion&task=advancedparam&tmpl=component&params=';
			link += base64;";
		if(!is_null($configfile)) {
			$js .= "
			link += '&configfile=".$configfile."';";
		}
		if(!is_null($multiselect)) {
			$js .= "
			link += '&multiselect=1';";
		}
		$js .= "
			document.getElementById('plugin_id').value = base64;
			document.getElementById('plugin_name').value = title;
			document.getElementById('plugin_link').href = link;
			document.getElementById('sbox-window').close();
		}";
		$doc->addScriptDeclaration($js);

		//Create Link
		$link = 'index.php?option=com_jfusion&amp;task=itemidselect&amp;tmpl=component&amp;params='.$value;
		if(!is_null($configfile)) {
			$link .= "&amp;configfile=".$configfile;
		}
		if(!is_null($multiselect)) {
			$link .= "&amp;multiselect=1";
		}

		//Get JParameter from given string
		if(empty($value)) {
			$params=array();
		} else {
			$params=base64_decode($value);
			$params=unserialize($params);
			if(!is_array($params)) {
				$params = array();
			}
		}

		$title = "";
		if(isset($params["jfusionplugin"])) {
			$title = $params["jfusionplugin"];
		} else if($multiselect) {
			$del = "";
			foreach($params as $key => $param) {
				if(isset($param["jfusionplugin"])) {
					$title .= $del . $param["jfusionplugin"];
					$del = "; ";
				}
			}
		}
		if(empty($title)) {
			$title = "No Plugin Selected";
		}

		//Replace new Lines with the placeholder \n
		JHTML::_('behavior.modal', 'a.modal');
		$html = "\n<div style=\"float: left;\"><input style=\"background: #ffffff;\" type=\"text\" id=\"plugin_name\" value=\"".$title."\" disabled=\"disabled\" /></div>";
		$html .= "<div class=\"button2-left\"><div class=\"blank\"><a id=\"plugin_link\" class=\"modal\" title=\"".JText::_('Select an JFusionPlugin')."\"  href=\"$link\" rel=\"{handler: 'iframe', size: {x: 650, y: 375}}\">".JText::_('Select')."</a></div></div>\n";
		$html .= "\n<input type=\"hidden\" id=\"plugin_id\" name=\"$fieldName\" value=\"$value\" />";
		return $html;
	}
}