<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

/**
* Load the JFusion framework
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.factory.php');
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');
JFusionFunction::displayDonate();

?>
<style type="text/css">
#ajax_bar {
    background-color: #e4ecf2;
    border: 1px solid #d6d6d6;
    border-left-color: #e4e4e4;
    border-top-color: #e4e4e4;
    margin-top: 0pt auto;
    height: 20px;
    padding: 3px 5px;
    vertical-align: center;
}
</style>


<div id="ajax_bar">Detailed JFusion Error Report</div>

<?php
//get the error
$errorid = JRequest::getVar('errorid', '', 'GET');
$error =  $this->syncdata['errors'][$errorid];

//display the userlist info
debug::show($error['user']['userlist'], 'User Info from Usersync List');
debug::show($error['user']['userinfo'], 'User Info from getUser() function');
debug::show($error['conflict']['error'], 'Error Info from updateUser() function');
debug::show($error['conflict']['debug'], 'Debug Info from updateUser() function');


