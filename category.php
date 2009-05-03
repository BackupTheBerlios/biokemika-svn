<?php
/**
 * The MsCategory class represents a Category. Nothing more, nothing
 * less ;-)
 * 
 * 
 **/

error_reporting(E_ALL);

class MsCategoryFactory {
	public static function get_category_tree() {
	}

	public static function exists($name) {
		return wfMsgExists("ms-${name}-category");
	}

	public static function get_root_category($name) {
		global $msConfiguration;
		$this->get_category($msConfiguration['root-category-name']);
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
}

class MsCategory {
	public $id;
	private $dummy; // if there doesn't exist such a category
	public $conf;

	// if name == false => ROOT category.
	public function __construct($id) {
		$this->id = $id;
		$msg = "ms-${id}-category";
		if(!wfMsgExists($msg))
			$this->dummy = true;
		else
			$this->read_configuration($msg);
	}

	// read in the config of this database
	private function read_configuration($message) {
		$lines = explode("\n", wfMsg($message));
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
	public function get_sub_categories() {
		return $this->get_array('sub');
	}

	// if this database exists
	public function exists() {
		return $this->dummy;
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


	function get_box($area='presearch') {
		return wfMsg('ms-'.$this->id."-${area}-box");
	}
}
