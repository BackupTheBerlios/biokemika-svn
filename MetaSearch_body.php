<?php
/**
 * Mediawiki MetaSearch Extension: special_page.body.php
 *
 * This is the "main body" function that is called by
 * Mediawiki when the Metasearch special page is loaded.
 * 
 * This file contains many central definitions of the
 * MetaSearch project, like
 * 
 *  - starting class MsSpecialPage, common MsPage class
 *  - important global functions like wfMsgExists
 *  - important classes like MsMsgConfiguration, MsException
 *  - $msDatabaseDriver and it's basic setup
 * 
 **/
error_reporting(E_ALL);

// Load Settings file
$ms_dir = dirname(__FILE__) . '/';
require $ms_dir.'MetaSearch.settings.php';

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

		// subpage handling
		// (code borrowed from SecureSearch extension)
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

		// pre and post notice handling
		$pre = wfMsgNonEmpty('ms-sitenotice');
		if($pre) $wgOut->addWiki($pre);

		$page->execute( $par );

		$post = wfMsgNonEmpty('ms-sitenotice-post');
		if($post) $wgOut->addWiki($post);
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

/// empty message: Message content is only "-", like in MediaWiki:sitenotice
/// @returns string if message is not empty
function wfMsgNonEmpty($name) {
	$msg = wfMsg($name);
	if("&lt;$name&gt;" == $msg || '-' == $msg) return false;
	else return $msg;
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
	/// The Configuration array
	public $conf = array();
	/// true or false, if key in $conf array is "multi", that is
	/// consists of an array instead of a single value. This is
	/// not only true or false but also the length of that array,
	/// but that's not that important.
	private $conf_multi = array();
	/// Set this in your subclass. Then you don't have to give
	/// the methods the name of your message any more directly.
	/// Type: String (message id), like in "MediaWiki:$conf_msg"
	public $conf_msg = Null;
	/// Nobody needs that:
	public $conf_text;

	/// Will load configuration for the values of these keys,
	/// running throught get_conf_msg_name(). This is some kind
	/// of automatic inheritance system, if you don't want this
	/// in your config, overwrite this with Null or an empty array.
	/// Values of this array must be lowercase.
	public $inherit_keys = array('inherit', 'include');
	
	public function has_configuration($message=false) {
		if(!$message) {
			if($this->conf_msg) $message = $this->conf_msg;
			else throw new MsException("Missing message ($message)");
		}
		return wfMsgExists($message);
	}

	/// Overwrite this to get the scheme for your config message.
	/// @returns something like "ms-${id}-foo".
	public static function get_conf_msg_name($id) {
		return Null;
	}

	/**
	 * Reads in a config message "file", Mediawiki:$message.
	 * Calling this method will *extend* the current config
	 * with the read in attributes. It's inteded to be called
	 * by extending subclasses, not by any public "foreign"
	 * instance.
	 *
	 * @param $message if false, will try to look at $conf_msg.
	 **/
	public function read_configuration($message=false) {
		if(!$message) {
			if($this->conf_msg) $message = $this->conf_msg;
			else throw new MsException("Missing message ($message)");
		}
		$lines = explode("\n", wfMsg($message));
		$this->conf_text = wfMsg($message); # for later use.
		# last top level entry
		$last_top_level = Null;

		foreach($lines as $line) {
			if(!preg_match('/^\s*(\*?)\s*(.+?)(\*?):\s*(.*)$/i', $line, $m)) {
				// Issue: Should parsing errors be fatal?
				#return false;
				// perhaps we should just go on?
				continue;
				#throw new MWException("Error: $message has bad MsMsgConfiguration format!");
			}

			$sub_level = !empty($m[1]);
			$key = strtolower($m[2]);
			$multi_entry = !empty($m[3]);
			$val = $m[4];

			// Yes, both sub_level and multi_entry (=array entry) implementions
			// *ARE* Quick & Dirty. Stupid, but quickly implemented.
			if(! $sub_level) {
				# normal top level entry
				if(!isset($this->conf_multi[$key]))
					$this->conf_multi[$key] = 0;
				if($multi_entry) {
					if(! isset($this->conf[$key]))
						$this->conf[$key] = array();
					$this->conf[$key][] = $val;
					$this->conf_multi[$key]++;
				} else {
					// single entry
					$this->conf[$key] = $val;
				}
				$last_top_level = $key;
			} else {
				# sub level entry
				# it's intended to throw away the old contents of the
				# top level entry (that is, the parent shall not have
				# content)
				if(!$last_top_level)
					throw new MsException("MediaWiki:$message starts with sub item.",
						MsException::BAD_CONFIGURATION);
				if(!isset($this->conf[$last_top_level]))
					throw new MsException("MediaWiki:$message parsing: $last_top_level hasn't been stored correctly");
				if($this->conf_multi[$last_top_level]) {
					$last_index = $this->conf_multi[$last_top_level] - 1;
					if(!isset($this->conf[$last_top_level][$last_index]))
						$this->conf[$last_top_level][$last_index] = array();
					$this->conf[$last_top_level][$last_index][$key] = $val;
				} else {
					if(!is_array($this->conf[ $last_top_level ]))
						$this->conf[ $last_top_level ] = array();
					$this->conf[ $last_top_level ][$key] = $val;
				}
			} // if ! $sub_level
		} // for lines

		#var_dump($this->conf, $this->conf_multi);

		// Check for special keys:
		if(!empty($this->inherit_keys)) {
			foreach($this->inherit_keys as $inherit_key) {
				$inherit_key = strtolower($inherit_key);
				foreach($this->get_array($inherit_key) as $id) {
					$msg = $this->get_conf_msg_name($id);
					//print "Inheritance: $id => $msg\n";
					if($msg) {
						// the simple way would be:
						//// $this->read_configuration($msg);
						// but that makes a PHP
						// Segmentation fault. So use that
						// way:

						$sub = new MsMsgConfiguration();
						$sub->conf_msg = $msg;
						$sub->inherit_keys = Null;
						$sub->read_configuration();
						$this->conf = array_merge($sub->conf, $this->conf);
						$this->conf_multi = array_merge($sub->conf_multi, $this->conf_multi);

						// well, this works, but it's not clean at
						// all. Multi inheritance (A -> B -> C)
						// won't work, only at the first level,
						// due to the limits of the
						// get_conf_msg_name() function.
					}
				}
			}
		} // for inherit_keys
	} // function read_configuration

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

	public function get($conf_key, $nonexistent=false) {
		return $this->has_set($conf_key) ? $this->conf[$conf_key] : $nonexistent;
	}

	public function get_sub($conf_key, $sub_key, $nonexistent=false) {
		if(!isset($this->conf_multi[$conf_key])) return $nonexistent;
		if(!$this->conf_multi[$conf_key]) return $nonexistent;
		if(!isset($this->conf[$conf_key][$sub_key])) return $nonexistent;
		return $this->conf[$conf_key][$sub_key];
	}

	public function is_multi($conf_key) {
		return $this->conf_multi[$conf_key];
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
