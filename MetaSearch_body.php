<?php
/**
 * Mediawiki MetaSearch Extension: special_page.body.php
 *
 * This is the "main body" function that is called by
 * Mediawiki when the Metasearch special page is loaded.
 * ms_SpecialPage is the controller class that will master
 * the call.
 **/
error_reporting(E_ALL);

$ms_dir = dirname(__FILE__) . '/';


function var_dump_ret($mixed = null) {
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();
  return $content;
}

function wfMsgExists($name) {
	return wfMsg($name) != "&lt;$name&gt;";
}


class MsSpecialPage extends SpecialPage {
	static $pages = array(
		#'choose' => 'MsChooserPage', # Choosing the category (query preparation)
		'query' => 'MsQueryPage',    # The core part: Quering
		'list' => 'MsListPage',      # (debugging:) List cats/dbs/etc.
	);

	function __construct() {
		parent::__construct( 'Metasearch' );
		wfLoadExtensionMessages('Metasearch');
		$this->controller = MsController::get_instance();
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgScriptPath;
		$this->setHeaders();

		$wgOut->addLink( array(
			'rel' => 'stylesheet',
			'href' => "$wgScriptPath/extensions/metasearch/MetaSearch.css",
			'type' => 'text/css'
		) );
		//$wgOut->addScriptFile( "$wgScriptPath/extensions/metasearch/mediawiki/search.js" );

		// code borrowed from SecureSearch extension
		$paramString = strval( $par );
		if ( $paramString === '' ) {
			$paramString = 'query';
		}
		$params = explode( '/', $paramString );
		$pageName = array_shift( $params );
		$page = $this->get_sub_page( $pageName );
		if ( !$page ) {
			$wgOut->addWikiMsg( 'ms-invalid-page', $pageName );
			return;
		}

		$page->execute( $par );
	} // execute

	function get_sub_page($name) {
		if(!isset(self::$pages[$name]))
			return false;
		$class = self::$pages[$name];
		return new $class($this);
	}
} // class MsSpecialPage

abstract class MsPage {
	public $controller;
	public $special_page;

	function __construct($special_page) {
		$this->controller = MsController::get_instance();
		$this->special_page = $special_page;
	}

	abstract function execute($par);

	# usage in execute: return $this->dump(...);
	static function dump( $data ) {
		global $wgOut;
		$wgOut->addHTML( "<pre>".var_dump_ret($data)."</pre>" );
		return Null;
	}
} // class MsPage


class MsQueryPage extends MsPage {
	public $controller;

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

