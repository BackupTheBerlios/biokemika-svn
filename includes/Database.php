<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsDatabase, MsDatabaseFactory, MsQuery, MsRecord
 *
 * This file contains the central MetaSearch model
 * components. All these classes just abstract simple
 * algorithms and primitive data structures, like arrays
 * where the real data is stored.
 * 
 * @see MsResult for the other central modelling part
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
 *
 **/

error_reporting(E_ALL);

/**
 * @class MsDatabaseFactory: This is just a kind of namespace
 * for some important global (static) functions concerning
 * Databases. Like the name says, this class implements the
 * "factory" concept.
 **/
abstract class MsDatabaseFactory {
	/**
	 * This scans the directory where all database drivers/implementions
	 * have to be stored and gives out an array of identifiers
	 * of these databases (just strings).
	 * That is, this function gives out all installed databases.
	 **/
	public static function get_all_installed_databases() {
		global $msConfiguration;
		$names = array();
		if($dh = opendir($msConfiguration['database_dir'])) {
			while($f = readdir($dh)) {
				if(preg_match('/(.+)\\.php$/', $f, $matches)) {
					$names[] = $matches[1];
				}
			}
			closedir($dh);
		}
		return $names;
	}

	/**
	 * This will create a database with the Identifier $name.
	 * Such a database has to be installed in the databases/ directory
	 * and has to be named correctly.
	 * @param name The Identifier of the database
	 * @return MsDatabase object
	 **/
	public static function create_database($name) {
		global $msConfiguration;
		if(require_once $msConfiguration['database_dir'].'/'.strtolower($name).'.php') {
			$classname = "MsDatabase_${name}";
			$database = new $classname($name);
			return $database;
		} else
			throw new MWException("Error: Metasearch database $name not found!");
	}
}

/**
 * @class MsDatabase: Represents an searchable Database
 * that can execute MsQuery objects. This class has to be
 * extended by all database implementions.
 **/
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

		$tmpl = $this->record_template;
		if($record->get_type()) {
			$tmpl .= '-'.$record->get_type();
		}
		return wfMsgData($tmpl, $record->data);
		/*
		$tmpl = wfMsg($this->record_template);
		if($tmpl == '&lt;'.$this->record_template.'&gt;') { # the output when there's no such page
			print $this->record_template." does not exist.<p>";
			$tmpl = wgMsg($msConfiguration['default-record-message']);
		}
		$ret = strtr($tmpl, $record->data);
		return $ret;
		*/
	}

	/// Just for debugging, when you want to print this database ;-)
	public function __toString() {
		return "Mediawiki MetaSearch Database [".$this->id."]";
	}
}

/**
 * @class MsQuery: This represents a query that can be made to
 * a MsDatabase object. This is really simple, stupid.
 **/
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
 * The MsRecord class is a generic output element that
 * contains data that can be formatted and printed out
 * to the wiki in a list.
 **/
class MsRecord {
	// the type of record:
	public $type = false;

	// and more things for merger, etc. process:
	public $database;
	public $relevance;

	// There don't seem to be such  "well-known" things for
	// every record, so keep it simple:
	public $data;

	function __construct($database, $type=false) {
		$this->database = $database;
		$this->set_type($type);
	}

	// nobody needs this constructor:
/*
	function __construct($title='No Title', $url='http://www.nowhere.com', $desc='[None given]') {
		$this->title = $title;
		$this->url = $url;
		$this->desc = $desc;
	}
*/

	function set_type($t) {
		$this->type = $t;
	}

	function get_type() {
		return $this->type; # default: false.
	}

	function set_data($key, $value) {
		//$this->data['{{{'.$key.'}}}'] = $value;
		$this->data[$key] = $value;
	}

	function get_data($key, $value) {
		//$key = '{{{'.$key.'}}}';
		if(isset($this->data[$key]))
			return $this->data[$key];
		else
			return false;
	}

	function set($k,$v) { $this->set_data($k,$v); }
	function get($k,$v) { $this->set_data($k,$v); }

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
