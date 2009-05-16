<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsDispatcher
 * 
 * The Dispatcher is the part that takes a set of queries
 * (MsQuery objects) and executes them. Since a search
 * should not take ages, and since a meta search engine
 * usually searches at many databases, this is a job that
 * should run simultanous on multiple databases, whenever
 * possible. Therefore MsDispatcher is only an abstract 
 * class, for wich there are multiple implementions with
 * various approaches to speed up a search.
 * 
 * This file contains all (simple) dispatcher classes:
 * MsDispatcher, MsSerialDispatcher and MsParallelDispatcher.
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
 * @class MsDispatcher: The common abstract base class
 * that defines the interface to a typical dispatcher
 * implementation. Use MsDispatcher::get_instance($your_queries)
 * to get the best matching dispatcher object for your
 * metasearch installation.
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