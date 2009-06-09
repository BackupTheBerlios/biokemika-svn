global $msDatabaseDriver;
$msDatabaseDriver['sigmaaldrich'] = array(
	'class' => 'MsSigmaAldrichDatabaseDriver',
	'view' => 'MsProxyPage',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'sigmaaldrich.com special rewrites',
);

class MsSigmaAldrichDatabaseDriver extends MsProxyDatabaseDriver {
	// you've got
	// $this->rewrite_url
	// $this->rewrite_content
	// now start. ;-)
	function rewrite_execute() {
		$this->rewrite_content = preg_replace_callback(
			'#http://www.sigmaaldrich.com#i',
			array(&$this, 'sa_rewrite'),
			$this->rewrite_content
		);
		#$this->rewrite_content = preg_replace()
	}

	function sa_rewrite($m) {
		return $this->proxify_url($m[0]);
	}
}
 
