<?php

//define the SMF hook to allow for dual login
define('SMF_INTEGRATION_SETTINGS', serialize(array(
	'integrate_verify_user' => 'integrate_user_login',
	'integrate_redirect' => 'integrate_redirect'
)));

function integrate_user_login() {
		$user =& JFactory::getUser();
    	$userlookup = JFusionFunction::lookupUser('smf', $user->get('id'));
    	return $userlookup->userid;
}

function integrate_redirect(&$setLocation, &$refresh)
{

    	//get the correct URL to joomla
    	$params = JFusionFactory::getParams('joomla_int');
		$source_url = $params->get('source_url');

    	//parse the non-SEF URL
		$uri = new JURI($setLocation);

        //set the jfusion references for Joomla
        $Itemid = JRequest::getVar('Itemid');
        if ($Itemid){
			$uri->setVar('Itemid', $Itemid);
        }
		$uri->setVar('option', 'com_jfusion');

		//set the URL with the jFusion params to correct any domain mistakes
		$setLocation = $source_url . 'index.php?' . $uri->getQuery();
}