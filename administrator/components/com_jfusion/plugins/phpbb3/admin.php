<?php

/**
* @package JFusion_phpBB3
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin Class for phpBB3
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_phpBB3
 */
class JFusionAdmin_phpbb3 extends JFusionAdmin{

    function getJname()
    {
        return 'phpbb3';
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
            $result = false;
            return $result;
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
            if (JError::isError($vdb) || !$vdb ) {
                JError::raiseWarning(0, JText::_('NO_DATABASE'));
	            $result = false;
    	        return $result;
            } else {
                $vdb->setQuery($query);
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

            //check for trailing slash
            if (substr($config['server_name'], -1) == '/' && substr($config['script_path'], 0, 1) == '/') {
                //too many slashes, we need to remove one
                $params['source_url'] = $config['server_name'] . substr($config['script_path'],1);
            } else if (substr($config['server_name'], -1) == '/' || substr($config['script_path'], 0, 1) == '/') {
                //the correct number of slashes
                $params['source_url'] = $config['server_name'] . $config['script_path'];
            } else {
                //no slashes found, we need to add one
                $params['source_url'] = $config['server_name'] . '/' . $config['script_path'] ;
            }

            //return the parameters so it can be saved permanently
            return $params;
        }
    }


    function getUserList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT username_clean as username, user_email as email, user_id as userid from #__users WHERE user_email NOT LIKE \'\' and user_email IS NOT NULL';
        $db->setQuery($query );

        //getting the results
        $userlist = $db->loadObjectList();

        return $userlist;
    }

    function getUserCount()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT count(*) from #__users WHERE user_email NOT LIKE \'\' and user_email IS NOT NULL ';
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
            $result = false;
            return $result;
        } else {
            $result = true;
            return $result;
        }
    }

    function show_auth_mod()
    {
    	$error = 0;
    	//check to see if a path is defined
        $params = JFusionFactory::getParams($this->getJname());
        $path = $params->get('source_path');
        if(empty($path)){
        	$error = 1;
        	$reason = JText::_('SET_PATH_FIRST');
        }
        //check for trailing slash and generate file path
        if (substr($path, -1) == DS) {
            $auth_file = $path . 'includes' .DS. 'auth' .DS. 'auth_jfusion.php';
        } else {
            $auth_file = $path .DS. 'includes' .DS. 'auth' .DS. 'auth_jfusion.php';
        }
        //see if the auth mod file exists
		if (!file_exists($auth_file) && $error == 0){
        	$error = 1;
        	$reason = JText::_('NO_FILE_FOUND');
		}

		if($error == 0) {
			//get the joomla path from the file
			jimport('joomla.filesystem.file');
			$file_data = JFile::read($auth_file);
	      	preg_match_all('/define\(\'JPATH_BASE\'\,(.*)\)/',$file_data,$matches);
	
			//compare it with our joomla path
			if($matches[1][0] != '\''. JPATH_SITE.'\''){
	        	$error = 1;
	        	$reason = JText::_('PATH') . ' ' . JText::_('INVALID');
			}
		}

		if ($error == 0){
			//check to see if the mod is enabled
    	    $db = JFusionFactory::getDatabase($this->getJname());
        	$query = 'SELECT config_value FROM #__config WHERE config_name = \'auth_method\'';
	        $db->setQuery($query );
    	    $auth_method = $db->loadResult();
        	if($auth_method != 'jfusion'){
	        	$error = 1;
    	    	$reason = JText::_('MOD_NOT_ENABLED');
	        }
	    }


?>
<script language="javascript" type="text/javascript">
<!--
function auth_mod(action) {
var form = document.adminForm;
form.customcommand.value = action;
form.action.value = 'apply';
submitform('saveconfig');
return;
}

//-->
</script>

<?php
		if ($error == 0){
			//return success
			$output = '<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px">' . JText::_('AUTHENTICATION_MOD') . ' ' . JText::_('ENABLED');
			$output .= ' <a href="javascript:void(0);" onclick="return auth_mod(\'disable_auth_mod\')">' . JText::_('AUTHENTICATION_MOD_DISABLE') . '</a>';
			return $output;
		} else {
       		$output = '<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px">' . JText::_('AUTHENTICATION_MOD') . ' ' . JText::_('DISABLED') .': ' . $reason;
			$output .= ' <a href="javascript:void(0);" onclick="return auth_mod(\'enable_auth_mod\')">' . JText::_('AUTHENTICATION_MOD_ENABLE') . '</a>';
			return $output;
		}
    }

    function enable_auth_mod()
    {
    	//check to see if a path is defined
        $params = JFusionFactory::getParams($this->getJname());
        $path = $params->get('source_path');
        if (substr($path, -1) == DS) {
            $auth_file = $path . 'includes' .DS. 'auth' .DS. 'auth_jfusion.php';
        } else {
            $auth_file = $path .DS. 'includes' .DS. 'auth' .DS. 'auth_jfusion.php';
        }

        //see if the auth mod file exists
		if (!file_exists($auth_file)){
			jimport('joomla.filesystem.file');
			$copy_file = JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.'phpbb3'.DS.'auth_jfusion.php';
			JFile::copy($copy_file,$auth_file);
		}

		//get the joomla path from the file
		jimport('joomla.filesystem.file');
		$file_data = JFile::read($auth_file);
      	preg_match_all('/define\(\'JPATH_BASE\'\,(.*)\)/',$file_data,$matches);

		//compare it with our joomla path
		if($matches[1][0] != '\'' . JPATH_SITE . '\''){
			$file_data = preg_replace('/define\(\'JPATH_BASE\'\,(.*)\)/', 'define(\'JPATH_BASE\',\''.JPATH_SITE.'\')', $file_data);
			JFile::write($auth_file, $file_data);
		}

		//check to see if the mod is enabled
   	    $db = JFusionFactory::getDatabase($this->getJname());
       	$query = 'SELECT config_value FROM #__config WHERE config_name = \'auth_method\'';
        $db->setQuery($query );
   	    $auth_method = $db->loadResult();
       	if($auth_method != 'jfusion'){
	       	$query = 'UPDATE #__config SET config_value = \'jfusion\' WHERE config_name = \'auth_method\'';
	        $db->setQuery($query );
    	    if (!$db->query()) {
        	    //there was an error saving the parameters
            	JError::raiseWarning(0,$db->stderr());
	        }
        }
    }

    function disable_auth_mod()
    {
		//check to see if the mod is enabled
   	    $db = JFusionFactory::getDatabase($this->getJname());
       	$query = 'UPDATE #__config SET config_value = \'db\' WHERE config_name = \'auth_method\'';
        $db->setQuery($query );
   	    if (!$db->query()) {
      	    //there was an error saving the parameters
           	JError::raiseWarning(0,$db->stderr());
        }
    }
}

