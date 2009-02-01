<?php
defined('_JEXEC' ) or die('Restricted access' );

require_once(dirname(__FILE__).'/admin.php');
require_once(dirname(__FILE__).'/user.php');

require_once(dirname(__FILE__).DS.'auth'.DS.'plain.class.php');

class Dokuwiki {
	var $auth = null;
	var $io = null;

	function Dokuwiki()
	{
	    $this->auth = new doku_auth_plain();
	}

	function getJname()
	{
		return 'dokuwiki';
	}

	function &getInstance( )
	{
		static $instances;

		if (!isset( $instances )) {
			$instance	= new Dokuwiki();
			$instances = & $instance;
		}
		return $instances;
	}

	function getConf($path=false)
	{
	  static $config;
	  if ( is_array($config) ) return $config;

	  if(!$path) {
		$params = JFusionFactory::getParams($this->getJname());
		$path = $params->get('source_path');
	  }

	  if (substr($path, -1) == DS) {
		$myfile[] = $path . 'conf/dokuwiki.php';
		$myfile[] = $path . 'conf/local.php';
		$myfile[] = $path . 'conf/local.protected.php';
	  } else {
		$myfile[] = $path . DS . 'conf/dokuwiki.php';
		$myfile[] = $path . DS . 'conf/local.php';
		$myfile[] = $path . DS . 'conf/local.protected.php';
	  }
	$conf=null;
	  foreach($myfile as $key => $file) {
		  if( file_exists($file)) {
			require($file);
		  } else if ( $key < 2 ) {
			JError::raiseWarning(500,JText::_('WIZARD_FAILURE').": ".$file." No files Founed ".JText::_('WIZARD_MANUAL'));
			return false;
		  }
	  }
	  $config=$conf;

	  if ( is_array($config) ) {
		return $config;
	  } else {
		JError::raiseWarning(500,JText::_('WIZARD_FAILURE').": Array Expected, file error? ".JText::_('WIZARD_MANUAL'));
		return false;
	  }
	}

	function getUserList($username=false,$full=false)
	{
	  $list = $this->auth->_loadUserData();
	  if ( !count($list) ) {
		  JError::raiseWarning(500,"NO USER FOUNED");
		  return false;
	  }

	  if ($full) return $list;
	  else if ($username) return $list[$username];

	   foreach($list as $key => $value) {
			$user = new stdClass;
			$user->username = $key;
			$user->email = $list[$key]['mail'];
			$userlist[] = $user;
		}

	  return $userlist;
	}

	function getDefaultUsergroup()
	{
		$share = Dokuwiki::getInstance();
		$conf = $share->getConf();
		if ($conf['defaultgroup']) return $conf['defaultgroup'];
		if ($conf) return 'user';
		return;
	}
}
