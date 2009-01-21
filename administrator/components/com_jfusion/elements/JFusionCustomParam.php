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

class JElementJFusionCustomParam extends JElement
{
	/**
	 * Element name
	 *
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'JFusionCustomParam';

	function fetchElement($name, $value, &$node, $control_name)
	{
        global $jname;
		if ($jname){
			//load the custom param output
            $JFusionPlugin = JFusionFactory::getAdmin($jname);
            if(method_exists($JFusionPlugin,$value)){
	            $output = $JFusionPlugin->{$value}();
    	        return $output;
            } else {
				return 'Undefined function:'.$value.' in plugin:' . $jname;
            }
        } else {
            return 'Programming error: You must define global $jname before the JParam object can be rendered';
        }
	}
}