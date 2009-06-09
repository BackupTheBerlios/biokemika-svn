<?php

error_reporting(E_ALL);


// bereits global angemeldet

/**
 * Central configuration keys for databases using the
 * proxydriver (will be autofilled if left empty)
 * 
 * start_url         The URL where to start
 * proxy_base        The Proxy base URL
 * 
 *
 **/
class MsProxyDatabaseDriver extends MsDriver {
	const ALLOW_URL_TYPE_REGEX = 'regex';
	const ALLOW_URL_TYPE_DOMAIN = 'domain';
	const ALLOW_URL_TYPE_WILDCARD = 'wildcard';

	private $cat_stack = Null;
	// cached for faster computation
	private $proxify_url_add = '';

	function init() {
		// check parameters
		$this->database->set_default('start_url',   'http://www.google.de');
		$this->database->set_default('proxy_base',  'blabla');
		$start_url_parsed = parse_url($this->database->get('start_url'));
		$this->database->set_default('allow_url', $start_url_parsed['host']);
		$this->database->set_default('allow_url_type',
			self::ALLOW_URL_TYPE_DOMAIN);
	}

	/// this is quite important...
	public function set_cat_stack(MsCategoryStack $stack) {
		$this->cat_stack = $stack;
		$this->proxify_url_add = 
			$this->database->build_query('ms-db').'&'.$stack->build_query('ms-cat');
	}

	/// Transform Real-world URL to Proxified URL
	/// TODO: Check if target URLs are is_allowed() :-)
	///       if not, don't proxify.
	/// Alternative: Let Proxy redirect straight to the NOT-ALLOWED
	///              page!
	/// Problem: Assistant should tell user about that
	/// solution: Redirection page (selfmade) that will quickly updaet
	///         the assistant.
	function proxify_url( $url ) {
		global $msConfiguration;
		return  $msConfiguration['proxy-entry-point'].
			'?action=view&'.
			$this->proxify_url_add.
			'&ms-url='.
			urlencode( $url );
	}

	/// Check wether real world url is allowed to be profixied
	function is_allowed( $url ) {
		$allow_url = $this->database->get('allow_url');
		switch($this->database->get('allow_url_type')) {
			case self::ALLOW_URL_TYPE_WILDCARD:
				throw MsException('Not Yet implemented', MsException::NOT_YET);
			case self::ALLOW_URL_TYPE_DOMAIN:
				#$allow_url = '#^http://([^/]+?)'.preg_quote($allow_url, '#').'/(.+?)#';
				// now take this regex (no break):
				$url_scheme = parse_url($url);
				return strpos($url_scheme['host'], $allow_url) !== false;
			case self::ALLOW_URL_TYPE_REGEX:
				return preg_match($allow_url, $url);
			default:
				throw new MsException("Bad Allow URL type: $allow_url for database ".$this->database->id,
					MsException::BAD_CONFIGURATION);
		}
	}

	private function get_assistant_script() {
		// script injection
		$trigger_id = $this->dispatch_trigger();
		$trigger = $this->database->get('trigger');
		$trigger = $trigger[$trigger_id];

		// get assistant text
		if(isset($trigger['assistant_msg']))
			$assistant_text = wfMsg('assistant_msg');
		else if(isset($trigger['assistant_text']))
			$assistant_text = $trigger['assistant_text'];
		else
			$assistant_text = "Trigger $trigger_id matches, but there's no assistant text.";

		// get assistant
		if(isset($trigger['assistant']))
			$assistant = wfMsg($trigger['assistant']);
		else
			$assistant = 'default assistant';

		$assistant_text = Xml::escapeJsString( $assistant_text );
		$assistant = Xml::escapeJsString( $assistant );
		return <<<EOF
<script type="text/javascript">
/*<![CDATA[*/
	// MediaWiki MetaSearch Assistant Wakeup
	// Code injection by MsProxyDatabaseDriver
	try {
		window.parent.msUpdateAssistant("${assistant_text}", "${assistant}");
	} catch(e) {} // for testing...
/*]]>*/
</script>
EOF;
	}

	/// @returns The ID in the $this->database->get('trigger') array,
	///          if nothing found, false.
	private function dispatch_trigger() {
		foreach($this->database->get('trigger') as $_id => $trigger) {
			extract($trigger); # a bit of PHP magic ;-)
			if(isset($url)) {
				if(preg_match($url, $this->rewrite_url))
					return $_id;
			}
		}
		// nothing found!
		return false;
	}

