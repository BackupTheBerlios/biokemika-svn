<?php

error_reporting(E_ALL);


class MsSearchMask {
	// this is the almighty query value that will be displayed there...
	public $query = '';

	// this is one of ::status_pre, ::status_post, ::status_error
	public $status = 'pre';

	// static values for status. Should be left by their values :-)
	public static $status_pre   = 'pre';
	public static $status_post  = 'post';
	public static $status_error = 'error';

	// Yeah, the current MsController object. We'll get that
	// automatically.
	public $controller;

	// the category stack to display
	public $cat_stack = array();

	// the status of the assistant. Something like "good", "bad" or
	// even "yourpersonalstatus". Will be replaced on
	// mw-assistant-$assistant_status
	public $assistant_status = 'happy';

	// data that will replaced in the messages with according data,
	// a hash like a => b will replace {{{a}}} with b.
	public $data = array();

	function __construct() {
		$this->controller = MsController::get_instance();
	}

	function fill_from_request() {
		global $wgRequest;
		$this->query = $wgRequest->getText('ms_query');
		$this->cat_stack = MsCategoryFactory::get_category_stack($wgRequest->getArray('ms_cat'));
	}

	// get the current category (top of the stack)
	function get_top_cat() {
		return $this->cat_stack[count($this->cat_stack)-1];
	}

	// get the best matching assistant message
	function get_assistant_msg() {
		$cat = $this->get_top_cat();
		$msg_postfix = $this->assistant_status;

		if($cat->get($this->get_status().'-assistant')) {
			$msg_postfix = $cat->get($this->get_status().'-assistant');
		} else if($cat->get('assistant')) {
			$msg_postfix = $cat->get('assistant');
		}

		return "ms-assistant-$msg_postfix";
	}

	function get_status() {
		if($this->status == self::$status_pre ||
			$this->status == self::$status_post ||
			$this->status == self::$status_error) {
			return $this->status;
		} else
			return self::$status_pre;
	}

	function print_out() {
		global $wgOut, $msConfiguration;

		# clean the category stack
		$this->cat_stack = MsCategoryFactory::clean_category_stack($this->cat_stack);

		# get the topmost category of the stack
		$current_cat = $this->get_top_cat();

		$prepost = $this->get_status().'search';
		$action = $this->controller->view->special_page->getTitle()->escapeLocalURL(); // <form> action.

		$wgOut->addHTML('<div class="ms-formbox ms-'.$prepost.'">');
		$wgOut->addHTML('<form method="get" action="'.$action.'" name="ms">');
		if($current_cat->has_input_text()) {
			$wgOut->addHTML('<div class="ms-right">');
			$wgOut->addHTML('<div class="ms-assistant-box">');
		} else {
			$wgOut->addHTML('<div class="ms-assistant-box">');
		}

		// Contents of assistant box
		$box = $current_cat->get_box($this->get_status().'search');
		$wgOut->addWikiText($box);

		$wgOut->addHTML('</div><!--assistant box-->');
		if(! $current_cat->has_input_text()) {
			$wgOut->addHTML('<div class="ms-right">');
		}
		$wgOut->addHTML('<div class="mc-bc">');
		$wgOut->addWikiText( wfMsg( $this->get_assistant_msg() ) );
		$wgOut->addHTML('</div>');
		$wgOut->addHTML('</div><!--ms-right-->');

		$wgOut->addHTML('<div class="ms-left">');
		if($current_cat->has_input_text()) {
			$wgOut->addHTML('<div class="ms-inputtext">');
			$current_cat->add_input_text($this->query);
			$wgOut->addHTML('</div>');
		}
		$wgOut->addHTML('<div class="ms-class-selector">');

		$str = ''; // out string buffer.
		for($x = 0; $x < count( $this->cat_stack ); $x++) {
			$sub_cats = $this->cat_stack[$x]->get_sub_categories(true);
			if(empty($sub_cats))
				// Endkategorie erreicht!
				break;

			if($x!=0) {
				$str .= '<img src="http://upload.wikimedia.org/wikipedia/commons/0/0e/Forward.png" class="arrow">';
			}

			$str .= '<select class="cat-'.$x.'" name="ms_cat[]" size="6" onchange="try{document.ms.ms_search.value=\'\';}catch(e){}; document.ms.submit();">';
			foreach($sub_cats as $sub_cat) {
				$str .= '<option value="'.$sub_cat->id.'" ';

				// Anwaehlen aktueller Datenbanken
				if($x+1 < count($this->cat_stack) && $this->cat_stack[$x+1]->id == $sub_cat->id)
					$str .= 'selected="selected"';

				// ausgrauen noch nicht gemachter Datenbanken
				if($sub_cat->get('input') == 'notyet')
					$str .= 'style="color:#aaa;"';
				$str .= '>';
				$str .= $sub_cat->get('name');
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
	} // function print_out

} // class MsSearchMask
