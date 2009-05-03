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
require_once $ms_dir.'conf.php';
require_once $ms_dir.'controller.php';
require_once $ms_dir.'database.php';
require_once $ms_dir.'category.php';
require_once $ms_dir.'dispatcher.php';

require_once $ms_dir.'databases/example.php';

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
	public $controller;

	function __construct() {
		parent::__construct( 'Metasearch' );
		wfLoadExtensionMessages('Metasearch');
		$this->controller = MsController::get_instance();
	}

	# usage in execute: return $this->dump(...);
	function dump( $data ) {
		global $wgOut;
		$wgOut->addHTML( "<pre>".var_dump_ret($data)."</pre>" );
		return Null;
	}


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

	function get_category_box($category, $area='presearch') {
		return wfMsg("ms-${category}-${area}-box");
	}

	function get_category_exists($category) {
		return wfMsgExists("ms-${category}-category");
	}

	function get_sub_categories($catname=false) {
		global $msConfiguration;
		if(!$catname) $catname = $msConfiguration['root-category-name'];
		$msg_name = "ms-${catname}-category";
		$msg = wfMsg($msg_name);
		// msg existiert nicht => cat existiert nicht => nix subcat.
		//var_dump($catname, $msg_name, $msg);
		if($msg == "&lt;$msg_name&gt;" || 
			!preg_match('/^\s*Sub:\s*(.+)$/mi', $msg, $matching))
			return array(); // einfach: nix subcat. Weil vielleicht
			// cat gar keine subcats hat, aber trotzdem existiert.
			//throw new MWException("$msg_name doesnt match regex!");
		return array_map('trim', explode(', ', $matching[1]) );
	}

	function get_category_name($category) {
		$msg = wfMsg("ms-${category}-category");
		if(preg_match('/^\s*Name:\s*(.+)$/mi', $msg, $matching))
			return $matching[1];
		else	return $category.' (nameless)';
	}

	function get_category_dbs($category) {
		$msg = wfMsg("ms-${category}-category");
		if(!preg_match('/^\sdbs?:\s*(.+)$/mi', $msg, $matching))
			return array(); # no databases
		return array_map('trim', explode(', ', $matching[1]) );
	}

	# $query: string, $cats: MsCategory stack, $assistant_status: will be param to Ms-assistant
	function print_search_mask($query='', $cats=false, $assistant_status='good') {
		global $wgOut, $msConfiguration;

		// make sure root is the very first category.
		#if(!is_array($cats)) $cats = array();
		#if(empty($cats) || $cats[0] != $msConfiguration['root-category-name'])
		#	array_unshift($cats, $msConfiguration['root-category-name']);

		# get the topmost category of the stack
		$current_cat = $cats[count($cats)-1];

		// Contents of prebox = most sub category
		$prebox = $current_cat->get_box('presearch');
		$action = $this->getTitle()->escapeLocalURL(); // <form> action.

		$wgOut->addHTML(<<<BLA
<div class="ms-formbox">
	<form method="get" action="$action" name="ms">
		<div class="ms-right">
			<div class="ms-prebox">
BLA
);
		$wgOut->addWikiText($prebox);
		$wgOut->addHTML('</div><div class="mc-bc">');
		$wgOut->addWikiText(wfMsg('ms-assistant', $assistant_status));
		$wgOut->addHTML('</div></div><!--ms-right-->');

		#		<img alt="Mr. BC" src="http://biokemika.uni-frankfurt.de/w/images/thumb/Mr_Happy.png/190px-Mr_Happy.png">
		#	</div>

		$wgOut->addHTML('<div class="ms-left">');
		$wgOut->addHTML('<div class="ms-inputtext">');
		$current_cat->add_input_text($query);
		$wgOut->addHTML('</div><div class="ms-class-selector">');

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

	/*
	function print_search_mask($keyword='', $category='') {
		global $wgOut;
		$wgOut->addHTML( 
			wfMsg('ms-inputform',
				$this->getTitle()->escapeLocalURL(),
				$keyword,
				empty($category)?'':("<option value='$category' selected='selected'>$category (Momentane Kategorie)</option>\n<option>-----------</option>")
			)
		);
	}*/

	function execute( $par ) {
		global $wgRequest, $wgOut;
		$this->setHeaders();

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
			MsCategoryFactory::get_category_stack($wgRequest->getArray('ms_cat'))
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
} // class
