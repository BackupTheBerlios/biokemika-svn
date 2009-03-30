<?php
/**
 * Database and QUERY
 *
 * @class MsDatabase: Represents an searchable Database
 * that can execute MsQuery objects. This is
 * a somewhat abstract class that needs to be implemented
 * by various search approaches.
 *
 **/

class MsDatabaseFactory {
	public static function get_all_installed_database_names() {
	}
	public static function get_all_configured_database_names() {
	}
	public static function create_databases($array_of_names) {
	}
	public static function create_database($name) {
		if(require_once 'databases/'.strtolower($name).'.php') {
			$classname = "MsDatabase_${name}";
			$database = new $classname($name);
			return $database;
		} else
			throw new MWException("Error: Metasearch database $name not found!");
	}
}

abstract class MsDatabase {
	# the short hand id, for usage e.g. in configuration
	public static $id;
	public function __construct($id) {
		$this->id = $id;
		$this->record_template = 'Ms-'.$id.'-record'; # default.
		$this->init();
	}

	/**
	 * Initialization of database -- overwrite this with your
	 * constructor things.
	 **/
	public function init() {}

	# System message (template) for record output
	public static $record_template;

	/// Execute an (outgoing) query to the database
	abstract public function execute(MsQuery $query);

	/// Render a record in a sparse result. This is not relevant for
	/// Databases that won't return a sparse result.
	public function generate_record($id, MsResult $result) {
	}

	/**
	 * The standarized way to print out redcords. This should
	 * RETURN the string to print out.
	 */
	public function print_record(MsRecord $record) {
		global $msConfiguration;
		$tmpl = wfMsg($this->record_template);
			#call_user_func_array('wfMsg',#array_merge(array(),$record->template_data)
			# NEIN, ersetzte Daten SELBST im {{{key}}} syntax.
		if($tmpl == '&lt;'.$this->record_template.'&gt;') { # the output when there's no such page
			print $this->record_template." does not exist.<p>";
			$tmpl = wgMsg($msConfiguration['default-record-message']); #call_user_func_array('wfMsg',
				#array_merge(array($msConfiguration['default-record-message']),$record->template_data)
		}
		$ret = strtr($tmpl, $record->data);
		return $ret;
	}

	/// Just for debugging, when you want to print this database ;-)
	public function __toString() {
		return "Mediawiki MetaSearch Database [".$this->id."]";
	}
}

class MsQuery {
	public $keyword;
	public $database;

	/**
	 * Run that query. That should return a MsResult.
	 **/
	function run() {
		return $this->database->execute($this);
	}
}

/**
 * The MsResult class represents the Result from a MsQuery.
 * Before merging, the class only has to contain the number
 * of records, and abstract record data to generate these
 * records when generate_record(int $id) is called.
 **/
class MsResult {
	/// There are two types of results: Sparse results that
	/// don't contain all records rendered and complete results
	/// where all records are fully rendered.
	public $is_sparse = true;

	/// The array that really contains MsRecords
	public $records = array(); // should be private
	/// An internal data structure for holding the records
	/// that have not been generated yet. For example an
	/// array with special identifiers from the database
	public $abstract_records; // should be private
	/// Numer of Records that are hold by this result
	public $number_of_records = 0; // should be private, with getter

	/// this is an array that will hold booleans.
	private $record_already_generated;
	
	/// The database that belongs to this result
	public $database;

	/// COMMON CONSTRUCTOR. A database can afterwards set
	/// $abstract_records and $number_of_records manually.
	function __construct(MsDatabase $source_database, array $record_list=Null) {
		if($record_list) {
			$this->is_sparse = false;
			$this->number_of_records = count($record_list);
			$this->records = $record_list;

			// just to make sure -- perhaps no more needed
			foreach($this->records as $record) {
				$record->database = $source_database;
			}
		} else {
			$this->is_sparse = true;
			$this->set_sparse_number(0);
		}
		$this->database = $source_database;
	}

	/// How many (sparse) records this result contains
	public function set_sparse_number($x) {
		if($x <= 0) {
			$this->number_of_records = $x;
			$this->record_already_generated = array();
		} else {
			$this->number_of_records = $x;
			$this->record_already_generated = array_fill(0, $x, false);
		}
	}

	/// Perhaps no more needed.
	function add($title, $url, $desc='[None given]') {
		$this->records[] = new MsRecord($title,$url,$desc);
	}

	/// This should be used to get ANY record.
	function get_record($id) {
		if(! $this->record_already_generated[$id]) {
			$this->records[$id] = $this->database->generate_record($id, $this);
			if(!isset($this->records[$id])) {
				throw new MWException("Database ".$this->database." did not generate record $id!");
			}
			$this->record_already_generated[$id] = true;
		}
		return $this->records[$id];
	}

	/// get a slice array of records
	function get_records($start, $end) {
		if($this->is_sparse) {
			$back = array();
			for($x=$start; $x<$end && $x < $this->number_of_records-1; $x++) {
				$back[] = $this->get_record($x);
			}
			return $back;
		} else {
			return array_splice($this->records, $start, $end);
		}
	}
}

/**
 * The MsRecord class is a generic output element that
 * contains data that can be formatted and printed out
 * to the wiki in a list.
 **/
class MsRecord {
	/// The title/heading of the record
	public $title;
	/// The URL the user can go to
	public $url;
	/// A short description
	public $desc;

	// and more things for merger, etc. process:
	public $database;
	public $relevance;

	// There don't seem to be such  "well-known" things for
	// every record, so keep it simple:
	public $data;

	function __construct($database) {
		$this->database = $database;
	}

	// nobody needs this constructor:
/*
	function __construct($title='No Title', $url='http://www.nowhere.com', $desc='[None given]') {
		$this->title = $title;
		$this->url = $url;
		$this->desc = $desc;
	}
*/

	function set_data($key, $value) {
		$this->data['{{{'.$key.'}}}'] = $value;
	}

	function get_data($key, $value) {
		//if(isset($this->data[$key]
	}

	// e.g. for usort()
	static function cmp_relevance(MsRecord $rec_a, MsRecord $rec_b) {
		if($rec_a->relevance == $rec_b->relevance) return 0;
		return ($rec_a->relevance > $rec_b->relevance) ? -1 : 1;
	}

	// delegate to db to print out this record
	public function __toString() {
		if(!isset($this->database)) {
			var_dump($this);
			throw MWException('This record is not attached to any database!');
		}
		return $this->database->print_record($this);
	}
}