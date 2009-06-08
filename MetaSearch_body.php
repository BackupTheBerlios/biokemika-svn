<?php
/**
 * Mediawiki MetaSearch Extension: special_page.body.php
 *
 * This is the "main body" function that is called by
 * Mediawiki when the Metasearch special page is loaded.
 * ms_SpecialPage is the controller class that will master
 * the call.
 * 
 * Further implementations HERE:
 * 
 * GLOBAL FUNCTIONS
 * 
 * MsMsgConfigurator
 * MsException
 **/
error_reporting(E_ALL);

$ms_dir = dirname(__FILE__) . '/';

////// DatenbankTreiber anmelden

global $msDatabaseDriver;
$msDatabaseDriver['proxydriver'] = array(
	#'id' => 'querydriver',
	'class' => 'MsProxyDatabaseDriver',
	'view' => 'MsProxyPage',
	'author' => 'The MetaSearch Project',
	'version' => '$Id$',
	'description' => 'A core Database Driver',
);

$msDatabaseDriver['querydriver'] = array(
	#'id' => 'querydriver',
	'class' => 'MsQueryDatabase',
	'view' => 'MsQueryPage',
	'author' => 'The MetaSearch Project',
	'version' => '$Id$',
	'description' => 'A core Database Driver',
);


class MsSpecialPage extends SpecialPage {
	static $pages = array(
		'choose' => 'MsChooserPage', # Choosing the category (query preparation)
		'query' => 'MsQueryPage',    # The core part: Quering
		'proxy' => 'MsProxyPage',    # Selected a proxy page
		'list' => 'MsListPage',      # (debugging:) List cats/dbs/etc.
	);
	public $subPageName;

	// Create this MsSpecialPage. This will be the entrypoint for almost *every*
	// call to the MetaSearch system, so this will start up the controller.
	function __construct() {
		parent::__construct( 'Metasearch' );
		wfLoadExtensionMessages('Metasearch');

		#$this->controller = MsController::get_instance();
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

		// code borrowed from SecureSearch extension
		$paramString = strval( $par );
		if ( $paramString === '' ) {
			//$paramString = 'choose'; # default page!
			// redirect to default page
			$title = Title::newFromText( $this->getTitle().'/choose' );
			$wgOut->redirect( $title->getFullURL() );
		}
		$params = explode( '/', $paramString );
		$this->subPageName = array_shift( $params );
		$page = $this->get_sub_page( $this->subPageName );
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

	function get_sub_title($name) {
		return Title::newFromText( $this->getTitle()."/$name");
	}
} // class MsSpecialPage

/**
 * Common base class for all classes that want to display a page.
 * This is a wider approach to the "SpecialPage" class concept:
 * When calling the MetaSearch special page (@class MsSpecialPage),
 * that class will create automatically the right Mspage object
 * which really will do the job.
 **/
abstract class MsPage {
	#public $controller;
	public $special_page;

	// should be placed somewhere here:
/*
	// General concept for getting user input data.
	/// User input data
	public $ms_query;
	public $ms_category_stack;
	public $ms_input_databases;

	/// This will need *everything*. And fill our thingies.
	function validate_user_data() {
		global $wgRequest, $msCategories;

		$this->ms_query = $wgRequest->getText('ms_query');
		if(empty($this->ms_query)) {
			throw new MsException('Please enter an input text.',
				MsException::BAD_INPUT);
		}

		// try{ } catch(MsException e) { }-Konstrukt um das hier:
		$this->ms_category_stack = new MsCategoryStack( $wgRequest->getArray('ms_cat') );

		if( $this->ms_category_stack->get_top()->is_root() ) {
			throw new MsException('Please select a category.',
				MsException::BAD_INPUT);
		} else if( $this->ms_category_stack->get_top()->has_databases() ) {
			throw new MsException('Please select a category that has databases!',
				MsException::BAD_INPUT);
		}

		return true;
	}
*/
	/**
	 * Create a new MsPage. It's not intended to overwrite this
	 * function. Use the 'executed' method to initialize your
	 * page.
	 * @param $special_page The one special page object (MsSpecialPage instance)
	 *                      created by MediaWiki
	 * @param $set_global_view Let the Controller think this is
	 *                         the current view object for the whole instance
	 **/
	function __construct($special_page) {
		#$this->controller = MsController::get_instance();
		$this->special_page = $special_page;

		#if($set_global_view) {
			// set the page to the global view for our controller
		#	$this->controller->view = $this;
		#}

		$this->init();
	}

