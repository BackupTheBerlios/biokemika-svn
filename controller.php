<?php
/**
 * MediaWiki MetaSearch Extension: controller.php
 * 
 * THIS IS THE CONTROLLER
 * 
 * The MsUserQuery class models a query to the meta
 * search engine made throught Mediawiki. This class does:
 *  - Reading in and validation of GET arguments
 *  - Looks up the databases where we can search and
 *    create a list of databases where we actually will
 *    search for this request
 *  - Creates queries for these databases
 *
 * The selection of the databases is one central point in
 * the MetaSearch features.
 *
 **/

error_reporting(E_ALL);

class MsController {
	/// This class is designed after the singleton pattern
	private static $instance;
	private function __construct() {
		# this does nothing, so far...
	}
	/// Use this function to get an instance.
	public static function get_instance() {
		if(!isset(self::$instance))
			self::$instance = new MsController();
		return self::$instance;
	}

	/// User field: The Query keywords
	public $input_keywords;
	public $input_category;
	/// wie viel Hits, vorgegeben, oder halt von Benutzer
	public $input_hits;

	/**
	 * get_databases: Get List of Databases where to search in
	 * for this user query.
	 * @returns array(MsDataBase,[...])
	 **/
	function get_databases() {
		// bevor was schlaues implementiert wird:
		return MsDatabase::get_all_databases();
	}

	function get_config($key) {
		# TODO.
		return false;
	}

	public function execute() {
		$queries = $this->create_queries();
		#var_dump($queries); exit(0);

		$dispatcher = MsDispatcher::get_instance($queries);
		$results = $dispatcher->run($queries);
		$this->check_results($results);

		#var_dump($results); exit(0);
		$records = $this->merge($results);
		return $records;
	}

	/**
	 * create_queries: Create a list of MsQuery
	 * objects that can be executed to fullfill the Request.
	 * @returns array(MsQuery,[...])
	 **/
	function create_queries() {
		global $msDatabases, $msCategories;
		$queries = array();

		foreach($msCategories[$this->input_category] as $db) {
			if(is_array($db)) $db = $db[0]; # get only name.
			$query = new MsQuery();
			$query->keyword = $this->input_keywords;
			$query->database = MsDatabaseFactory::create_database($db);
			$queries[] = $query;
		}
		return $queries;
	}

	/**
	 * Checks the results for integrity. Result array passed
	 * by reference, so call
	 *   $your_controller->check_results($my_result_array);
	 * and be happy.
	 **/
	function check_results(&$results) {
		foreach($results as $result) {
			foreach($result->records as $x => $rec) {
				# yes, this is stupid:
				if(!isset($rec->relevance))
					$rec->relevance = $x+1; # $x start from 0
				# okay, this is not so stupid:
				if(!isset($rec->database))
					$rec->database = $result->database;
			}
		}
	}

	/**
	 * Merge all the results together to one record list.
	 *
	 **/
	function merge($results) {
		global $msDatabases, $msCategories, $msCategoryHits;

		//var_dump($results); exit();
		$out = array(); # the MsRecord list for output
		foreach($results as $result) {
			# handel one result from one database.
			if(count($msCategories[$this->input_category]) == 1) {
				# its a simple category: only one database.
				$out = $result->get_records(0, $msCategoryHits[$this->input_category]);
			} else {
				# multi database category.
				$cur_relevance = 0;
				$cur_priority = 0;
				# search the corresponding database in current cat
				foreach($msCategories[$this->input_category] as $db) {
					if($db[0] == $result->database->id) {
						$cur_relevance = $db[2];
						$cur_priority = $db[1];
						break;
					}
				}
				# look if we've found the database
				if(!$cur_relevance)
					throw new MWException('Configuration Integrity errnous.');

				# now compute how much records this result may
				# contribute:
				$hits = round( $msCategoryHits[$this->input_category]
					* $cur_priority );
				# and get these records
				$this_out = $result->get_records(0, $hits);

				# now calculate the new relevance of each record
				# NOTE: This is stupid.
				foreach($this_out as $record) {
					$record->relevance = $cur_relevance * $record->relevance;
				}

				# and sort the records after their new relevance
				# since we don't know if they were sorted before
				# (do we?) -- YES WE'LL ASSUME EXACTLY *THAT*
				//usort($this_out, array('MsRecord','cmp_relevance'));

				# and add our {$hits} best records:
				#print "BEFORE MERGING RECORDS:"; var_dump($result->records);
				$out = array_merge($out, $this_out);
					//array_slice($result->records, 0, $hits));
			} // multi db cat
		} // foreach

		# no we've got exatly ${msCategoryHits[$this->input_category]}
		# hits in our $out array, but not neccessarily sorted. So sort'em:
		usort($out, array('MsRecord', 'cmp_relevance'));

		# and as last but not least: Give every record a number, for
		# output numbering. Just as a quick and dirty solution.
		$x = 1;
		foreach($out as $rec) {
			$rec->set_data('number', $x++);
		}

		# we're done.
		return $out;
	}
} // Class