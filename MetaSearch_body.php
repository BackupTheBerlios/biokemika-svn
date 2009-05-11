<?php
/**
 * Mediawiki MetaSearch Extension: special_page.body.php
 *
 * This is the "main body" function that is called by
 * Mediawiki when the Metasearch special page is loaded.
 * ms_SpecialPage is the controller class that will master
 * the call.
 **/
error_reporting(E_ALL);

$ms_dir = dirname(__FILE__) . '/';


class MsSpecialPage extends SpecialPage {
	static $pages = array(
		#'choose' => 'MsChooserPage', # Choosing the category (query preparation)
		'query' => 'MsQueryPage',    # The core part: Quering
		'list' => 'MsListPage',      # (debugging:) List cats/dbs/etc.
	);

	// Create this MsSpecialPage. This will be the entrypoint for almost *every*
	// call to the MetaSearch system, so this will start up the controller.
	function __construct() {
		parent::__construct( 'Metasearch' );
		wfLoadExtensionMessages('Metasearch');

		$this->controller = MsController::get_instance();
		// (important for global function setup)
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgScriptPath;
		$this->setHeaders();

		$wgOut->addLink( array(
			'rel' => 'stylesheet',
			'href' => "$wgScriptPath/extensions/metasearch/MetaSearch.css",
			'type' => 'text/css'
		) );
		//$wgOut->addScriptFile( "$wgScriptPath/extensions/metasearch/mediawiki/search.js" );

		// code borrowed from SecureSearch extension
		$paramString = strval( $par );
		if ( $paramString === '' ) {
			$paramString = 'query';
		}
		$params = explode( '/', $paramString );
		$pageName = array_shift( $params );
		$page = $this->get_sub_page( $pageName );
		if ( !$page ) {
			$wgOut->addWikiMsg( 'ms-invalid-page', $pageName );
			return;
		}

		$page->execute( $par );
	} // execute

	function get_sub_page($name) {
		if(!isset(self::$pages[$name]))
			return false;
		$class = self::$pages[$name];
		return new $class($this, true); // set als global view!
	}
} // class MsSpecialPage

abstract class MsPage {
	public $controller;
	public $special_page;

	function __construct($special_page, $set_global_view=false) {
		$this->controller = MsController::get_instance();
		$this->special_page = $special_page;

		if($set_global_view) {
			// set the page to the global view for our controller
			$this->controller->view = $this;
		}
	}

	abstract function execute($par);

	# usage in execute: return $this->dump(...);
	static function dump( $data ) {
		global $wgOut;
		$wgOut->addHTML( "<pre>".var_dump_ret($data)."</pre>" );
		return Null;
	}
} // class MsPage


