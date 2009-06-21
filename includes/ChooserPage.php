<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsChooserPage
 * 
 *   1. Choose Category
 *   2. Choose Database, if category databases don't match
 *      MsDatabase::are_query_databases() criteria
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
	// input data
	public $cat_stack;
	

	function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser;

		try {
			$this->cat_stack = new MsCategoryStack( $wgRequest->getArray('ms-cat') );
		} catch(MsException $e) {
			$wgOut->add("Stack: {$this->cat_stack}.<br>Your params are bad: $e");
		}

		if($this->cat_stack->get_top()->has_sub_categories()) {
			// display category chooser
			$this->display_cat_chooser();
		} else {
			// no more categories to choose of.
			// Check what we've got for databases:
			$dbs = $this->cat_stack->get_top()->get_databases(MsCategory::AS_OBJECTS);

			if(empty($dbs)) {
				throw new MsException("Top cat of {$this->cat_stack} has no databases attached",
					MsException::BAD_CONFIGURATION);
			}

			if(count($dbs) > 1) {
				// the cat has more than one database
				if( MsDatabase::are_query_databases($dbs) ) {
					// but they are all Query Databases, so
					// let MsQueryPage do it's job
					$this->redirect('query');
				} else {
					// Since we cannot merge different databases on
					// one page, display a database chooser.
					$this->display_db_chooser();
				}
			} else {
				// Only one database in this cat.
				// Relaxed situation :-)
				$db = $dbs[0];

				// Quick & Dirty, to be improved:
				$subtitle = $db->is_driver_type('proxydriver') ? 'proxy' : 'query';
				$this->redirect( $subtitle );
			}
		} // if ...->has_sub_categories()
	} // function execute

	function redirect( $subpage ) {
		global $wgOut;

		$wgOut->redirect(
			$this->special_page->get_sub_title($subpage)->getLocalUrl(
				$this->cat_stack->build_query('ms-cat')
			)
		);
	}

	/// only to be called from execute()
	private function display_cat_chooser() {
		global $wgOut;

		$template = new MsChooserTemplate();

		$template->setRef('view', $this); // for callbacks...
		$template->setRef('title', $this->getTitle() );
		$template->setRef('stack', $this->cat_stack);

		$wgOut->addTemplate($template);
	}

	private function display_db_chooser() {
		global $wgOut;

		// ...
		$wgOut->addHTML('Display some kind of DB chooser.');
	}

	function link_title_for(MsCategory $cat) {
		// should be implemented with some wfMsg...
		return "Select $cat...";
	}

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
