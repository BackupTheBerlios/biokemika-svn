<?php
/**
 * The MetaSearch MsProxyDatabaseDriver helper program.
 * This is a valid entry point to MediaWiki!
 *
 **/

error_reporting(E_ALL);

// Set up a lightweight MediaWiki environment
putenv('MW_INSTALL_PATH='.dirname( __FILE__ ) . "/../..");
require_once( '../../includes/WebStart.php' );

// check GET data
if(! isset($_GET['ms-url']) || ! isset($_GET['ms-cat']))
	msProxyUsage();

// get GET arguments
try {
	$url = $_GET['ms-url']; # no MediaWiki magic!
	$stack = new MsCategoryStack( $wgRequest->getArray('ms-cat') );
	$db = isset($_GET['ms-db']) ?
		new MsDatabase($_GET['ms-db']) :
		$stack->get_top();

	if( ! 1) {#$db->driver->is_allowed($url) ) {
		wfHttpError( 403, 'Forbidden',
			"Requested URL is not allowed to be proxified in database $db!" );
		return;
	}

	$db->set('proxify_url_add',
		$db->build_query('ms-db').'&'.$stack->build_query('ms-cat'));
} catch(MsException $e) {
	$e->reportHTML();
	return;
}

// now download the URL's contents. The behaviour is very simple...
if(!empty($_POST)) {
	// This was a POST call. So give them the data...
	// could be a little complex
	// stream_copy_contents ...
	echo "Make POST Query.";
	return;
} else {
	// Only a "simple" GET query
	$contents = file_get_contents($url);
	// check out the response header from the request
	$header = array_filter_start_with($http_response_header, array('HTTP', 'Content', 'Set-Cookie'));
	array_walk($header, 'header');
	#var_dump($http_response_header, $header); exit();
	// rewrite the page...
	$db->driver->rewrite_page($url, $contents);
	// and print it out.
	echo $contents;
}

// Still missing:
//   - COOKIES (a bit implemented in GET)
//   - POST DATA
//   - error codes? (HTTP error...)
//   - Forms: Create <input type="hidden"> elements!

// hm. we are done. ;-)

/// small and stupid.
function array_filter_start_with($haystack, $keyword_array) {
	$result = array();
	foreach($haystack as $x) {
		foreach($keyword_array as $keyword) {
			if(strpos($x, $keyword) === 0)
				$result[$keyword] = $x;
		}
	}
	return $result;
}


function msProxyUsage() {
	?><h2>MediaWiki MetaSearch Extension: MsProxyDatabaseDriver helper usage</h2>
	<p>GET params:</p>
	<ul>
		<li><i>ms-cat</i>: The category stack context (optional)</li>
		<li><i>ms-db</i>: The name of the database</li>
		<li><i>ms-url</i>: The URL you want to go to</li>
	</ul>
	<?php
}

?>
