<?php
/**
 * This MsPage will be seen most of the time: It's the central
 * page that displays the query form and everything around that.
 *
 *
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
		$records = $this->controller->execute();

		#$this->dump($records);

		if(empty($records))
			$this->search_mask->assistant_status = 'sad';

		$this->search_mask->status = MsSearchMask::$status_post;
		$this->search_mask->print_out();

		if(!empty($records)) {
			$wgOut->addWikiText("==Suchergebnisse==");
			#var_dump($records); exit();
			foreach($records as $rec) {
				$wgOut->addWikiText($rec->__toString());
			}
		} else {
			$msg = 'Ms-bad-'.$this->search_mask->get_top_cat()->id.'-search';
			if(wfMsgExists($msg))
				$wgOut->addWikiText( wfMsg($msg) );
			else
				$wgOut->addWikiText( wfMsg('Ms-bad-search') );
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