	/// The real rewriting page thingy
	public $rewrite_url = Null;
	public $rewrite_content = Null;
	function rewrite_page( $url, &$content ) {
		$this->rewrite_url = $url; # for global access
		$this->rewrite_content =& $content; # for global rw(!) access

		if(!$this->rewrite_before())
			return;

		# 1. Hook before <body> tag
		$content = preg_replace(
			'#<\s*body\s#i',
			$this->get_assistant_script()."\n<body ",
			&$content
		);

		# 2. General URL rewriting
		$content = preg_replace_callback(
			# this is all the core magic ;-) :
			'#(<\s*(a|script|style|link|i?frame|object|img|form).+?(?:href|src|url|background|codebase|archive|action)=[\\\\"\']+)(.+?)(["\'\\\\]+.+?>)#si',
			array(&$this, 'rewrite_link_helper'),
			&$content
		);

		# 3. URL rewriting in CSS (inline or CSS files)
		$content = preg_replace_callback(
			'#((url)\()(.+?)(\))#si',
			array(&$this, 'rewrite_link_helper'),
			&$content
		);

		# 4. Hook for <form> tag
		$content = preg_replace_callback(
			'#<\s*form.+?>#si',
			array(&$this, 'rewrite_form_helper'),
			&$content
		);

		$this->rewrite_execute();
	}

	/// To be overwritten by extending classes.
	public function rewrite_execute() { }

	/// Like rewrite_execute, can do forework or
	/// stop rewriting.
	/// @returns true if rewrite process shall start
	public function rewrite_before() { return true; }

	/// Will rewrite URLs, evv. proxify them
	private function rewrite_link_helper($m) {
		// 1=pre, 2=tag name, 3=url, 4=post
		$tag = strtolower($m[2]);
		// javascript links or inner page links (hashes)
		if(preg_match('/^javascript|^#/i', $m[3]))
			return $m[0];
		if($tag == 'img' || $tag == 'url') # url => CSS thing
			return $m[1].resolve_url($this->rewrite_url, $m[3]).$m[4];
		else
			return $m[1].$this->proxify_url(
				resolve_url($this->rewrite_url, $m[3])).$m[4];
	}

	/// Will attach own Input elements to Forms
	// POST Form: The Get parameters in the action will already be interpreted correctly
	// GET Form: The Get parameters in the action are ignored.
	// In any case we'll add the parameters here via hidden elements
	private function rewrite_form_helper($m) {
		$r = $m[0];
		$r .= '<input type="hidden" name="ms-db" value="'.$this->database->id.'">';
		if(!$this->cat_stack) return $r;
		foreach($this->cat_stack->get_all() as $cat) {
			$r .= '<input type="hidden" name="ms-cat[]" value="'.$cat->id.'">';
		}
		return $r;
	}

	function strip_proxy_post_fields($post_array) {
		// I wish there was an usable grep/map implemention in PHP ;-)
		foreach($post_array as $k=>$v) {
			if(preg_match('/^ms-/i', $k))
				unset($post_array[$k]);
		}
		return $post_array;
	}

	/// can use $_COOKIES, if Null
	function strip_cookies($cookie_array=Null) {
		if(!$cookie_array) $cookie_array = $_COOKIE;
		foreach($cookie_array as $k=>$v) {
			if(preg_match('/BioKemika/i', $k))
				unset($cookie_array[$k]);
		}
		return $cookie_array;
	}
}


/********************* helper functions (quite global) *******************/

#var_dump(	
#	resolve_url("http://www.technikum29.de/foo/", "ca/b/c")
#);
#exit(0);

/**
 * From php.net, parseurl():
 * 
 * Resolve a URL relative to a base path. This happens to work with POSIX
 * filenames as well. This is based on RFC 2396 section 5.2.
 * 
 * Will get an absolute URL from a relative one.
 */
function resolve_url($base, $url) {
        if (!strlen($base)) return $url;
        // Step 2
        if (!strlen($url)) return $base;
        // Step 3
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") {
                // Step 2 (fragment)
                $base['fragment'] = substr($url, 1);
                return glue_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
                // Step 4
                return glue_url(array(
                        'scheme'=>$base['scheme'],
                        'path'=>$url,
                ));
        } else if ($url{0} == "/") {
                // Step 5
                $base['path'] = $url;
        } else {
                // Step 6
                $path = explode('/', $base['path']);
                $url_path = explode('/', $url);
                // Step 6a: drop file from base
                array_pop($path);
                // Step 6b, 6c, 6e: append url while removing "." and ".." from
                // the directory portion
                $end = array_pop($url_path);
                foreach ($url_path as $segment) {
                        if ($segment == '.') {
                                // skip
                        } else if ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                                array_pop($path);
                        } else {
                                $path[] = $segment;
                        }
                }
                // Step 6d, 6f: remove "." and ".." from file portion
                if ($end == '.') {
                        $path[] = '';
                } else if ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                        $path[sizeof($path)-1] = '';
                } else {
                        $path[] = $end;
                }
                // Step 6h
                $base['path'] = join('/', $path);

        }
        // Step 7
        return glue_url($base);
}

/// reverse to parse_url,
/// by php.net
function glue_url($parsed) { 
    if (!is_array($parsed)) { 
        return false; 
    } 

    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : ''; 
    $uri .= isset($parsed['user']) ? $parsed['user'].(isset($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : ''; 
    $uri .= isset($parsed['host']) ? $parsed['host'] : ''; 
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : ''; 

    if (isset($parsed['path'])) { 
        $uri .= (substr($parsed['path'], 0, 1) == '/') ? 
            $parsed['path'] : ((!empty($uri) ? '/' : '' ) . $parsed['path']); 
    } 

    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : ''; 
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : ''; 

    return $uri; 
}