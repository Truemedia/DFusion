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


    var url = '<? echo JURI::root() . 'administrator'. DS .'index.php'; ?>';
    // refresh every 4 seconds
    var timer = 4;
    // periodical and dummy variables for later use
    var periodical, dummy;
    var start = $('start'), stop = $('stop'), log = $('log_res');

    /* our ajax istance */
    var ajax = new Ajax(url, {
        update: log,
        method: 'get',

        onComplete: function() {
            // when complete, we remove the spinner
            log.removeClass('ajax-loading');
        }
        ,
        onCancel: function() {
            // when we stop timed ajax while it's requesting
            // we forse to cancel the request, so here we
            // just remove the spinner
            log.removeClass('ajax-loading');
        }
    }
    );

    /* our refresh function: it sets a dummy to prevent
caching of php and add the loader class */
    var refresh = (function() {
        // dummy to prevent caching of php
        //dummy = $time() + $random(0, 100);
        // we add out fancy spinner
        //log.empty().addClass('ajax-loading');
        // requests of our php plus dummy as query
        var sync_status = document.getElementById("log_res").innerHTML;
        if (sync_status.search(/http/) == -1) {
            alert('continue');
        } else {
            // let's stop our timed ajax
            $clear(periodical);
            // and let's stop our request in case it was waiting for a response
            ajax.cancel();
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
        log.empty().addClass('ajax-loading');
        /* ********************* */

        //give the user a last chance to opt-out
        var answer = confirm("Are you sure you want to run usersync and make PERMANENT changes to your user tables?");
        if (answer) {

            // when we press start we want to inform JFusion how to run the usersync
            var paramString = document.adminForm.toQueryString();
            ajax.request(paramString);

            // then we want to refresh the progress window periodically
            periodical = refresh.periodical(timer * 1000, this);
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

<table><tr><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'jfusion_large.png'; ?>" height="75px" width="75px">
</td><td width="100px">
<img src="<?php echo 'components'.DS.'com_jfusion'.DS.'images'.DS.'usersync.png'; ?>" height="75px" width="75px">
<td><h2><? echo JText::_('USERSYNC'); ?></h2></td></tr></table><br/>
<h3>
<?php echo JText::_('SYNC_WARNING');
?>
</h3><br/>

<div id="ajax_bar"><b>
<?php echo JText::_('SYNC_STEP1');
?>
</div>
<br/>

<form method="post" action="index2.php" name="adminForm">
<input type="hidden" name="option" value="com_jfusion" />
<input type="hidden" name="task" value="synccontroller" />
<input type="hidden" name="syncid" value="<?php echo $this->syncid;?>" />
<table class="adminlist" cellspacing="1"><thead><tr><th width="50px">
<?php echo JText::_('NAME');
?>
</th><th width="50px">
<?php echo JText::_('TYPE');
?>
</th><th width="50px">
<?php echo JText::_('USERS_TOTAL');
?>
</th><th width="200px">
<?php echo JText::_('OPTIONS');
?>
</th></tr></thead>
<tr><td>
<?php echo $this->master_data['jname'];
?>
</td><td>
<?php echo JText::_('MASTER') ?>
<input type="hidden" name="master" value="<?php echo $this->master_data['jname'];
?>" />
</td><td>
<?php echo $this->master_data['total'];
?>
</td><td></td></tr>

<?php foreach($this->slave_data as $slave) {
    ?>
    <tr><td>
    <?php echo $slave['jname'];
    ?>
    <input type="hidden" name="slave[<?php echo $slave['jname'];
    ?>]" value="<?php echo $slave['jname'];
    ?>" />
    </td><td>
    <?php echo JText::_('SLAVE') ?>
    </td><td>
    <?php echo $slave['total'];
    ?>
    <input type="hidden" name="slave[<?php echo $slave['jname'];
    ?>][total]" value="<?php echo $slave['total'];
    ?>" />
    </td><td>
    <?php echo JText::_('SYNC_INTO_MASTER');
    ?><input type="checkbox" name="slave[<?php echo $slave['jname'];
    ?>][sync_into_master]" value="1">
    </td></tr>

<?php }
?>


</table></form>
<br/><br/>

<div id="ajax_bar"><b><?php echo JText::_('SYNC_STEP1_INSTR');
?>
</b>&nbsp;
&nbsp;
&nbsp;
<a id="start" href="#"><?php echo JText::_('START');
?></a>
<span class="border">&nbsp;
</span>
<a id="stop" href="#"><?php echo JText::_('STOP');
?></a>
<div id="aspin"></div>
</div><br/>

<div id="log_res">
</div>
