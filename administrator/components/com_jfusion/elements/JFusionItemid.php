<?php
/**
* @package JFusion
* @subpackage Elements
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class JElementJFusionItemid extends JElement
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'JFusionItemid';

	function fetchElement($name, $value, &$node, $control_name)
	{
		global $mainframe;

		static $elId;
		
		if(!is_int($elId)) {
			$elId = 0;
		} else {
			$elId++;
		}
		
		$db			=& JFactory::getDBO();
		$doc 		=& JFactory::getDocument();
		$template 	= $mainframe->getTemplate();
		$fieldName	= $control_name.'['.$name.']';

  	    $js = "
		function jSelectItemid(id,num) {
			document.getElementById('{$name}_id'+num).value = id;
			document.getElementById('{$name}_name'+num).value = id;
			document.getElementById('sbox-window').close();
		}";
		$doc->addScriptDeclaration($js);

		$link = 'index.php?option=com_jfusion&amp;task=itemidselect&amp;tmpl=component&amp;ename='.$name.'&amp;elId='.$elId;

		JHTML::_('behavior.modal', 'a.modal');
		$html = "\n".'<div style="float: left;"><input style="background: #ffffff;" type="text" id="'.$name.'_name'.$elId.'" value="'.$value.'" disabled="disabled" /></div>';
		$html .= '<div class="button2-left"><div class="blank"><a class="modal" title="'.JText::_('Select an Article').'"  href="'.$link.'" rel="{handler: \'iframe\', size: {x: 650, y: 375}}">'.JText::_('Select').'</a></div></div>'."\n";
		$html .= "\n".'<input type="hidden" id="'.$name.'_id'.$elId.'" name="'.$fieldName.'" value="'.(int)$value.'" />';

		return $html;
	}
}
