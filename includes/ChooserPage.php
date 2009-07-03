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

		$chosen_db = $wgRequest->getText('ms-db', false);
		if($chosen_db) {
			// there's an "ms-db" argument present. So redirect directly
			// to that db.
			$db = new MsDatabase($chosen_db);

			$subtitle = $db->is_driver_type('proxydriver') ? 'proxy' : 'query';
			$wgOut->redirect(
				$this->special_page->get_sub_title($subtitle)->getLocalUrl(
					$this->cat_stack->build_query('ms-cat')
					.'&'.
					$db->build_query('ms-db')
				)
			);
			return;
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
					$template = new MsChooserTemplate();
					$template->setRef('dbs', $dbs);
					$this->display_cat_chooser($template);
				}
			} else {
				// Only one database in this cat.
				// Relaxed situation :-)
				$db = $dbs[0];
				$this->redirect_to_db($db);

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

	/// only to be called from execute()...
	/// @param $template If you've prepared a template, you can give it to me ;-)
	private function display_cat_chooser($template=Null) {
		global $wgOut;

		if(!$template)
			$template = new MsChooserTemplate();

		$template->setRef('view', $this); // for callbacks...
		$template->setRef('title', $this->getTitle() );
		$template->setRef('stack', $this->cat_stack);

		$wgOut->addTemplate($template);
	}

	// no more needed.
	function print_end_page() {
		global $wgUser, $wgOut;
		# display a neat infobox thingy for registered users
		if(! $wgUser->isAnon() ) {
			$wgOut->addWikiText( wfMsgData('Ms-user-box', $this->search_mask->get_top_cat()->conf) );
		}
	}
} // class MsQueryPage 
