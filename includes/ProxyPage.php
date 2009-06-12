<?php
error_reporting(E_ALL);

class MsProxyTemplate extends MsQuickTemplate {

	function execute() {
		?>
<div class="ms-page">
	<div class="ms-right">
		<div class="ms-assistant-box" id="ms-assistant-msg">
			<?php $this->html( $this->get('assistant_text') ); ?>
		</div>
		<div class="mc-bc" id="ms-assistant">
			<?php $this->msgWiki( $this->get('assistant_msg') ); ?>
		</div>
	</div>
	<div class="ms-left">
		<iframe src="<?php $this->html('iframe_start'); ?>" style="width: 100%; height: 500px;">
			<h3>Metasearch iframe</h3>
			<p>The ProxyDatabaseDriver needs iframes and extensive javascript
			to work. Your browser doesn't seem to support that. These are the
			core requirements for using the proxy driver. So simply browse
			directly at the databases, maybe your browser can do *that* :-)</p>
		</iframe>
	</div>
</div>
<?php
	} // function execute()

}

class MsProxyPage extends MsPage {
	function execute($par) {
		global $wgOut, $wgScriptPath, $wgRequest;
		$wgOut->addScriptFile( "$wgScriptPath/extensions/metasearch/proxy.js" );

		$cat_stack = new MsCategoryStack( $wgRequest->getArray('ms_cat') );

		/// TODO: Copied from QueryPage
		if( $cat_stack->get_top()->is_root() ) {
			throw new MsException('Please select a category.',
				MsException::BAD_INPUT);
		} else if( !$cat_stack->get_top()->has_databases() ) {
			throw new MsException('Please select a category that has databases!',
				MsException::BAD_INPUT);
		}

		// get some database
		$database = $cat_stack->get_top()->get_one_database(MsCategory::AS_OBJECTS);
		if(!$database)
			throw new MsException("No db in top cat of $cat_stack");

		// verify that we've got some 'proxydriver' type driver.
		// We cannot handle other drivers!
		if(!$database->is_driver_type('proxydriver'))
			throw new MsException("MsProxyPage can only handle 'proxydriver' databases. ".
				"Unfortunately [$database] is not such a database.",
				MsException::BAD_INSTALLATION);

		// Load common proxy engine configuration
		// (for proxify, etc.)
		$proxy_conf = MsProxyConfiguration::get_instance();

		// Get values for the template:
		$start_url = $database->get('start_url');
		if(!$start_url) $start_url = 'http://www.google.de';

		$template = new MsProxyTemplate();
		# eigentlich unnoetig:
		#$template->set_from_array($this->database->conf);
		$template->set('assistant_text', wfMsg('start-assistant-box-msg-for-this-database'));
		$template->set('assistant_msg', 'ms-assistant-happy');
		$template->set('iframe_start', $proxy_conf->proxify( $start_url ));
		//print $template->get('iframe_start');exit();

		$wgOut->addTemplate($template);
	}
}