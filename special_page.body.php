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
require_once $ms_dir.'dispatcher.php';

require_once $ms_dir.'databases/example.php';

function var_dump_ret($mixed = null) {
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();
  return $content;
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

		$this->controller->input_category = strtolower($wgRequest->getText('ms_cat'));
		if(!isset($msCategories[$this->controller->input_category])) {
			throw new MWException('Invalid Category.');
		}

		return true;
	}

	function print_search_mask($keyword='', $category='') {
		global $wgOut;
		$wgOut->addHTML( 
			wfMsg('ms-inputform',
				$this->getTitle()->escapeLocalURL(),
				$keyword,
				empty($category)?'':("<option value='$category' selected='selected'>$category (Momentane Kategorie)</option>\n<option>-----------</option>")
			)
		);
	}

	function execute( $par ) {
		global $wgRequest, $wgOut;
		$this->setHeaders();

		if(! $wgRequest->getBool('ms_search')) {
			# display search mask only
			$this->print_search_mask();
			return;
		} else {
			# do some validation
			try {
				$this->validate_user_data();
			} catch(MWException $e) {
				$wgOut->addHTML("Fehler bei Eingabe: ".$e->getLogMessage());
				$this->print_search_mask();
				return;
			}
		}

		$records = $this->controller->execute();

		#$this->dump($records);

		$this->print_search_mask($this->controller->input_keywords,
			$this->controller->input_category);
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
