/*
	corz pngFIX v0.2

	PNG transparency handler in Win IE 5.5 or higher. based on the IE PNG hack 
	type code.. http://homepage.ntlworld.com/bobosola, and other stuff.
	
	put something like this in your header..
	
		<!--[if gte IE 5.5000]>
			<script src="/inc/js/pngfix.js" type="text/javascript"></script>
		<![endif]-->

	the roll-over image tags, as used in ampsig.com galleries (with their names being 
	"something_static.png" and "something_hover.png") go something like this..

		<img id="bspideyswitch" alt="ampsig.. BIG Spidey" src="big-spidey_static.png" 
		onmouseover="PNGswap(this)" onmouseout="PNGswap(this)" />

	have fun!

	;o)
	(or
*/

function pngFIX()
{
	for(var i=0; i<document.images.length; i++)
	{
		var img = document.images[i]
		var imgName = img.src.toLowerCase()
		if (imgName.substring(imgName.length-3, imgName.length) == "png")
		{
			var imgID = (img.id) ? "id=\"" + img.id + "\" " : ""
			var imgClass = (img.className) ? "class=\"" + img.className + "\" " : ""
			var imgTitle = (img.title) ? "title=\"" + img.title + "\" " : "title=\"" + img.alt + "\" "
			var imgStyle = "display:inline-block;" + img.style.cssText 
			var imgAttribs = img.attributes;
			for (var j=0; j<imgAttribs.length; j++)
			{
				var imgAttrib = imgAttribs[j];
			}
			var strNewHTML = "<span " + imgID + imgClass + imgTitle
			strNewHTML += " style=\"" + "width:" + img.width + "px; height:" + img.height + "px;" + imgStyle + ";"
			strNewHTML += "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader"
			strNewHTML += "(src='" + img.src + "', sizingMethod='scale');\""
			if (img.onmouseover) strNewHTML += " onmouseover=\"PNGswap('" + img.id + "');\" onmouseout=\"PNGswap('" + img.id +"');\""
			strNewHTML += " /></span>" 
			img.outerHTML = strNewHTML
			i = i - 1
		}
	}
}

function PNGswap(sIMG)
{
	var strOver  = "_hover"
	var strOff = "_static"
	var oSpan = document.getElementById(sIMG)
	var currentAlphaImg = oSpan.filters(0).src
	if (currentAlphaImg.indexOf(strOver) != -1)
	{
		oSpan.filters(0).src = currentAlphaImg.replace(strOver,strOff)
	}
	else
	{
		oSpan.filters(0).src = currentAlphaImg.replace(strOff,strOver)
	}
}

window.attachEvent("onload", pngFIX);
