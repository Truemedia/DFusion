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
         $params['database_type'] = $config['dbtype'];
         $params['database_host'] = $config['hostname'];
         $params['database_user'] = $config['username'];
         $params['database_password'] = $config['password'];
         $params['database_name'] = $config['database'];
         $params['database_prefix'] = $config['table_prefix'];
         $params['source_path'] = $forumPath;

         //find the source url to mybb
         $driver = $config['dbtype'];
         $host = $config['hostname'];
         $user = $config['username'];
         $password = $config['password'];
         $database = $config['database'];
         $prefix = $config['table_prefix'];
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

    function getThreadURL($threadid, $subject)
    {
       return  'showthread.php?tid='.$threadid;

    }

    function getPostURL($threadid, $postid, $subject)
    {
        return  'showthread.php?tid='.$threadid.'&amp;pid='.$postid.'#pid'.$postid;
    }

    function getProfileURL($uid,$uname)
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

		if ($disableregs == 'no') {
			return true;
		} else {
			return false;
		}
    }
}