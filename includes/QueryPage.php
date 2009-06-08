<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsQueryPage
 * 
 * The MsQueryPage is directly called by the SpecialClass when
 * the extension is called via Special:Metasearch by the user.
 * It manages user input (MsSearchMask) and Result output.
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

class MsQueryPage extends MsPage {
	#public $controller;
	#public $search_mask;

	/// User input data
	public $ms_query;
	public $ms_category_stack;
	public $ms_input_databases;


	/**
	 * Read in and validate user input. This method will take
	 * the user input from the MediaWiki globale $wgRequest.
	 * @exception MsException when some user input was bad.
	 * @return Nothing interesting (if no exception)
	 **/
	function validate_user_data() {
		global $wgRequest, $msCategories;

		$this->ms_query = $wgRequest->getText('ms_query');
		if(empty($this->ms_query)) {
			throw new MsException('Please enter an input text.',
				MsException::BAD_INPUT);
		}

		// try{ } catch(MsException e) { }-Konstrukt um das hier:
		$this->ms_category_stack = new MsCategoryStack( $wgRequest->getArray('ms_cat') );

		if( $this->ms_category_stack->get_top()->is_root() ) {
			throw new MsException('Please select a category.',
				MsException::BAD_INPUT);
		} else if( ! $this->ms_category_stack->get_top()->has_databases() ) {
			throw new MsException('Please select a category that has databases!',
				MsException::BAD_INPUT);
		}

		return true;
	}

	/**
	 * The really "controller like" function that will
	 * execute the query according to the ms_ class variables.
	 **/
	public function execute_query() {
		/// 1. Get top category of the stack
		$cat = $this->ms_category_stack->get_top();
		/// 2. Create databases
		$dbs = $this->ms_category_stack->get_top()->get_databases(MsCategory::AS_OBJECTS);
		/// 4. Create queries
		$queries = MsQuery::create_for_databases($this, $dbs);
		#var_dump($queries); exit(0);
		/// 5. Create a good dispatcher
		$dispatcher = MsDispatcher::get_instance($queries);
		/// 6. Run dispatcher (will run queries and create results)
		$results = $dispatcher->run($queries);
		#var_dump($results); exit(0);
		/// 7. Merge results
		$master_result = $this->merge($results);
		return $master_result;
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

	function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;
		$this->search_mask = new MsSearchMask();
		$this->search_mask->fill_from_request();

		if(! $wgRequest->getBool('ms_search')) {
			# display search mask only
			$this->search_mask->print_out();
			$this->print_end_page();
			return;
		}

		
		# do some validation
		try {
			$this->validate_user_data();
		} catch(MWException $e) {
			$wgOut->addHTML("Fehler bei Eingabe: ".$e->getLogMessage());
			$this->search_mask->status = MsSearchMask::$status_error;
			$this->search_mask->print_out();
			$this->print_end_page();
			return;
		}

		# Perform search...
		$result = $this->controller->execute();

		#$this->dump($result->get_records());

		if($result->is_empty())
			$this->search_mask->assistant_status = 'sad';
		else if($result->is_success())
			$this->search_mask->assistant_status = 'success';

		$this->search_mask->status = MsSearchMask::$status_post;
		$this->search_mask->print_out();

		if( $result->is_success() ) {
			$wgOut->addWikiText("==Success!==");
			$wgOut->addWikiText( $result->__toString() );
		} else if(! $result->is_empty() ) {
			$wgOut->addWikiText("==Suchergebnisse==");
			#var_dump($records); exit();
			$wgOut->addWikiText( $result->__toString() );
		} else {
			$wgOut->addWikiText(
				wfMsgFallback(
					'Ms-bad-'.$this->search_mask->get_top_cat()->id.'-search',
					'Ms-bad-search'
				)
			);
		}

		# we're done.
		$this->print_end_page();
		return;
	} // execute

	function print_end_page() {
		global $wgUser, $wgOut;
		# display an infobox for every user
		$wgOut->addWikiText( wfMsg('Ms-end-box') );
		# display a neat infobox thingy for registered users
		if(! $wgUser->isAnon() ) {
			$wgOut->addWikiText( wfMsgData('Ms-user-box', $this->search_mask->get_top_cat()->conf) );
		}
	}
} // class MsQueryPage 
