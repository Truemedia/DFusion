<?php
/**
* @package JFusion
* @subpackage Views
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* load the JFusion framework
*/
jimport('joomla.application.component.view');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');

/**
 * Class that handles the framelesss integration
 * @package Views
 */
class jfusionViewframeless extends JView {

    function display($tpl = null)
    {
        $application = JFactory::getApplication();
        $uri		= JURI::getInstance();

        //Get the base URL and make sure we have a ? delimiter
        $Itemid = JRequest::getVar('Itemid');
        if ($Itemid) {
            $baseURL	= JRoute::_(JURI::base() .'index.php?option=com_jfusion&Itemid=' . $Itemid);
        } else {
            $baseURL	= JRoute::_('index.php?option=com_jfusion&view=frameless&jname='.$this->jname);
        }

        //Get the full URL, making note of the query
        $query	= $uri->getQuery();
        $url	= $uri->current();
        $fullURL = $url.'?'.$query;

        //Get the integrated URL
        $JFusionParam = JFusionFactory::getParams($this->jname);
        $integratedURL =$JFusionParam->get('source_url');

        // Get the output from the JFusion plugin
        $JFusionPlugin = JFusionFactory::getPublic($this->jname);

        $buffer =& $JFusionPlugin->getBuffer($this->jPluginParam);
		//check to see if the Joomla database is still connnected incase the plugin messed it up
		JFusionFunction::reconnectJoomlaDb();
		
		if ($buffer === 0){
            JError::raiseWarning(500, JText::_('NO_FRAMELESS'));
            $result = false;
            return $result;
		}

        if (! $buffer ) {
            JError::raiseWarning(500, JText::_('NO_BUFFER'));
            $result = false;
            return $result;
        }

		//we set the backtrack_limit to twice the buffer length just in case!
		$backtrack_limit = ini_get('pcre.backtrack_limit');
		ini_set('pcre.backtrack_limit',strlen($buffer)*2);

        $pattern	= '#<head>(.*?)</head>\s*<body>(.*)</body>#';
        $pattern	= '#<head[^>]*>(.*)<\/head>\s*<body[^>]*>(.*)<\/body>#si';
        preg_match($pattern, $buffer, $data);

        // Check if we found something
        if (count($data) < 3 || !strlen($data[1]) || !strlen($data[2])) {
            JError::raiseWarning(500, JText::_('NO_HTML'));
        } else {
			// Add the header information
            if (isset($data[1]) ) {
                $document	= JFactory::getDocument();
                global $mainframe;
				$regex_header = array();
				$replace_header = array();

	            //change the page title
				$pattern = '#<title>(.*?)<\/title>#Si';
				preg_match($pattern, $data[1], $page_title);
				$mainframe->setPageTitle(html_entity_decode( $page_title[1], ENT_QUOTES, "utf-8" ));
        		$regex_header[]	= $pattern;
        		$replace_header[] = '';

				//set meta data to that of softwares
        		$meta = array('keywords','description','robots');

				foreach($meta as $m) {
	        		$pattern = '#<meta name=["|\']'.$m.'["|\'](.*?)content=["|\'](.*?)["|\'](.*?)>#Si';
	        		 if (preg_match($pattern, $data[1], $page_meta)){
				    	if($page_meta[2]) {
				    		$document->setMetaData( $m, $page_meta[2] );
				    	}
				    	$regex_header[]	= $pattern;
	       				$replace_header[] = '';
				    }
        		}

        		$pattern = '#<meta name=["|\']generator["|\'](.*?)content=["|\'](.*?)["|\'](.*?)>#Si';
                if(preg_match($pattern, $data[1], $page_generator)) {
                	if($page_generator[2]) {
                		$document->setGenerator( $document->getGenerator().', '. $page_generator[2]);
                	}
					$regex_header[]	= $pattern;
					$replace_header[] = '';
                }

                //use Joomla's default
                $regex_header[]	= '#<meta http-equiv=["|\']Content-Type["|\'](.*?)>#Si';
				$replace_header[] = '';

                //remove above set meta data from software's header
    	        $data[1] = preg_replace($regex_header, $replace_header, $data[1]);

				$JFusionPlugin->parseHeader($data[1], $baseURL, $fullURL, $integratedURL);
                $document->addCustomTag($data[1]);
            }

            // Output the body
            if (isset($data[2]) ) {
                // 	parse the URL's'
                $JFusionPlugin->parseBody($data[2], $baseURL, $fullURL, $integratedURL);
                echo $data[2];
            }
			ini_set('pcre.backtrack_limit',$backtrack_limit);
        }
    }
}
