<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsController
 * 
 * This is the Controller class of the MetaSearch extension,
 * it is some kind of "central part" in the search process.
 * The MetaSearch object model implements the (at least a
 * bit ;-) ) a model-view-controller pattern, where
 * MsPage (especially MsQueryPage with MsSearchMask) plays
 * the part of the view, MsQuery, MsDatabase and MsResult
 * objects play the part of the models and this, MsController,
 * is the ... controller! ;-)
 * 
 * Well, this is not a really clean implemention of that
 * approach, since the metasearch extension is somewhat
 * quick and dirty in some circumstances. Actually, the job
 * of Controller.php is:
 *  - implementing some global functions that are missing
 *    to PHP or Mediawiki, like some wfMsg extensions
 *  - performing the search, by calling all the neccessary
 *    functions.
 * 
 * For the later part, this is an abstract how such a search
 * works:
 *
 *  1. MsController->execute() is called
 *  2. Neccessary parts of the category tree are built,
 *     via MsCategory
 *  3. The databases to search in are collected and set up
 *  4. Queries for all databases are constructed
 *  5. A Dispatcher is initialized and called
 *  6. The Dispatcher executes the Queries for the Databases
 *  7. Each Database executes the queries and returns a
 *     Result
 *  8. The controller takes all these results and merge them
 *     together to one big result
 *  9. execute() returns the big result.
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
	$tmpl = wfMsg($name); $new = array();
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

/********************* CONTROLLER CLASS ****************************/

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

	/// The view. This should be set very easy in the program,
	/// so that should be always a valid object.
	public $view;

	/// User field: The Query keywords
	public $input_keywords;
	public $input_category;
	public $input_databases;
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
		#var_dump($results); exit(0);
		$master_result = $this->merge($results);
		return $master_result;
	}

	/**
	 * create_queries: Create a list of MsQuery
	 * objects that can be executed to fullfill the Request.
	 * @returns array(MsQuery,[...])
	 **/
	function create_queries() {
		global $msDatabases, $msCategories;
		$queries = array();

		foreach(//$msCategories[$this->input_category]
			$this->input_databases as $db) {
			if(is_array($db)) $db = $db[0]; # get only name.
			$query = new MsQuery();
			$query->keyword = $this->input_keywords;
			$query->database = MsDatabaseFactory::create_database($db);
			$queries[] = $query;
		}
		return $queries;
	}


	/**
	 * Merge all MsResult entries together to one new big
	 * MsResult.
	 **/
	function merge(array $results) {
		global $msConfiguration;

		//var_dump($results); exit();
		$out = array(); # the MsRecord list for (almost) output
		$out = $results[0]->get_records(0, 20);
		for(;0!=0;) {
		//foreach($results as $result) {
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
		$out_result = new MsResult($out);
		return $out_result;
	}
} // Class
