<?php

/**
* @package JFusion_vBulletin
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC' ) or die('Restricted access' );

/**
 * JFusion Admin Class for vBulletin
 * For detailed descriptions on these functions please check the model.abstractadmin.php
 * @package JFusion_vBulletin
 */
class JFusionAdmin_vbulletin extends JFusionAdmin{

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
            $result = false;
            return $result;
        } else {

            //parse the file line by line to get only the config variables
            $file_handle = fopen($myfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$config') === 0) {
                    $vars = split("'", $line);
                    if(isset($vars[5])){
	                    $name1 = trim($vars[1], ' $=');
    	                $name2 = trim($vars[3], ' $=');
        	            $value = trim($vars[5], ' $=');
            	        $config[$name1][$name2] = $value;
                    }
                } else if (strpos($line, 'Licence Number')) {
                    //extract the vbulletin license code while we are at it
                    $vb_lic = substr($line, strpos($line, 'Licence Number') + 14, strlen($line));
                }
            }
            fclose($file_handle);

			//newer versions of vB no longer have the license number in the config file so we need to get it elsewhere
            if(empty($vb_lic)) {
            	$myfile = str_replace('config.php','functions.php',$myfile);
                $file_handle = fopen($myfile, 'r');
            	while (!feof($file_handle)) {
	                $line = fgets($file_handle);
					if (strpos($line, 'Licence Number')) {
	                    //extract the vbulletin license code while we are at it
	                    $vb_lic = substr($line, strpos($line, 'Licence Number') + 14, strlen($line));
	                    fclose($file_handle);
	                    break;
	                }
            	}
            }

            //save the parameters into the standard JFusion params format
            $params = array();
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
            $result = true;
            return $result;
        } else {
            $result = false;
            return $result;
        }
    }
    
    function installFramelessHook()
    {
    	$js  = "<script language=\"javascript\" type=\"text/javascript\">\n";
		$js .= "<!--\n";
		$js .= "function toggleHook(action) {\n";
		$js .= "var form = document.adminForm;\n";
		$js .= "if(action=='enable_redirect' && form.paramsitemid_redirect.value=='') {\n";
		$js .= "alert('".JText::_('VB_REDIRECT_HOOK_ITEMID_EMPTY')."');\n";
		$js .= "return false;\n";
		$js .= "}\n";
		$js .= "alert('".JText::_('VB_PLUGIN_CHANGE_ALERT')."');\n";		
		$js .= "form.customcommand.value = action;\n";
		$js .= "form.action.value = 'apply';\n";
		$js .= "submitform('saveconfig')\n";
		$js .= "return;\n";
		$js .= "}\n";
		$js .= "//-->\n";
		$js .= "</script>\n";
		
		$document =& JFactory::getDocument();
		$document->addCustomTag($js);
		
		$db =& JFusionFactory::getDatabase($this->getJname());
		if(!JError::isError($db) && !empty($db)) {
			$query = "SELECT COUNT(*) FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Frameless Integration Plugin' AND active = 1";
			$db->setQuery($query);
			$check = ($db->loadResult() > 0) ? true : false;
			
	    	if ($check){
				//return success
				$output = '<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px">' . JText::_('VB_FRAMELESS_HOOK') . ' ' . JText::_('ENABLED');
				$output .= ' <a href="javascript:void(0);" onclick="return toggleHook(\'disable_frameless\')">' . JText::_('DISABLE_THIS_PLUGIN') . '</a>';
				return $output;
			} else {
	       		$output = '<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px">' . JText::_('VB_FRAMELESS_HOOK') . ' ' . JText::_('DISABLED');
				$output .= ' <a href="javascript:void(0);" onclick="return toggleHook(\'enable_frameless\')">' . JText::_('ENABLE_THIS_PLUGIN') . '</a>';
				return $output;
			}
		} else {
			return JText::_('VB_CONFIG_FIRST');
		}
    }

  	function enable_frameless() 
   	{
    	$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT active FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Frameless Integration Plugin'";
		$db->setQuery($query);
		$active = $db->loadResult();
		
		//remove and recreate the hook for easy upgrade purposes
		if($active=='0' || $active == '1') {
			$query = "DELETE FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Frameless Integration Plugin'";
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500,$db->stderr());
			}
		} 
		$php = $this->getHookPHP('frameless');
		$query = "INSERT INTO #__plugin SET 
			title = 'JFusion Frameless Integration Plugin', 
			hookname = 'init_startup', 
			phpcode = ".$db->Quote($php).",
			product = 'vbulletin',
			active = 1,
			executionorder = 5";
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500,$db->stderr());
		}	   	
   	}		
    
   	function disable_frameless()
    {
    	$db =& JFusionFactory::getDatabase($this->getJname());
    	$query = "DELETE FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Frameless Integration Plugin'";
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500,$db->stderr());
		}
    }		    
    
    function installDualLoginHook()
    {
		$db =& JFusionFactory::getDatabase($this->getJname());
		if(!JError::isError($db) && !empty($db)) {
			$query = "SELECT COUNT(*) FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Dual Login Plugin' AND active = 1";
			$db->setQuery($query);
			$check = ($db->loadResult() > 0) ? true : false;
	
			//make sure the vb auth plugin is installed and published
			if($check===true) {
				$check = (JPluginHelper::getPlugin('authentication','jfusionvbulletin')) ? true : false;	
			} 
				
	        if ($check){
				//return success
				$output = '<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px">' . JText::_('VB_DUALLOGIN_HOOK') . ' ' . JText::_('ENABLED');
				$output .= ' <a href="javascript:void(0);" onclick="return toggleHook(\'disable_duallogin\')">' . JText::_('DISABLE_THIS_PLUGIN') . '</a>';
				return $output;
			} else {
	       		$output = '<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px">' . JText::_('VB_DUALLOGIN_HOOK') . ' ' . JText::_('DISABLED');
				$output .= ' <a href="javascript:void(0);" onclick="return toggleHook(\'enable_duallogin\')">' . JText::_('ENABLE_THIS_PLUGIN') . '</a>';
				return $output;
			}
		} else {
			return JText::_('VB_CONFIG_FIRST');
		}  	
    }

    function enable_duallogin()
    {
        $db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT active FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Dual Login Plugin'";
		$db->setQuery($query);
		$active = $db->loadResult();
		
		//remove and recreate the hook for easy upgrade purposes
		if($active=='0' || $active == '1') {
			$query = "DELETE FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Dual Login Plugin'";
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500,$db->stderr());
			}	   	
		}
		
		$php = $this->getHookPHP('duallogin');
		$query = "INSERT INTO #__plugin SET 
			title = 'JFusion Dual Login Plugin', 
			hookname = 'init_startup', 
			phpcode = ".$db->Quote($php).",
			product = 'vbulletin',
			active = 1,
			executionorder = 5";
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500,$db->stderr());
		}  

		jimport('joomla.installer.helper');
		jimport('joomla.installer.installer');
    	$config =& JFactory::getConfig();
		$url = 'http://jfusion.googlecode.com/svn/trunk/side_projects/vbulletin/plg_auth_jfusionvbulletin.zip';
		$filename = JInstallerHelper::downloadPackage($url);			
		$filename = $config->getValue('config.tmp_path').DS.$filename;
		$package = JInstallerHelper::unpack($filename);
		$tmpInstaller = new JInstaller();
		if(!$tmpInstaller->install($package['dir'])) {
			JError::raiseWarning(550,JText::_('VB_AUTH_PLUGIN_INSTALL_FAILED'));
		} else {
			$jdb =& JFactory::getDBO();
			$query = "UPDATE #__plugins SET published = 1 WHERE folder = 'authentication' AND element = ' jfusionvbulletin'";
			$jdb->Execute($query);
		}
		unset ($package, $tmpInstaller,$filename);
    }
    		
    function disable_duallogin()
    {
    	$db =& JFusionFactory::getDatabase($this->getJname());
    	$query = "DELETE FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Dual Login Plugin'";
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500,$db->stderr());
		}   	
    }
        
    function installRedirectHook()
    {
    	$db =& JFusionFactory::getDatabase($this->getJname());
    	if(!JError::isError($db) && !empty($db)) {    	
			$query = "SELECT COUNT(*) FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Redirect Plugin' AND active = 1";
			$db->setQuery($query);
			$check = ($db->loadResult() > 0) ? true : false;
	
			if ($check){
				//return success
				$output = '<img src="components/com_jfusion/images/check_good.png" height="20px" width="20px">' . JText::_('VB_REDIRECT_HOOK') . ' ' . JText::_('ENABLED');
				$output .= ' <a href="javascript:void(0);" onclick="return toggleHook(\'disable_redirect\')">' . JText::_('DISABLE_THIS_PLUGIN') . '</a>';
				return $output;
			} else {
	       		$output = '<img src="components/com_jfusion/images/check_bad.png" height="20px" width="20px">' . JText::_('VB_REDIRECT_HOOK') . ' ' . JText::_('DISABLED');
				$output .= ' <a href="javascript:void(0);" onclick="return toggleHook(\'enable_redirect\')">' . JText::_('ENABLE_THIS_PLUGIN') . '</a>';
				return $output;
			}
    	} else {
			return JText::_('VB_CONFIG_FIRST');
		}	
    }
    
    function enable_redirect()
    {
		$db =& JFusionFactory::getDatabase($this->getJname());
		$query = "SELECT active FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Redirect Plugin'";
		$db->setQuery($query);
		$active = $db->loadResult();
		
		//remove and recreate the hook for easy upgrade purposes
		if($active=='0' || $active == '1') {
			$query = "DELETE FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Redirect Plugin'";
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500,$db->stderr());
			}
		} 
		
  		$params = JRequest::getVar('params');
   		$itemid = $params['itemid_redirect'];
		
   		if(!empty($itemid))
   		{
   			$php = $this->getHookPHP('redirect',$itemid);
			$query = "INSERT INTO #__plugin SET 
				title = 'JFusion Redirect Plugin', 
				hookname = 'init_startup', 
				phpcode = ".$db->Quote($php).",
				product = 'vbulletin',
				active = 1,
				executionorder = 5";
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500,$db->stderr());   	
			}
   		} else {
			JError::raiseWarning(500,JText::_('VB_REDIRECT_HOOK_ITEMID_EMPTY'));
   		}   		
    }
    
    function disable_redirect()
    {
    	$db =& JFusionFactory::getDatabase($this->getJname());
    	$query = "DELETE FROM #__plugin WHERE hookname = 'init_startup' AND title = 'JFusion Redirect Plugin'";
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500,$db->stderr());
		}
    }
    
    function getHookPHP($plugin, $itemid = false)
    {
    	if($plugin=="frameless") {
			$php = "if(defined('_JEXEC')){\n";
    	} elseif($plugin=="redirect") {
    		$php = "if(!defined('_JEXEC')){\n";

    		///get the visual integration mode
    		jimport('joomla.application.menu');
			$JMenu =& new JMenu;
			$menu = $JMenu->getInstance('site');
    		$params =& $menu->getParams($itemid);
    		$mode = $params->get('visual_integration','frameless');
    		
    		$url = str_replace('/administrator','',JURI::base());
    		$php .= "define('BASEURL','{$url}index.php?option=com_jfusion&Itemid=$itemid');\n";
    		$php .= "define('INTEGRATION_MODE','$mode');\n";
    	} 
    	
    	$hookPath = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jfusion'.DS.'plugins'.DS.$this->getJname().DS.'hooks.php';
    	
		$php .= "define('HOOK_FILE','$hookPath');\n";
    	$php .= "include_once(HOOK_FILE);\n";
     	$php .=	"\$val = '$plugin';\n";
     	$php .= "\$JFusionHook = new executeJFusionHook('init_startup',\$val);\n";
		
     	if($plugin!="duallogin") {
     		$php .= "}";
     	}
		
		return $php;
    }
}