/* einzubinden nach metasearch.js */

/**
 * MetaSearch.Context: Fuer jeden Tab gibt es eine Instanz dieser Klasse
 *
 **/
MetaSearch.Context = function(contentBrowser, marginParent) {
	this.destroyed = false;
	this.sidebar = null;
	this.contentBrowser = contentBrowser;
	this.id = MetaSearch.Extension.nextContextCount;
	this.seenDOMLoad = false;

	// supi, eine pseudozufaellige alpha-nonnumerische id
	for(var ix = 0; ix < 9; ix++) 
		this.id += 'abcdefghij'.charAt(10 * Math.random());

	var self = this; // fuer anonyme funktionen (listener)
	
	// SIDEBAR-Widget wird HIER eingerichtet, gehoert also dem CONTEXT
	this.sidebarFrame = document.createElement('browser'); // statt ('iframe');
	this.sidebarFrame.setAttribute('type', 'content'); // KEINE CHROME-RECHTE
	this.sidebarFrame.style.overflow = 'hidden';
	this.sidebarFrame.addEventListener("DOMContentLoaded", function(e) { self.onSidebarLoad(e); }, true);
	marginParent.appendChild(this.sidebarFrame);

	this.attachNamespace(this.sidebarFrame.contentWindow);

	var sidebarSource =  'chrome://metasearch/content/empty-sidebar.xul';
	this.sidebarFrame.setAttribute('src', sidebarSource);

	// Das wichtigste: Content-Listener HIER
	this.contentBrowser.addEventListener("DOMContentLoaded", function(event) { self.onContentDOMLoad(event); }, false);
}

MetaSearch.Context.prototype = {
	get contentWindow() {
		return this.contentBrowser.contentWindow;
	},

	get contentDocument() {
		return this.contentBrowser.contentDocument;
	},
  
	destroy: function() {
		this.destroyed = true;
    
		if(this.sidebar && this.sidebar.onContextDestroy)
			this.sidebar.onContextDestroy();
      
		this.sidebarFrame.parentNode.removeChild(this.sidebarFrame);
    
		try {
			this.selectionInterface.removeSelectionListener(this);
			this.contentBrowser.removeProgressListener(this);
		} catch(ex) {}
	}, // destroy
  
	attachNamespace: function(win) {
		// diese funktion macht nichts geringeres als dem kompletten iframe
		// javascriptmaessig zugriff auf chrome-Elemente zu geben (der content
		// von reframeit kam ja auch ausm chrome...)
		win.aaa = "hallo wie gehts";
		win.metasearch = {};
		win.metasearch.contentWindow = this.contentWindow;
		win.metasearch.contentDocument = this.contentDocument;
	},

	// die wichtigste funktion schlechthin!
	onContentDOMLoad: function(event) {
		this.seenDOMLoad = true;
		MetaSearch.Extension.log("Lade Website!");
		if(event.originalTarget == this.contentWindow.document) {
			var self = this;
			setTimeout(function() { self.updateBC(); }, 10);
		}
	},

	// wird von MetaSearch.Extension aufgerufen (beim Tabschliessen, oeffnen, etc.)
	onContextShow: function() {
		// Die Sidebar zu DIESEM Tab anzeigen
		this.sidebarFrame.parentNode.selectedPanel = this.sidebarFrame;
		if(this.sidebar && this.sidebar.onContextShow) {
			var self = this;
			// ruft sich wohl alle 10 sek rekursiv auf... krank
			//setTimeout(function() { self.sidebar.onContextShow(); }, 10);
			// nein, das ruft IN der Sidebar auf
			MetaSearch.Extension.log("onContextShow");
		}
	},

	// Sidebar irgendwie die API stellen und so
	onSidebarLoad : function(event) {
		// funktion wird sogar bei DOMContentLoaded aufgerufen,
		// sobald der Inhalt der *sidebar* sich aendert, nicht
		// des hauptbrowsers.
		// wichtig: Kommunikation herstellen und so
		
		// was nicht geht: Variablen dort gueltig machen und dann
		// global verwenden (vmtl. wegen fehlenden Chrome-Rechten!)
	    var win = this.sidebarFrame.contentWindow ? this.sidebarFrame.contentWindow : event.target.defaultView;
		MetaSearch.Extension.log("onSidebarLoad auf "+win);
		win.MetaSearch = MetaSearch;
		win.bla = "bluu";
		
		// was schon geht: Von aussen den DOM abklappern:
		if(win.document.getElementById("metasearch-hide-sidebar")) {
			// wow, das geht sogar
			this.last_site_disabled_sidebar = true; // um flickern zu vermeiden bei vielen seiten mit deaktivierter sidebar
			MetaSearch.Extension.hideSidebar();
			return;
		} else if(this.last_site_disabled_sidebar) {
			this.last_site_disabled_sidebar = false;
			MetaSearch.Extension.showSidebar();
		}
		
		// ja, das ist dumm, aber es ist 05:00. Hier kommt noch ne
		// richtige API...
	}, // onSidebarLoad
	
	updateBC: function() {
		var win = this.contentDocument; // = defaultView

		/*// this is the content document of the loaded page.  
		if (doc.defaultView.frameElement) {
			// Frame within a tab was loaded.  
			// Find the root document:  
			while (doc.defaultView.frameElement) {  
				doc = doc.defaultView.frameElement.ownerDocument;
			}
		}
		*/
		// here we got a clean doc.
		MetaSearch.Extension.log("New Document loaded: " + this.contentWindow.location.href);
		
		// hier geht jetzt die Party ab:
		// gut zum debuggen: "http://de.selfhtml.org/cgi-bin/comments.pl"
		var base_url = "http://svenk.homeip.net/metasearch2010/server/index.pl";
		var dataString = "query=1&url=" + encodeURIComponent(this.contentDocument.location.href);
		dataString += "&content=" + encodeURIComponent(this.contentDocument.documentElement.innerHTML); // <html> tag fehlt, aber egal
		
		MetaSearch.Extension.log("QUERY new page "+this.contentDocument.location.href);

		// POST method requests must wrap the encoded text in a MIME
		// stream
		const Cc = Components.classes;
		const Ci = Components.interfaces;
		var stringStream = Cc["@mozilla.org/io/string-input-stream;1"].
						   createInstance(Ci.nsIStringInputStream);
		if ("data" in stringStream) // Gecko 1.9 or newer
			stringStream.data = dataString;
		else // 1.8 or older
			stringStream.setData(dataString, dataString.length);

		var postData = Cc["@mozilla.org/network/mime-input-stream;1"].
						createInstance(Ci.nsIMIMEInputStream);
		postData.addHeader("Content-Type", "application/x-www-form-urlencoded");
		postData.addContentLength = true;
		postData.setData(stringStream);

		// POST-Call im Sidebar-Browser absetzen
		if(this.sidebarFrame)
			this.sidebarFrame
				.loadURIWithFlags(base_url, null, null, null, postData);
		else
			MetaSearch.Extension.log("hab keinen sidebariframe: "+this.sidebarFrame);
	} // updateBC
} // MetaSearch.Context
