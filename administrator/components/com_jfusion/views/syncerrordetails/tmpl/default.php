<?php
/**
* @package JFusion
* @subpackage Views
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

	/**
	* 	Load usersync library
	*/
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.usersync.php');

	/**
	* 	Load debug library
	*/
	require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');


?>
<style type="text/css">
#ajax_bar {
    background-color: #7fa9ff;
    border: 1px solid #d6d6d6;
    border-left-color: #e4e4e4;
    border-top-color: #e4e4e4;
    margin-top: 0pt auto;
    height: 20px;
    padding: 3px 5px;
    vertical-align: center;
}
</style>


<div id="ajax_bar"><font size="3">Detailed JFusion Error Report</font></div>

<?php

$syncdata = $this->syncdata;

//get the error
$errorid = JRequest::getVar('errorid', '', 'GET');
$error =  $this->syncerror[$errorid];

//display the userlist info
debug::show($error['user']['jname'], 'User from Plugin',1);
echo '<br/>';
debug::show($error['user']['userlist'], 'User Info from Usersync List',1);
echo '<br/>';
debug::show($error['user']['userinfo'], 'User Info from getUser() function');
echo '<br/>';
debug::show($error['conflict']['jname'], 'User target Plugin',1);
echo '<br/>';
debug::show($error['conflict']['error'], 'Error Info from updateUser() function');
echo '<br/>';
debug::show($error['conflict']['debug'], 'Debug Info from updateUser() function');
echo '<br/>';
debug::show($error['conflict']['userinfo'], 'User Info from updateUser() function');


