<?php
error_reporting(E_ALL);

class MsProxyTemplate extends MsQuickTemplate {

	function execute() {
		?>
<div class="ms-page">
	<div class="ms-right">
		<div class="ms-assistant-box" id="ms-assistant-msg">
			<?php $this->msgWiki( $this->get('assistant_box_msg') ); ?>
		</div>
		<div class="mc-bc" id="ms-assistant">
			<?php $this->msgWiki( $this->get('assistant_msg') ); ?>
		</div>
	</div>
	<div class="ms-left">
		<iframe src="<?php $this->html('iframe_start'); ?>" style="width: 100%; height: 500px;">
			<h3>Metasearch iframe</h3>
			<p>The ProxyDatabaseDriver needs iframes and extensive javascript
			to work. Your browser doesn't seem to support that.</p>
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

		$dbs = $cat_stack->get_top()->get_databases(MsCategory::AS_OBJECTS);
		// assume we have only one database:
		if(empty($dbs)) throw new MsException('No DB in cat. :(');
		$database = $dbs[0];

		# like in proxy.php
		$database->set('proxify_url_add',
			$database->build_query('ms-db').'&'.$cat_stack->build_query('ms-cat'));

		$template = new MsProxyTemplate();
		# eigentlich unnoetig:
		#$template->set_from_array($this->database->conf);
		$template->set('assistant_box_msg', 'start-assistant-box-msg-for-this-database');
		$template->set('assistant_msg', 'ms-assistant-happy');
		$template->set('iframe_start', $database->driver->proxify_url(
			$database->get('start_url') ));
		//print $template->get('iframe_start');exit();

		$wgOut->addTemplate($template);
	}
}