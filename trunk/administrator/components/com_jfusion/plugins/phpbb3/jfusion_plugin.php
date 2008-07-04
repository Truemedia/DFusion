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
* Define some variables
*/
define('SUPER_ADMIN', 'Super Administrator');
define('ADMIN', 'Administrator');
define('ANONYMOUS_ID', 1);

/**
* load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractplugin.php');

/**
* JFusion plugin class for phpBB3
* @package JFusion_phpBB3
*/
class JFusionPlugin_phpbb3 extends JFusionPlugin{

    function getJname()
    {
        return "phpbb3";
    }

    function getTablename()
    {
        return 'users';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'config.php';
        } else {
            $myfile = $forumPath . DS. 'config.php';
        }

        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
            return false;
        } else {
            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$') === 0) {
                    //extract the name and value, it was coded to avoid the use of eval() function
                    $vars = split("'", $line);
                    $name = trim($vars[0], ' $=');
                    $value = trim($vars[1], ' $=');
                    $config[$name] = $value;
                }
            }
            fclose($file_handle);

            //save the parameters into array
            $params = array();
            $params['database_host'] = $config['dbhost'];
            $params['database_name'] = $config['dbname'];
            $params['database_user'] = $config['dbuser'];
            $params['database_password'] = $config['dbpasswd'];
            $params['database_prefix'] = $config['table_prefix'];
            $params['database_type'] = $config['dbms'];

            //create a connection to the database
            $options = array('driver' => $config['dbms'], 'host' => $config['dbhost'], 'user' => $config['dbuser'], 'password' => $config['dbpasswd'], 'database' => $config['dbname'], 'prefix' => $config['table_prefix'] );

            //Get configuration settings stored in the database
            $vdb =& JDatabase::getInstance($options);
            $query = "SELECT config_name, config_value FROM #__config WHERE config_name IN ('script_path', 'cookie_path', 'server_name', 'cookie_domain', 'cookie_name', 'allow_autologin');";
            $vdb->setQuery($query);
            if (JError::isError($vdb) ) {
                JError::raiseWarning(0, JText::_('NO_DATABASE'));
                return false;
            } else {
                $rows = $vdb->loadObjectList();
                foreach($rows as $row ) {
                    $config[$row->config_name] = $row->config_value;
                }
                //store the new found parameters
                $params['cookie_path'] =  $config['cookie_path'];
                $params['cookie_domain'] =  $config['cookie_domain'];
                $params['cookie_prefix'] =  $config['cookie_name'];
                $params['allow_autologin'] =  $config['allow_autologin'];
                $params['source_path'] = $forumPath;
            }
            //return the parameters so it can be saved permanently
            return $params;
        }
    }


    function getRegistrationURL()
    {
        return JFusionFunction::createURL('ucp.php?mode=register', $this->getJname());
    }

    function getLostPasswordURL()
    {
        return JFusionFunction::createURL('ucp.php?mode=sendpassword', $this->getJname());
    }

    function getLostUsernameURL()
    {
        return JFusionFunction::createURL('ucp.php?mode=sendpassword', $this->getJname());
    }

    function getThreadURL($threadid)
    {
        return  JFusionFunction::createURL('viewtopic.php?t=' . $threadid, $this->getJname());
    }

    function getPostURL($threadid, $postid)
    {
        return JFusionFunction::createURL('viewtopic.php?p='.$postid.'#p' . $postid, $this->getJname());
    }

    function getProfileURL($uid)
    {
        return JFusionFunction::createURL('memberlist.php?mode=viewprofile&u='.$uid, $this->getJname());
    }

    function getPrivateMessageURL()
    {
        return JFusionFunction::createURL('ucp.php?i=pm&folder=inbox', $this->getJname());
    }

    function getViewNewMessagesURL()
    {
        return JFusionFunction::createURL('search.php?search_id=newposts', $this->getJname());
    }


    function getAvatar($puser_id)
    {
        if ($puser_id) {

            $params = JFusionFactory::getParams($this->getJname());
            $db = JFusionFactory::getDatabase($this->getJname());

            $db->setQuery("SELECT user_avatar, user_avatar_type FROM #__users WHERE user_id=".$puser_id);
            $db->query();
            $result = $db->loadObject();

            if (!empty($result)) {
                if ($result->user_avatar_type == 1) {
                    // AVATAR_UPLOAD
                    $url = $params->get('source_url').'download'.DS.'file.php?avatar='.$result->user_avatar;
                } else if ($result->user_avatar_type == 3) {
                    // AVATAR_GALLERY
                    $db->setQuery("SELECT config_value FROM #__config WHERE config_name='avatar_gallery_path'");
                    $db->query();
                    $path = $db->loadResult();
                    if (!empty($path)) {
                        $url = $params->get('source_url').$path.DS.$result->user_avatar;
                    } else {
                        $url = '';
                    }
                } else {
                    $url = '';
                }
                return $url;
            }
        }
        return 0;
    }

    function getPrivateMessageCounts($puser_id)
    {

        if ($puser_id) {

            // read pm counts
            $db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT COUNT(msg_id)
FROM #__privmsgs_to
WHERE pm_unread = 1
AND folder_id <> -2
AND user_id = '.$puser_id);
            $unreadCount = $db->loadResult();

            // read total pm count
            $db->setQuery('SELECT COUNT(msg_id)
FROM #__privmsgs_to
WHERE folder_id NOT IN (-1, -2)
AND user_id = '.$puser_id);
            $totalCount = $db->loadResult();

            return array('unread' => $unreadCount, 'total' => $totalCount);
        }
        return array('unread' => 0, 'total' => 0);
    }

    function getQuery($usedforums, $result_order, $result_limit, $display_limit)
    {

        //set a WHERE query if a forum list was passed through
        if ($usedforums) {
            $where = ' WHERE a.forum_id IN (' . $usedforums .')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.topic_id , a.topic_first_poster_name, a.topic_poster, a.topic_title, a.topic_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts`  as b ON a.topic_first_post_id = b.post_id " . $where . " ORDER BY a.topic_last_post_time ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.topic_id , a.topic_last_poster_name, a.topic_last_poster_id, a.topic_last_post_subject, a.topic_last_post_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.topic_last_post_id = b.post_id " . $where . " ORDER BY a.topic_last_post_time ".$result_order." LIMIT 0,".$result_limit.";"),
        1 => array(0 => "SELECT a.topic_id , a.topic_first_poster_name, a.topic_poster, a.topic_title, a.topic_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts`  as b ON a.topic_first_post_id = b.post_id " . $where . " ORDER BY a.topic_time ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.topic_id , a.topic_last_poster_name, a.topic_last_poster_id, a.topic_last_post_subject, a.topic_last_post_time, left(b.post_text,$display_limit), a.forum_id as forum_specific_id FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.topic_last_post_id = b.post_id " . $where . " ORDER BY a.topic_time ".$result_order." LIMIT 0,".$result_limit.";"),
        2 => array(0 => "SELECT a.post_id , b.username, a.poster_id, a.post_subject, a.post_time, left(a.post_text, $display_limit), a.topic_id  FROM `#__posts` as a INNER JOIN #__users as b ON a.poster_id = b.user_id " . $where . " ORDER BY a.post_time ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.post_id , b.username, a.poster_id, a.post_subject, a.post_time, left(a.post_text, $display_limit), a.topic_id  FROM `#__posts` as a INNER JOIN #__users as b ON a.poster_id = b.user_id " . $where . " ORDER BY a.post_time ".$result_order." LIMIT 0,".$result_limit.";")
        );
        return $query;
    }

    function getForumList()
    {
        //get the connection to the db

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT forum_id as id, forum_name as name FROM #__forums
WHERE forum_type = 1
ORDER BY left_id';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getUserList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username_clean as username, user_email as email from #__users';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__users';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT group_id as id, group_name as name from #__groups;';
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
        $query = 'SELECT group_name from #__groups WHERE group_id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT config_value FROM #__config WHERE config_name = 'require_activation'";
        $db->setQuery($query );
        //getting the results

        $new_registration = $db->loadResult();

        if ($new_registration == 3) {
            return false;
        } else {
            return true;
        }
    }

}

