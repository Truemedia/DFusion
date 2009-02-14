<?php
/** This file is used to simulate the functions required to parse text for the discussion bot **/

/**
 * "Cleans" a string
 * @param $text
 * @return clean string
 */
function utf8_clean_string($text)
{
	if (!function_exists('utf8_clean_string_phpbb')) {
		//load the filtering functions for phpBB3
		global $jname;
		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$jname.DS.'username_clean.php');
	}
	$text = utf8_clean_string_phpbb($text);
	return $text;
}
?>