<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsCategory, MsCategoryFactory
 * 
 * A MsCategory represents a category. All databases are organized
 * in one or more categories. Category configuration is done via
 * Mediawiki system messages in a very simple format that is parsed
 * rapidly for every category in every case it is used.
 * All categories are organized in a hierarchic stucture, called
 * the Categorytree. That behaves quite like a directory tree.
 * 
 * The MsCategoryFactory class implements all concepts for
 * creating categories and handling category stacks.
 * 
 * (c) Copyright 2009 Sven Koeppel
 *
 * This program is free software; you can redistribute
 * it and/or modify it under the terms of the GNU General
 * Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General
 * Public License along with this program; if not, see
 * http://www.gnu.org/licenses/
 **/

error_reporting(E_ALL);

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
////////////////////      CATEGORY FACTORY     ////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

class MsCategoryFactory {
	/**
	 * Checks whether a category with this name exists, that is, if it
	 * is set up or not. A Category is defined to be set up when there
	 * exists an appropriate MediaWiki configuration page for that category.
	 * @param $name String: Name of the category. Case sensitive!
	 **/
	public static function exists($name) {
		return wfMsgExists("ms-${name}-category");
	}

	/**
	 * Get *the* fully featured root category object. You should save your
	 * root category object somewhere, since this is not a singleton, but
	 * a very ordinary object that is created on each call.
	 * @returns A new Root Category object (MsCategory).
	 **/
	public static function get_root_category() {
		global $msConfiguration;
		return self::get_category($msConfiguration['root-category-name']);
	}

	/**
	 * An alias for new MsCategory($name). Nothing more.
	 * @param $id String: Name of the category.
	 **/
	public static function get_category($id) {
		return new MsCategory($id);
	}

	/**
	 * A performant shorthand to get only the full name of a category.
	 * This will only parse quickly the corresponding mediawiki message
	 * and return the full name, if found.
	 * @param $id String: Name of the category.
	 * @return String: Full Name of category
	 **/
	// this is a perfomant shorthand to get only the name of a category (id).
	public static function get_category_name($id) {
		$msg = wfMsg("ms-${id}-category");
		if(preg_match('/^\s*Name:\s*(.+)$/mi', $msg, $matching))
			return $matching[1];
		else	return $id.' (nameless)';
	}
}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
////////////////////      CATEGORY STACK       ////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

class MsCategoryStack {
	/// Configuration: The root category name
	static $root_category_name;

	// data
	private $array = array();

	/**
	 * @param $array_of_names Create stack from array of names
	 **/
	function __construct($array_of_names=Null) {
		// setup config:
		global $msConfiguration;
		self::$root_category_name = $msConfiguration['root-category-name'];

		if($array_of_names)
			$this->from_string_array($array_of_names);
		else
			$this->clean(); // will initialize the stack somewhat (add root)
	}

	/// @returns nothing.
	function from_string_array($array_of_names) {
		global $msConfiguration;
		if(!is_array($array_of_names))
			$this->array = array();
		if(empty($array_of_names) || $array_of_names[0] != self::$root_category_name)
			array_unshift($array_of_names, self::$root_category_name);

		foreach($array_of_names as $k => $v) {
			if(! MsCategoryFactory::exists($v))
				throw new MsException("MsCategoryStack: Category <i>$v</i> doesn't exist.",
					MsException::BAD_CONFIGURATION);
			$this->array[$k] = new MsCategory($v);
		}

		// clean category stack.
		$this->clean();
	}

	/**
	 * Create a part from a HTTP Query based on these data
	 *
	 **/
	function build_query($arg_name='ms_cat') {
		# http_build_query does not do the job
		$r = array();
		foreach($this->array as $v) {
			$r[] = $arg_name.'[]='.$v->id;
		}
		return implode('&', $r);
	}

	/**
	 * check stack for inheritance consistency, repair the stack internally -- 
	 * that is, set $this->array to the top to at least the errorous position
	 **/
	function clean() {
		// trivial case.
		if(empty($this->array)) {
			// add a root
			$this->array[] = MsCategoryFactory::get_root_category();
			return;
		}

		// check root
		if($this->array[0]->id != self::$root_category_name)
			// the root was bad.
			array_unshift($this->array[0], MsCategoryFactory::get_root_category());

		// walk down stack from the TOP until almost-root
		for($x = count($this->array)-1; $x >= 1; $x--) {
			// if the top element is not a child from the one below...
			if(! $this->array[$x-1]->has_sub_category($this->array[$x])) {
				// ... then kill it.
				array_pop($this->array);
			}
		}

		// stack is clean.
	}

	/// Push a category on the top. Yes, that changes the category.
	/// Will clean stack afterwards.
	/// @param $cat a MsCategory object or a string
	function push( $cat ) {
		if(is_string($cat))
			$cat = new MsCategory($cat);
		if(! ($cat instanceof MsCategory) )
			throw new MsException("MsCategoryStack::push: Error: $cat is no category");

		$this->array[] = $cat;
		$this->clean();
	}

