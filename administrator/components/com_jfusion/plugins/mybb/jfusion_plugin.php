<?php

/**
 * @package JFusion_MyBB
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractplugin.php');


/**
 * JFusion plugin class for myBB
 * @package JFusion_MyBB
 */
class JFusionPlugin_mybb extends JFusionPlugin{

    function getJname()
    {
        return 'mybb';
    }

    function getTablename()
    {
    	return 'users';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate config file path
        if (substr($forumPath, -1) != DS) {
			$forumPath .= DS;
        }
      	$myfile = $forumPath . 'inc'. DS . 'config.php';


        //include config file
        require_once $myfile;

         //Save the parameters into the standard JFusion params format
         $params = array();
      $params['database_type'] = $config['database']['type'];
         $params['database_host'] = $config['database']['hostname'];
         $params['database_user'] = $config['database']['username'];
         $params['database_password'] = $config['database']['password'];
         $params['database_name'] = $config['database']['database'];
         $params['database_prefix'] = $config['database']['table_prefix'];
         $params['source_path'] = $forumPath;

         //find the source url to mybb
         $driver = $params['database_type'];
         $host = $params['database_host'];
         $user = $params['database_user'];
         $password = $params['database_password'];
         $database = $params['database_name'];
         $prefix = $params['database_prefix'];
         $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );
         $bb =& JDatabase::getInstance($options );
         $query = "SELECT value FROM #__settings WHERE name = 'bburl'";
         $bb->setQuery($query);
         $bb_url = $bb->loadResult();
         if (substr($bb_url, -1) != DS) $bb_url .= DS;
         $params['source_url'] = $bb_url;

         $query = "SELECT value FROM #__settings WHERE name='cookiedomain'";
         $bb->setQuery($query);
         $cookiedomain = $bb->loadResult();
         $params['cookie_domain'] =  $cookiedomain;

         $query = "SELECT value FROM #__settings WHERE name='cookiepath'";
         $bb->setQuery($query);
         $cookiepath = $bb->loadResult();
         $params['cookie_path'] =  $cookiepath;

