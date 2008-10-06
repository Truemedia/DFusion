<?php

//define the SMF hook to allow for dual login
define('SMF_INTEGRATION_SETTINGS', serialize(array(
	'integrate_verify_user' => 'integrate_user_login'
)));

function integrate_user_login() {

		$user =& JFactory::getUser();
    	$userlookup = JFusionFunction::lookupUser('smf', $user->get('id'));
    	return $userlookup->userid;


}