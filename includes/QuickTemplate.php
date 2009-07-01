<?php
/**
 * Classes:
 *   - MsQuickTemplate
 *   - MsExceptionTemplate
 *
 **/

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