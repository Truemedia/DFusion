<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class jfusionViewadvancedparam extends JView {
	var $configArray = array(1 => array(1 => "config.xml", 2 => " WHERE status = 1"),
		                     2 => array(1 => "activity.xml", 2 => "WHERE activity = 1 and status = 1"),
		                     3 => array(1 => "search.xml", 2 => " WHERE search = 1 and status =1"),
							 4 => array(1 => "whosonline.xml", 2 => "WHERE status = 1"));
	function display($tpl = null){
		global $mainframe, $option;
		
		//Load Current Configfile
		$config = JRequest::getVar('configfile');
		if(empty($config)) {
			$config=null;
		}
		
		//Load multiselect
		$multiselect = JRequest::getVar('multiselect');
		if($multiselect) {
			$multiselect=true;
			//Load Plugin XML Parameter
			$params = $this->loadXMLParamMulti($config);
			//Load enabled Plugin List
			list($output, $js) = $this->loadElementMulti($params, $config);
		} else {
			$multiselect=false;
			//Load Plugin XML Parameter
			$params = $this->loadXMLParamSingle($config);
			//Load enabled Plugin List
			list($output, $js) = $this->loadElementSingle($params, $config);
		}
		
		//Add Document dependen things like javascript, css
		$document	= & JFactory::getDocument();
		$document->setTitle('Plugin Selection');
		$template = $mainframe->getTemplate();
		$document->addStyleSheet("templates/$template/css/general.css");
		$document->addScriptDeclaration($js);

		$this->assignRef('output', $output);
		$this->assignRef('comp', $params);

		JHTML::_('behavior.modal');
		JHTML::_('behavior.tooltip');

		parent::display($multiselect?'multi':'single');
	}
	
	function loadElementSingle($params, $config) {
		$JPlugin = $params->get('jfusionplugin', '');
		$db = & JFactory::getDBO();
		$query = 'SELECT name as id, name as name from #__jfusion '.$this->configArray[$config][2];
		$db->setQuery($query );
		$noSelected = new stdClass();
		$noSelected->id = NULL;
		$noSelected->name = "";
		$rows = array_merge(array($noSelected), $db->loadObjectList());
		
		$attributes = array("size" => "1",
			"class" => "inputbox",
			"onchange" => "jPluginChange(this);"
		);

		if (!empty($rows)) {
			$output = JHTML::_('select.genericlist', $rows, 'params[jfusionplugin]', $attributes,
                'id', 'name', $JPlugin);
		} else {
			$output = JText::_('NO_VALID_PLUGINS');
		}
		
		$configLink = "";
		if(isset($this->configArray[$config])) {
			$configLink = "&configfile=".$config;
		}
		
		$js = "
		function jPluginChange(select) {
			plugin = select.options[select.selectedIndex].value;
			plugin = 'a:1:{s:13:\"jfusionplugin\";s:'+plugin.length+':\"'+plugin+'\";}';
			value = encode64(plugin);
			window.location.href = 'index.php?option=com_jfusion&task=advancedparam' + 
			                       '&tmpl=component".$configLink."&params='+value;
		}
		
		function encode64(inp){
		    var key='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
		    var chr1,chr2,chr3,enc3,enc4,i=0,out='';
		    while(i<inp.length){
		        chr1=inp.charCodeAt(i++);if(chr1>127) chr1=88;
		        chr2=inp.charCodeAt(i++);if(chr2>127) chr2=88;
		        chr3=inp.charCodeAt(i++);if(chr3>127) chr3=88;
		        if(isNaN(chr3)) {enc4=64;chr3=0;} else enc4=chr3&63
		        if(isNaN(chr2)) {enc3=64;chr2=0;} else enc3=((chr2<<2)|(chr3>>6))&63
		        out+=key.charAt((chr1>>2)&63)+key.charAt(((chr1<<4)|(chr2>>4))&63)+key.charAt(enc3)+key.charAt(enc4);
		    }
		    return encodeURIComponent(out);
		}";
		
		return array($output, $js);
	}
	
	function loadXMLParamSingle( $config ) {
		global $option;

		//Load current Parameter
		$value = JRequest::getVar('params');
		if(empty($value)) {
			$value=array();
		} else {
			$value=base64_decode($value);
			$value=unserialize($value);
			if(!is_array($value)) {
				$value = array();
			}
		}
		
		//Load Plugin XML Parameter
		$params = new JParameter( '' );
		$params->loadArray($value);
		$params->addElementPath(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'elements');
		$JPlugin = $params->get('jfusionplugin', '');
		if(isset($this->configArray[$config]) && !empty($JPlugin)) {
			$path = JPATH_ADMINISTRATOR.DS.'components'.DS.$option.DS.'plugins'.DS.
			        $JPlugin.DS.$this->configArray[$config][1];
			if (file_exists( $path ))
			{
				$xml =& JFactory::getXMLParser('Simple');
				if ($xml->loadFile($path))
				{
					$params->setXML( $xml->document->params[0] );
				}
			}
		}
		return $params;
	}
	
	function loadElementMulti($params, $config) {
		$db = & JFactory::getDBO();
		$query = 'SELECT name as id, name as name from #__jfusion '.$this->configArray[$config][2];
		$db->setQuery($query );
		$rows = $db->loadObjectList();
		
		$attributes = array(
			"size" => "1",
			"class" => "inputbox"
		);

		if (!empty($rows)) {
			$output = JHTML::_('select.genericlist', $rows, 'jfusionplugin', $attributes,
                'id', 'name');
			$output .= '&nbsp;<input type="button" value="add" name="add" onclick="jPluginAdd(this);" />';
		} else {
			$output = JText::_('NO_VALID_PLUGINS');
		}
		
		$configLink = "";
		if(isset($this->configArray[$config])) {
			$configLink = "&configfile=".$config;
		}
		
		$js = "
		function jPluginAdd(button) {
			button.form.jfusion_task.value = 'add';
			button.form.action = 'index.php?option=com_jfusion&task=advancedparam' + 
			                       '&tmpl=component".$configLink."&multiselect=1';
			button.form.submit();
		}
		function jPluginRemove(button, value) {
			button.form.jfusion_task.value = 'remove';
			button.form.jfusion_value.value = value;
			button.form.action = 'index.php?option=com_jfusion&task=advancedparam' + 
			                       '&tmpl=component".$configLink."&multiselect=1';
			button.form.submit();
		}";
		
		return array($output, $js);
	}
	
	function loadXMLParamMulti( $config ) {
		global $option;

		//Load current Parameter
		$value = JRequest::getVar('params');
		if(empty($value)) {
			$value=array();
		} else if(!is_array($value)) {
			$value=base64_decode($value);
			$value=unserialize($value);
			if(!is_array($value)) {
				$value = array();
			}
		}
		
		$task = JRequest::getVar('jfusion_task');
		if($task == 'add') {
			$newPlugin = JRequest::getVar('jfusionplugin');
			if(!array_key_exists($newPlugin, $value)) {
				$value[$newPlugin] = array('jfusionplugin' => $newPlugin);
			} else {
				$this->assignRef('error', JText::_('NOT_ADDED_TWICE'));
			}
		} else if($task == 'remove') {
			$rmPlugin = JRequest::getVar('jfusion_value');
			if(array_key_exists($rmPlugin, $value)) {
				unset($value[$rmPlugin]);
			} else {
				$this->assignRef('error', JText::_('NOT_PLUGIN_REMOVE'));
			}
		}
		
		foreach(array_keys($value) as $key) {
			$params = new JParameter('');
			$params->loadArray($value[$key]);
			$params->addElementPath(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'elements');
			$JPlugin = $params->get('jfusionplugin', '');
			if(isset($this->configArray[$config]) && !empty($JPlugin)) {
				$path = JPATH_ADMINISTRATOR.DS.'components'.DS.$option.DS.'plugins'.DS.
				        $JPlugin.DS.$this->configArray[$config][1];
				if (file_exists( $path ))
				{
					$xml =& JFactory::getXMLParser('Simple');
					if ($xml->loadFile($path))
					{
						$params->setXML( $xml->document->params[0] );
					}
				}
			}
			$value[$key]["params"] = $params;			
		}
		
		return $value;
	}
}
?>
