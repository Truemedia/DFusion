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
			getXMLDocument(jf_scripturl + "&action=quotefast;quote=" + messageid + ";sesc=" + cur_session_id + ";xml", onDocReceived);
		}
		else
			reqWin(jf_scripturl + "&action=quotefast;quote=" + messageid + ";sesc=" + cur_session_id, 240, 90);

		if (navigator.appName == "Microsoft Internet Explorer")
			window.location.hash = "quickreply";
		else
			window.location.hash = "#quickreply";
	}
}