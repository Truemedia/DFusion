<?php

/**
* @package JFusion_Moodle
* @version 1.0.8-007
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
 * JFusion plugin class for Moodle 1.8+
 * @package JFusion_Moodle
 */

class JFusionPlugin_moodle extends JFusionPlugin{

    function getJname(){
        return 'moodle';
    }

    function getTablename(){
        return 'user';
    }

    function setupFromPath($forumPath){
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
            $params['database_host']        = $config['CFG->dbhost'];
            $params['database_name']        = $config['CFG->dbname'];
            $params['database_user']        = $config['CFG->dbuser'];
            $params['database_password']    = $config['CFG->dbpass'];
            $params['database_prefix']      = $config['CFG->prefix'];
            $params['database_type']        = $config['CFG->dbtype'];
            $params['passwordsaltmain']    = $config['CFG->passwordsaltmain'];
            for ($i=1; $i<=20; $i++) { //20 alternative salts should be enough, right?
                  $alt = 'passwordsaltalt'.$i;
                  if ($config['CFG->'.$alt]){$params[$alt] = $config['CFG->'.$alt];}
            }
            $params['source_path']        = $forumPath;
            if (substr($config['wwwroot'], -1) == '/') {
              $params['source_url'] = $config['CFG->wwwroot'];
            } else {
                //no slashes found, we need to add one
                $params['source_url'] = $config['CFG->wwwroot'] . '/' ;
            }
            $params['usergroup'] = '7';  #make sure we do not assign roles with more capabilities automatically
            //return the parameters so it can be saved permanently
            return $params;
        }
    }


    function getRegistrationURL(){
        return 'login/signup.php';
    }

    function getLostPasswordURL(){
        return 'login/forgot_password.php';
    }

    function getLostUsernameURL(){
        return 'login/forgot_password.php';
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
        $query = 'SELECT username, email from #__user';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount(){
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__user';
        $db->setQuery($query );

        //getting the results
        $no_users = $db->loadResult();

        return $no_users;
    }

    function getUsergroupList(){
        //get the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT id, name from #__role;';
        $db->setQuery($query );

        //getting the results
        return $db->loadObjectList();
    }

    function getDefaultUsergroup(){
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup_id = $params->get('usergroup');

        //we want to output the usergroup name
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT name from #__role WHERE id = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function allowRegistration()
    {
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__config WHERE name = 'auth'";
        $db->setQuery($query );
        $auths = $db->loadResult();
        if (empty($auths)) {
            return false;
        } else {
            return true;
        }
    }



/*
    function & getBuffer()
    {
        // Get the path
        $params = JFusionFactory::getParams($this->getJname());
        $source_path = $params->get('source_path');

        //get the filename
        $jfile = JRequest::getVar('jfile');

        //redirect directly to admincp if needed
        if ($jfile == 'adm/index.php') {
            $url ="Location: " . $params->get('source_url') . 'adm/index.php?' . $_SERVER['QUERY_STRING'] ;
            header($url);
        }

        if ($jfile == 'file.php') {
            $url ="Location: " . $params->get('source_url') . 'download/file.php?' . $_SERVER['QUERY_STRING'] ;
            header($url);
        }

        if (!$jfile) {
            //use the default index.php
            $jfile = 'index.php';
        }


        //combine the path and filename
        if (substr($source_path, -1) == DS) {
            $index_file = $source_path . basename($jfile);
        } else {
            $index_file = $source_path . DS . basename($jfile);
        }

        if (! is_file($index_file) ) {
            JError::raiseWarning(500, 'The path to the requested does not exist');
            return null;
        }

        //set the current directory to phpBB3
        chdir($source_path);

       # set scope for variables required later
        global $phpbb_root_path, $phpEx, $db, $config, $user, $auth, $cache, $template, $phpbb_hook, $module;

        //define the phpBB3 hooks
        require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS. $this->getJname().DS.'hooks.php');

        // Get the output
        ob_start();
        try {
            define('UTF8_STRLEN', true);
            define('UTF8_CORE', true);
            define('UTF8_CASE', true);
            include_once($index_file);
        }
        catch(Exception $e) {
            $buffer = ob_get_contents() ;
            ob_end_clean();
        }


        // continue with joomla here
        //die($buffer);
        //change the current directory back to Joomla.
        chdir(JPATH_SITE);

        return $buffer;
    }



    function parseBody(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_body, $replace_body;

        if (! $regex_body || ! $replace_body ) {
            // Define our preg arrays
            $regex_body    = array();
            $replace_body  = array();

            //convert relative links from images into absolute links
      $regex_body[]  = '#(src="|background="|url\(\'?)./(.*?)("|\'?\))#mS';
            $replace_body[]  = '$1'.$integratedURL.'$2$3';

            //fix up and ampersands that slipped past the parse url function.
      $regex_body[]  = '#&amp\;(.*?)\=#mS';
            $replace_body[]  = '/$1,';



        }

        $buffer = preg_replace($regex_body, $replace_body, $buffer);
    }

    function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
    {
        static $regex_header, $replace_header;

        if (! $regex_header || ! $replace_header ) {
            // Define our preg arrays
            $regex_header    = array();
            $replace_header  = array();

            //convert relative links into absolute links
           $regex_header[]  = '#(href="|src=")./(.*?")#mS';
           $replace_header[]  = '$1'.$integratedURL.'$2"';

        }
        $buffer = preg_replace($regex_header, $replace_header, $buffer);
    }
*/
    }