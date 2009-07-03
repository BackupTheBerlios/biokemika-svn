<?php
error_reporting(E_ALL);

class MsProxyTemplate extends MsQuickTemplate {

	function execute() {
		?>
<div class="ms-page ms-proxydriver">
	<div class="ms-navbar">
		<?php echo  wfMsg('ms-proxypage-top', $this->get('catchooser_link'),
			$this->get('leave_biokemika_link')
		); ?>
	</div>
		<div class="ms-assistant-text" id="ms-assistant-msg">
			<?php
			# da es hier grundsaetzlich nur um den Initialtext geht:
			#$this->html( $this->get('assistant_text') );
			?>
			Bitte warte, bis ich wei&szlig;, wo auf der Datenbank
			du dich befindest.
			Erst dann kann ich dir helfen.
		</div>
	<div class="ms-right">
		<div class="ms-assistant" id="ms-assistant">
			<?php $this->msgWiki( $this->get('assistant_msg') ); ?>
		</div>
	</div>
	<div class="ms-left">
		<iframe id="ms-proxy-frame" src="<?php $this->html('iframe_start'); ?>">
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

		$cat_stack = new MsCategoryStack( $wgRequest->getArray('ms-cat') );

		/// TODO: Copied from QueryPage
		if( $cat_stack->get_top()->is_root() ) {
			throw new MsException("Cat stack is $cat_stack. Please select a category",
				MsException::BAD_INPUT);
		} else if( !$cat_stack->get_top()->has_databases() ) {
			throw new MsException('Please select a category that has databases!',
				MsException::BAD_INPUT);
		}

		// get "the" database
		if( $wgRequest->getVal('ms-db') ) {
			$database = new MsDatabase( $wgRequest->getVal('ms-db') );
		} else {
			$database = $cat_stack->get_top()->get_one_database(MsCategory::AS_OBJECTS);
		}
		if(!$database)
			throw new MsException("No db in top cat of $cat_stack / ms-db param");

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
		if(!$start_url)
			throw new MsException("start_url field missing in config for $database!",
				MsException::BAD_CONFIGURATION);

		$template = new MsProxyTemplate();
		# eigentlich unnoetig:
		#$template->set_from_array($this->database->conf);
		$template->set('assistant_text', wfMsg('ms-assistant-starting-bla'));
		$template->set('assistant_msg', 'ms-assistant-happy');
		$template->set('iframe_start', $proxy_conf->proxify( $start_url ));

		# das ist jetzt eher quick & dirty, aber den cat_stack brauch dann keiner mehr:
		$cat_stack->pop(); # poppen fuer catchooser_link (damit der catchooser nicht direkt zur cat leitet)

		$template->set('leave_biokemika_link', '#');
		$template->set('catchooser_link', $this->special_page->get_sub_title('choose')->escapeFullURL(
			# haesslich: [] a la rawurlencode() kodieren, damit mediawiki die URL
			# als Link erkennt beim Parsen
			str_replace('[]', '%5B%5D', $cat_stack->build_query('ms-cat') )
		));
		//print $template->get('iframe_start');exit();

		$wgOut->addTemplate($template);
	}
}
