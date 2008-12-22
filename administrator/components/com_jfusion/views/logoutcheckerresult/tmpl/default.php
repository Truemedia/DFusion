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


//use an output buffer, in order for cookies to be passed onto the header
ob_start();


JFusionFunction::displayDonate();

/**
* 	Load debug library
*/
require_once(JPATH_ADMINISTRATOR .DS.'components'.DS.'com_jfusion'.DS.'models'.DS.'model.debug.php');


/**
* Output information about the server for future support queries
*/
?>
<table><tr><td width="100px">
<img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px">
</td><td width="100px">
<img src="components/com_jfusion/images/login_checker2.png" height="75px" width="75px">
<td><h2><? echo JText::_('LOGOUT_CHECKER_RESULT');?></h2></td>
</tr></table>

<?php

//get the submitted joomla id
$JoomlaId = JRequest::getVar('JoomlaId');

//get the JFusion master
$master = JFusionFunction::getMaster();
if ($master->name && $master->name != 'joomla_int') {
        echo '<br/><h2>' . JText::_('SLAVE'). ' ' . JText::_('JFUSION') . ' ' . JText::_('PLUGIN') . ' ' . JText::_('LOGOUT') .': '. $master->name . '</h2>';
        $JFusionMaster = JFusionFactory::getUser($master->name);
        $userlookup = JFusionFunction::lookupUser($master->name, $JoomlaId);
        $MasterUser = $JFusionMaster->getUser($userlookup->username);
        //check if a user was found
        if ($MasterUser) {
            $MasterSession = $JFusionMaster->destroySession($MasterUser, $options);
            if (!empty($MasterSession['error'])) {
                JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'), $MasterSession['error'],0);
            } else {
                JFusionFunction::raiseWarning($master->name .' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'), $MasterSession['debug'],0);
            }
        } else {
            JFusionFunction::raiseWarning($master->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'),0);
        }
    }
    $slaves = JFusionFunction::getPlugins();
    foreach($slaves as $slave) {
        //check if sessions are enabled
        if ($slave->dual_login == 1) {
            echo '<h2>' . $slave->name . ' ' . JText::_('USER') . ' ' . JText::_('LOGOUT'). '</h2>';
            $JFusionSlave = JFusionFactory::getUser($slave->name);
            $userlookup = JFusionFunction::lookupUser($slave->name, $JoomlaId);
            $SlaveUser = $JFusionSlave->getUser($userlookup->username);
            //check if a user was found
            if ($SlaveUser) {
                $SlaveSession = $JFusionSlave->destroySession($SlaveUser, $options);
                if ($SlaveSession['error']) {
                    JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'),$SlaveSession['error'],0);
                } else {
                    JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('SESSION'). ' ' .JText::_('DESTROY'),$SlaveSession['debug'],0);
                }
            } else {
                JFusionFunction::raiseWarning($slave->name . ' ' .JText::_('LOGOUT'), JText::_('COULD_NOT_FIND_USER'));
            }
        }
    }

ob_end_flush();
return;

