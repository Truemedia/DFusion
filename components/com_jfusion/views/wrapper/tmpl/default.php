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


<script language="javascript" type="text/javascript">
    function getElement(aID)
    {
        return (document.getElementById) ?
            document.getElementById(aID) : document.all[aID];
    }

    function getIFrameDocument(aID){
        var rv = null;
        var frame=getElement(aID);
        // if contentDocument exists, W3C compliant (e.g. Mozilla)

        if (frame.contentDocument)
            rv = frame.contentDocument;
        else // bad IE  ;)

            rv = document.frames[aID].document;
        return rv;
    }

    function adjustMyFrameHeight()
    {
        var frame = getElement("blockrandom");
        var frameDoc = getIFrameDocument("blockrandom");
        frame.height = frameDoc.body.offsetHeight;
    }



</script>
<div class="contentpane">
<iframe

<?php if($this->params->get('wrapper_autoheight')) {?>
onload="adjustMyFrameHeight();"
<?php }?>

id="blockrandom"
name="iframe"
src="<?php echo $this->url; ?>""
width="<?php echo $this->params->get('wrapper_width'); ?>"
height="<?php echo $this->params->get('wrapper_height'); ?>"
scrolling="<?php echo $this->params->get('wrapper_scroll'); ?>"

<?php if ($this->params->get('wrapper_transparency')) { ?>
allowtransparency="true"
<?php } else { ?>
allowtransparency="false"
<?php } ?>

align="top" frameborder="0" class="wrapper">
<?php echo JText::_('OLD_BROWSER');?>
</iframe>
</div>

