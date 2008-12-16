<?php

/**
* @package JFusion_phpBB3
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
* JFusion plugin class for phpBB3
* @package JFusion_phpBB3
*/
class JFusionPublic_phpbb3 extends JFusionPublic{

    function getJname()
    {
        return 'phpbb3';
    }

    function getRegistrationURL()
    {
        return 'ucp.php?mode=register';
    }

    function getLostPasswordURL()
    {
        return 'ucp.php?mode=sendpassword';
    }

    function getLostUsernameURL()
    {
        return 'ucp.php?mode=sendpassword';
    }

    function & getBuffer()
    {
        // Get the path
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');

        //Allow for admin redirects in the hooks
        global $jfusion_source_url;
        $jfusion_source_url = $params->get('source_url');

        //get the filename
        $jfile = JRequest::getVar('jfile');

        //redirect directly to admincp if needed
        if ($jfile == 'adm/index.php') {
            $url ="Location: " . $params->get('source_url') . 'adm/index.php?' . $_SERVER['QUERY_STRING'] ;
            header($url);
        }

		//redirect for file download requests
        if ($jfile == 'file.php') {
            $url ="Location: " . $params->get('source_url') . 'download/file.php?' . $_SERVER['QUERY_STRING'] ;
            header($url);
        }

        //add check for thread subscriptions
        $subscribe = JRequest::getVar('e');
        if($subscribe){
            $jfile = 'viewtopic.php';
            $_GET['p'] = $subscribe;
            $_REQUEST['p'] = $subscribe;
            $_POST['p'] = $subscribe;
        }

        //add check for search function
        $submit = JRequest::getVar('submit');
        if($submit == 'Search'){
            $jfile = 'search.php';
        }

        if (!$jfile) {
            //use the default index.php
            $jfile = 'index.php';
        }


        //combine the path and filename
        if (substr($source_path, -1) == DS) {
            $index_file = $source_path . basename($jfile);
        } else {
            $index_file = $source_path . DS . basename($jfile);
        }

        if (! is_file($index_file) ) {
            JError::raiseWarning(500, 'The path to the requested does not exist');
            $result = false;
            return $result;
        }

        //set the current directory to phpBB3
        chdir($source_path);

        /* set scope for variables required later */
        global $phpbb_root_path, $phpEx, $db, $config, $user, $auth, $cache, $template, $phpbb_hook, $module;

        //define the phpBB3 hooks
        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $this->getJname().DS.'hooks.php');

        // Get the output
        ob_start();
        try {
            define('UTF8_STRLEN', true);
            define('UTF8_CORE', true);
            define('UTF8_CASE', true);

            include_once($index_file);
        }
        catch(Exception $e) {
            $buffer = ob_get_contents() ;
            ob_end_clean();
        }

		global $mainframe;
		$mainframe->setPageTitle('');

        //change the current directory back to Joomla.
        chdir(JPATH_SITE);

