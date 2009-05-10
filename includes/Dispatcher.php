<?php
/**
 * Mediawiki MetaSearch Extension: dispatcher.php
 *
 * This file contains all dispatcher classes:
 *  - MsDispatcher: The common abstract base class that
 *    defines the interface to a typical dispatcher
 *    implementation
 *  - MsSerialDispatcher: A simple implementation that
 *    executes each query after each other
 *  - MsParallelDispatcher: A (almost) real multithreading
 *    implemention that executes all queries simultanous.
 *
 **/

abstract class MsDispatcher {
	/**
	 * Creates an instance that fits best to the querie
	 * array.
	 **/
	static function get_instance($queries) {
		if(count($queries) < 2 or
		   !MsController::get_instance()->get_config('parallel-dispatcher-url')) {
			return new MsSerialDispatcher();
		} else {
			new MsParallelDispatcher(MsController::get_instance()->get_config('parallel-dispatcher-url'));
		}
	}

	/**
	 * The run function: Run all queries in the array
	 * and return after all queries have been executed.
	 * @return array(array(MsRecord,...),...)
	 **/
	abstract function run($array_of_database_queries);
}

/**
 * @class SerialDispatcher: The most simple scheduler
 * implemention: Simply runs every query after each other.
 **/
class MsSerialDispatcher extends MsDispatcher {
	function run($queries) {
		$result_array = array();
		foreach($queries as $query) {
			$result_array[] = $query->run();
		}
		#var_dump($queries); exit(0);
		return $result_array;
	}
}

/**
 * @class ParallelDispatcher: This scheduler opens one
 * new thread for each query and waits until all threads
 * have finished. Threading simulation is done via non
 * blocking HTTP subrequests to the own server. That's not
 * really perfomant, but since most time while perfoming the
 * queries is spent for connecting to the Database servers,
 * this solution takes as few time as possible.
 *
 **/
class MsParallelDispatcher extends MsDispatcher {
	private $fork_url;
	function __construct($fork_url) {
		$this->fork_url = $fork_url;
	}

	function fork() {
		# well, implement some logic here
	}

	function run($queries) {
		# not yet implemented.
	}
}