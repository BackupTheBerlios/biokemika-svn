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

	function execute() {
		?>
<div class="ms-formbox ms-pre">
<form method="get" action="<?php $this->text('action'); ?>" name="ms">
<?php  if($this->bool('display_input_box')) {  ?>
	<div class="ms-right">
		<div class="ms-assistant-box">
<?php  } else { ?>
		<div class="ms-assistant-box">
<?php  }

	$this->msgWiki( $this->get('assistant_box_msg') );
?>
		</div><!-- assistant box -->
<?php  if(! $this->bool('display_input_box')) { ?>
	<div class="ms-right">
<?php  } ?>
		<div class="mc-bc">
<?php
	$this->msgWiki( $this->get('assistant_msg') );
?>
		</div><!-- mr bc -->
	</div><!-- ms-right -->

	<div class="ms-left">
	<?php
		if($this->bool('display_input_box')) {
			echo '<div class="ms-inputtext">';
			// $wgOut->addWikiText( wfMsg( $this->get_assistant_msg() ) );
			echo '</div>';
		}
	?>
	</div><!-- ms-left -->

	<div class="ms-class-selector">
	<?php
		// $this->data['stack'] = MsCategoryStack object

		// go throught the stack and get data from each category
		for($x = 0; $x < $this->data['stack']->count(); $x++) {
			$sub_cats = $this->data['stack']->get($x)->get_sub_categories(true);
			if(empty($sub_cats))
				// Endkategorie erreicht!
				break;

			if($x!=0)
				// very simple arrow
				echo '<img src="http://upload.wikimedia.org/wikipedia/commons/0/0e/Forward.png" class="arrow">';

			echo '<select class="cat-'.$x.'" name="ms_cat[]" size="6" onchange="try{document.ms.ms_search.value=\'\';}catch(e){}; document.ms.submit();">';
			foreach($sub_cats as $sub_cat) {
				echo '<option value="'.$sub_cat->id.'" ';

				// Anwaehlen aktueller Datenbanken
				if($x+1 < $this->data['stack']->count() && $this->data['stack']->get($x+1)->id == $sub_cat->id)
					echo 'selected="selected"';

				// ausgrauen noch nicht gemachter Datenbanken
				if($sub_cat->get('input') == 'notyet')
					echo 'style="color:#aaa;"';
				echo '>';
				echo $sub_cat->get('name');
				#$this->get_category_name($sub_cat);
				echo '</option>';
			}
			echo '</select>';
		}
	?>
	</div><!--ms-class-selector -->

	</form>
</div><!--formbox-->
<?php
	} // function execute()

} // class

?>