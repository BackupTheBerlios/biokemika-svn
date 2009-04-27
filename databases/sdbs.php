<?php

/*
 * Problematik dieser Seite:
 *
 * Schottet sich schon ab
 *   http://riodb01.ibase.aist.go.jp/sdbs/
 * aktiv gegen automatische Auslesung ab, in dem Javascript (trivial)
 * und vor allem Cookies (problematisch) gefordert werden => bräuchte
 * komplettes Browserframework!
 *
 */


# to test it:
error_reporting(E_ALL);

// Well... since we have like ordinary users, we also can
// pretend to be such ones.
ini_set('user_agent', 'Mozilla/5.0 (X11; U; Linux i686; de; rv:1.9.0.6) Gecko/2009011912 Firefox/3.0.6');

class MsDatabase_sdbs extends MsDatabase {

	# URL of the input form, will get search url automatically
	public static $form_url = 'http://riodb01.ibase.aist.go.jp/sdbs/cgi-bin/cre_search.cgi';

/*	public static $parser = '#<h4.+?>(.+?)\s+<small>.+?</small></h4>|<a href="(.+?)">(.+?)\s*\(([^()]+)\)</a>#i';
*/	

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
