<?php
defined('_JEXEC' ) or die('Restricted access' );

if (substr($source_path, -1) == DS) {
	define ('DOKU_INC', $source_path);
	require_once($source_path.'inc'.DS.'events.php');
} else {
	define ('DOKU_INC', $source_path.DS);
	require_once($source_path.DS.'inc'.DS.'events.php');
}

/**
 * JFusion Hooks for dokuwiki
 * @package JFusion_dokuwiki
 */
class JFusionDokuWikiHook
{
  /**
   * Register its handlers with the DokuWiki's event controller
   */
  function register(&$controller) {
    $controller->register_hook('ACTION_SHOW_REDIRECT', 'BEFORE',  $this, '_ACTION_SHOW_REDIRECT');
    $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE',  $this, '_ACTION_ACT_PREPROCESS');
    $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE',  $this, '_DOKUWIKI_STARTED');
  }

  function _ACTION_SHOW_REDIRECT(&$event, $param) {
	header('Location: '. JFusionFunction::routeURL('doku.php?id='.$event->data['id'].'&do='.$event->data['preact'], JRequest::getVar('Itemid') ) );
  }

  function _ACTION_ACT_PREPROCESS(&$event, $param) {
	ini_set("session.save_handler", "files");
  }

  function _DOKUWIKI_STARTED(&$event, $param) {
	global $ID;
	/*
	if( !JRequest::getVar('id') ) {
		$share = Dokuwiki::getInstance();
		$conf = $share->getConf();
  		if ( $conf['userewrite'] ) {
			$uri = explode('/' , JRequest::getURI());
			$id = $uri[count($uri)-1];

			list($id) = explode('?' , $id);

			if (!empty($id)) $ID = $id;
			else $ID = $conf['start'];
		}
	}
	*/
  }
}

$hook = new JFusionDokuWikiHook ();

$hook->register($EVENT_HANDLER);