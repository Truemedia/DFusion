<?php

/**
* @package JFusion_SMF
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin Class for SMF 1.1.x
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_SMF
 */
 require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractadmin.php');

class JFusionAdmin_smf extends JFusionAdmin{

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
            $params = JFusionFactory::getParams($this->getJname());
            return $params;

        } else {
            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$') === 0) {
                    $vars = split("'", $line);
                     if(isset($vars[1]) && isset($vars[0])){
	                    $name = trim($vars[0], ' $=');
    	                $value = trim($vars[1], ' $=');
        	            $config[$name] = $value;
                    }
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

    function getUserList()
    {
        // initialise some objects
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT memberName as username, emailAddress as email from #__members';
        $db->setQuery($query );
        $userlist = $db->loadObjectList();

        return $userlist;
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
        $query = 'SELECT ID_GROUP as id, groupName as name FROM #__membergroups WHERE minPosts = -1';
        $db->setQuery($query );
 		$usergrouplist = $db->loadObjectList();

 		//append the default usergroup
 		$default_group = new stdClass;
        $default_group->id = 0;
        $default_group->name = 'Default User';
        $usergrouplist[] = $default_group;


        return $usergrouplist;
    }

    function getDefaultUsergroup()
    {
        $params = JFusionFactory::getParams($this->getJname());
        $usergroup_id = $params->get('usergroup');

        if ($usergroup_id==0) return "Default Usergroup";

        //we want to output the usergroup name
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT groupName FROM #__membergroups WHERE ID_GROUP = ' . $usergroup_id;
        $db->setQuery($query );
        return $db->loadResult();
    }

    function getUserpostgroupList()
    {
        //getting the connection to the db
        $db = JFusionFactory::getDatabase($this->getJname());
        $query = 'SELECT ID_GROUP as id, groupName as name FROM #__membergroups WHERE minPosts != -1';
        $db->setQuery($query);
	    return $db->loadObjectList();
    }

    function allowRegistration()
    {

        $db = JFusionFactory::getDatabase($this->getJname());
        $query = "SELECT value FROM #__settings WHERE variable ='registration_method';";
        $db->setQuery($query );
        $new_registration = $db->loadResult();

        if ($new_registration == 3) {
            $result = false;
            return $result;
        } else {
            $result = true;
            return $result;
        }
    }

	function generateRedirectCode()
	{
        	$params = JFusionFactory::getParams($this->getJname());
        	$joomla_params = JFusionFactory::getParams('joomla_int');
		    $joomla_url = $joomla_params->get('source_url');
    		$joomla_itemid = $params->get('redirect_itemid');

			//create the new redirection code
			$redirect_code = '
//JFUSION REDIRECT START
//SET SOME VARS
$joomla_url = \''. $joomla_url . '\';
$joomla_itemid = ' . $joomla_itemid .';
	';
		    $redirect_code .= '
if(!defined(\'_JEXEC\') && strpos($_SERVER[\'QUERY_STRING\'], \'dlattach\') === false && strpos($_SERVER[\'QUERY_STRING\'], \'verificationcode\') === false)';

		    $redirect_code .= '
{
    $file = $_SERVER["SCRIPT_NAME"];
    $break = Explode(\'/\', $file);
    $pfile = $break[count($break) - 1];
    $jfusion_url = $joomla_url . \'index.php?option=com_jfusion&Itemid=\' . $joomla_itemid . \'&jfile=\'.$pfile. \'&\' . $_SERVER[\'QUERY_STRING\'];
    header(\'Location: \' . $jfusion_url);
}
//JFUSION REDIRECT END
';
	return $redirect_code;
	}

    function enable_redirect_mod()
    {
    	$error = 0;
    	$error = 0;
    	$reason = '';
    	$mod_file = $this->getModFile('index.php',$error,$reason);

		if($error == 0) {
			//get the joomla path from the file
			jimport('joomla.filesystem.file');
			$file_data = JFile::read($mod_file);
	      	preg_match_all('/\/\/JFUSION REDIRECT START(.*)\/\/JFUSION REDIRECT END/ms',$file_data,$matches);

			//remove any old code
			if(!empty($matches[1][0])){
		      	$search = '/\/\/JFUSION REDIRECT START(.*)\/\/JFUSION REDIRECT END/ms';
		      	$file_data = preg_replace($search,'',$file_data);

			}

			$redirect_code = $this->generateRedirectCode();

	      	$search = '/\<\?php/si';
			$replace = '<?php' . $redirect_code;
	      	$file_data = preg_replace($search,$replace,$file_data);
			JFile::write($mod_file, $file_data);
		}
    }

    function disable_redirect_mod()
    {
    	$error = 0;
    	$reason = '';
    	$mod_file = $this->getModFile('index.php',$error,$reason);

		if($error == 0) {
			//get the joomla path from the file
			jimport('joomla.filesystem.file');
			$file_data = JFile::read($mod_file);
	      	$search = '/\/\/JFUSION REDIRECT START(.*)\/\/JFUSION REDIRECT END/si';
	      	$file_data = preg_replace($search,'',$file_data);
			JFile::write($mod_file, $file_data);
		}

    }

    function getModFile($filename, &$error,&$reason)
    {
    	//check to see if a path is defined
        $params = JFusionFactory::getParams($this->getJname());
        $path = $params->get('source_path');
        if(empty($path)){
        	$error = 1;
        	$reason = JText::_('SET_PATH_FIRST');
        }
        //check for trailing slash and generate file path
        if (substr($path, -1) == DS) {
            $mod_file = $path . $filename;
        } else {
            $mod_file = $path .DS. $filename;
        }
        //see if the file exists
		if (!file_exists($mod_file) && $error == 0){
        	$error = 1;
        	$reason = JText::_('NO_FILE_FOUND');
		}
		return $mod_file;
    }

	function outputJavascript(){
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
	}

    function show_redirect_mod($name, $value, $node, $control_name)
    {
    	$error = 0;
    	$reason = '';
    	$mod_file = $this->getModFile('index.php',$error,$reason);

		if($error == 0) {
			//get the joomla path from the file
			jimport('joomla.filesystem.file');
			$file_data = JFile::read($mod_file);
	      	preg_match_all('/\/\/JFUSION REDIRECT START(.*)\/\/JFUSION REDIRECT END/ms',$file_data,$matches);

			//compare it with our joomla path
			if(empty($matches[1][0])){
	        	$error = 1;
	        	$reason = JText::_('MOD_NOT_ENABLED');
			}
		}

		//add the javascript to enable buttons
		$this->outputJavascript();

		if ($error == 0){
			//return success
			$output = '<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px">' . JText::_('REDIRECTION_MOD') . ' ' . JText::_('ENABLED');
			$output .= ' <a href="javascript:void(0);" onclick="return auth_mod(\'disable_redirect_mod\')">' . JText::_('MOD_DISABLE') . '</a>';
			$output .= ' <a href="javascript:void(0);" onclick="return auth_mod(\'enable_redirect_mod\')">' . JText::_('MOD_UPDATE') . '</a>';
			return $output;
		} else {
       		$output = '<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px">' . JText::_('REDIRECTION_MOD') . ' ' . JText::_('DISABLED') .': ' . $reason;
			$output .= ' <a href="javascript:void(0);" onclick="return auth_mod(\'enable_redirect_mod\')">' . JText::_('MOD_ENABLE') . '</a>';
			return $output;
		}

    }




}

