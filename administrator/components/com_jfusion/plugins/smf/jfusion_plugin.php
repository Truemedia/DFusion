<?php

/**
* @package JFusion_SMF
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
* JFusion plugin class for SMF 1.1.4
* @package JFusion_SMF
*/
class JFusionPlugin_smf extends JFusionPlugin{

    function getJname()
    {
        return 'smf';
    }

    function getTablename()
    {
        return 'members';
    }

    function setupFromPath($forumPath)
    {
        //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $myfile = $forumPath . 'Settings.php';
        } else {
            $myfile = $forumPath . DS. 'Settings.php';
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
                if (strpos($line, '$') === 0) {
                    $vars = split("'", $line);
                    $name = trim($vars[0], ' $=');
                    $value = trim($vars[1], ' $=');
                    $config[$name] = $value;

                }
            }
            fclose($file_handle);
            //Save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['db_server'];
            $params['database_type'] = 'mysql';
            $params['database_name'] = $config['db_name'];
            $params['database_user'] = $config['db_user'];
            $params['database_password'] = $config['db_passwd'];
            $params['database_prefix'] = $config['db_prefix'];
            $params['source_url'] = $config['boardurl'];
            $params['cookie_name'] = $config['cookiename'];
            $params['source_path'] = $forumPath;

            return $params;
        }
    }

    function getRegistrationURL()
    {
        return 'index.php?action=register';
    }

    function getLostPasswordURL()
    {
        return 'index.php?action=reminder';
    }

    function getLostUsernameURL()
    {
        return 'index.php?action=reminder';
    }

    function getThreadURL($threadid)
    {
        return  'index.php?topic=' . $threadid;

    }

    function getPostURL($threadid, $postid)
    {
        return  'index.php?topic=' . $threadid . 'msg'.$postid.'#msg' . $postid;
    }

    function getProfileURL($uid)
    {
        return  'member.php?u='.$uid;
    }



    function getQuery($usedforums, $result_order, $result_limit)
    {
        if ($usedforums) {
            $where = ' WHERE a.ID_BOARD IN (' . $usedforums .')';
        } else {
            $where = '';
        }

        $query = array(0 => array(0 => "SELECT a.ID_TOPIC , c.posterName, c.ID_MEMBER, c.subject, c.posterTime, left(c.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_LAST_MSG = b.ID_MSG INNER JOIN `#__messages` as c ON a.ID_FIRST_MSG = c.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";" ,
        1 => "SELECT a.ID_TOPIC , b.posterName, b.ID_MEMBER, b.subject, b.posterTime, left(b.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_LAST_MSG = b.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";"  ),
        1 => array(0 => "SELECT a.ID_TOPIC , b.posterName, b.ID_MEMBER, b.subject, b.posterTime, left(b.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_FIRST_MSG = b.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";",
        1 => "SELECT a.ID_TOPIC , c.posterName, c.ID_MEMBER, c.subject, c.posterTime, left(c.body, $result_limit) FROM `#__topics` as a INNER JOIN `#__messages` as b ON a.ID_FIRST_MSG = b.ID_MSG INNER JOIN `#__messages` as c ON a.ID_LAST_MSG = c.ID_MSG " . $where . " ORDER BY b.posterTime  ".$result_order." LIMIT 0,".$result_limit.";"),
        2 => array(0 => "SELECT a.ID_MSG , a.posterName, a.ID_MEMBER, a.subject, a.posterTime, left(a.body, $result_limit), a.ID_TOPIC  FROM `#__messages` as a " . $where . " ORDER BY a.posterTime ".$result_order." LIMIT 0,".$result_limit.";" ,
        1 => "SELECT a.ID_MSG , a.posterName, a.ID_MEMBER, a.subject, a.posterTime, left(a.body, $result_limit), a.ID_TOPIC  FROM `#__messages` as a " . $where . " ORDER BY a.posterTime ".$result_order." LIMIT 0,".$result_limit.";")
        );

        return $query;

    }

    function getUserList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT memberName as username, emailAddress as email from #__members';
        $db->setQuery($query );
        $userlist = $db->loadObjectList();

        return $userlist;
    }


    function getForumList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT ID_BOARD as id, name FROM #__boards';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
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
        $query = 'SELECT ID_GROUP as id, groupName as name FROM #__membergroups';
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
        $query = 'SELECT groupName FROM #__membergroups WHERE ID_GROUP = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__settings WHERE variable ='registration_method';";
        $db->setQuery($query );
        $new_registration = $db->loadResult();

        if ($new_registration == 3) {
            return false;
        } else {
            return true;
        }
    }


    function getPrivateMessageCounts($userid)
    {

        if ($userid) {

            // initialise some objects
            $db = JFusionFactory::getDatabase($this->getJname());

            // read unread count
            $db->setQuery('SELECT unreadMessages FROM #__members WHERE ID_MEMBER = '.$userid);
            $unreadCount = $db->loadResult();

            // read total pm count
            $db->setQuery('SELECT instantMessages FROM #__members WHERE ID_MEMBER = '.$userid);
            $totalCount = $db->loadResult();

            return array('unread' => $unreadCount, 'total' => $totalCount);
        }
        return array('unread' => 0, 'total' => 0);
    }

    function getAvatar($puser_id)
    {
        return 0;
    }
}

