<?php
/**
 * MetaSearch: ChooserTemplate.php
 * 
 * This file contains the MsChooserTemplate class.
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
<div class="ms-page <?php echo 'ms-page-'.str_replace(' ', '_', $stack->get_top()->id); ?>">
	<div class="ms-assistant-text">
		<?php
			$assistant_text_msg = $stack->get_top()->get(
				'assistant_text',
				'ms-'.$stack->get_top()->id.'-presearch-box'
			);

			$assistant_msg = $stack->get_top()->get(
				'assistant',
				'ms-assistant-happy-right' # I hate PHP. MediaWiki... # I hate PHP. MediaWiki.....
			);

			# brute force:
			$assistant_msg = 'ms-assistant-happy-right';

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
		// $this->data['stack'] = $stack = MsCategoryStack object

		// Cats not to display in the list but only
		// at the end of page (debugging, maintenance, etc. cats)
		$maintenance_cats = array();

		// make an own temporary category stack for the current location
		$current_stack = new MsCategoryStack();
		// go throught the stack and get data from each category
		for($x = 0; $x < $stack->count(); $x++) {
			$sub_cats = $stack->get($x)->get_sub_categories(MsCategory::AS_OBJECTS);
			
			if(empty($sub_cats)) {
				// Endkategorie erreicht!
				break;
			}

			// update loop stack
			$current_stack->push( $stack->get($x) );

			$is_last = $x < $stack->count()-1;

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

				// display title attribute
				if($cat->has_set('tooltip_text'))
					$a['title'] = $cat->get('tooltip_text');

				$a['class'] = ''; // will be filled :-)

				// Highlight the selected database
				if($x+1 < $stack->count() &&
				     $stack->get($x+1)->id == $cat->id)
					$a['class'] .= ' selected';

				// this came from the idea of the "notyet" tag.
				$a['class'] .= $cat->get('class', '');

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
	<?php

	if(! $stack->get_top()->has_sub_categories()) {
		// display the db chooser
	?>
	<div class="ms-dbchooser">
	<?php
		$dbs = $stack->get_top()->get_databases(MsCategory::AS_INFO_ARRAY);
		foreach($dbs as $db) {
			echo '<div class="ms-db">';

			// Inhalt der Box
			if(isset($db['desc-msg'])) {
				$this->wiki( wfMsg($db['desc-msg']) );
			} else {
				// no details for this database... how sad ;-)
				echo '<h3>'.$db['id'].'</h3>';
			}

			// Link zum "Diese Datenbank waehlen..."
			echo '<a href="'.$title->getLocalURL(
				$stack->build_query('ms-cat').
				'&ms-db='.urlencode($db['id'])
			).'">Datenbank auswaehlen...</a>';

			echo '</div>';
		}
	?>
	</div><!--ms-dbchooser-->
	<?php
	} /* endif dbchooser */

	?>
</div><!--ms-page-->
<?php
	} // function execute()

	function link_design_content($c) {
		// some workarounds for CSS design in the link
		return '<span class="pre"></span><span class="post"></span><span class="content">'.$c.'</span>';
	}

} // class