	/// Will remove the topmost category from the stack. This will
	/// pop the stack until it reaches the root cat, then always
	/// return the root cat.
	/// @returns The popped cat.
	function pop() {
		if(count($this->array) > 1)
			return array_pop($this->array);
		else
			return $this->array[0];
	}

	/// @returns the size of the stack
	function count() {
		return count($this->array);
	}

	/// @returns the nth element of the stack (starting with 0, root).
	function get($x) {
		return $this->array[$x];
	}

	/// @returns get the category on the top.
	function get_top() {
		return $this->array[ count($this->array) - 1 ];
	}

	/// @returns all Categories (that is, the array).
	function get_all() {
		return $this->array;
	}

	/// @returns A simple string representation of this cat stack, for debugging
	function __toString() {
		$r = 'MsCategoryStack: (bottom) ';
		foreach($this->array as $cat) {
			$r .= "$cat "; # string context!
		}
		$r .= '(top)';
		return $r;
	}
}

///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
////////////////////      CATEGORY             ////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

class MsCategory extends MsMsgConfiguration {
	public $id;
	private $dummy; // if there doesn't exist such a category
	/// Never access this directly, use only get and set. Direct access
	/// only for friends (Ms prefix classes).
	#public $conf; # configuration array => use get_conf_array from external
	#public $conf_msg; # the parsed wfMsg that holds the configuration
	public $subs; # sub category object array

	/// For usage with get_databases, get_sub_categories
	const AS_OBJECTS = -1;
	const AS_STRINGS = -2;
	const AS_INFO_ARRAY = -3;

	// if name == false => ROOT category.
	public function __construct($id) {
		$this->id = $id;
		$this->conf['id'] = $id; // for better access. Used in userbox.
		$this->conf_msg = self::get_conf_msg_name($id);
		if(! $this->has_configuration() )
			$this->dummy = true;
		else
			$this->read_configuration();
	}

	public static function get_conf_msg_name($id) {
		return "ms-${id}-category";
	}

	// get the identifiers of the named databases OR
	// get the OBJECTS.
	/// @returns depending on $output_type. AS_INFO_ARRAY *will* contain sub 'name' entries.
	public function get_databases($output_type=self::AS_STRINGS) {
		$name_array_from_conf = $this->get_array($this->has_set('db')?'db':'dbs');
		$ret_array = array();
		foreach($name_array_from_conf as $db_entry) {
			if(is_array($db_entry)) {
				// we have the mutli scheme:
				// db*:
				// * name: thisistherelevantthing
				// * other info: blabla
				if(!isset($db_entry['id'])) {
					// name attribute missing! This is bad...
					// perhaps we should throw an exception.
					continue;
				}

				if($output_type == self::AS_INFO_ARRAY) {
					// well... how nice, that's exactly what we want ;-)
					$ret_array[] = $db_entry;
					continue;
				} else {
					$db_entry = $db_entry['id'];
					// important: go on (no continue).
				}
			} // if is_array $db_entry

			if(!is_string($db_entry)) {
				// this is somewhat strange, since we need strings.
				throw new MsException("MsCategory::get_databases: Really weird db entry in name_array ($name_array): $db_entry");
			}

			switch($output_type) {
				case self::AS_INFO_ARRAY:
					// hm... we want an info array, so create one:
					$ret_array[] = array('id' => $db_entry);
					break;
				case self::AS_STRINGS:
					// we already have what we want
					$ret_array[] = $db_entry;
					break;
				case self::AS_OBJECTS:
					$ret_array[] = new MsDatabase($db_entry);
					break;
				default:
					throw new MsException("Illegal output type: $output_type");
			}
		} // foreach databases

		// should be considered: Caching.
		return $ret_array;
	}

	/// Eigentlich nur fuer Testzwecke: die erste DB kriegen.
	public function get_one_database($as_object=self::AS_STRINGS) {
		$dbs = $this->get_databases($as_object);
		return empty($dbs) ? Null : $dbs[0];
	}

	public function has_databases() {
		$dbs = $this->get_array($this->has_set('db')?'db':'dbs');
		#var_dump($dbs,!empty($dbs));exit();
		return !empty($dbs);
	}

	/// @param $as_objects one of AS_STRINGS or AS_OBJECTS.
	public function get_sub_categories($as_objects=self::AS_STRINGS) {
		if($as_objects == self::AS_STRINGS)
			return $this->get_array('sub');
		else if(!isset($this->subs)) {
			// create subs
			$this->subs = array();
			foreach($this->get_sub_categories(self::AS_STRINGS) as $cat_id) {
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

	/// Is this category the root category?
	public function is_root() {
		global $msConfiguration;
		return $this->id == $msConfiguration['root-category-name'];
	}

	function get_box($area='presearch') {
		return wfMsg('ms-'.$this->id."-${area}-box");
	}

	/// A simple string representation (showing the id), for debugging
	function __toString() {
		return '[MsCategory:'.$this->id.']';
	}
}
