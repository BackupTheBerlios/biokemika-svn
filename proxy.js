/**
 * MediaWiki MetaSearch extension
 * JavaScript for MsProxyPage
 **/

function msUpdateAssistant(assistant_text, assistant) {
	document.getElementById('ms-assistant-msg').innerHTML = assistant_text;
	document.getElementById('ms-assistant').innerHTML = assistant;
}
