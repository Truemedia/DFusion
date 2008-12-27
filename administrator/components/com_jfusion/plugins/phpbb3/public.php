<?php

/**
* @package JFusion_phpBB3
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Public Class for phpBB3
 * For detailed descriptions on these functions please check the model.abstractpublic.php
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

        //add a mode for quicktools


        //add check for search function
        $submit = JRequest::getVar('submit');
        if($submit == 'Search'){
            $jfile = 'search.php';
        }

        //add check for quick mod tools function
        $quickmod = JRequest::getVar('quickmod');
        if($quickmod == 1){
            $jfile = 'mcp.php';
			$_GET['mode'] = 'quickmod';
            $_REQUEST['mode'] = 'quickmod';
            $_POST['mode'] = 'quickmod';
        }

        if (!$jfile) {
            //use the default index.php
            $jfile = 'index.php';
        }

		//allow for fix action urls for ucp.php
		if ($jfile == 'ucp.php'){
			global $jfusion_file;
			$jfusion_file = 'ucp.php';
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

        //get the Itemid
        $Itemid_joomla = JRequest::getVar('Itemid');

        /* set scope for variables required later */
        global $phpbb_root_path, $phpEx, $db, $config, $user, $auth, $cache, $template, $phpbb_hook, $module, $mode;

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

        //reset the global itemid
        global $Itemid;
        $Itemid = $Itemid_joomla;

        return $buffer;
    }



    function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_body, $replace_body;

        //some urls such as PM related ones have items appended to it after the url has been parsed by append_sid()
        $url_search = '#(href|action)="(.*?)"(.*?)>#mS';
        $buffer = preg_replace_callback($url_search,'fixAppendedQueries',$buffer);

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
        }

        $buffer = preg_replace($regex_body, $replace_body, $buffer);
    }

      function fixURL($url){
      	$url = preg_replace('#(/&amp\;|/\?|&amp;)(.*?)\=#mS', '/$2,', $url);
      	return $url . '"';
      }

      function fixAction($url, $extra, $baseURL){
      	$url = htmlspecialchars_decode($url);

      	//check to see if the URL is in SEF
		if (strpos($url,'index.php/')){
			$parts = preg_split('/\/\&|\//', $url);
			foreach ($parts as $part){
				$vars =preg_split('/,|=/', $part);
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

		//actions references from the ucp file always go back to ucp
		global $jfusion_file;
		if($jfusion_file == 'ucp.php'){
			$jfile = $jfusion_file;
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

        //needed in case the url is generated using default view from the joomla_int plugin
		$view = JRequest::getVar('view');
		if ($view){
			$replacement .=  '<input type="hidden" name="view" value="'. $view . '">';
		}
		unset($url_variables['view']);
		$jname = JRequest::getVar('jname');
		if ($jname){
			$replacement .=  '<input type="hidden" name="jname" value="'. $jname . '">';
		}
		unset($url_variables['jname']);

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

		//detect the redirect value
		$redirect = JRequest::getVar('redirect');
      	//split up the timeout from url
		$parts = explode(';url=', $url);

		//allow for redirects on login
		if($redirect){
			$parts[1] = $redirect;
		}

    	//get the correct URL to joomla
    	$params = JFusionFactory::getParams('joomla_int');
		$source_url = $params->get('source_url');

      	//check to see if the URL is in SEF
		if (strpos($parts[1],'index.php/')){
		    $query = explode('index.php/', $parts[1]);
    		$redirect_url = $source_url . 'index.php/' . $query[1];
		} else {
			//parse the non-SEF URL
			$uri = new JURI($parts[1]);

			//set the URL with the jFusion params to correct any domain mistakes
			//set the jfusion references for Joomla
        	$Itemid = JRequest::getVar('Itemid');
        	if ($Itemid){
				$uri->setVar('Itemid', $Itemid);
        	}
			$uri->setVar('option', 'com_jfusion');

			$redirect_url = $source_url . 'index.php'.$uri->toString(array('query', 'fragment'));
			$redirect_url = urldecode(JRoute::_($redirect_url, true));
	    }

       //let's do a test to see if the url exists as some redirects are corrupted by phpBB when using SEF
	   //if it does not, then reconstruct the url for the main page of the board
      	$exists = (($ftest = @fopen($redirect_url, ‘r’)) === false) ? false : @fclose($ftest);

		if(!$exists)
		{
			//get the current base
			$base = JURI::base();

			//do we have a sid?
			if(strpos($url,'sid,')){
				$redirect_url = preg_replace('#url=(.*)\/sid,(.*)\/#mS',"$base/index.php?sid=$2",$url);
			} else {
				$redirect_url = $base;
			}

			//Create the URL
			$uri = new JURI($redirect_url);

			//set the jfusion references for Joomla
        	$Itemid = JRequest::getVar('Itemid');
        	if ($Itemid){
				$uri->setVar('Itemid', $Itemid);
        	}
			$uri->setVar('option', 'com_jfusion');

			$redirect_url = 'index.php'.$uri->toString(array('query', 'fragment'));
			$redirect_url = urldecode(JRoute::_($redirect_url, true));
		}

      	//reconstruct the redirect meta tag
        return '<meta http-equiv="refresh" content="'.$parts[0].';url=' . $redirect_url .'">';
      }
}

function fixAppendedQueries($matches)
{
	$tag = $matches[1];
	$url = $matches[2];
	$extra = $matches[3];

	//Clean the url and the params first
	$url  = str_replace( '&amp;', '&', $url );

	//we need to make some exceptions

	//this only applies to a SEF'd URL
	if(!strpos($url,"jfile,")){
		return "$tag=\"$url\" $extra >";
	}

	//only parse urls that have a query attached
	if(!strpos($url,"?") && !strpos($url,"&")){
		return "$tag=\"$url\" $extra >";
	}

	//we need to get the query
	$query = explode("&",$url);

	$url = "";

	//now rebuild the URL
	foreach($query as $k => $q)
	{
		$url .= str_replace("=",",",$q)."/";
	}

	//set the correct url and close the a tag
	$replacement = "$tag=\"$url\" $extra >";

	return $replacement;
}