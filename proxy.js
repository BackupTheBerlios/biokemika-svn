/**
 * MediaWiki MetaSearch extension
 * JavaScript for MsProxyPage
 **/

/*function msUpdateAssistant(assistant_text, assistant) {
	document.getElementById('ms-assistant-msg').innerHTML = assistant_text;
	document.getElementById('ms-assistant').innerHTML = assistant;
}*/

function msUpdateProxyPage( o ){
	// the "empty" element, e.g. "!EMPTY!". Yes, that's some kind of stupid,
	// but I think "false" cannot be transported throught GET variables in a
	// good way...
	empty = o.empty_value ? o.empty_value : false;
	
	if(o.assistant_text_content && o.assistant_text_content != empty)
		document.getElementById('ms-assistant-msg').innerHTML = o.assistant_text_content;

	if(o.assistant_content && o.assistant_content != empty)
		document.getElementById('ms-assistant').innerHTML = o.assistant_content;

	if(o.height) {
		// we've got the height attribute. That's interesting.
		document.getElementById('ms-proxy-frame').style.height = o.height+"px";
	}

	if(o.deproxified_url && o.deproxified_url != empty) {
		document.getElementById('ms-deprofixy-link').href = o.deproxified_url;
	}

	// TODO: The Link attribute.

}
