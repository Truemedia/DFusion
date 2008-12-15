<?php

/**
* @package JFusion_Magento
* @version 1.0.8-006
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
 * JFusion plugin class for Magento 1.1
 * @package JFusion_Magento
 */

class JFusionAdmin_magento extends JFusionPlugin{

    function getJname(){
        return 'magento';
    }

    function getTablename(){
        return 'customer_entity';
    }

    function setupFromPath($forumPath){
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) != DS) {
            $forumpath = $forumPath.DS;
        }
        $xmlfile = $forumpath.'app'.DS.'etc'.DS.'local.xml';
        if (file_exists($xmlfile)) {
            if (!$xml = simplexml_load_file($xmlfile)) {
                unset($xml);
                JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). " $xmlfile " . JText::_('WIZARD_MANUAL'));
                return false;
            }
            //save the parameters into array
            $params = array();
            $params['database_host']     = (string)$xml->global->resources->default_setup->connection->host;
            $params['database_name']     = (string)$xml->global->resources->default_setup->connection->dbname;
            $params['database_user']     = (string)$xml->global->resources->default_setup->connection->username;
            $params['database_password'] = (string)$xml->global->resources->default_setup->connection->password;
            $params['database_prefix']   = (string)$xml->global->resources->db->table_prefix;
            $params['database_type']     = "mysql";
            $params['source_path']       = $forumpath;
            return $params;
        } else {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). " $xmlfile " . JText::_('WIZARD_MANUAL'));
            return false;
        }
    }


    function getRegistrationURL(){
        return 'index.php/customer/account/create/';
    }

    function getLostPasswordURL(){
        return 'index.php/customer/account/forgotpassword/';
    }

    function getLostUsernameURL(){
        return 'index.php/customer/account/forgotpassword/'; // not available in Magento, map to lostpassword
    }

  /*
    function getThreadURL($threadid)
    {
        return  'viewtopic.php?t=' . $threadid;
    }

    function getPostURL($threadid, $postid)
    {
        return 'viewtopic.php?p='.$postid.'#p' . $postid;
    }

    function getProfileURL($uid)
    {
        return 'memberlist.php?mode=viewprofile&u='.$uid;
    }

    function getPrivateMessageURL()
    {
        return 'ucp.php?i=pm&folder=inbox';
    }

    function getViewNewMessagesURL()
    {
        return 'search.php?search_id=newposts';
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
*/
    function getUserList(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT email as username, email from #__customer_entity';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__customer_entity';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList(){
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT customer_group_id as id, customer_group_code as name from #__customer_group;';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getDefaultUsergroup(){
/*        $params = JFusionFactory::getParams($this->getJname());
        $usergroup_id = $params->get('usergroup');

        //we want to output the usergroup name
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT customer_group_code from #__customer_group WHERE customer_group_id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
*/      return JText::_('USING_PLUGIN_DEFAULT');

    }
}