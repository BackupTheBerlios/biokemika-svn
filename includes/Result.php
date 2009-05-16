<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsResult, MsSparseResult
 * 
 * "Results" play a central part in the Metasearch extension,
 * since they are the objects we are interested in ;-)
 * The MsResult class represents the result from a MsQuery.
 * It is usually a set of records, in a special order.
 * 
 * There are actually three different states where a MsResult
 * can be:
 * 
 *   a) Empty record list: No records, bad search
 *   b) One special record entry: The query returned one
 *      matching ("success")
 *   c) Ordinary record list: The query returned many
 *      matching records
 * 
 * Since a search can principally give out thousands of records
 * that are probabily not needed, there's MsSparseResult.
 * This subclass generates MsRecord instances only when
 * they are actually requested by the program (via getters).
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

// old documentation, to be sorted:
/*
 * The MsResult class represents the Result from a MsQuery.
 * 
 * A result can either be:
 *  - A full set of records (in order)
 *  - A sparse set of records (to be generated while beeing called)
 *  - An empty set of records -- that is, no reecords
 *  - Only one exact matching, the exact result.
 * 
 * Before merging, the class only has to contain the number
 * of records, and abstract record data to generate these
 * records when generate_record(int $id) is called.
 */

/**
 * This is the ordinary simple MsResult implementation.
 * It is not much more than a wrapper around an array
 * of MsRecord objects.
 **/
class MsResult {
	/// The array that really contains MsRecords
	private $records = array();

	function __construct(array $record_list=Null) {
		$this->set_records($record_list);
	}

	public function set_records(array $record_list) {
		$this->records = $record_list;
	}

	/// whether this result hold records or not (bad search)
	public function is_empty() {
		return empty($this->records);
	}

	public function is_success() {
		return (count($this->records) == 1);
	}

	function get_record($id) {
		return $this->records[$id];
	}

	function get_records($start=null, $end=null) {
		if(!$start)
			return $this->records;
		else if(!$end)
			return array_splice($this->records, $start);
		else
			return array_splice($this->records, $start, $end);
	}

	function dump() {
		print '<pre>';
		print "This is $this, a MsResult, holding:\n";
		print_r($this->records);
		print '</pre>';
	}

	function __toString() {
		$r = '';
		foreach($this->records as $rec) {
			$r .= $rec->__toString();
			$r .= "\n";
		}
		return $r;
	}
}

/**
 * This implements a fairly more complex implemention of the
 * Result class: Sparse results. The MsRecord objects are
 * generated via calling generate_record(..) on the
 * database when they are requested via get_...().
 *
 * Therefore this class must hold an instance of the connected
 * database!
 **/
class MsSparseResult extends MsResult {
	/// The array that really contains MsRecords
	//public $records = array(); // should be private
	/// An internal data structure for holding the records
	/// that have not been generated yet. For example an
	/// array with special identifiers from the database
	public $abstract_records; // should be private
	/// Numer of Records that are hold by this result
	public $number_of_records = 0; // should be private, with getter

	/// this is an array that will hold booleans.
	private $record_already_generated;
	
	/// The database that belongs to this result. Only
	/// really needed for sparse Results.
	public $database;

	/// COMMON CONSTRUCTOR. A database can afterwards set
	/// $abstract_records and $number_of_records manually.
	function __construct(MsDatabase $source_database) {
		$this->set_sparse_number(0);
		$this->database = $source_database;
	}

	/// whether this result hold records or not (bad search)
	public function is_empty() {
		return $numer_of_records == 0;
	}

	public function is_success() {
		return $this->get_record(0)->get_type() == MsResult::$type_success;
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

	/// get a slice array of records, counting from 0.
	/// Use as ->get_records() to get all records, rendered.
	function get_records($start=0, $end=null) {
		if(!$end)   $end = $this->number_or_records-1;
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
