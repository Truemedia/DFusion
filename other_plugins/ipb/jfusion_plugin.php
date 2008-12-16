<?php

/**
* @package JFusion_IPB
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractplugin.php');

/**
* JFusion plugin class for IPB
* @package JFusion_IPB
*/
class JFusionPlugin_ipb extends JFusionPlugin
{

    function getJname()
    {
        return 'ipb';
    }

    function getTablename()
    {
        return 'members_converge';
    }

    function setupFromPath($forumPath)
    {
        // Check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'conf_global.php';
        } else {
            $myfile = $forumPath . DS . 'conf_global.php';
        }

        // Try to open the file
        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500, JText::_('WIZARD_FAILURE') . ": $myfile " . JText::_('WIZARD_MANUAL'));
            $result = false;
            return $result;
        } else {
            // Parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$') === 0) {
                    // Extract the name and value, it was coded to avoid the use of eval() function
                    $vars = split("'", $line);
                    $name = trim($vars[1], ' $=');
                    $value = trim($vars[3], ' $=');
                    $config[$name] = $value;
                }
            }
            fclose($file_handle);

            //Save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['sql_host'];
            $params['database_name'] = $config['sql_database'];
            $params['database_user'] = $config['sql_user'];
            $params['database_password'] = $config['sql_pass'];
            $params['database_prefix'] = $config['sql_tbl_prefix'];
            $params['database_type'] = $config['sql_driver'];
            $params['source_url'] = $config['board_url'];
            $params['source_path'] = $forumPath;

            return $params;
        }
    }

    function getRegistrationURL()
    {
        return 'index.php?act=Reg&amp;CODE=00';
    }

    function getLostPasswordURL()
    {
        return 'index.php?act=Reg&amp;CODE=10';
    }

    function getLostUsernameURL()
    {
        return 'index.php?act=Reg&amp;CODE=10';
    }

    function getThreadURL($threadid)
    {
        return 'index.php?showtopic=' . $threadid;
    }

    function getPostURL($threadid, $postid)
    {
        return 'index.php?showtopic=' . $threadid . '&amp;view=findpost&amp;p='.$postid;
    }

    function getProfileURL($uid)
    {
        return 'index.php?showuser='.$uid;
    }

    function getQuery($usedforums, $result_order, $result_limit, $char_limit)
    {
        if ($usedforums) {
            $where = ' WHERE a.forum_id IN (' . $usedforums . ')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.tid, a.starter_name, a.starter_id, a.title, a.start_date, left(b.post, $char_limit) FROM #__topics as a INNER JOIN #__posts as b ON a.topic_firstpost = b.pid " . $where . " ORDER BY a.last_post ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.tid, a.starter_name, a.starter_id, a.title, a.start_date, left(b.post, $char_limit) FROM #__topics as a INNER JOIN #__posts as b ON a.topic_firstpost = b.pid " . $where . " ORDER BY a.last_post ".$result_order." LIMIT 0,".$result_limit.";"),
        1 => array(0 => "SELECT a.tid, a.starter_name, a.starter_id, a.title, a.start_date, left(b.post, $char_limit) FROM #__topics as a INNER JOIN #__posts as b ON a.topic_firstpost = b.pid " . $where . " ORDER BY a.start_date ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.tid, a.starter_name, a.starter_id, a.title, a.start_date, left(b.post, $char_limit) FROM #__topics as a INNER JOIN #__posts as b ON a.topic_firstpost = b.pid " . $where . " ORDER BY a.start_date ".$result_order." LIMIT 0,".$result_limit.";"),
        2 => array(0 => "SELECT b.pid, b.author_name, b.author_id, a.title, b.post_date, left(b.post, $char_limit) FROM #__topics as a INNER JOIN #__posts as b ON a.topic_firstpost = b.pid " . $where . " ORDER BY b.post_date ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT b.pid, b.author_name, b.author_id, a.title, b.post_date, left(b.post, $char_limit) FROM #__topics as a INNER JOIN #__posts as b ON a.topic_firstpost = b.pid " . $where . " ORDER BY b.post_date ".$result_order." LIMIT 0,".$result_limit.";")
        );

        return $query;
    }

    function getUserList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT name as username, email from #__members';
        $db->setQuery($query);
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getForumList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT id, name FROM #__forums';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }


    function getPrivateMessageCounts($userid)
    {

        if ($userid) {

            // read pm counts
            $db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT new_msg, msg_total FROM #__members WHERE id = '.$userid);
            $pminfo = $db->loadObject();


            return array('unread' => $pminfo->new_msg, 'total' => $pminfo->msg_total);
        }
        return array('unread' => 0, 'total' => 0);
    }


    function getPrivateMessageURL()
    {
        return 'index.php?act=Msg&amp;CODE=01';
    }

    function getViewNewMessagesURL()
    {
        return 'index.php?act=Search&amp;CODE=getnew';
    }


    function getAvatar($userid)
    {

       if ($userid)
        {

            // Get connection with forums database.
            $db = JFusionFactory::getDatabase($this->getJname());

            // Set up the query for required avatar details.
            $db->setQuery('SELECT avatar_location, avatar_type FROM #__member_extra WHERE id = '.$userid);

            // Load results from query.
        	$avatar_info = $db->loadObject();

            // Verify that we have results.
            if (!empty($avatar_info))

            {
                // Handle Pre-installed avatars: Choose an avatar from one of our galleries option.
       			if ($avatar_info->avatar_type == 'local') {

                    // Set URL.
          			$params = JFusionFactory::getParams($this->getJname());
          			$forums_url = $params->get('source_url');
                    $url =  $forums_url . 'style_avatars/' . $avatar_info->avatar_location;

      			// Handle Your image avatars: Enter a URL to an online avatar image option.
                } elseif ($avatar_info->avatar_type == 'url') {

          			// Set URL.
                    $url =  $avatar_info->avatar_location;

      				// Handle Your image avatars: Upload a new image from your computer option.
                } elseif ($avatar_info->avatar_type == 'upload') {

          			// Set URL.
          			$params = JFusionFactory::getParams($this->getJname());
          			$forums_url = $params->get('source_url');
                    $url =  $forums_url . 'uploads/' . $avatar_info->avatar_location;

                 // Handle unexpected case.
       			 } else {

            		$url = '';
        		}

      			// Return the determined URL.
                return $url;

            }

        	return 0;

    	}

        return 0;

	}


    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__members';
        $db->setQuery($query );

        //getting the results
        return $db->loadResult();
    }

    function getUsergroupList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT g_id as id, g_title as name FROM #__groups';
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
        $query = 'SELECT g_title from #__groups WHERE g_id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT conf_value FROM #__settings  WHERE conf_key = 'no_reg'";
        $db->setQuery($query );
        $no_reg = $db->loadResult();


        if ($no_reg) {
            $result = false;
            return $result;
        } else {
            $result = true;
            return $result;
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

		//set the current directory to phpBB3
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