         return $params;
   }



    function getRegistrationURL()
    {
       return 'member.php?action=register';
    }

    function getLostPasswordURL()
    {
       return 'member.php?action=lostpw';
    }

    function getLostUsernameURL()
    {
       return 'member.php?action=lostpw';
    }

    function getThreadURL($threadid)
    {
       return  'showthread.php?tid='.$threadid;

    }

    function getPostURL($threadid, $postid)
    {
        return  'showthread.php?tid='.$threadid.'&amp;pid='.$postid.'#pid'.$postid;
    }

    function getProfileURL($uid)
    {
        return  'member.php?action=profile&amp;uid'.$uid;
    }


    function getQuery($usedforums, $result_order, $result_limit, $display_limit)
    {
      if ($usedforums)
      {
         $where = ' WHERE a.fid IN (' . $usedforums .')';
      } else {
         $where = '';
      }
      $query = array(0 => array( 0 => "SELECT a.tid , a.username, a.uid, a.subject, a.dateline, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.firstpost = b.pid " . $where . " ORDER BY a.lastpost ".$result_order." LIMIT 0,".$result_limit.";",
                                 1 => "SELECT a.tid , a.lasposter, a.lasposteruid, a.subject, a.laspost, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.lastpost = b.dateline " . $where . " ORDER BY a.lastpost ".$result_order." LIMIT 0,".$result_limit.";"),
                     1 => array( 0 => "SELECT a.tid , a.username, a.uid, a.subject, a.dateline, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.firstpost = b.pid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";",
                                 1 => "SELECT a.tid , a.lastposter, a.lasposteruid, a.subject, a.dateline, left(b.message, $display_limit) FROM #__threads as a INNER JOIN #__posts as b ON a.lastpost = b.dateline " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";"),
                     2 => array( 0 => "SELECT a.pid , b.username, a.uid, a.subject, a.dateline, left(a.message, $display_limit), a.tid  FROM `#__posts` as a INNER JOIN #__users as b ON a.uid = b.uid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";",
                                 1 => "SELECT a.pid , b.username, a.uid, a.subject, a.dateline, left(a.message, $display_limit), a.tid  FROM `#__posts` as a INNER JOIN #__users as b ON a.uid = b.uid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";")
                 );
                 return $query;
    }

    function getUserList()
    {
    	//getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT username, email from #__users';
    	$db->setQuery( $query );
    	$userlist = $db->loadObjectList();

    	return $userlist;
	}


    function getForumList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT fid as id, name FROM #__forums';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }


    function getPrivateMessageCounts($userid)
    {

        if ($userid)
        {

        	//get the connection to the db
        	$db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT totalpms, newpms FROM #__users WHERE uid = '.$userid);
            $pminfo = $db->loadObject();


            return array('unread' => $pminfo->newpms, 'total' => $pminfo->totalpms);
        }
        return array('unread' => 0, 'total' => 0);
    }


    function getPrivateMessageURL()
    {
        return 'private.php';
    }

    function getViewNewMessagesURL()
    {
        return 'search.php?action=getnew';
    }


    function getAvatar($userid)
    {
        	//get the connection to the db
        	$db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT avatar FROM #__users WHERE uid = '.$userid);
            $avatar = $db->loadResult();

            $avatar = substr($avatar, 2);
            $params = JFusionFactory::getParams($this->getJname());
            $url = $params->get('source_url'). $avatar;
            return $url;
    }


    function getUserCount()
    {
        //getting the connection to the db
		$db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__users';
        $db->setQuery($query );

        //getting the results
        return $db->loadResult();
    }

    function getUsergroupList()
    {
    	//getting the connection to the db
		$db = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT gid as id, title as name FROM #__usergroups';
    	$db->setQuery($query );

    	//getting the results
    	return $db->loadObjectList();
    }

    function getDefaultUsergroup()
    {
    	$params = JFusionFactory::getParams($this->getJname());
    	$usergroup_id = $params->get('usergroup');

    	//we want to output the usergroup name
		$db = JFusionFactory::getDatabase($this->getJname());
    	$query = 'SELECT title from #__usergroups WHERE gid = ' . $usergroup_id;
    	$db->setQuery($query );
    	return $db->loadResult();
    }

    function allowRegistration()
    {
    	$db = JFusionFactory::getDatabase($this->getJname());
    	$query = "SELECT value FROM #__settings  WHERE name ='disableregs'";
    	$db->setQuery( $query );
    	$disableregs = $db->loadResult();

		if ($disableregs == '0') {
			return true;
		} else {
			return false;
		}
    }

	function & getBuffer()
	{
		// Get the path
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');

		//get the filename
		$jfile = JRequest::getVar('jfile', '', 'GET', 'STRING');
		if(!$jfile) {
			//use the default index.php
			$jfile = 'index.php';
		}

		//combine the path and filename
        if (substr($source_path, -1) == DS) {
            $index_file = $source_path . $jfile;
        } else {
            $index_file = $source_path . DS . $jfile;
        }

		if ( ! is_file($index_file) ) {
            JError::raiseWarning(500, 'The path to the requested does not exist');
			return null;
		}

		//set the current directory to MyBB
		chdir($source_path);

		/* set scope for variables required later */
		define('IN_PHPBB', true);
		global $phpbb_root_path, $phpEx, $db, $config, $user, $auth, $cache, $template;

		// Get the output
		ob_start();
		include_once($index_file);
        $buffer = ob_get_contents() ;
        ob_end_clean();

		//change the current directory back to Joomla.
		chdir(JPATH_SITE);

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
			$regex_body[]	= '#href="./(.*)\?(.*)"#mS';
			$replace_body[]	= 'href="'.$baseURL.'&jfile=$1&$2"';

			//convert relative links without query into absolute links
			$regex_body[]	= '#href="./(.*)"#mS';
			$replace_body[]	= 'href="'.$baseURL.'&jfile=$1"';

			//convert relative links from images into absolute links
			$regex_body[]	= '#(src="|url\()./(.*)("|\))#mS';
			$replace_body[]	= '$1'.$integratedURL.'$2$3"';

			//convert links to the same page with anchors
			$regex_body[]	= '#href="\#(.*?)"#';
			$replace_body[]	= 'href="'.$fullURL.'&#$1"';

			//update site URLs to the new Joomla URLS
			$regex_body[]	= "#$integratedURL(.*)\?(.*)\"#mS";
			$replace_body[]	= $baseURL . '&jfile=$1&$2"';

			//convert action URLs inside forms to absolute URLs
			//$regex_body[]	= '#action="(.*)"#mS';
			//$replace_body[]	= 'action="'.$integratedURL.'/"';

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