/**
 * MetaSearch Firefox Extension
 *
 * Copyright (C) 2010 Sven Koeppel
 * Copyright (C) 2010 The BioKemika Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <http://www.gnu.org/licenses/>.
 *
 **/
 
var MetaSearch = {} // sic

MetaSearch.version = "0.2";

MetaSearch.Extension = {
	// ein getter... hm.
	get currentContext() {
		return getBrowser().selectedBrowser['metasearch-context'];
	},
	
	// die holt sich der MetaSearch.Context-Konstruktor
	_contextCounter: 0,
	get nextContextCount() {
		var count = MetaSearch.Extension._contextCounter;
		MetaSearch.Extension._contextCounter++;
		return count;
	},

	// Logausgaben an Standard-Mozilla-Log geben
	log : function(aMessage) {
		var consoleService = Components.classes["@mozilla.org/consoleservice;1"]
									   .getService(Components.interfaces.nsIConsoleService);
		consoleService.logStringMessage("MetaSearch: " + aMessage);
	},

	onTabSelect: function(event) {
		var browser = event.target.linkedBrowser;
		var context = browser['metasearch-context'];
		context.onContextShow();
	},

	onTabOpen: function(event) {
		var browser = event.target.linkedBrowser;
		browser['metasearch-context'] = new MetaSearch.Context(browser, document.getElementById('metasearch-sidebar'));
	},

	onTabClose: function(event) {
		var browser = event.target.linkedBrowser;
		var context = browser['metasearch-context'];

		context.destroy();
		delete browser['metasearch-context'];

		if(MetaSearch.Extension.currentContext)
			MetaSearch.Extension.currentContext.onContextShow();
	},
	
	// Clients (Browser-Tabs) und Listener einrichten
	// Code ist ganz schoen geklaut bei reframeit
	setupClientEventListeners : function() {
		var tabContainer = getBrowser().tabContainer;
		tabContainer.addEventListener("TabOpen", MetaSearch.Extension.onTabOpen, false);
		tabContainer.addEventListener("TabSelect", MetaSearch.Extension.onTabSelect, false);
		tabContainer.addEventListener("TabClose", MetaSearch.Extension.onTabClose, false);

		// Fuer alle Tabs einen MetaSearch.Context anlegen
		for(var x=0; x < getBrowser().browsers.length; x++) {
			var browser = getBrowser().getBrowserAtIndex(x);
			browser['metasearch-context'] = new MetaSearch.Context(browser, document.getElementById('metasearch-sidebar'));
		}
		
		// aktuellen Context aktivieren
		for(var x=0; x < getBrowser().browsers.length; x++) {
			var browser = getBrowser().getBrowserAtIndex(x);
			if(browser == getBrowser().selectedBrowser) {
				browser['metasearch-context'].onContextShow();
			}
		}
	}, // setupClientEventListeners

	// Constructor
	initialize : function() {
		this.initialized = true;
		this.strings = document.getElementById("metasearch-strings");
		
		MetaSearch.Extension.log("INITIALIZING");
		MetaSearch.Extension.setupClientEventListeners();
	},
	
	// Sidebar-Status
	isSidebarHidden : function() {
		// splitter, sidebar, button - alles eine Einheit
		var splitter = document.getElementById("metasearch-splitter");
		return splitter.hasAttribute("hidden");
	},
	
	hideSidebar : function(e) {
		if(!MetaSearch.Extension.isSidebarHidden())
			MetaSearch.Extension.toggleSidebar();
	},
	
	showSidebar : function(e) {
		if(MetaSearch.Extension.isSidebarHidden())
			MetaSearch.Extension.toggleSidebar();
	},
	
	// Sidebar-Sichtbarkeit togglen
	toggleSidebar : function(e) {
		MetaSearch.Extension.log("Toggle Sidebar");
		var sidebar = document.getElementById("metasearch-sidebar");
		var splitter = document.getElementById("metasearch-splitter");
		var button = document.getElementById("metasearch-statusbar-button");
		var sidebarbox = document.getElementById("metasearch-sidebarbox");
		// sidebarbox wrappt sidebar - eigentlich also unnoetig, sidebar auch noch
		// zu collapsen

		if(!MetaSearch.Extension.isSidebarHidden()) {
			// Sidebar verstecken
			sidebar.setAttribute("collapsed", "true");
			splitter.setAttribute("hidden", "true");
			sidebarbox.setAttribute("hidden", "true");
			button.setAttribute("sidebar-visible", "false");
		} else {
			// Sidebar wieder anzeigen
			sidebar.removeAttribute("collapsed");
			splitter.removeAttribute("hidden");
			sidebarbox.removeAttribute("hidden");
			button.setAttribute("sidebar-visible", "true");
		}
	},
	
	// Statusbutton geklickt
	onStatusButtonClick : function(e) {
		// jetzt einfach mal
		this.toggleSidebar();
	}
} // MetaSearch.Extension ende


window.addEventListener("load", function() {
	if(!getBrowser().getBrowserAtIndex(0).contentWindow.opener) 
		setTimeout(MetaSearch.Extension.initialize, 10);
	else
		MetaSearch.Extension.log("Gibt nix zu sehen fuer metasearch");
}, false);