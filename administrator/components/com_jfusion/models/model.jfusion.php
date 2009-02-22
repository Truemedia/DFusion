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

        //reset the params instance for this plugin
        JFusionFactory::getParams($jname,true);

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
            $base_url = 'index.php?option=com_jfusion&amp;Itemid=-1&amp;view=' . $view . '&amp;jname=' . $jname;
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
        $fdb =& JFactory::getDBO();
		$modified = time();

		//check to see if content item has already been created in forum software
        $query = 'SELECT COUNT(*) FROM #__jfusion_forum_plugin WHERE contentid = ' . $contentid . ' AND jname = ' . $fdb->Quote($jname);
        $fdb->setQuery($query);
	    if($fdb->loadResult() == 0) {
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
	        $fdb->insertObject('#__jfusion_forum_plugin', $lookup);
	    } else {
	    	//content itmem has been created so updated variables to prevent duplicate threads
	    	$query = "UPDATE #__jfusion_forum_plugin SET forumid = {$forumid}, threadid = {$threadid}, postid = {$postid}, modified = {$modified} WHERE contentid = {$contentid} AND jname = {$fdb->Quote($jname)}";
	    	$fdb->setQuery($query);
	    	$fdb->query();
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
     * @param $morePatten array $morePatten[0] startsearch, $morePatten[1] startreplace, $morePatten[2] endsearch, $morePatten[3] endreplace, strips all unsupported html from text     *
     * @return string with converted text
     */
    function parseCode($text, $to, $stripAllHtml = false,$morePatten=null)
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
 				$search[] = "#<(blockquote|cite).*?>(.*?)<\/\\1>#si";
 				$replace[] = "[quote]$2[/quote]";

 				$search[] = "#<ol.*?>(.*?)<\/ol>#si";
 				$replace[] = "[list=1]$1[/list]";

				$search[] = "#<ul.*?>(.*?)<\/ul>#si";
 				$replace[] = "[list]$1[/list]";

 				$search[] = "#<li.*?>(.*?)<\/li>#si";
 				$replace[] = "[*]$1";

 				$search[] = "#<img.*?src=['|\"](?!\w{0,10}://)(.*?)['|\"].*?>#sie";
 				$replace[] = "'[img]'.JRoute::_(JURI::base().\"$1\").'[/img]'";

 				$search[] = "#<img.*?src=['|\"](.*?)['|\"].*?>#sim";
 				$replace[] = "[img]$1[/img]";

 				$search[] = "#<a .*?href=['|\"]mailto:(.*?)['|\"].*?>(.*?)<\/a>#si";
 				$replace[] = "[email=$1]$2[/email]";

				$search[] = "#<a .*?href=['|\"](?!\w{0,10}://|\#)(.*?)['|\"].*?>(.*?)</a>#sie";
 				$replace[] = "'[url='.JRoute::_(JURI::base().\"$1\").']$2[/url]'";

 				$search[] = "#<a .*?href=['|\"](.*?)['|\"].*?>(.*?)<\/a>#si";
 				$replace[] = "[url=$1]$2[/url]";

 				$search[] = "#<(b|i|u)>(.*?)<\/\\1>#si";
 				$replace[] = "[$1]$2[/$1]";

 				$search[] = "#<font.*?color=['|\"](.*?)['|\"].*?>(.*?)<\/font>#si";
 				$replace[] = "[color=$1]$2[/color]";

 				$search[] = "#<p>(.*?)<\/p>#si";
 				$replace[] = "$1\n\n";
 			}
 			$searchNS = $replaceNS = array();
 			//convert anything between code, html, or php tags to html entities to prevent conversion
            $searchNS[] = "#<(code|pre)(.*?)>(.*?)<\/\\1>#sie";
			$replaceNS[] = "'[code]'.htmlspecialchars($3, ENT_QUOTES, UTF-8).'[/code]'";

 			if ( is_array($morePatten) && isset($morePatten[0]) && isset($morePatten[1]) ) {
				$searchNS = array_merge($searchNS, $morePatten[0]);
				$replaceNS = array_merge($replaceNS, $morePatten[1]);
 			}
			$searchNS = array_merge($searchNS, $search);
			$replaceNS = array_merge($replaceNS, $replace);
 			if ( is_array($morePatten) && isset($morePatten[2]) && isset($morePatten[3]) ) {
				$searchNS = array_merge($searchNS, $morePatten[2]);
				$replaceNS = array_merge($replaceNS, $morePatten[3]);
 			}
 			//decode html entities that we converted for code and pre tags
 			$searchNS[] = "#\[code\](.*?)\[\/code\]#sie";
 			$replaceNS[] = "'[code]'.htmlspecialchars_decode($1,ENT_QUOTES).'[/code]'";

 			$text = str_ireplace(array("<br />","<br>","<br/>"), "\n", $text);
 			$text = preg_replace($searchNS,$replaceNS,$text);
 			 $text = preg_replace( '/\p{Z}/u', ' ', $text );
 			if($stripAllHtml) { $text = strip_tags($text); }
    	}

    	return $text;
    }

    /**
     * Reconnects Joomla DB if it gets disconnected
     */
    function reconnectJoomlaDb()
    {
		//check to see if the Joomla database is still connnected
		$db = & JFactory::getDBO();

		if (!is_resource($db->_resource)) {

			//joomla connection needs to be re-established
			jimport('joomla.database.database');
			jimport( 'joomla.database.table' );
			$conf =& JFactory::getConfig();

			$host 		= $conf->getValue('config.host');
			$user 		= $conf->getValue('config.user');
			$password 	= $conf->getValue('config.password');
			$database	= $conf->getValue('config.db');
			$prefix 	= $conf->getValue('config.dbprefix');
			$dbtype 	= $conf->getValue('config.dbtype');

			if($dbtype == 'mysqli'){
				// Unlike mysql_connect(), mysqli_connect() takes the port and socket
				// as separate arguments. Therefore, we have to extract them from the
				// host string.
				$port	= NULL;
				$socket	= NULL;
				$targetSlot = substr( strstr( $host, ":" ), 1 );
				if (!empty( $targetSlot )) {
					// Get the port number or socket name
					if (is_numeric( $targetSlot ))
						$port	= $targetSlot;
					else
						$socket	= $targetSlot;

					// Extract the host name only
					$host = substr( $host, 0, strlen( $host ) - (strlen( $targetSlot ) + 1) );
					// This will take care of the following notation: ":3306"
					if($host == '')
						$host = 'localhost';
				}

				// connect to the server
				if (!($db->_resource->_resource = @mysqli_connect($host, $user, $password, NULL, $port, $socket))) {
					$db->_errorNum = 2;
					$db->_errorMsg = 'Could not connect to MySQL';
					die ('could not reconnect to the Joomla database');
				}
			} else {
				// connect using mysql
				if (!($db->_resource = @mysql_connect( $host, $user, $password, true ))) {
					$db->_errorNum = 2;
					$db->_errorMsg = 'Could not connect to MySQL';
					die ('could not reconnect to the Joomla database');
				}
			}

			// select the database
			$db->select($database);
		}

		//legacy $database must be restored
		if(JPluginHelper::getPlugin('system','legacy')) {
			$GLOBALS['database'] =& $db;
		}
	}

   /**
     * Retrieves the URL to a userprofile of a Joomla supported component
     * @param $software string name of the software
     * @param $uid int userid of the user
     * @return string URL
     */
    function getAltProfileURL($software,$username)
    {
    	$db =& JFactory::getDBO();
    	$query = "SELECT id FROM #__jfusion_users_plugin WHERE username = '$username' LIMIT 1";
    	$db->setQuery($query);
    	$uid = $db->loadResult();

    	if(!empty($uid)){
	    	if($software=="cb") {
	    		$query = "SELECT id FROM #__menu WHERE type = 'component' AND link LIKE '%com_comprofiler%' LIMIT 1";
	    		$db->setQuery($query);
	    		$itemid = $db->loadResult();
	    		$url = 'index.php?option=com_comprofiler&task=userProfile&Itemid='.$itemid.'&user='.$uid;
	    	} elseif($software=="jomsocial") {
	    		$query = "SELECT id FROM #__menu WHERE type = 'component' AND link LIKE '%com_community%' LIMIT 1";
	    		$db->setQuery($query);
	    		$itemid = $db->loadResult();
	    		$url = 'index.php?option=com_community&view=profile&Itemid='.$itemid.'&userid='.$uid;
	    	} elseif($software=="joomunity") {
	    		$query = "SELECT id FROM #__menu WHERE type = 'component' AND link LIKE '%com_joomunity%' LIMIT 1";
	    		$db->setQuery($query);
	    		$itemid = $db->loadResult();
	    		$url = 'index.php?option=com_joomunity&Itemid='.$itemid.'&cmd=Profile.View.'.$uid;
	    	} else {
	    		$url = false;
	    	}
    	} else {
    		$url = false;
    	}

    	return $url;
    }

    /**
     * Retrieves the source of the avatar for a Joomla supported component
     * @param $software
     * @param $uid
     * @param $isPluginUid boolean if true, look up the Joomla id in the look up table
     * @return unknown_type
     */
    function getAltAvatar($software,$uid,$isPluginUid = false)
    {
    	$db = & JFactory::getDBO();

    	if($isPluginUid) {
    		$jname = $this->getJname();
    		$query = "SELECT id FROM #__jfusion_users_plugin WHERE jname = '$jname' AND userid = '$uid'";
    		$db->setQuery($query);
    		$uid = $db->loadResult();
    	}

        if($software=="cb") {
       		$query = "SELECT avatar FROM #__comprofiler WHERE user_id = '$uid'";
    		$db->setQuery($query);
    		$result = $db->loadResult();
    		if(!empty($result)) {
    			$avatar = "images".DS."comprofiler".DS.$result;
    		} else {
    			$avatar = "components".DS."com_comprofiler".DS."plugin".DS."templates".DS."default".DS."images".DS."avatar".DS."nophoto_n.png";
    		}
    	} elseif($software=="jomsocial") {
    	    $query = "SELECT avatar FROM #__community_users WHERE userid = '$uid'";
    		$db->setQuery($query);
    		$result = $db->loadResult();
    		if(!empty($result)) {
    			$avatar = $result;
    		} else {
    			$avatar = "components".DS."com_community".DS."assets".DS."default_thumb.jpg";
    		}
    	} elseif($software=="joomunity") {
    		$query = "SELECT user_picture FROM #__joom_users WHERE user_id = '$uid'";
    		$db->setQuery($query);
    		$result = $db->loadResult();
    		$avatar = "components".DS."com_joomunity".DS."files".DS."avatars".DS.$result;
    	} elseif($software=="gravatar") {
      		$query = "SELECT email FROM #__users WHERE id = '$uid'";
    		$db->setQuery($query);
    		$email = $db->loadResult();
    		$avatar = "http://www.gravatar.com/avatar.php?gravatar_id=".md5( strtolower($email) )."&size=40";
    	} else {
    		$avatar = false;
    	}

    	return $avatar;
    }    	
}