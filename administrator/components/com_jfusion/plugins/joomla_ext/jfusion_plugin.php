<?php
/**
 * @package JFusion_Joomla_Ext
 * @version 1.1.0-001
 * @author JFusion development team -- Henk Wevers
 * @copyright Copyright (C) 2008 JFusion. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */


defined('_JEXEC' ) or die('Restricted access' );

/**
 * load the JFusion framework
 */
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jfusion.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.abstractplugin.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.jplugin.php');

/**
 * JFusion plugin class for an external Joomla database
 * @package JFusion_Joomla_Ext
 */
class JFusionPlugin_joomla_ext extends JFusionPlugin
{
    function getJname()
    {
    return 'joomla_ext';
    }

    function getTablename(){
        return JFusionJplugin::getTablename();
    }

    function getRegistrationURL(){
        return JFusionJplugin::getRegistrationURL();
    }

    function getLostPasswordURL(){
        return JFusionJplugin::getLostPasswordURL();
    }

    function getLostUsernameURL(){
             return JFusionJplugin::getLostUsernameURL();
    }


    function getUserList(){
     $db = JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::getUserList($db);
    }

    function getUserCount(){
     $db = JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::getUserCount($db);
    }

    function getUsergroupList(){
     $db = JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::getUsergroupList($db);
    }

    function getDefaultUsergroup(){
        //we want to output the usergroup name
     $db = JFusionFactory::getDatabase($this->getJname());
        return JFusionJplugin::getDefaultUsergroup($db,$this->getJname());
    }


  function setupFromPath($forumPath)
    {
      //check for trailing slash and generate file path
        if (substr($forumPath, -1) == DS) {
            $configfile = $forumPath . 'configuration.php';
        } else {
            $configfile = $forumPath . DS. 'configuration.php';
        }

        if (($file_handle = @fopen($configfile, 'r')) === FALSE) {
            JError::raiseWarning(500,JText::_('WIZARD_FAILURE'). ": $myfile " . JText::_('WIZARD_MANUAL'));
      return false;
        } else {
            //parse the file line by line to get only the config variables
            //we can not directly include the config file as JConfig is already defined
            $file_handle = fopen($configfile, 'r');
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                if (strpos($line, '$')) {
                    //extract the name and value, it was coded to avoid the use of eval() function
                    $vars = split ("'", $line);
                    $names = split ('var', $vars[0] );
                    $name = trim($names[1], ' $=');
                    $value = trim($vars[1], ' $=');
                    $config[$name] = $value;
                }
            }
            fclose($file_handle);
            //Save the parameters into the standard JFusion params format
            $params = array();
            $params['database_host'] = $config['host'];
            $params['database_name'] = $config['db'];
            $params['database_user'] = $config['user'];
            $params['database_password'] = $config['password'];
            $params['database_prefix'] = $config['dbprefix'];
            $params['database_type'] = $config['dbtype'];
            $params['source_path'] = $forumPath;
            return $params;
        }
  }


    function allowRegistration(){
      $db = JFusionFactory::getDatabase($this->getJname());
      $query = "SELECT params FROM #__components WHERE name=".$db->quote("User Manager");//." AND option='aa'"  ;
      $db->setQuery( $query );
      $params = $db->loadResult();

      $parametersInstance = new JParameter($params, '' );
      if ($parametersInstance->get('allowUserRegistration')) {
           return true;
           } else {
      return false;
    }
    }
/*
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

    // set scope for variables required later
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
      $regex_body    = array();
      $replace_body  = array();

      //convert relative links with query into absolute links
      $regex_body[]  = '#href="./(.*)\?(.*)"#mS';
      $replace_body[]  = 'href="'.$baseURL.'&jfile=$1&$2"';

      //convert relative links without query into absolute links
      $regex_body[]  = '#href="./(.*)"#mS';
      $replace_body[]  = 'href="'.$baseURL.'&jfile=$1"';

      //convert relative links from images into absolute links
      $regex_body[]  = '#(src="|url\()./(.*)("|\))#mS';
      $replace_body[]  = '$1'.$integratedURL.'$2$3"';

      //convert links to the same page with anchors
      $regex_body[]  = '#href="\#(.*?)"#';
      $replace_body[]  = 'href="'.$fullURL.'&#$1"';

      //update site URLs to the new Joomla URLS
      $regex_body[]  = "#$integratedURL(.*)\?(.*)\"#mS";
      $replace_body[]  = $baseURL . '&jfile=$1&$2"';

      //convert action URLs inside forms to absolute URLs
      //$regex_body[]  = '#action="(.*)"#mS';
      //$replace_body[]  = 'action="'.$integratedURL.'/"';

    }

    $buffer = preg_replace($regex_body, $replace_body, $buffer);
  }

  function parseHeader(&$buffer, $baseURL, $fullURL, $integratedURL)
  {
    static $regex_header, $replace_header;

    if ( ! $regex_header || ! $replace_header )
    {
      // Define our preg arrays
      $regex_header    = array();
      $replace_header  = array();

      //convert relative links into absolute links
      $regex_header[]  = '#(href|src)=("./|"/)(.*?)"#mS';
      $replace_header[]  = 'href="'.$integratedURL.'$3"';

      //$regex_header[]  = '#(href|src)="(.*)"#mS';
      //$replace_header[]  = 'href="'.$integratedURL.'$2"';

    }

    $buffer = preg_replace($regex_header, $replace_header, $buffer);
}

*/

 }