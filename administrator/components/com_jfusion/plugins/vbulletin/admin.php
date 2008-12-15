<?php

/**
* @package JFusion_vBulletin
* @version 1.1.0-001
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
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');


/**
* JFusion plugin class for vBulletin 3.6.8
* @package JFusion_vBulletin
*/
class JFusionAdmin_vbulletin extends JFusionPlugin{

    function getJname()
    {
        return 'vbulletin';
    }

    function getTablename()
    {
        return 'user';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'includes'. DS .'config.php';
        } else {
            $myfile = $forumPath . DS . 'includes'. DS . 'config.php';
        }

        //try to open the file
        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));

            //get the default parameters object
            $params = AbstractForum::getSettings($this->getJname());
            return $params;


        } else {

            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$config') === 0) {
                    $vars = split("'", $line);
                    $name1 = trim($vars[1], ' $=');
                    $name2 = trim($vars[3], ' $=');
                    $value = trim($vars[5], ' $=');
                    $config[$name1][$name2] = $value;

                } else if (strpos($line, 'Licence Number')) {
                    //extract the vbulletin license code while we are at it
                    $vb_lic = substr($line, strpos($line, 'Licence Number') + 14, strlen($line));
                }
            }
            fclose($file_handle);

            //save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['dbhost'];

            $params['database_host'] = $config['MasterServer']['servername'];
            $params['database_type'] = $config['Database']['dbtype'];
            $params['database_name'] = $config['Database']['dbname'];
            $params['database_user'] = $config['MasterServer']['username'];
            $params['database_password'] = $config['MasterServer']['password'];
            $params['database_prefix'] = $config['Database']['tableprefix'];
         	$params['source_path'] = $forumPath;

            //find the path to vbulletin, for this we need a database connection
            $host = $config['MasterServer']['servername'];
            $user = $config['MasterServer']['username'];
            $password = $config['MasterServer']['password'];
            $database = $config['Database']['dbname'];
            $prefix = $config['Database']['tableprefix'];
            $driver = 'mysql';
            $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );
            $vdb =& JDatabase::getInstance($options );

            //Find the path to vbulletin
            $query = "SELECT value FROM #__setting WHERE varname = 'bburl'";
            $vdb->setQuery($query);
            $vb_url = $vdb-> loadResult();
            $params['source_url'] = $vb_url;

            $params['source_license'] = trim($vb_lic);

            return $params;
        }
    }

    function getPrivateMessageCounts($forumuserid)
    {
        // initialise some objects
        $params = JFusionFactory::getParams($this->getJname());
        $db = JFusionFactory::getDatabase($this->getJname());

        $query = 'SELECT pmtotal,pmunread FROM #__user WHERE userid = '.$db->Quote($forumuserid);
        $db->setQuery($query);
        $vbPMData = $db->loadObject();

        $pmcount['total'] = $vbPMData->pmtotal;
        $pmcount['unread'] = $vbPMData->pmunread;

        return $pmcount;
    }

    function getPrivateMessageURL()
    {
        return 'private.php';
    }

    function getViewNewMessagesURL()
    {
        return 'search.php?do=getnew';
    }



    function getAvatar($userid)
    {
        if ($userid) {
            // initialise some objects
            $params = JFusionFactory::getParams($this->getJname());
            $url = $params->get('source_url').'image.php?u='.$userid .'&amp;dateline='. time() ;
            return $url;

        } else {

            return 0;
        }
    }



    function getRegistrationURL()
    {
        return 'register.php';
    }

    function getLostPasswordURL()
    {
        return 'login.php?do=lostpw';
    }

    function getLostUsernameURL()
    {
        return 'login.php?do=lostpw';
    }

    function getThreadURL($threadid)
    {
        return  'showthread.php?t=' . $threadid;

    }

    function getPostURL($threadid, $postid)
    {
        return  'showthread.php?p='.$postid.'#post' . $postid;
    }

    function getProfileURL($uid)
    {
        return  'member.php?u='.$uid;
    }

    function getQuery($usedforums, $result_order, $result_limit)
    {
        if ($usedforums) {
            $where = ' WHERE forumid IN (' . $usedforums .')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid " . $where . " ORDER BY a.lastpost  ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.lastpostid = b.postid " . $where . " ORDER BY a.lastpost  ".$result_order." LIMIT 0,".$result_limit.";"),
        1 => array(0 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.firstpostid = b.postid " . $where . " ORDER BY a.dateline  ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.threadid , b.username, b.userid, b.title, b.dateline, left(b.pagetext, $result_limit) FROM `#__thread` as a INNER JOIN `#__post` as b ON a.lastpostid = b.postid " . $where . " ORDER BY a.dateline  ".$result_order." LIMIT 0,".$result_limit.";"),
        2 => array(0 => "SELECT a.postid , a.username, a.userid, a.title, a.dateline, a.pagetext, a.threadid FROM `#__post` as a INNER JOIN `#__thread` as b ON a.threadid = b.threadid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.postid , a.username, a.userid, a.title, a.dateline, a.pagetext, a.threadid FROM `#__post` as a INNER JOIN `#__thread` as b ON a.threadid = b.threadid " . $where . " ORDER BY a.dateline ".$result_order." LIMIT 0,".$result_limit.";")
        );


        return $query;

    }




    function getUserList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username, email from #__user';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;

    }


    function getForumList()
    {
        //get the connection to the db

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT forumid as id, title_clean as name FROM #__forum ORDER BY forumid';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__user';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT usergroupid as id, title as name from #__usergroup';
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
        $query = 'SELECT title from #__usergroup WHERE usergroupid = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__setting WHERE varname = 'allowregistration'";
        $db->setQuery($query );
        //getting the results

        $new_registration = $db->loadResult();

        if ($new_registration == 1) {
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

		//set the current directory to vBulletin
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

