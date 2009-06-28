<?php

error_reporting(E_ALL);

if(!class_exists('MsDatabase')) {
	#return; # was soll das auch?

class MsDatabase extends MsMsgConfiguration {
	public $id;
	public $driver = Null;

	function __construct( $id ) {
		$this->id = $id;
		$this->conf_msg = $this->get_conf_msg_name($id);

		if(! self::is_installed($id))
			throw new MsException("Database $id is not installed in this setup!",
				MsException::BAD_INPUT);

		if($this->has_configuration())
			$this->read_configuration();
		else
			$this->load_basic_configuration();
		$this->load_driver();
	}

	private function load_basic_configuration() {
		// Try some auto configuration things
		$this->conf['driver'] = $this->id;
	}

	private function load_driver() {
		if(! $this->has_set('driver') ) {
			$this->load_basic_configuration();
		}

		$this->driver = self::create_driver( $this->conf['driver'], $this );
	}

	/// Trivial, but like MsCategory::build_query()
	public function build_query($name='ms-db') {
		return $name.'='.urlencode($this->id);
	}

	public static function get_driver_filename($id) {
		global $msConfiguration;
		return $msConfiguration['database_dir'].'/'.strtolower($id).'.php';
	}

	public static function get_conf_msg_name($id) {
		return "ms-$id-database";
	}

	public static function is_installed($id) {
		return 	file_exists(self::get_driver_filename($id)) || 
			wfMsgExists(self::get_conf_msg_name($id));
	}

	/// Checks whether all databases in $database array are 'querydriver'
	/// databases. Such databases can be run together, 'proxydriver'
	/// databases cannot do that.
	/// @param $databases Database array
	/// @returns true if can run together (all querydriver), false if not
	public static function are_query_databases(array $databases) {
		global $msDatabaseDriver;
		foreach($databases as $db) {
			if(! $db->is_driver_type('querydriver'))
				return false;
		}
		return true;
	}

	/// @param $name Typically a name in $msDatabaseDriver
	/// @returns true if this is an instance / child class instance of that driver
	public function is_driver_type($name) {
		global $msDatabaseDriver;
		$supposed_driver_conf = self::get_driver_conf($name);

		return ($this->driver instanceof $supposed_driver_conf['class']);
	}

	/// Returns the appropriate driver configuration array (subarray
	/// of $msDatabaseDriver and trys to load that driver otherwise.
	/// Will also check integrity of driver configuration (class field)
	public static function get_driver_conf($name) {
		global $msConfiguration, $msDatabaseDriver;
		if(!isset($msDatabaseDriver[$name])) {
			// checked whether exists already via is_installed()!

			if(! file_exists(self::get_driver_filename($name) )) {
				throw new MsException("Error: Metasearch database driver file for $name not found!",
					MsException::BAD_CONFIGURATION);
			}

			include_once self::get_driver_filename($name);

			// the file should have added it's entry to $msDatabaseDriver
			if(! isset($msDatabaseDriver[$name])) {
				throw new MsException("Missing meta data for metasearch database driver $name!",
					MsException::BAD_DRIVER);
			}

			// check for some entries
			if(! isset($msDatabaseDriver[$name]['class']) ) {
				throw new MsException("Missing class name in metaseach database driver meta data for $name!",
					MsException::BAD_DRIVER);
			}
		}

		return $msDatabaseDriver[$name];
	}

	public static function create_driver($name, $database) {
		// create thingy... bla.
		global $msConfiguration, $msDatabaseDriver;
		// standarize $name:
		$name = strtolower($name);
		$classname = Null;

		// lookup in driver array
		$driver_conf = self::get_driver_conf($name);

		$classname = $driver_conf['class'];
		#$classname = "MsDriver_${name}";
		$driver = new $classname($database);
		return $driver;
	}


	/// Just for debugging, when you want to print this database ;-)
	public function __toString() {
		return "Mediawiki MetaSearch Database [".$this->id."]";
	}
} // class MsDatabase

/**
 * A driver for one database. This is a kind of "controller"
 * that directly communicates to the fronted!
 * database object = configuration object.
 **/
abstract class MsDriver {
	public $database;

	public function __construct($database) {
		$this->database = $database;
		$this->init();
	}

	/// overwrite this
	abstract function init();

	public function __toString() {
		return "MediaWiki MetaSearch Driver [".$this->id."]";
	}
}

} // if !defined class MsDatabase
