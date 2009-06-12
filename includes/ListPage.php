<?php
/**
 * Special:Metasearch/list implements a complete
 * nice debugging environment where you can get
 * information about the installed databases, etc.
 *
 *
 **/

error_reporting(E_ALL);

class MsListPage extends MsPage {
	public $list_of_cats;
	public $list_of_dbs = array();
	public $list_of_msgs = array();

	function execute( $par ) {
		global $wgOut;
		$wgOut->addWikiText(<<<WIKI
This is a page for debugging and developing the extension, as well as
for administrators that can check their configuration details by using
the following listings.
WIKI
		);

		$wgOut->addWikiText("== Metasearch Categorytree ==");
		$wgOut->addWikiText( $this->category_tree() );

		$wgOut->addWikiText("== Metasearch Databases ==");
		$wgOut->addWikiText( $this->list_databases() );

		$wgOut->addWikiText("== Metasearch Mediawiki pages ==");
		$wgOut->addHTML('<pre>');
		foreach(array_unique($this->list_of_msgs) as $msg) {
			$wgOut->addHTML("MediaWiki:$msg\n");
		}
		$wgOut->addHTML('</pre>');
	}

	function list_databases() {

		// Problem:
		//   No more simple method to get all installed databases, since
		//   we had to search for articles in the form MediaWiki:ms-*-database
		//   and return that.
		//   
		// Other issue:
		//   DRIVERS are much more important and simplier to get
		//   (include all /databases/* stuff)

		return;

		$r = wfMsg('ms-list-databases-pre');
		# At this point: Get all dbs from MsDatabaseFactory and
		# get installation details directly from Database objects!
		$r .= <<<WIKI

{| class="prettytable"
|- 
! Database Id
! used
! installed

WIKI;
		$used_dbs = array_unique($this->list_of_dbs);
		$installed_dbs = MsDatabaseFactory::get_all_installed_databases();

		foreach(array_unique($used_dbs+$installed_dbs) as $db) {
			$r .= "|---\n! $db\n| ";
			$r .= in_array($db, $used_dbs) ? 'Yes' : "'''No'''";
			$r .= "\n| ";
			$r .= in_array($db, $installed_dbs) ? 'Yes' : "'''No'''";
			$r .= "\n";

			if(wfMsgExists("Ms-$db-record"))
				$this->list_of_msgs[] = "ms-$db-record";
		}
		$r .= "|}\n";
		return $r;
	}

	function category_tree($leaf=false, $level=1) {
		if(!$leaf) $leaf = MsCategoryFactory::get_root_category();
		$indent = str_repeat('#', $level);
		$name = $leaf->get('name');
		if(!$name) $name = "''no name set!''";
		$id = $leaf->id;
		$r = "${indent} '''[[MediaWiki:ms-$id-category|$id]]''' ".($leaf->exists()?'':"'''DOES NOT EXIST'''")."\n";
		$r .= "${indent}* ''MSGS'': [[MediaWiki:ms-$id-record|record]], [[MediaWiki:ms-$id-category-input|input]], [[MediaWiki:ms-$id-presearch-box|presearch]], [[MediaWiki:ms-$id-postsearch-box|postsearch]]\n";
		foreach($leaf->get_conf_array() as $k=>$v) {
			$r .= "${indent}* ''$k'': $v\n";
		}
		#if(wfMsgExists($msg)) $this->list_of_msgs += $msg;
		$this->list_of_msgs = array_merge($this->list_of_msgs, $leaf->get_messages());
		$this->list_of_dbs = array_merge($this->list_of_dbs, $leaf->get_databases());

		foreach($leaf->get_sub_categories(true) as $subcat) {
			$r .= $this->category_tree($subcat, $level+1);
		}
		return $r;
	}
} 
