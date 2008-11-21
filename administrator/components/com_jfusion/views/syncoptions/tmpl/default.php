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
JFusionFunction::displayDonate();

?>

<style type="text/css">

#log {
    float: left;
    padding: 0.5em;
    margin-left: 10px;
    width: 600px;
    border: 1px solid #d6d6d6;
    border-left-color: #e4e4e4;
    border-top-color: #e4e4e4;
    margin-top: 10px;
}

#log_res {
    overflow: auto;
}

#aspin.ajax-loading {
    background: url(http://demos.mootools.net/demos/Group/spinner.gif) no-repeat center;
}

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



<script type="text/javascript">
window.addEvent('domready', function() {


    var url = '<?php echo JURI::root() . 'administrator/index.php'; ?>';
    // refresh every 15 seconds
    var timer = 1;
    var time_update = 10;
    var counter = 10;
    // periodical and dummy variables for later use
    var periodical, dummy, sub_vars;
    var start = $('start'), stop = $('stop'), log = $('log_res');

    /* our ajax istance for starting the sync */
    var ajax = new Ajax(url, {
        update: log,
        method: 'get',

        onComplete: function() {
            // when complete, check to see if we should stop the countdown
			div_content = document.getElementById('log_res').innerHTML;
			if (div_content.search(/finished/) != -1) {
		       	// let's stop our timed ajax
		       	$clear(periodical);
        		document.getElementById("counter").innerHTML = '<b><?php echo JText::_('FINISHED');?></b>';
			}

        }

    }

    );

    /* our usersync status update function: */
    var refresh = (function() {

            //add another second to the counter
            counter = counter - 1;
            if (counter < 1) {
				div_content = document.getElementById('log_res').innerHTML;
				if (div_content.search(/finished/) != -1) {
		        	// let's stop our timed ajax
		        	$clear(periodical);
        			document.getElementById("counter").innerHTML = '<b><?php echo JText::_('FINISHED');?></b>';
				} else {
            		counter = time_update;
            		// dummy to prevent caching of php
            		dummy = $time() + $random(0, 100);
            		//generate the get variable for submission
            		sub_vars = 'option=com_jfusion&task=syncstatus&dummy=' + dummy + '&syncid=' + '<?php echo $this->syncid;?>';
					document.getElementById("log_res").innerHTML = '<img src="<?php echo 'components/com_jfusion/images/ajax_loader.gif'; ?>"> Loading ....';
	    			ajax.request(sub_vars);
	    		}
	    	} else {
			//update the counter
        	document.getElementById("counter").innerHTML = '<b><?php echo JText::_('UPDATE_IN');?> ' + counter + ' <?php echo JText::_('SECONDS');?></b>';
	    }

    }
    );

    // start and stop click events
    start.addEvent('click', function(e) {
        // prevent default
        new Event(e).stop();
        // prevent insane clicks to start numerous requests
        $clear(periodical);

        /* a bit of fancy styles */
        stop.setStyle('font-weight', 'normal');
        start.setStyle('font-weight', 'bold');
        /* ********************* */

        //give the user a last chance to opt-out
        var answer = confirm("Are you sure you want to run usersync and make PERMANENT changes to your user tables?");
        if (answer) {

			//check to see what type of output we need
			if (document.forms['adminForm2'].elements['jfusiondebug'].value == 0){
	            // give a summary output

    	        var paramString = 'option=com_jfusion&task=syncstatus&syncid=<?php echo $this->syncid;?>';
				for(i=0; i<document.adminForm.elements.length; i++){
					if(document.adminForm.elements[i].type=="select-one"){
						if(document.adminForm.elements[i].options[document.adminForm.elements[i].selectedIndex].value) {
							paramString = paramString + '&' + document.adminForm.elements[i].name + '=' + document.adminForm.elements[i].options[document.adminForm.elements[i].selectedIndex].value;
						}
					}				}
				new Ajax(url, {method: 'get'}).request(paramString);
            	periodical = refresh.periodical(timer * 1000, this);
			} else {
	            // give a detailed output
	            alert('<?php echo JText::_('SYNC_EXTENDED_REDIRECT');?>');
    	        var paramString = url + '?option=com_jfusion&task=syncstatus&syncid=<?php echo $this->syncid;?>';
				for(i=0; i<document.adminForm.elements.length; i++){
					if(document.adminForm.elements[i].type=="select-one"){
						if(document.adminForm.elements[i].options[document.adminForm.elements[i].selectedIndex].value) {
							paramString = paramString + '&' + document.adminForm.elements[i].name + '=' + document.adminForm.elements[i].options[document.adminForm.elements[i].selectedIndex].value;
						}
					}
				}
				window.location = paramString;
			}

        }

    }
    );

    stop.addEvent('click', function(e) {
        new Event(e).stop();
        // prevent default;

        /* a bit of fancy styles
        note: we do not remove 'ajax-loading' class
        because it is already done by 'onCancel'
        since we later do 'ajax.cancel()'
        */
        start.setStyle('font-weight', 'normal');
        stop.setStyle('font-weight', 'bold');
        /* ********************* */

        // let's stop our timed ajax
        $clear(periodical);
        // and let's stop our request in case it was waiting for a response
        ajax.cancel();
    }
    );
}
);
</script>

