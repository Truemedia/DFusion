<?php

/**
 * @package JFusion_punBB
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
 * JFusion plugin class for punBB
 * @package JFusion_punBB
 */
class JFusionPlugin_punbb extends JFusionPlugin{

    function getJname()
    {
        return 'punbb';
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
            $myfile = $forumPath . DS . 'config.php';
        }

        //try to open the file
        if (($file_handle = @fopen($myfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
            return false;


         } else {

            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$') === 0) {
                    if (strpos($line, '\'') === false) {
                        $name = substr($line,1,strpos($line,' ')-1);
                        $value = substr($line,strpos($line,'=')+2,-2);
                    } else {
                        $vars = split("'", $line);
                        $name = trim($vars[0],' $=');
                        $value = trim($vars[1],' $=');
                    }
                    $config[$name] = $value;
                }

            }
            fclose($file_handle);


            $params = array();
            $params['database_host'] = $config['db_host'];
            $params['database_type'] = $config['db_type'];
            $params['database_name'] = $config['db_name'];
            $params['database_user'] = $config['db_username'];
            $params['database_password'] = $config['db_password'];
            $params['database_prefix'] = $config['db_prefix'];
            $params['cookie_name'] = $config['cookie_name'];
			$params['cookie_path'] = $config['cookie_path'];
			$params['cookie_domain'] = $config['cookie_domain'];
			$params['cookie_seed'] = $config['cookie_seed'];
			$params['cookie_secure'] = $config['cookie_secure'];
            $params['source_path'] = $forumPath;

            //find the path to PunBB, for this we need a database connection
            $host = $config['db_host'];
            $user = $config['db_username'];
            $password = $config['db_password'];
            $database = $config['db_name'];
            $prefix = $config['db_prefix'];
            $driver = 'mysql';
            $options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix );
            $pundb =& JDatabase::getInstance($options );

            //Find the path to PunBB
            $query = "SELECT conf_value FROM #__config WHERE conf_name = 'o_base_url'";
            $pundb->setQuery($query);
            $pun_url = $pundb-> loadResult();
            $params['source_url'] = $pun_url;

            return $params;
        }
    }


    function getAvatar($userid)
    {
        if ($userid)
        {
            	$params = JFusionFactory::getParams($this->getJname());
                $avatarfile = $params->get('source_url') . 'img'.DS.'avatars'.DS . $userid . '.';
                if (file_exists($avatarfile . 'gif')) {
                    $url = $params->get('source_url').'img'.DS.'avatars'.DS . $userid . '.gif';
                } elseif (file_exists($avatarfile . 'jpg')) {
                    $url = $params->get('source_url').'img'.DS.'avatars'.DS . $userid . '.jpg';
                } else {
                    $url = $params->get('source_url').'img'.DS.'avatars'.DS . $userid . '.png';
                }
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
       return 'login.php?action=forget';
    }

    function getLostUsernameURL()
    {
       return 'login.php?action=forget';
    }

    function getThreadURL($threadid)
    {
        return  'viewtopic.php?id=' . $threadid;

    }

    function getPostURL($threadid, $postid)
    {
        return  'viewtopic.php?id=' . $threadid . '#p' . $postid;
    }

    function getProfileURL($uid)
    {
        return  'profile.php?id='.$uid;
    }

    function getQuery($usedforums, $result_order, $result_limit)
    {
if ($usedforums) {
$where = ' WHERE forumid IN (' . $usedforums .')';
} else {
$where = '';
}

$query = array ( 0 => array( 0 => ""/*"SELECT a.id , b.poster, b.poster_id, a.subject, b.posted, left(b.message, $result_limit) FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.firstpostid = b.id " . $where . " ORDER BY a.last_post  ".$result_order." LIMIT 0,".$result_limit.";"*/,
                             1 => "SELECT a.id , b.poster, b.poster_id, a.subject, b.posted, left(b.message, $result_limit) FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.last_post_id = b.id " . $where . " ORDER BY a.last_post  ".$result_order." LIMIT 0,".$result_limit.";"),
                 1 => array( 0 => ""/*"SELECT a.id , b.poster, b.poster_id, a.subject, b.posted, left(b.message, $result_limit) FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.firstpostid = b.id " . $where . " ORDER BY a.posted  ".$result_order." LIMIT 0,".$result_limit.";"*/,
                             1 => "SELECT a.id , b.poster, b.poster_id, a.subject, b.posted, left(b.message, $result_limit) FROM `#__topics` as a INNER JOIN `#__posts` as b ON a.last_post_id = b.id " . $where . " ORDER BY a.posted  ".$result_order." LIMIT 0,".$result_limit.";"),
                 2 => array( 0 => "SELECT a.id , a.username, a.poster, b.subject, a.posted, a.message, a.topic_id FROM `#__posts` as a INNER JOIN `#__topics` as b ON a.topic_id = b.id " . $where . " ORDER BY a.posted ".$result_order." LIMIT 0,".$result_limit.";",
                             1 => "SELECT a.id , a.username, a.poster, b.subject, a.posted, a.message, a.topic_id FROM `#__posts` as a INNER JOIN `#__topics` as b ON a.topic_id = b.id " . $where . " ORDER BY a.posted ".$result_order." LIMIT 0,".$result_limit.";")
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


    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__users';
        $db->setQuery($query );
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList()
    {
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT g_id as id, g_title as name from #__groups;';
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
        $query = "SELECT config_value FROM #__config WHERE config_name = 'o_regs_allow'";
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
