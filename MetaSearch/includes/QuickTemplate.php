<?php
/**
 * MetaSearch System: QuickTemplate.php
 * 
 * Classes in this file:
 *   - MsQuickTemplate
 *   - MsExceptionTemplate
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
 *
 **/

error_reporting(E_ALL);

/**
 * The MsQuickTemplate is the MediaWikis QuickTemplate class with
 * some nice improved features.
 **/
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
 * This template is used when rendering MsExceptions. Simply put
 * the exception to the data['exception'] and print out the 
 * template.
 **/
class MsExceptionTemplate extends MsQuickTemplate {
	function execute() {
		extract($this->data);
		?><div id="ms-page">
			<h2>Ein Fehler ist aufgetreten</h2>
			<p>Ja schlecht, gell? Find ich auch:</p>
			<div class="ms-exception-message">
				<?php echo $exception->getMessage(); ?>
			</div>
			<p>Helf mit, die BioKemika qualitativ auf einem hohen
			Niveau zu behalten. Melde dem BioKemika-Team diesen Fehler!</p>
			<p>Diese Daten werden dem Team helfen:</p>
			<pre>
Backtrace:
<?php echo htmlspecialchars( $exception->getTraceAsString() ); ?>;

LogMessage:
<?php echo htmlspecialchars( $exception->getLogMessage() ); ?>

Message Code:
<?php echo $exception->getCode(); ?>
</pre>
		</div><!-- ms-page -->
		<?php
	}
}