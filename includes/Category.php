<?php
/**
 * The MsCategory class represents a Category. Nothing more, nothing
 * less ;-)
 * 
 * 
 **/

error_reporting(E_ALL);

/**
 * The CategoryFactory is a bit quick & dirty like, since it also manages
 * some "almost global" functions on Categories, like category tree and
 * category stack handling.
 **/
class MsCategoryFactory {
	public static function exists($name) {
		return wfMsgExists("ms-${name}-category");
	}

	public static function get_root_category() {
		global $msConfiguration;
		return self::get_category($msConfiguration['root-category-name']);
	}

	// just an alias for `new MsCategory($name)`
	public static function get_category($id) {
		return new MsCategory($id);
	}

	// this is a perfomant shorthand to get only the name of a category (id).
	public static function get_category_name($id) {
		$msg = wfMsg("ms-${id}-category");
		if(preg_match('/^\s*Name:\s*(.+)$/mi', $msg, $matching))
			return $matching[1];
		else	return $id.' (nameless)';
	}

	// this will create a stack, where the ROOT category *IS* the
	// very first one.
	public static function get_category_stack($array_of_names) {
		global $msConfiguration;
		if(!is_array($array_of_names))
			$array_of_names = array();
		if(empty($array_of_names) || $array_of_names[0] != $msConfiguration['root-category-name'])
			array_unshift($array_of_names, $msConfiguration['root-category-name']);

		foreach($array_of_names as $k => $v) {
			$array_of_names[$k] = new MsCategory($v);
		}

		return $array_of_names;
	}

	// check stack for inheritance consistency, return a clean stack from
	// the top to at least the errorous position
	public static function clean_category_stack($stack) {
		global $msConfiguration;

		// trivial case.
		if(empty($stack)) return $stack;

		// check root
		if($stack[0]->id != $msConfiguration['root-category-name'])
			// the root was bad.
			array_unshift($stack, $msConfiguration['root-category-name']);

		// walk down stack from the TOP until almost-root
		for($x = count($stack)-1; $x >= 1; $x--) {
			// if the top element is not a child from the one below...
			if(! $stack[$x-1]->has_sub_category($stack[$x])) {
				// ... then kill it.
				array_pop($stack);
			}
		}

		// stack is clean.
		return $stack;
	}
}

class MsCategory {
	public $id;
	private $dummy; // if there doesn't exist such a category
	/// Never access this directly, use only get and set. Direct access
	/// only for friends (Ms prefix classes).
	public $conf; # configuration array => use get_conf_array from external
	public $conf_msg; # the parsed wfMsg that holds the configuration
	public $subs; # sub category object array

	// if name == false => ROOT category.
	public function __construct($id) {
		$this->id = $id;
		$this->conf['id'] = $id; // for better access. Used in userbox.
		$msg = "ms-${id}-category";
		if(!wfMsgExists($msg))
			$this->dummy = true;
		else
			$this->read_configuration($msg);
	}

	// read in the config of this database
	private function read_configuration($message) {
		$lines = explode("\n", wfMsg($message));
		$this->conf_msg = wfMsg($message); # for later use.
		foreach($lines as $line) {
			if(!preg_match('/^\s*(.+?):\s*(.+)$/i', $line, $matching))
				// TODO: parsing errors should not be fatal
				throw new MWException("Error: $message has bad Mscategory format!");
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

	// get the identifiers of the named databases
	public function get_databases() {
		 return $this->get_array($this->has_set('db')?'db':'dbs');
	}
	public function get_sub_categories($as_objects=false) {
		if(!$as_objects)
			return $this->get_array('sub');
		else if(!isset($this->subs)) {
			// create subs
			$this->subs = array();
			foreach($this->get_sub_categories(false) as $cat_id) {
				$this->subs[] = new MsCategory($cat_id);
			}
		}
		return $this->subs;
	}
	public function has_sub_categories() {
		$cats = $this->get_sub_categories();
		return !empty($cats);
	}
	public function has_sub_category($another_cat) {
		if(is_object($another_cat)) $another_cat = $another_cat->id;
		// very lightweight check if that's a sub category or not...
		return in_array($another_cat, $this->get_array('sub'));
	}

	// collect all mediawiki messages
	public function get_messages() {
		$possible_msgs = array(
			'Ms-$1-category',
			'Ms-$1-record',
			#'Ms-category-input-$2', $2 = $this->get('input') !
			'Ms-$1-presearch-box',
			'Ms-$1-postsearch-box'
		);
		$existing_msgs = array();
		foreach($possible_msgs as $msg) {
			$msg = str_replace('$1', $this->id, $msg);
			if(wfMsgExists($msg))
				$existing_msgs[] = $msg;
		}
		return $existing_msgs;
	}

	public function get_conf_array() {
		return $this->exists() ? $this->conf : array();
	}

	// if this database exists
	public function exists() {
		return !$this->dummy;
	}

	// will produce output right to $wgOut.
	public function add_input_text($query) {
		global $wgOut;
		switch($this->get('input')) {
			case false:
			case 'query':
			$wgOut->addHTML(<<<HTML
				Suchbegriff:
				<input type="text" name="ms_query" value="$query" class="text">
				<input type="hidden" name="ms_search" value="Suche jetzt durchfuehren">
				<input type="submit" value="Suchen" class="button">
HTML
			);
			break;
			default:
			$wgOut->addWikiText(wfMsg('ms-category-input-'.$this->get('input')));
		}
	}

	// do we have an input form? If not => don't display.
	public function has_input_text() {
		return strtolower($this->get('input')) != 'none';
	}


	function get_box($area='presearch') {
		return wfMsg('ms-'.$this->id."-${area}-box");
	}
}