	# $query: string, $cats: MsCategory stack, $assistant_status: will be param to Ms-assistant
	function print_search_mask($query='', $cats=false, $postsearch=false, $assistant_status='good') {
		global $wgOut, $msConfiguration;

		// make sure root is the very first category.
		#if(!is_array($cats)) $cats = array();
		#if(empty($cats) || $cats[0] != $msConfiguration['root-category-name'])
		#	array_unshift($cats, $msConfiguration['root-category-name']);

		# get the topmost category of the stack
		$current_cat = $cats[count($cats)-1];

		$prepost = $postsearch?'postsearch':'presearch';
		$action = $this->special_page->getTitle()->escapeLocalURL(); // <form> action.

		$wgOut->addHTML('<div class="ms-formbox ms-'.$prepost.'">');
		$wgOut->addHTML('<form method="get" action="'.$action.'" name="ms">');
		if($current_cat->has_input_text()) {
			$wgOut->addHTML('<div class="ms-right">');
			$wgOut->addHTML('<div class="ms-assistant-box">');
		} else {
			$wgOut->addHTML('<div class="ms-assistant-box">');
		}

		// Contents of assistant box = most sub category
		$box = $current_cat->get_box($prepost);
		$wgOut->addWikiText($box);

		$wgOut->addHTML('</div><!--assistant box-->');
		if(! $current_cat->has_input_text()) {
			$wgOut->addHTML('<div class="ms-right">');
		}
		$wgOut->addHTML('<div class="mc-bc">');
		$wgOut->addWikiText(wfMsg('ms-assistant', $assistant_status));
		$wgOut->addHTML('</div></div><!--ms-right-->');

		$wgOut->addHTML('<div class="ms-left">');
		if($current_cat->has_input_text()) {
			$wgOut->addHTML('<div class="ms-inputtext">');
			$current_cat->add_input_text($query);
			$wgOut->addHTML('</div>');
		}
		$wgOut->addHTML('<div class="ms-class-selector">');

		$str = ''; // out string buffer.
		for($x=0;$x<count($cats);$x++) {
			$sub_cats = $cats[$x]->get_sub_categories();
			#$sub_cats = $this->get_sub_categories($x==0 ? false : $cats[$x]);
			if(empty($sub_cats)) {
				// Endkategorie erreicht!
				break;
			}
			if($x!=0) {
				$str .= '<img src="http://upload.wikimedia.org/wikipedia/commons/0/0e/Forward.png" class="arrow">';
			}
			$str .= '<select class="cat-'.$x.'" name="ms_cat[]" size="6" onchange="try{document.ms.ms_search.value=\'\';}catch(e){}; document.ms.submit();">';
			foreach($sub_cats as $sub_cat) {
				$str .= "<option value='$sub_cat' ";
				if($x+1 < count($cats) && $cats[$x+1]->id == $sub_cat)
					$str .= 'selected="selected"';
				$str .= '>';
				$str .= MsCategoryFactory::get_category_name($sub_cat);
				#$this->get_category_name($sub_cat);
				$str .= '</option>';
			}
			$str .= '</select>';
		}
		$str .= <<<BLU
			</div>
		</div><!--left-->
	</form>
</div><!--formbox-->
BLU;
		$wgOut->addHTML($str);
	}

	function execute( $par ) {
		global $wgRequest, $wgOut;

		if(! $wgRequest->getBool('ms_search')) {
			# display search mask only
			$this->print_search_mask(
				$wgRequest->getText('ms_query'),
				MsCategoryFactory::get_category_stack($wgRequest->getArray('ms_cat'))
			);
			return;
		} else {
			# do some validation
			try {
				$this->validate_user_data();
			} catch(MWException $e) {
				$wgOut->addHTML("Fehler bei Eingabe: ".$e->getLogMessage());
				$this->print_search_mask(
					$wgRequest->getText('ms_query'),
					MsCategoryFactory::get_category_stack($wgRequest->getArray('ms_cat'))
				);
				return;
			}
		}

		$records = $this->controller->execute();

		#$this->dump($records);

		$this->print_search_mask(
			$this->controller->input_keywords,
			MsCategoryFactory::get_category_stack($wgRequest->getArray('ms_cat')),
			true
		);
		$wgOut->addWikiText("==Suchergebnisse==");
		#var_dump($records); exit();
		foreach($records as $rec) {
			$wgOut->addWikiText($rec->__toString());
			#$wgOut->addWikiText(
			#	wfMsg('ms-record', $rec->url, $rec->title,
			#		$rec->desc, $rec->database->id)
			#);
		}

		#$dispatcher = new MsDispatcher();
		#$record_list = $dispatcher->assess($query_result_array);
		#$formatter = new MsOutput();
		#$formatter->print_out($record_list);

 
		# Output
		#$wgOut->addHTML( "Hello World." );
	} // execute
} // class MsQueryPage

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
		foreach($this->list_of_msgs as $msg) {
			$wgOut->addHTML("$msg\n");
		}
		$wgOut->addHTML('</pre>');
	}

	function list_databases() {
		$r = "'''TODO''': This only lists all dbs found in categorytree, not all installed ones.\n\n";
		# At this point: Get all dbs from MsDatabaseFactory and
		# get installation details directly from Database objects!
		foreach($this->list_of_dbs as $db) {
			$r .= "* $db\n";
		}
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
