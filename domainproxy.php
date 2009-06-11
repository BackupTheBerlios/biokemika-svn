<?php
/**
 * The MetaSearch MsProxyDatabaseDriver helper program.
 * proxy that will use subdomains like
 * 
 *    www.your-site.com.proxy.biokemika.de/your/page.html
 * 
 * Needs no rewriting, will just take a look at the
 * HTTP_HOST_NAME.
 * 
 * This is a valid entry point to MediaWiki!
 **/

// lightweight MediaWiki Environment
error_reporting(E_ALL);
putenv('MW_INSTALL_PATH='.dirname( __FILE__ ) . "/../..");
require_once( '../../includes/WebStart.php' );

// Set up Metasearch system
require_once('MetaSearch_body.php'); // important defs
$conf = MsProxyConfiguration::get_instance();
$url = $conf->get_request_url();
$db = $conf->create_database($url);

if(!$db) throw new MsException("Got no DB for $url");

// Set up HTTP Request
$request = new HttpRequest( $url );

$options = array(); $headers = array();
if(isset($_SERVER['HTTP_USER_AGENT']))
	$headers['User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
$options['timeout'] = 30;
# Compression? Don't use since we've got at least 100mbit bandwith,
#              so that isn't important
# $options['compress'] = true;
# Auto redirection handling? Nice, but doesn't work correctly so
#                            we'll use our own implementation.
# $options['redirect'] = 10;
if(isset($_SERVER['HTTP_REFERER']))
	$options['referer'] = $conf->deproxify($_SERVER['HTTP_REFERER']);

$request->addHeaders( $headers );
$request->setOptions( $options );

// handle GET / POST requests and data
if(!empty($_POST)) {
	$request->setMethod( HttpRequest::METH_POST );
	$request->setPostFields( $_POST );
} else {
	// GET Request
	$request->setMethod( HttpRequest::METH_GET );
}

// Cookie handling
$request->enableCookies = true;
$request->setCookies( $_COOKIE );

try {
	// HttpRequest can follow redirects automatically, but that
	// works strangely...
	$max_follow_redirects = 10;
	do {
		$response = $request->send();
		if($response->getResponseCode() >= 400) {
			// Error codes. Redirect client to error page so he
			// can see the mess on his own ;-)
			header("Location: $url");
			exit();
		}

		if($response->getResponseCode() != 301 && $response->getResponseCode() != 302)
			// no redirection codes, go on in program...
			break;
		// else: redirection codes.

		// problem: Can be relative. Get absolute!
		// (since HttpRequest won't remember the host)
		$new_url = $response->getHeader('Location');
		// get absolute url, proxified, and set to new Location.
		$response->addHeaders(array(
			'Location' => $conf->proxify( resolve_url($url, $new_url) ),
		));
		// Take care: This will *NOT* rewrite the body, where the
		// Redirection URL will probably also be send for old browsers.

		// Don't follow yourself the new location but tell it the client
		// since relative links otherwise won't work any more!
		$response->send();
		exit();

		// that was a nasty bug to detect... took me hours
		// $request->setUrl( resolve_url($url, $new_url) );
	} while(1); #$max_follow_redirects-- > 0);
	if(!$max_follow_redirects)
		throw new MsException("Got rewriting loop while redirecting to $url!");

	// now we've got our request finished... (at first ;-) )
} catch(HttpException $ex) {
	echo "Urghs... We got an Exception: $ex";
	return;
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
}

// check if we can skip rewriting because we have binary data
if(stripos($response->getHeader('Content-Type'), 'image') !== false) {
	// we have an image -- send response to client...
	$response->send();
	exit();
}

// Header handling
foreach(array('Content-Type') as $name) {
	$content = $response->getHeader($name);
	if($content) header("$name: $content");
}

$debug = 0;
// debugging
if($debug) {
	echo "DEBUGGING CALL TO <a href='$url'>$url</a>:\n<br>";
	echo 'Seen by pecl extension as:'.$request->getUrl();
	echo '<h2>Request</h2> <pre>'.$request->getRawRequestMessage().'</pre>';
	echo '<h2>Response</h2> <pre>'.$request->getRawResponseMessage().'</pre>';
	echo '<h2>Output headers</h2> <pre>'; print_r(headers_list()); print '</pre>';
	echo '<h2>Content</h2>';
	echo $response->getBody();
	exit;
}

// well, that's our body
$contents = $response->getBody();

// rewrite the page...
$db->driver->rewrite_page($url, $contents);

// and print it out.
echo $contents;

// EOF :-)