	/**
	 * Same as SpecialPage->getTitle(), but with correct sub page handling
	 **/
	function getTitle() {
		// quick & dirty
		return $this->special_page->get_sub_title($this->special_page->subPageName);
		//return Title::newFromText( $this->special_page->getTitle().'/'.
		//	$this->special_page->subPageName );
	}

	/**
	 * Run this page. This works exactly like the "SpecialPage" class from
	 * MediaWiki: Simply overwrite this method.
	 **/
	abstract function execute($par);

	/**
	 * Use this as your constructor.
	 **/
	private function init() { }

	/**
	 * A simple dumper. Nothing special. You can use it like
	 *   return $this->dump(...);
	 * in execute() or wherever you want.
	 **/
	static function dump( $data ) {
		global $wgOut;
		$wgOut->addHTML( "<pre>".var_dump_ret($data)."</pre>" );
		return Null;
	}
} // class MsPage


/********************* Global functions ****************************/

/// Returns the content of a var_dump to a string
function var_dump_ret($mixed = null) {
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();
  return $content;
}

/// Checks whether a system message exists.
/// @param name The Name of the message, will be composed like MediaWiki:$name
/// @returns Boolean: True/False
function wfMsgExists($name) {
	return wfMsg($name) != "&lt;$name&gt;";
}

/**
 * A simple implemention of the MediaWiki {{{var}}} replacement
 * for system messages. This replaces {{{key}}} in the corresponding
 * message with $data[key] and returns all that.
 * @param name The name of the system message
 * @param data An associative array (hash) of key => value pairs
 * @returns A string
 **/
function wfMsgData($name, $data) {
	$tmpl = wfMsgExt($name, array( 'parse', 'replaceafter' )); $new = array();
	foreach($data as $k=>$v) { $new['{{{'.$k.'}}}']=$v; }
	return strtr($tmpl, $new);
}

/**
 * wfMsg with auto-fallback functionality: Returns wfMsg($name)
 * if it exists, else wfMsg($fallback_name).
 */
function wfMsgFallback($name, $fallback_name) {
	if(wfMsgExists($name))
		return wfMsg($name);
	else
		return wfMsg($fallback_name);
}

/********************* MsgConfiguration ****************************/
class MsMsgConfiguration {
	public $conf = array();
	public $conf_msg; ///<- String: Message id
	public $conf_text;
	
	public function has_configuration($message=false) {
		return wfMsgExists($message);
	}

	/// read in the config file (MediaWiki:$message)
	public function read_configuration($message=false) {
		$lines = explode("\n", wfMsg($message));
		$this->conf_text = wfMsg($message); # for later use.
		foreach($lines as $line) {
			if(!preg_match('/^\s*(.+?):\s*(.+)$/i', $line, $matching))
				// TODO: parsing errors should not be fatal
				// DONE: Now they are not. *g*
				return false;
				#throw new MWException("Error: $message has bad MsMsgConfiguration format!");
			$this->conf[ strtolower($matching[1]) ] = $matching[2];
		}
	}

	// parse and get configuration key as array.
	// Key has to be built up like: a, b, c
	public function get_array($conf_key) {
		if(! $this->has_set($conf_key))
			// there are no sub categories
			return array();
		else if(!is_array($this->conf[$conf_key])) {
			// key has not been parsed yet
			$this->conf[$conf_key] = array_map('trim', explode(', ', $this->conf[$conf_key]) );
		}
		return $this->conf[$conf_key];
	}

	public function has_set($conf_key) {
		return isset($this->conf[$conf_key]);
	}

	public function get($conf_key) {
		return $this->has_set($conf_key) ? $this->conf[$conf_key] : false;
	}

	/// Use with care...
	public function set($conf_key, $value) {
		$this->conf[$conf_key] = $value;
	}

	/// set a default value if conf_key was not set. Will return
	/// if it was set before we set the default value
	public function set_default($conf_key, $default_value) {
		$was_set = $this->has_set($conf_key);
		if(!$was_set)
			$this->set($conf_key, $default_value);
		return $was_set;
	}

}

/********************* MsException Class ***************************/
/**
 * Use it like
 *    new MsException('Your text', MsException::BAD_CONFIG);
 * and the Metasearch system will create the right action...
 **/
class MsException extends MWException {
	#public $type; # => $this->code (Exception)
	const BAD_INPUT = 1;
	const BAD_INSTALLATION = 2;
	const BAD_CONFIGURATION = 3;
	const BAD_DRIVER = 4;
	const NOT_YET = 5;
}
