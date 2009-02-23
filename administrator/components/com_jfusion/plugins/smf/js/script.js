function jfusion_doQuote(messageid, cur_session_id)
{
	if (quickReplyCollapsed)
		window.location.href = jf_scripturl + "&action=post;quote=" + messageid + ";topic=" + smf_topic + "." + smf_start + ";sesc=" + cur_session_id;
	else
	{

		if (window.XMLHttpRequest)
		{
			if (typeof window.ajax_indicator == "function")
				ajax_indicator(true);
			jfusion_getXMLDocument(jf_scripturl + "&action=quotefast;quote=" + messageid + ";sesc=" + cur_session_id + ";xml", jfusion_onDocReceived);
		}
		else
			reqWin(jf_scripturl + "&action=quotefast;quote=" + messageid + ";sesc=" + cur_session_id, 240, 90);

		if (navigator.appName == "Microsoft Internet Explorer")
			window.location.hash = "quickreply";
		else
			window.location.hash = "#quickreply";
	}
}
function jfusion_onDocReceived(XMLDoc)
{
	alert("jfusion_onDocReceived:"+text);
	for (var i = 0; i < XMLDoc.getElementsByTagName("quote")[0].childNodes.length; i++){
		text += XMLDoc.getElementsByTagName("quote")[0].childNodes[i].nodeValue;
	}

	replaceText(text, document.forms.postmodify.message);

	if (typeof window.ajax_indicator == "function")
		ajax_indicator(false);
}
// Load an XML document using XMLHttpRequest.
function jfusion_getXMLDocument(url, callback)
{
	if (!window.XMLHttpRequest)
		return false;
	var callit = callback;
	var myDoc = new XMLHttpRequest();
	if (typeof(callback) != "undefined")
	{

		myDoc.onreadystatechange = function ()
		{
			if (myDoc.readyState != 4)
				return;
				//alert(myDoc.responseText);
			if ( myDoc.responseXML != null && myDoc.status == 200 ) {
				//callback(myDoc.responseXML);
				var text = "";
				for (var i = 0; i < myDoc.responseXML.getElementsByTagName("quote")[0].childNodes.length; i++){
					text += myDoc.responseXML.getElementsByTagName("quote")[0].childNodes[i].nodeValue;
				}

				replaceText(text, document.forms.postmodify.message);

				if (typeof window.ajax_indicator == "function")
					ajax_indicator(false);
			}
		};
	}
	myDoc.open("GET", url, true);
	myDoc.send(null);

	return true;
}