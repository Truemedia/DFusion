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

<script type="text/javascript">
function autofitIframe(frame,name)
{
	var ua, s, i;

	var isIE = false;
	var version = null;

	ua = navigator.userAgent;

	if ((i = ua.indexOf("MSIE")) >= 0)
	{
		isIE = true;
		version = parseFloat(ua.substr(i + 4));
	}

	if (isIE)
	{
		try
		{
			var win = document.getElementById(name).contentWindow.document;

			//find the height of the internal page
			var the_height = win.body.scrollHeight;

			//change the height of the iframe
			document.getElementById(name).height=the_height;
			window.scrollTo(0,0);
		}
		//An error is raised if the IFrame domain != its container's domain
		catch(e)
		{
			window.status =	'Error: ' + e.number + '; ' + e.description;
		}
	}
	else
	{
		var win = document.getElementById(name).contentWindow.document;

		//find the height of the internal page
		var the_height = win.body.scrollHeight;

		//change the height of the iframe
		document.getElementById(name).height=the_height;
		window.scrollTo(0, 0);
	}
}
</script>

<div class="contentpane">

<iframe frameborder='0' id='the_iframe'

<?php if($this->params->get('wrapper_autoheight', 1)) {?>
onload="autofitIframe(this, 'the_iframe')"
<?php }?>

src="<?php echo $this->url; ?>""
width="<?php echo $this->params->get('wrapper_width', '100%'); ?>"
height="<?php echo $this->params->get('wrapper_height', '500'); ?>"
scrolling="<?php echo $this->params->get('wrapper_scroll', 'auto'); ?>"

<?php if ($this->params->get('wrapper_transparency')) { ?>
allowtransparency="true"
<?php } else { ?>
allowtransparency="false"
<?php } ?>
>
<?php echo JText::_('OLD_BROWSER');?>
</iframe>
</div>

