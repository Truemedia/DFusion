<?php

/**
* @package JFusion
* @subpackage Models
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
* Class for general JFusion functions
* @package JFusion
*/

class JFusionFunction{

    /**
* Returns the JFusion plugin name of the software that is currently the master of user management
* @param string $jname Name of master JFusion plugin
*/

    function getMaster()
    {
        static $jfusion_master;
        if (!isset($jfusion_master )) {
          	$db = & JFactory::getDBO();
	        $query = 'SELECT * from #__jfusion WHERE master = 1 and status = 1';
    	    $db->setQuery($query );
        	$jfusion_master = $db->loadObject();
	        if ($jfusion_master) {
    	        return $jfusion_master;
        	}
        } else {
    	    return $jfusion_master;
        }
    }

    /**
* Returns the JFusion plugin name of the software that are currently the slaves of user management
* @param string $jname Name of master JFusion plugin
*/

    function getSlaves()
    {
        static $jfusion_slaves;
        if (!isset($jfusion_slaves )) {
	        $db = & JFactory::getDBO();
    	    $query = 'SELECT * from #__jfusion WHERE slave = 1 and status = 1';
        	$db->setQuery($query );
	        $jfusion_slaves = $db->loadObjectList();
        }
    	return $jfusion_slaves;
    }

    /**
* By default, returns the JFusion plugin of the software that is currently the slave of user management, minus the joomla_int plugin.
* If activity, search, or discussion is passed in, returns the plugins with that feature enabled
* @param array $jname Array list of slave JFusion plugin names
*/

    function getPlugins($criteria = 'slaves')
    {
    	if($criteria = 'slaves') {
	        static $jfusion_plugins;
	        if (!isset($jfusion_plugins )) {
		        $db = & JFactory::getDBO();
	    	    $query = 'SELECT * from #__jfusion WHERE (slave = 1 AND status = 1 AND name NOT LIKE \'joomla_int\')';
		        $db->setQuery($query );
	    	    $jfusion_plugins = $db->loadObjectList();
	        }
	        return $jfusion_plugins;
    	} else {
    		if( $criteria = 'activity') {
   				$query = 'SELECT * from #__jfusion WHERE (status = 1 AND activity = 1 AND name NOT LIKE \'joomla_int\')';
	    	} elseif( $criteria = 'search') {
	    		$query = 'SELECT * from #__jfusion WHERE (status = 1 AND search = 1 AND name NOT LIKE \'joomla_int\')';
	    	} elseif( $criteria = 'discussion') {
				$query = 'SELECT * from #__jfusion WHERE (status = 1 AND discussion = 1 AND name NOT LIKE \'joomla_int\')';
	    	}

			$db = & JFactory::getDBO();
			$db->setQuery($query );
			$jfusion_plugins = $db->loadObjectList();
    	}
    }

    /**
* Returns the parameters of a specific JFusion integration
* @param string $jname name of the JFusion plugin used
* @return object Joomla parameters object
*/


    /**
* Saves the posted JFusion component variables
* @param string $jname name of the JFusion plugin used
* @param array $post Array of JFusion plugin parameters posted to the JFusion component
* @return true|false returns true if succesful and false if an error occurred
*/

    function saveParameters($jname, $post)
    {
        $mergedpost = array_merge((array) JFusionFactory::getParams($jname)->_registry['_default']['data'],$post);
        //serialize the $post to allow storage in a SQL field
        $serialized = base64_encode(serialize($mergedpost));

        //set the current parameters in the jfusion table
        $db = & JFactory::getDBO();
        $query = 'UPDATE #__jfusion SET params = ' . $db->Quote($serialized) .' WHERE name = ' . $db->Quote($jname);
        $db->setQuery($query );

        if (!$db->query()) {
            //there was an error saving the parameters
            JError::raiseWarning(0,$db->stderr());
            $result = false;
            return $result;
        }

        $result = true;
        return $result;
    }

    /**
* Checks to see if the JFusion plugins are installed and enabled
*/
    function checkPlugin()
    {
        $userPlugin = true;
        $authPlugin = true;
        if (!JFusionFunction::isPluginInstalled('jfusion','authentication', false)) {
            JError::raiseWarning(0,JText::_('FUSION_MISSING_AUTH'));
            $authPlugin = false;
        }

        if (!JFusionFunction::isPluginInstalled('jfusion','user', false)) {
            JError::raiseWarning(0,JText::_('FUSION_MISSING_USER'));
            $userPlugin = false;
        }

        if ($authPlugin && $userPlugin) {
            $jAuth = JFusionFunction::isPluginInstalled('jfusion','user',true);
            $jUser = JFusionFunction::isPluginInstalled('jfusion','authentication',true);
            if (!$jAuth) {
                JError::raiseNotice(0,JText::_('FUSION_READY_TO_USE_AUTH'));
            }
            if (!$jUser) {
                JError::raiseNotice(0,JText::_('FUSION_READY_TO_USE_USER'));
            }
        }
    }