        return $buffer;
    }



    function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_body, $replace_body;

        if (! $regex_body || ! $replace_body ) {
            // Define our preg arrays
            $regex_body		= array();
            $replace_body	= array();

            //convert relative links from images into absolute links
	        $regex_body[]	= '#(src="|background="|url\(\'?)./(.*?)("|\'?\))#mS';
            $replace_body[]	= '$1'.$integratedURL.'$2$3';

			//fix for form actions
	        $regex_body[]	= '#action="(.*?)"(.*?)>#me';
			$uri		= JURI::getInstance();
			$indexURL	= JURI::base() .'index.php';
            $replace_body[]	= '$this->fixAction("$1","$2","' . $indexURL .'")';

			//phpBB3 URL parsing is not perfect, if sh404SEF is enabled some extra cleanup is needed
            $params = JFusionFactory::getParams('joomla_int');
			$sh404sef_parse = $params->get('sh404sef_parse');
			if ($sh404sef_parse == 1){
	            $currentURL = JURI::base();
	            //fix up and ampersands that slipped past the parse url function.
		        $regex_body[]	= '#'.$currentURL.'(.*?)(/&amp\;|/\?)(.*?)"#me';
				$replace_body[]	= '$this->fixURL("'.$currentURL.'$1$2$3")';
			}
        }

        $buffer = preg_replace($regex_body, $replace_body, $buffer);
    }

      function fixURL($url){
      	$url = preg_replace('#(/&amp\;|/\?|&amp;)(.*?)\=#mS', '/$2,', $url);
      	return $url . '"';
      }

      function fixAction($url, $extra, $baseURL){
      	$url = htmlspecialchars_decode($url);

      	//check to see if SH404SEF is enabled
        $params = JFusionFactory::getParams('joomla_int');
    	$sh404sef_parse = $params->get('sh404sef_parse');
		if ($sh404sef_parse == 1){
			$parts = explode('/', $url);
			foreach ($parts as $part){
				$vars = explode(',', $part);
				if(isset($vars[1])){
					$url_variables[$vars[0]] = $vars[1];
				}
			}
	    } else {
	      	$url_details = parse_url($url);
    	  	$url_variables = array();
      		parse_str($url_details['query'], $url_variables);
		}

      	//set the correct action and close the form tag
		$replacement = 'action="'.$baseURL . '"' . $extra . '>';

		//add which file is being referred to
		if ($url_variables['jfile']){
        	//use the action file that was in jfile variable
        	$jfile = $url_variables['jfile'];
        	unset($url_variables['jfile']);
		} else {
			//get the filename
        	$jfile = JRequest::getVar('jfile');
        	if(!$jfile){
				//use the action file from the action URL itself
    	     	$jfile = basename($url_details['path']);
        	}
		}
      	$replacement .= '<input type="hidden" name="jfile" value="'. $jfile . '">';

		//add a reference to JFusion
      	$replacement .= '<input type="hidden" name="option" value="com_jfusion">';
        unset($url_variables['option']);

		//add a reference to the itemid if set
        $Itemid = JRequest::getVar('Itemid');
	    if ($Itemid){
      		$replacement .=  '<input type="hidden" name="Itemid" value="'.$Itemid . '">';
	    }
        unset($url_variables['Itemid']);

		//add any other variables
		if(is_array($url_variables)){
			 foreach ($url_variables as $key => $value){
      			$replacement .=  '<input type="hidden" name="'. $key .'" value="'.$value . '">';
      		}
		}

      	return $replacement;
      }

    function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_header, $replace_header;

        if (! $regex_header || ! $replace_header ) {
            // Define our preg arrays
            $regex_header		= array();
            $replace_header	= array();

            //convert relative links into absolute links
           $regex_header[]	= '#(href="|src=")./(.*?")#mS';
           $replace_header[]	= '$1'.$integratedURL.'$2"';

           //fix for URL redirects
           $regex_header[]	= '#<meta http-equiv="refresh" content="(.*?)"(.*?)>#me';
		   $replace_header[]	= '$this->fixRedirect("$1")';

        }
        $buffer = preg_replace($regex_header, $replace_header, $buffer);
    }

      function fixRedirect($url){
      	//split up the timeout from url
		$parts = explode(';url=', $url);

    	//get the correct URL to joomla
    	$params = JFusionFactory::getParams('joomla_int');
		$source_url = $params->get('source_url');

      	//check to see if SH404SEF is enabled
        $params = JFusionFactory::getParams('joomla_int');
    	$sh404sef_parse = $params->get('sh404sef_parse');
		if ($sh404sef_parse == 1){
			//get the query
		    $query = explode('index.php/', $url);
		    if(isset($query[1])){
				$redirect_url = $source_url . 'index.php/' . $query[1];
		    } else {
		    	$Itemid = JRequest::getVar('Itemid');
		    	$explode = explode('/', $url);
		    	$jfile = end($explode);
				$redirect_url = $source_url . 'index.php/component/option,com_jfusion/Itemid,' . $Itemid . '/jfile,' . $jfile;
		    }

	    } else {
			//parse the URL
			$uri = new JURI($parts[1]);

			//set the URL with the jFusion params to correct any domain mistakes
			$redirect_url = $source_url . 'index.php?' . $uri->getQuery();

	    }






      	//reconstruct the redirect meta tag
        return '<meta http-equiv="refresh" content="'.$parts[0] . ';url=' . $redirect_url .'">';
      }
}

