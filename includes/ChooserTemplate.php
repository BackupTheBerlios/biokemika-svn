<?php

error_reporting(E_ALL);

class MsQuickTemplate extends QuickTemplate {
	/// This will *reference* each value from $array to the
	/// template data array.
	function set_from_array( $array ) {
		foreach($array as $k => $v) {
			$this->setRef($k, $v);
		}
	}

	function bool( $str ) {
		return isset($this->data[$str]) && $this->data[$str];
	}

	function get( $str ) {
		return $this->data[$str];
	}

	/// like msgWiki, just for strings: Render string as wiki.
	/// An ugly hack ;-)
	/// Advantage to msgWiki: $this->wiki(wfMsg()) will do what you
	/// think (msgWiki doesn't use wfMsg()).
	function wiki( $str ) {
		global $wgParser, $wgOut, $wgTitle;
		$parserOutput = $wgParser->parse( $str, $wgTitle,#$wgOut->getTitle(),
			$wgOut->parserOptions(), true );
		echo $parserOutput->getText();
	}
}

/**
 * Fields:
 *   - action
 *   - display_input_box (was has_input_text)
 *   - assistant_box_msg (was $this->get_status().'search' on $current_cat->get_box)
 *   - assistant_msg (was $this->get_assistant_msg())
 *   - stack (MsCategoryStack object)
 **/
class MsChooserTemplate extends MsQuickTemplate {

/* <form method="get" action="<?php $this->text('action'); ?>" name="ms"> */

	function execute() {
		extract($this->data); // PHP magic, mainly for shorthand $stack.
		?>
<div class="ms-page <?php echo 'ms-page-'.str_replace(' ', '_', $this->data['stack']->get_top()->id); ?>">
	<div class="ms-assistant-text">
		<?php
			$assistant_text_msg = $stack->get_top()->get(
				'assistant_text',
				'ms-'.$stack->get_top()->id.'-presearch-box'
			);

			$assistant_msg = $stack->get_top()->get(
				'assistant',
				'ms-assistant'
			);

			$assistant_text = wfMsg( $assistant_text_msg );
			$assistant = wfMsg($assistant_msg);

			$this->wiki( $assistant_text );
		?>
	</div>
	<div class="ms-assistant">
		<?php $this->wiki( $assistant ); ?>
	</div>

	<div class="ms-catchooser">
	<?php
		// $this->data['stack'] = MsCategoryStack object

		// Cats not to display in the list but only
		// at the end of page (debugging, maintenance, etc. cats)
		$maintenance_cats = array();

		// make an own temporary category stack for the current location
		$current_stack = new MsCategoryStack();
		// go throught the stack and get data from each category
		for($x = 0; $x < $this->data['stack']->count(); $x++) {
			$sub_cats = $this->data['stack']->get($x)->get_sub_categories(true);
			if(empty($sub_cats))
				// Endkategorie erreicht!
				break;

			// update loop stack
			$current_stack->push( $this->data['stack']->get($x) );

			$is_last = $x < $this->data['stack']->count()-1;

			echo '<div class="sub level'.$x.' '.($is_last?'level_last':'').'">';
			if($is_last) {
				// for a bit barrierefreiheit...
				echo '<div class="help">Hier hast du schon ausgewaehlt:</div>';
			} else {
				echo '<div class="help">Bitte waehle hier aus:</div>';
			}
			echo '<ul>';
			foreach($sub_cats as $cat) {
				if($cat->has_set('maintenance')) {
					$maintenance_cats[] = $cat;
					continue;
				}
				
				// update stack
				$current_stack->push( $cat );

				echo '<li>';
				$a = array(); // the <a> Xml tag
				$a['href'] = $this->data['title']->getLocalURL(
					$current_stack->build_query('ms-cat')
				);
				$a['title'] = $this->data['view']->link_title_for($cat);
				$a['class'] = '';

				// Highlight the selected database
				if($x+1 < $this->data['stack']->count() &&
				     $this->data['stack']->get($x+1)->id == $cat->id)
					$a['class'] .= ' selected';

				// grey out databases not done yet
				if($cat->has_set('notyet'));
					$a['class'] .= ' notyet';

				// print out <a ...>...</a> tag:
				echo Xml::tags('a', $a, 
					$this->link_design_content($cat->get('name'))
				);
				
				echo '</li>';
				$current_stack->pop();
			}
			echo '</ul>';
			echo '</div>';
		}
	?>
	</div><!--ms-catchooser -->

</div><!--ms-page-->
<?php
	} // function execute()

	function link_design_content($c) {
		// some workarounds for CSS design in the link
		return '<span class="pre"></span><span class="post"></span><span class="content">'.$c.'</span>';
	}

} // class

?>