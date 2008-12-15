<?php

/**
* @package JFusion_SMF
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractpublic.php');

/**
* JFusion plugin class for SMF 1.1.4
* @package JFusion_SMF
*/
class JFusionPublic_smf extends JFusionPublic{

    function getJname()
    {
        return 'smf';
    }

    function getRegistrationURL()
    {
        return 'index.php?action=register';
    }

    function getLostPasswordURL()
    {
        return 'index.php?action=reminder';
    }

    function getLostUsernameURL()
    {
        return 'index.php?action=reminder';
    }

	function & getBuffer()
	{
		// We're going to want a few globals... these are all set later.
		global $time_start, $maintenance, $msubject, $mmessage, $mbname, $language;
		global $boardurl, $boarddir, $sourcedir, $webmaster_email, $cookiename;
		global $db_server, $db_name, $db_user, $db_prefix, $db_persist, $db_error_send, $db_last_error;
		global $db_connection, $modSettings, $context, $sc, $user_info, $topic, $board, $txt;
		global $scripturl;

		// Required to avoid a warning about a license violation even though this is not the case
		global $forum_version;

		require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'hooks.php');


		// Get the path
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');
        if (substr($source_path, -1) == DS) {
            $index_file = $source_path .'index.php';
        } else {
            $index_file = $source_path .DS.'index.php';
        }

		if ( ! is_file($index_file) ) {
            JError::raiseWarning(500, 'The path to the SMF index file set in the component preferences does not exist');
			return null;
		}

		//set the current directory to SMF
		chdir($source_path);

		// Get the output
		ob_start();
		$rs = include_once($index_file);
		$buffer = ob_get_contents();
		ob_end_clean();

		//change the current directory back to Joomla.
		chdir(JPATH_SITE);


		// Log an error if we could not include the file
		if (!$rs) {
            JError::raiseWarning(500, 'Could not find SMF in the specified directory');
		}

		return $buffer;
	}



	function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_body, $replace_body;

		if ( ! $regex_body || ! $replace_body )
		{
			// Define our preg arrayshttp://www.jfusion.org/administrator/index.php?option=com_extplorer#
			$regex_body		= array();
			$replace_body	= array();

			//convert relative links with query into absolute links
			$regex_body[]	= '#' . $integratedURL . 'index.php\?(.*?)"#mS';
			$replace_body[]	=  $baseURL . '&$1"';

			//convert relative links without query into absolute links
			$regex_body[]	= '#' . $integratedURL . 'index.php"#mS';
			$replace_body[]	= $baseURL . '"';

			//convert relative links from images into absolute links
			$regex_body[]	= '#\./(.*?)("|\))#mS';
			$replace_body[]	= $integratedURL.'$1$2"';

			//convert links to the same page with anchors
			$regex_body[]	= '#href="\#(.*?)"#';
			$replace_body[]	= 'href="'.$fullURL.'&#$1"';
		}

		$buffer = preg_replace($regex_body, $replace_body, $buffer);
	}

	function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
	{
		static $regex_header, $replace_header;

		if ( ! $regex_header || ! $replace_header )
		{
			// Define our preg arrays
			$regex_header		= array();
			$replace_header	= array();

			//convert relative links into absolute links
			$regex_header[]	= '#(href|src)=("./|"/)(.*?)"#mS';
			$replace_header[]	= 'href="'.$integratedURL.'$3"';

			//$regex_header[]	= '#(href|src)="(.*)"#mS';
			//$replace_header[]	= 'href="'.$integratedURL.'$2"';

		}

		$buffer = preg_replace($regex_header, $replace_header, $buffer);
}

}

