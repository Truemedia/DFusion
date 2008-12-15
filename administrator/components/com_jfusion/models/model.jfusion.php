<?php

/**
* @package JFusion
* @subpackage Models
* @version 1.0.7
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
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE master = 1 and status = 3';
        $db->setQuery($query );
        $jname = $db->loadObject();

        if ($jname) {
            return $jname;
        }
    }

    /**
* Returns the JFusion plugin name of the software that are currently the slaves of user management
* @param string $jname Name of master JFusion plugin
*/

    function getSlaves()
    {
        //find the first forum that is enabled
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE slave = 1 and status = 3';
        $db->setQuery($query );
        $jname = $db->loadObjectList();
        return $jname;

    }

    /**
* Returns the JFusion plugin name of the software that is currently the slave of user management, minus the joomla_int plugin
* @param array $jname Array list of slave JFusion plugin names
*/

    function getPlugins()
    {
        //find the first forum that is enabled
        $db = & JFactory::getDBO();
        $query = 'SELECT * from #__jfusion WHERE (slave = 1 AND status = 3 AND name NOT LIKE \'joomla_int\')';
        $db->setQuery($query );
        $list = $db->loadObjectList();
        return $list;
    }

    /**
* Returns the parameters of a specific JFusion integration
* @param string $jname name of the JFusion plugin used
* @return object Joomla parameters object
*/

    function &getParameters($jname)
    {
        //get the current parameters from the jfusion table
        $db = & JFactory::getDBO();
        $query = 'SELECT params from #__jfusion WHERE name = ' . $db->quote($jname);
        $db->setQuery($query );
        $serialized = $db->loadResult();

        //get the parameters from the XML file
        $file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $jname . DS.'jfusion.xml';

        $parametersInstance = new JParameter('', $file );

        //apply the stored valued
        if ($serialized) {
            $params = unserialize(base64_decode($serialized));

            if (is_array($params)) {
                foreach($params as $key => $value) {
                    $parametersInstance->set($key, $value );
                }
            }
        }

        if (!is_object($parametersInstance)) {
            JError::raiseError(500, JText::_('NO_FORUM_PARAMETERS'));
        }

        return $parametersInstance;
    }

    /**
* Saves the posted JFusion component variables
* @param string $jname name of the JFusion plugin used
* @param array $post Array of JFusion plugin parameters posted to the JFusion component
* @return true|false returns true if succesful and false if an error occurred
*/

    function saveParameters($jname, $post)
    {
        $mergedpost = array_merge((array) JFusionFunction::getparameters($jname)->_registry[_default][data],$post);  //HJW 15-11-08
        //serialize the $post to allow storage in a SQL field
        $serialized = base64_encode(serialize($mergedpost));

        //set the current parameters in the jfusion table
        $db = & JFactory::getDBO();
        $query = 'UPDATE #__jfusion SET params = ' . $db->quote($serialized) .' WHERE name = ' . $db->quote($jname);
        $db->setQuery($query );

        if (!$db->query()) {
            //there was an error saving the parameters
            JError::raiseWarning(0,$db->stderr());
            return false;
        }

        return true;
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

    function checkConfig($jname)
    {

		//internal Joomla plugin is always properly configured
		if ($jname == 'joomla_int') {
			return 3;
		}

        $db = JFusionFactory::getDatabase($jname);
        $jdb =& JFactory::getDBO();

        if (JError::isError($db) || !$db || !method_exists($jdb,'setQuery')) {
            //Save this error for the integration
            $query = 'UPDATE #__jfusion SET status = 1 WHERE name =' . $jdb->quote($jname);
            $jdb->setQuery($query );
            $jdb->query();
            return 1;
        } else {
            //get the user table name
            $JFusionPlugin = JFusionFactory::getAdmin($jname);
            $tablename = $JFusionPlugin->getTablename();

            //lets see if we can get some data
            $query = 'SELECT * FROM #__' . $tablename . ' LIMIT 1';
            $db->setQuery($query );
            $result = $db->loadObject();
            if ($result) {
                //Save this succes on check
                $query = 'UPDATE #__jfusion SET status = 3 WHERE name =' . $db->quote($jname);
                $jdb->setQuery($query );
                $jdb->query();
                return 3;
            } else {
                //Save this error for the integration
                $query = 'UPDATE #__jfusion SET status = 2 WHERE name =' . $db->quote($jname);
                $jdb->setQuery($query );
                $jdb->query();
                return 2;

            }

        }
    }



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
        $query = 'SELECT published FROM #__plugins WHERE element=' . $db->quote($element) . ' AND folder=' . $db->quote($folder);
        $db->setQuery($query);
        $result = $db->loadObject();
        if ($result) {
            if ($testPublished) {
                return($result->published == 1);
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
* Acquires a database connection to the database of the software integrated by JFusion
* @param string $jname name of the JFusion plugin used
* @return object JDatabase
*/
    function &getDatabase($jname)
    {
		//check to see if joomla DB is requested
		if ($jname == 'joomla_int'){
        	$db = & JFactory::getDBO();
        	return $db;
		}

        //get the debug configuration setting
        $conf =& JFactory::getConfig();
        $debug = $conf->getValue('config.debug');

        //TODO see if we can delete these jimports below
        jimport('joomla.database.database');
        jimport('joomla.database.table' );

        //get config values
        $conf =& JFactory::getConfig();
        $params = JFusionFactory::getParams($jname);

        //prepare the data for creating a database connection
        $host = $params->get('database_host');
        $user = $params->get('database_user');
        $password = $params->get('database_password');
        $database = $params->get('database_name');
        $prefix = $params->get('database_prefix');
        $driver = $params->get('database_type');
        $debug = $conf->getValue('config.debug');

		//added extra code to prevent error when $driver is incorrect
		if ($driver != 'mysql' && $driver != 'mysqli') {
			//invalid driver
            JError::raiseWarning(0, JText::_('INVALID_DRIVER'));
            return false;
		}

        //create an options variable that contains all database connection variables
        $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );

        //create the actual connection
        $jfusion_database =& JDatabase::getInstance($options );
        if (JError::isError($jfusion_database) ) {
            JError::raiseWarning(0, JText::_('NO_DATABASE'));
        }

        //add support for UTF8
        $jfusion_database->Execute('SET names \'utf8\'');

        return $jfusion_database;
    }

    /**
* Returns either the Joomla wrapper URL or the full URL directly to the forum
* @param string $url relative path to a webpage of the integrated software
* @param string $jname name of the JFusion plugin used
* @return string full URL to the filename passed to this function
*/

    function createURL($url, $jname, $view)
    {
        if ($view == 'direct') {
            $params = JFusionFactory::getParams($jname);
            $url = $params->get('source_url') . $url;
        } elseif ($view == 'wrapper') {
            $url = 'index.php?option=com_jfusion&amp;view=' . $view . '&amp;jname=' . $jname . '&amp;wrap='. base64_encode($url);
            $url = JRoute::_($url);
        } elseif ($view == 'frameless'){
            $base_url = 'index.php?option=com_jfusion&amp;view=' . $view . '&amp;jname=' . $jname;

            //split the filename from the query
            $parts = explode('?', $url);
            if (isset($parts[1])) {
            	$base_url .= '&amp;jfile=' . $parts[0] . '&amp;' . $parts[1];
            } else {
            	$base_url .= '&amp;jfile=' . $parts[0];
            }

            $url = JRoute::_($base_url);
        }
        return $url;

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
* Returns the userinfo data for JFusion plugin based on the Joomla userid
* @param string $jname name of the JFusion plugin used
* @param string $userid The Joomla ID of the user
* @return object database Returns the userinfo as a Joomla database object
**/

    function lookupUser($jname, $userid)
    {
        $db =& JFactory::getDBO();
        $query = 'SELECT * FROM #__jfusion_users_plugin WHERE id =' . $userid . ' AND jname = ' . $db->quote($jname);
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
        $query = 'SELECT status FROM #__jfusion WHERE name =' . $db->quote($jname);
        $db->setQuery($query);
        $result = $db->loadResult();
		if ($result == '3') {
			return true;
		} else {
			return false;
		}
    }

    function removeUser($userinfo)
    {
    	//Delete old user data in the lookup table
		$db =& JFactory::getDBO();
        $query = 'DELETE FROM #__jfusion_users WHERE id =' . $userinfo->userid . ' OR username =' . $db->quote($userinfo->username);
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

		$expires = time() + intval($expires_time);
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
<table class="adminform"><tr><td><font size="3"><b>Please help support the JFusion project with a donation. This will ensure the continued development of this revolutionary project.</b></font></td><td>
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
<input type="image" src="https://www.paypal.com/en_AU/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal donation button."/>
<img alt="" border="0" src="https://www.paypal.com/en_AU/i/scr/pixel.gif" width="1" height="1"/>
</form></td></tr></table>

    	<?php

    }

    function updateForumLookup($contentid, $threadid, $postid, $jname)
    {
        $db =& JFactory::getDBO();
		$modified = time();

		//check to see if content item has already been created in forum software
        $query = 'SELECT COUNT(*) FROM #__jfusion_forum_plugin WHERE contentid = ' . $contentid . ' AND jname = ' . $db->Quote($jname);
        $db->setQuery($query);
	    if($db->loadResult() == 0)
	    {
	    	//content item has not been created
	        //prepare the variables
	        $lookup = new stdClass;
	        $lookup->contentid = $contentid;
	        $lookup->threadid = $threadid;
	        $lookup->postid = $postid;
	        $lookup->modified = $modified;
	        $lookup->jname = $jname;

	        //insert the entry into the lookup table
	        $db->insertObject('#__jfusion_forum_plugin', $lookup);
	    }
	    else
	    {
	    	//content itmem has been created so updated variables to prevent duplicate threads
	    	$query = "UPDATE #__jfusion_forum_plugin SET threadid = {$threadid}, postid = {$postid}, modified = {$modified} WHERE contentid = {$contentid} AND jname = {$db->Quote($jname)}";
	    	$db->setQuery($query);
	    	$db->query();
	    }
    }

    function createJoomlaArticleURL($contentid,$text)
    {
		$link = JRoute::_('index.php?option=com_content&view=article&id=' . $contentid);
		$link = "<a href='$link'>$text</a>";
		return $link;
    }
}