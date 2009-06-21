<?php
/**
 * The MetaSearch MsProxyDatabaseDriver helper program.
 * This is a valid entry point to MediaWiki!
 *
 * This file needs the PECL extension
 *    PECL-HTTP
 * installed
 *
 **/

error_reporting(E_ALL);

// Set up a lightweight MediaWiki environment
putenv('MW_INSTALL_PATH='.dirname( __FILE__ ) . "/../..");
require_once( '../../includes/WebStart.php' );

// check GET data
if(! isset($_GET['ms-url']) || ! isset($_GET['ms-cat'])) {
	msProxyUsage();
	return;
}

// get GET arguments
try {
	$url = $_GET['ms-url']; # no MediaWiki magic!
	$stack = new MsCategoryStack( $wgRequest->getArray('ms-cat') );
	$db = isset($_GET['ms-db']) ?
		new MsDatabase($_GET['ms-db']) :
		$stack->get_top()->get_one_database();

	if(!$db) {
		throw new MsException('Please specify a database.');
	}

	if( ! $db->driver->is_allowed($url) ) {
		wfHttpError( 403, 'Forbidden',
			"Requested URL is not allowed to be proxified in database $db!" );
		return;
	}

	$db->driver->set_cat_stack($stack);
} catch(MsException $e) {
	$e->reportHTML();
	return;
}

// make use of PECL-HTTP
$request = new HttpRequest($url);
$request->setHeaders(array(
	'User-Agent' => $_SERVER['HTTP_USER_AGENT'],
));
$request->setOptions(array(
	'timeout' => 30,
	'compress' => true,
));
$request->enableCookies = true; # don't think that's important...

// fill the query with some data:

if(!empty($_POST)) {
	$request->setMethod( HttpRequest::METH_POST );
	$request->setPostFields( $db->driver->strip_proxy_post_fields($_POST) );
} else {
	// GET Request
	$request->setMethod( HttpRequest::METH_GET );
}

// Cookie handling while sending
$request->setCookies( $db->driver->strip_cookies( $_COOKIE ) );

try {
	// It seems that HttpRequest doesn't follow redirects...
	do {
		$response = $request->send();
		if($response->getResponseCode() != 301 && $response->getResponseCode() != 302)
			break;
		$request->setUrl($response->getHeader('Location'));
	} while(1);

	// now we've got our request finished... (at first ;-) )
} catch(HttpException $ex) {
	echo "Urghs... We got an Exception: $ex";
	return;
}

// HTTP Error status handling
if(400 <= $response->getResponseCode()) {
	echo "The Database won't let you see <a href='$url'>$url</a>:\n<br>";
}

// Cookie handling while getting answer = 
// Header handling. Cookie could be rewritten..
if($response->getHeader('Set-Cookie')) {
	$cookies = $response->getHeader('Set-Cookie');
	// Wenn mehrere Cookies gegeben, wird es Array.
	// Sicherstellen, dass Array:
	if(!is_array($cookies)) $cookies = array($cookies);
	foreach($cookies as $cookie) {
		$parsed = http_parse_cookie($cookie);
		foreach($parsed->cookies as $name => $value) {
			#print "SET COOKIE $name to $value\n";
			setcookie($name, $value, 0, '/');
		}
	}
	#var_dump($cookie, $parsed); exit();
	#$parsed['path'] = '/';
	#$cookie = http_build_cookie($parsed);
}

// Header handling
foreach(array('Content-Type') as $name) {
	$content = $response->getHeader($name);
	if($content) header("$name: $content");
}

// well, that's our body
$contents = $response->getBody();

//print $contents;

	// Only a "simple" GET query
	//$contents = file_get_contents($url);
	// check out the response header from the request
	//$header = array_filter_start_with($http_response_header, array('HTTP', 'Content', 'Set-Cookie'));
	//array_walk($header, 'header');
	#var_dump($http_response_header, $header); exit();

$is_html = ( stripos($response->getHeader('Content-Type'), 'html') !== false );

// rewrite the page...
$db->driver->rewrite_page($url, $contents, $is_html);

// and print it out.
echo $contents;


// Still missing:
//   - COOKIES (a bit implemented in GET)
//   - POST DATA

// hm. we are done. ;-)

/// small and stupid.
function array_filter_start_with($haystack, $keyword_array) {
	$result = array();
	foreach($haystack as $x) {
		foreach($keyword_array as $keyword) {
			if(strpos(strtolower($x), strtolower($keyword)) === 0)
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


