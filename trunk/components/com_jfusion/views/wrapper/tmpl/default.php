<?php
/**
* @package JFusion
* @subpackage Views
* @version 1.0.7
* @author JFusion development team
* @copyright Copyright (C) 2008 JFusion. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access'); ?>


<script language=\"javascript\" type=\"text/javascript\">
function iFrameHeight() {
var h = 0;
if ( !document.all ) {
h = document.getElementById('blockrandom').contentDocument.height;
document.getElementById('blockrandom').style.height = h + 60 + 'px';
} else if( document.all ) {
h = document.frames('blockrandom').document.body.scrollHeight;
document.all.blockrandom.style.height = h + 20 + 'px';
}
}
</script>
<div class=\"contentpane\">
<iframe

<?php if($params->get('wrapper_autoheight')) {?>
onload="iFrameHeight()"
<?php }?>

id="blockrandom"
name="iframe"
src="<?php echo $this->url; ?>""
width="<?php echo $this->params->get('wrapper_width'); ?>"
height="<?php echo $this->params->get('wrapper_height'); ?>"
scrolling="<?php echo $this->params->get('wrapper_scroll'); ?>"

<?php if ($params->get('wrapper_transparency')) { ?>
allowtransparency="true"
<?php } else { ?>
allowtransparency="false"
<?php } ?>

align="top"
frameborder="0"
class="wrapper">
This option will not work correctly.
Unfortunately, your browser does not support Inline Frames
</iframe>
</div>
<div class="back_button">
<a href='javascript:history.go(-1)'>
[ Back ]</a>
</div>
