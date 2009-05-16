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
	public $controller;
	public $search_mask;

	/**
	 * Read in and validate user input. This method will take
	 * the user input from the MediaWiki globale $wgRequest.
	 * @exception MWException when some user input was bad.
	 * @return Nothing interesting (if no exception)
	 **/
	function validate_user_data() {
		global $wgRequest, $msCategories;

		$this->controller->input_keywords = $wgRequest->getText('ms_query');
		if(empty($this->controller->input_keywords)) {
			throw new MWException('Input text may not be empty.');
		}

		$this->controller->input_category = strtolower(array_pop($wgRequest->getArray('ms_cat')));
		if(empty($this->controller->input_category) || !MsCategoryFactory::exists($this->controller->input_category)) {
			//if(!isset($msCategories[$this->controller->input_category])) {
			throw new MWException('<b>'.htmlspecialchars($this->controller->input_category).'</b>: Invalid Category');
		}

		$cat = new MsCategory($this->controller->input_category);
		$this->controller->input_databases = $cat->get_databases();

		return true;
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
