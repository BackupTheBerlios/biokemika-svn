<?php

error_reporting(E_ALL);

global $msDatabaseDriver;
$msDatabaseDriver['sdbs'] = array(
	'class' => 'MsSDBSDatabaseDriver',
	'view' => 'MsProxyPage',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'sdbs special rewrites for frame proxying',
);

class MsSDBSDatabaseDriver extends MsProxyDatabaseDriver {
	// you've got
	// $this->rewrite_url
	// $this->rewrite_content
	// now start. ;-)
	function rewrite_execute() {
		$this->rewrite_content = str_replace(
			array(
				'if( self != top ) { top.location = self.location; }',
				'/sdbs/cgi-bin/cre_index.cgi?lang=eng',
				'document.form.action="direct_frame_top.cgi"',
				'../LINKS/',
			),
			array(
				"/* don't kill biokemika :-) */",
				$this->proxify_url('/sdbs/cgi-bin/cre_index.cgi?lang=eng'),
				'document.form.action="'.$this->proxify_url('/sdbs/cgi-bin/direct_frame_top.cgi').'"',
				$this->proxify_url('/sdbs/LINKS/'),
			),
			$this->rewrite_content
		);

		#$this->rewrite_content = preg_replace_callback(
		#	'#http://www.sigmaaldrich.com#i',
		#	array(&$this, 'sa_rewrite'),
		#	$this->rewrite_content
		#);
		#$this->rewrite_content = preg_replace()
	}

	function sa_rewrite($m) {
		return $this->proxify_url($m[0]);
	}
}
 



/*
 * Problematik dieser Seite:
 *
 * Schottet sich schon ab
 *   http://riodb01.ibase.aist.go.jp/sdbs/
 * aktiv gegen automatische Auslesung ab, in dem Javascript (trivial)
 * und vor allem Cookies (problematisch) gefordert werden => br�uchte
 * komplettes Browserframework!
 *
 */

/*
class MsDatabase_sdbs extends MsDatabase {

	# URL of the input form, will get search url automatically
	public static $form_url = 'http://riodb01.ibase.aist.go.jp/sdbs/cgi-bin/cre_search.cgi';

///	public static $parser = '#<h4.+?>(.+?)\s+<small>.+?</small></h4>|<a href="(.+?)">(.+?)\s*\(([^()]+)\)</a>#i';

	function execute(MsQuery $query) {
		$records = $this->fetch($query);
		#var_dump($records);
		return new MsResult($this, $records);
	}


	function fetch($query) {
		$form_page = file_get_contents(self::$form_url);
		if(!$form_page)
			throw new MWException('SDBS: Getting form page failed');
		// wir suchen nach einem Eintrag wie
		//<form name="form" action="cre_result.cgi?STSI=124084353525304" method="POST" target="_self">
		// um dort die action zu kriegen.
		if(!preg_match('/<form([^>]+)action="(.+?)"/i', $form_page, $matches)) {
			print '<pre>'.htmlspecialchars($form_page).'</pre>';
			throw new MWException('SDBS: Regex on form page did not match!');
		}
		
		print_r($matches);
		exit(0);

	}

} // class
*/