<table><tr>
<td width="100px"><img src="components/com_jfusion/images/jfusion_large.png" height="75px" width="75px"></td>
<td width="100px"><img src="components/com_jfusion/images/usersync.png" height="75px" width="75px"></td>
<td><h2><?php echo JText::_('USERSYNC'); ?></h2></td>
</tr></table><br/>

<h3><?php echo JText::_('SYNC_WARNING');?></h3><br/>

<div id="log_res">
<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="syncstatus" />
<input type="hidden" name="syncid" value="<?php echo $this->syncid;?>" />

<div id="ajax_bar"><?php echo JText::_('SYNC_DIRECTION_SELECT');?>&nbsp;&nbsp;&nbsp;
<select name="action">
<option value="master"><?php echo JText::_('SYNC_MASTER');?></option>
<option value="slave"><?php echo JText::_('SYNC_SLAVE');?></option>
</select><br/></div><br/>

<table class="adminlist" cellspacing="1"><thead><tr>
<th width="50px"><?php echo JText::_('NAME');?></th>
<th width="50px"><?php echo JText::_('TYPE');?></th>
<th width="50px"><?php echo JText::_('USERS');?></th>
<th width="200px"><?php echo JText::_('OPTIONS');?></th>
</tr></thead>

<tr><td><?php echo $this->master_data['jname'];?></td>
<td><?php echo JText::_('MASTER') ?></td>
<td><?php echo $this->master_data['total'];?></td>
<td></td></tr>

<?php foreach($this->slave_data as $slave) {?>

    <tr><td><?php echo $slave['jname'];?></td>
    <td><?php echo JText::_('SLAVE') ?></td>
    <td><?php echo $slave['total']; ?></td>
    <td><select name="slave[<?php echo $slave['jname'];?>][perform_sync]">
    <option value=""><?php echo JText::_('SYNC_EXCLUDE_PLUGIN');?></option>
    <option value="1"><?php echo JText::_('SYNC_INCLUDE_PLUGIN');?></option>
    </select></td></tr>

<?php }
?>

</table></form></div><br/>

<div id="counter"></div><br/>

<div id="ajax_bar"><form name="adminForm2"><?php echo JText::_('SYNC_OUTPUT');?> &nbsp;
<select name="jfusiondebug" default="0">
<option value="0"><?php echo JText::_('SYNC_OUTPUT_NORMAL');?></option>
<option value="1"><?php echo JText::_('SYNC_OUTPUT_EXTENDED');?></option>
</select></form></div>
<br/><br/>

<div id="ajax_bar"><b><?php echo JText::_('SYNC_CONTROLLER');?></b>&nbsp;&nbsp;&nbsp;
<a id="start" href="#"><?php echo JText::_('START');?></a>
<span class="border">&nbsp;</span>
<a id="stop" href="#"><?php echo JText::_('STOP');?></a></div><br/>

<br/><br/><br/>
<?php echo '<a href="index.php?option=com_jfusion&task=syncresume&syncid=' . $this->syncid . '">' . JText::_('SYNC_RESUME') . '</a>';
