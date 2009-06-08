<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsChooserPage
 * 
 *   1. Choose Category
 *   2. Evventually choose database, if category contains
 *        db-choose: yes
 *      or anything like that (perhaps db scanning and checking
 *      if they behave good...)
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

class MsChooserPage extends MsPage {
	public $search_mask;

	// input data
	public $input_category;
	

	/**
	 * Read in and validate user input. This method will take
	 * the user input from the MediaWiki globale $wgRequest.
	 * @exception MWException when some user input was bad.
	 * @return Nothing interesting (if no exception)
	 **/
	function validate_user_data() {
		global $wgRequest, $msCategories;

		$this->input_category = strtolower(array_pop($wgRequest->getArray('ms_cat')));
		if(empty($this->input_category) || !MsCategoryFactory::exists($this->input_category)) {
			//if(!isset($msCategories[$this->controller->input_category])) {
			throw new MWException('<b>'.htmlspecialchars($this->input_category).'</b>: Invalid Category');
		}

		$cat = new MsCategory($this->input_category);
		$this->controller->input_databases = $cat->get_databases();

		return true;
	}

	function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;
		//$this->search_mask = new MsSearchMask();
		//$this->search_mask->fill_from_request();

		$template = new MsChooserTemplate();
		$stack = new MsCategoryStack( $wgRequest->getArray('ms_cat') );

		$template->set('action' , $this->getTitle()->escapeLocalURL() );
		$template->set('display_input_box', true);
		$template->set('assistant_box_msg', 'blasearch assistant msg');
		$template->set('assistant_msg', 'assistant bla msg');
		$template->setRef('stack', $stack);

		#var_dump($stack); exit(0);

		if($stack->get_top()->has_sub_categories()) {
			// display catchooser
			$wgOut->addTemplate($template);
		} else {
			// "end" category chosen.
			// Check what we've got for databases:
			$dbs = $stack->get_top()->get_databases(MsCategory::AS_OBJECTS);

			if(empty($dbs)) {
				throw MsException('Top cat has no databases attached',
					MsException::BAD_CONFIGURATION);
			}
			$subtitle = 'proxy'; # default: 'query'
			#if(is_a($dbs[0], 'MsQueryDatabaseDriver')) {
			#	$subtitle = 'query';
			#} else if(is_a($dbs[0]

			$wgOut->redirect(
				$this->special_page->get_sub_title('proxy')->getLocalURL(
					$stack->build_query()
				)
			);
			#exit(0);
		}
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