    /**
* Checks to see if the configuration of a Jfusion plugin is valid and stores the result in the JFusion table
* @param string $jname name of the JFusion plugin used
*/

    /**
* Tests if a plugin is installed with the specified name, where folder is the type (e.g. user)
* @param string $element element name of the plugin
* @param string $folder folder name of the plugin
* @param integer $testPublished Variable to determine if the function should test to see if the plugin is published
* @return true|false returns true if succesful and false if an error occurred
*/
    function isPluginInstalled($element,$folder, $testPublished)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT published FROM #__plugins WHERE element=' . $db->Quote($element) . ' AND folder=' . $db->Quote($folder);
        $db->setQuery($query);
        $result = $db->loadObject();
        if ($result) {
            if ($testPublished) {
                return($result->published == 1);
            } else {
	            $result = true;
    	        return $result;
            }
        } else {
            $result = false;
            return $result;
        }
    }


    /**
* Returns either the Joomla wrapper URL or the full URL directly to the forum
* @param string $url relative path to a webpage of the integrated software
* @param string $jname name of the JFusion plugin used
* @return string full URL to the filename passed to this function
*/

    function createURL($url, $jname, $view, $itemid='')
    {
    	if(!empty($itemid)){
            //use the itemid only to identify plugin name and view type
            $base_url = 'index.php?option=com_jfusion&amp;Itemid=' . $itemid;
    	} else {
            $base_url = 'index.php?option=com_jfusion&amp;view=' . $view . '&amp;jname=' . $jname;
    	}

		if ($view == 'direct') {
            $params = JFusionFactory::getParams($jname);
            $url = $params->get('source_url') . $url;
            return $url;
		} elseif ($view == 'wrapper') {
        	//use base64_encode to encode the URL for passing.  But, base64_code uses / which throws off SEF urls.  Thus slashes
        	//must be translated into something base64_encode will not generate and something that will not get changed by Joomla or Apache.
            $url = $base_url . '&amp;wrap='. str_replace("/","_slash_",base64_encode($url));
            $url = JRoute::_($url);
            return $url;
        } elseif ($view == 'frameless'){
            //split the filename from the query
            $parts = explode('?', $url);
            if (isset($parts[1])) {
            	$base_url .= '&amp;jfile=' . $parts[0] . '&amp;' . $parts[1];
            } else {
            	$base_url .= '&amp;jfile=' . $parts[0];
            }

            $url = JRoute::_($base_url);
        	return $url;
        }
    }

    /**
* Updates the JFusion user lookup table during login
* @param object $userinfo object containing the userdata
* @param string $jname name of the JFusion plugin used
* @param string $joomla_id The Joomla ID of the user
*/

    function updateLookup($userinfo, $jname, $joomla_id)
    {

        $db =& JFactory::getDBO();
        //prepare the variables
        $lookup = new stdClass;
        $lookup->userid = $userinfo->userid;
        $lookup->username = $userinfo->username;
        $lookup->jname = $jname;
        $lookup->id = $joomla_id;


        //insert the entry into the lookup table
        $db->insertObject('#__jfusion_users_plugin', $lookup, 'autoid' );
    }

    /**
* Returns the userinfo data for JFusion plugin based on the userid
* @param string $jname name of the JFusion plugin used
* @param string $userid The ID of the user
* @param boolean $isJoomlaId if true, returns the userinfo data based on Joomla, otherwise the plugin
* @return object database Returns the userinfo as a Joomla database object
**/

    function lookupUser($jname, $userid, $isJoomlaId = true)
    {
    	$column = ($isJoomlaId) ? 'id' : 'userid';
        $db =& JFactory::getDBO();
        $query = 'SELECT * FROM #__jfusion_users_plugin WHERE '.$column.' = ' . $userid . ' AND jname = ' . $db->Quote($jname);
        $db->setQuery($query);
        $result = $db->loadObject();
        return $result;
    }

    /**
* Checks to see if a JFusion plugin is properly configured
* @param string $jname name of the JFusion plugin used
* @return bolean returns true if plugin is correctly configured
*/

    function validPlugin($jname)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT status FROM #__jfusion WHERE name =' . $db->Quote($jname);
        $db->setQuery($query);
        $result = $db->loadResult();
		if ($result == '1') {
            $result = true;
            return $result;
		} else {
            $result = false;
            return $result;
		}
    }

    function removeUser($userinfo)
    {
    	//Delete old user data in the lookup table
		$db =& JFactory::getDBO();
        $query = 'DELETE FROM #__jfusion_users WHERE id =' . $userinfo->userid . ' OR username =' . $db->Quote($userinfo->username);
       	$db->setQuery($query);
		if(!$db->query()) {
            JError::raiseWarning(0,$db->stderr());
        }

        $query = 'DELETE FROM #__jfusion_users_plugin WHERE id =' . $userinfo->userid ;
       	$db->setQuery($query);
	    if(!$db->query()) {
       		JError::raiseWarning(0,$db->stderr());
   		}

    }

    function addCookie($name, $value, $expires_time, $cookiepath, $cookiedomain, $httponly)
    {
    	if($expires_time != 0) {
			$expires = time() + intval($expires_time);
    	} else {
    		$expires = 0;
    	}

        // Versions of PHP prior to 5.2 do not support HttpOnly cookies and IE is buggy when specifying a blank domain so set the cookie manually
        $cookie = "Set-Cookie: {$name}=".urlencode($value);
        if ($expires > 0) {
            $cookie .= "; expires=".gmdate('D, d-M-Y H:i:s \\G\\M\\T', $expires);
        }
        if (!empty($cookiepath)) {
            $cookie .= "; path={$cookiepath}";
        }
        if (!empty($cookiedomain)) {
            $cookie .= "; domain={$cookiedomain}";
        }
        if ($httponly == true) {
            $cookie .= "; HttpOnly";
        }

   		header($cookie, false);

        //$document	= JFactory::getDocument();
	    //$document->addCustomTag($cookie);

    }

    function raiseWarning($type, $warning, $jerror){
    	if(is_array($warning)){
			foreach ($warning as $warningtext){
				if ($jerror){
					JError::raiseWarning('500', $type . ': '. $warningtext);
				} else {
					echo $type . ': '. $warningtext . '<br/>';
				}
			}
    	} else {
    		if ($jerror) {
				JError::raiseWarning('500', $type .': '. $warning);
    		} else {
				echo $type . ': '. $warning . '<br/>';
    		}
    	}
    }

	function phpinfo_array(){
		//get the phpinfo and parse it into an array
		ob_start();
		phpinfo();
		$phpinfo = array('phpinfo' => array());
		if(preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', ob_get_clean(), $matches, PREG_SET_ORDER))
    	foreach($matches as $match)
        if(strlen($match[1]))
        $phpinfo[$match[1]] = array();
        elseif(isset($match[3]))
        $phpinfo[end(array_keys($phpinfo))][$match[2]] = isset($match[4]) ? array($match[3], $match[4]) : $match[3];
        else
        $phpinfo[end(array_keys($phpinfo))][] = $match[2];

        return $phpinfo;
	}

    function displayDonate(){
    	?>
<table class="adminform"><tr><td style="width:90%;"><font size="3"><b><?php echo JText::_('DONATION_MESSAGE'); ?></b></font></td><td style="width:10%; text-align:right;">
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_donations"/>
<input type="hidden" name="business" value="webmaster@jfusion.org"/>
<input type="hidden" name="item_name" value="jfusion.org"/>
<input type="hidden" name="no_shipping" value="0"/>
<input type="hidden" name="no_note" value="1"/>
<input type="hidden" name="currency_code" value="AUD"/>
<input type="hidden" name="tax" value="0"/>
<input type="hidden" name="lc" value="AU"/>
<input type="hidden" name="bn" value="PP-DonationsBF"/>
<input type="image" src="components/com_jfusion/images/donate.png" name="submit" alt="PayPal donation button."/>
<img alt="" border="0" src="https://www.paypal.com/en_AU/i/scr/pixel.gif" width="1" height="1"/>
</form></td></tr></table>

    	<?php

    }

    /**
     * Updates the forum lookup table
     * @param $contentid
     * @param $threadid
     * @param $postid
     * @param $jname
     */
    function updateForumLookup($contentid, $forumid, $threadid, $postid, $jname)
    {
        $db =& JFactory::getDBO();
		$modified = time();

		//check to see if content item has already been created in forum software
        $query = 'SELECT COUNT(*) FROM #__jfusion_forum_plugin WHERE contentid = ' . $contentid . ' AND jname = ' . $db->Quote($jname);
        $db->setQuery($query);
	    if($db->loadResult() == 0) {
	    	//content item has not been created
	        //prepare the variables
	        $lookup = new stdClass;
	        $lookup->contentid = $contentid;
	        $lookup->forumid = $forumid;
	        $lookup->threadid = $threadid;
	        $lookup->postid = $postid;
	        $lookup->modified = $modified;
	        $lookup->jname = $jname;

	        //insert the entry into the lookup table
	        $db->insertObject('#__jfusion_forum_plugin', $lookup);
	    } else {
	    	//content itmem has been created so updated variables to prevent duplicate threads
	    	$query = "UPDATE #__jfusion_forum_plugin SET forumid = {$forumid}, threadid = {$threadid}, postid = {$postid}, modified = {$modified} WHERE contentid = {$contentid} AND jname = {$db->Quote($jname)}";
	    	$db->setQuery($query);
	    	$db->query();
	    }
    }

    /**
     * Creates the URL of a Joomla article
     * @param $contentitem
     * @param $text string to place as the link
     * @return link
     */
    function createJoomlaArticleURL(&$contentitem,$text)
    {
    	require_once JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php';
		$needles = array(
			'article'  => (int) $contentitem->id,
			'category' => (int) $contentitem->catid,
			'section'  => (int) $contentitem->sectionid
		);

    	if($item = ContentHelperRoute::_findItem($needles)) {
			$itemid = $item->id;
		};

		$link = JRoute::_(JURI::base().'index.php?option=com_content&view=article&id=' . $contentitem->id.'&Itemid='.$itemid);
		$link = "<a href='$link'>$text</a>";
		return $link;
    }

    /**
     * Pasrses text from bbcode to html or html to bbcode
     * @param $text
     * @param $to string with what to conver the text to; bbcode or html
     * @param $stripAllHtml boolean  if $to==bbcode, strips all unsupported html from text
     * @return string with converted text
     */
    function parseCode($text, $to, $stripAllHtml = false)
    {
    	if($to=='html') {
    		//entities must be decoded to prevent encoding already encoded entities
			$text = html_entity_decode($text);
   			require_once("parsers/nbbc.php");
   			$bbcode = new BBCode_Parser;
   			$text = $bbcode->Parse($text);
   			//must decode again to display entities properly
   			$text = html_entity_decode($text);

    	} elseif($to=='bbcode') {
 			static $search, $replace;
 			if(!is_array($search)) {
 				$search = $replace = array();

 				//convert anything between code, html, or php tags to html entities to prevent conversion
 				$search[] = "#\<(code|pre)(.*?)\>(.*?)\<\/(code|pre)\>#sie";
 				$replace[] = "'[code]'.htmlspecialchars($3, ENT_QUOTES, UTF-8).'[/code]'";

 				$search[] = "#\<(blockquote|cite)(.*?)\>(.*?)\<\/(blockquote|cite)\>#si";
 				$replace[] = "[quote]$3[/quote]";

 				$search[] = "#\<\ol(.*?)>(.*?)\<\/ol\>#si";
 				$replace[] = "[list=1]$2[/list]";

				$search[] = "#\<ul(.*?)\>(.*?)\<\/ul\>#si";
 				$replace[] = "[list]$2[/list]";

 				$search[] = "#\<li(.*?)>(.*?)\<\/li\>#si";
 				$replace[] = "[*]$2";

 				$search[] = "#\<img (.*?) src=('|\")(.*?)('|\")\>(.*?)\<\/img\>#si";
 				$replace[] = "[img]$3[/img]";

 				$search[] = "#\<a (.*?) href=('|\")mailto:(.*?)('|\")(.*?)\>(.*?)\<\/a\>#si";
 				$replace[] = "[email=$3]$6[/email]";

 				$search[] = "#\<a (.*?) href=('|\")(.*?)('|\")(.*?)\>(.*?)\<\/a\>#si";
 				$replace[] = "[url=$3]$6[/url]";

 				$search[] = "#\<([biu])\>(.*?)\<\/([biu])\>#si";
 				$replace[] = "[$1]$2[/$3]";

 				$search[] = "#\<(.*?)style=(.*?)color:(.*?);(.*?)\>(.*?)\<\/(.*?)\>#si";
 				$replace[] = "[color=$3]$5</font>";

 				$search[] = "#\<font color=('|\")(.*?)('|\")(.*?)\>(.*?)\<\/font\>#si";
 				$replace[] = "[color=$2]$5[/color]";

				$search[] = "#\<tr(.*?)\>(.*?)\<\/tr\>#si";
 				$replace[] = "$2\n";

				$search[] = "#\<td(.*?)\>(.*?)\<\/td\>#si";
 				$replace[] = " $2 ";

 				$search[] = "#\<p\>\<\/p>#si";
 				$replace[] = "\n\n";

 				//decode html entities that we converted for code and pre tags
 				$search[] = "#\[code\](.*?)\[\/code\]#sie";
 				$replace[] = "'[code]'.htmlspecialchars_decode($1,ENT_QUOTES).'[/code]'";

 			}

 			$text = str_ireplace(array("<br />","<br>","<br/>"), "\n", $text);
 			$text = preg_replace($search,$replace,$text);
 			 $text = preg_replace( '/\p{Z}/u', ' ', $text );
 			if($stripAllHtml) { $text = strip_tags($text); }
    	}

    	return $text;
    }
}