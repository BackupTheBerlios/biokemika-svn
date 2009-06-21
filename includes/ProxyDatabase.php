<?php

error_reporting(E_ALL);

/**
 * Soll dann Konfiguration auslesen, von sowas wie
 *   MediaWiki:ms-proxy-domains
 * oder so, im Format
 * 
 * # Domain         Datenbank
 * www.blo.bli                 # heisst: Ist erlaubt
 * example.com      ncbi       # heisst: ncbi domain laden
 * 
 * Wenn keine Domain zugewiesen... dann... erfinden wir
 * dafuer ne Default Configuration Message, die dem 
 * Driver uebergeben wird.
 **/
class MsProxyConfiguration extends MsMsgConfiguration {
	// the default database, must be installed like
	// MediaWiki:ms-defaultproxy-database.
	// You don't need such a thing unless you want
	// proxify domains without any specific assisstant
	// help.
	const DEFAULT_DB = 'pubmed';

	/// including trailing dot
	public $proxy_domain;// = '.proxy.biokemika.svenk.homeip.net';
	/// *The* URL path to your proxy.php thingy
	public $proxy_assistant_url;// = 'http://biokemika.svenk.homeip.net/extensions/metasearch/assistant.php';
	/// The config array
	/*
	private $domains = array(
		'nih.gov' => 'pubmed',
		'expasy.ch' => self::DEFAULT_DB,
		'ebi.ac.uk' => self::DEFAULT_DB,
		'hprd.org' => self::DEFAULT_DB,
		'pdb.org' => self::DEFAULT_DB,
		'abcam.com' => self::DEFAULT_DB,
		'itrust.de' => self::DEFAULT_DB,
		'aist.go.jp' => self::DEFAULT_DB,
		'atcc.org' => 'atcc',
		'atcc.com' => 'atcc',
		'wisc.edu' => self::DEFAULT_DB,
		'ottobib.com' => self::DEFAULT_DB,

		'svenk.homeip.net' => self::DEFAULT_DB
	);
	*/

	/// singleton pattern
	private function __construct() {
		global $msConfiguration;

		$this->read_configuration('ms-databases');
		$this->proxy_domain = $msConfiguration['proxy_domain'];
		$this->proxy_assistant_url = $msConfiguration['proxy_assistant_url'];
	}

	static private $singleton = Null;
	function get_instance() {
		if(!self::$singleton)
			self::$singleton = new MsProxyConfiguration();
		return self::$singleton;
	}

	function create_database($url) {
		$parse = parse_url( $this->deproxify($url) );
		return $this->create_database_for_domain($parse['host']);
	}

	/// @param $domain Any Domain Name
	/// @returns MsDatabase
	function create_database_for_domain($domain) {
		$reg_domain = $this->get_registered_domain($domain);
		if(!$reg_domain)
			throw new MsException("$domain is not allowed to be proxified!");
		if(! $this->has_set($reg_domain) )
			throw new MsException("No database given for $domain");
		return new MsDatabase( $this->get($reg_domain) );
	}

	// checks if url is allowed to be proxified
	function is_allowed($url) {
		$parse = parse_url($url);
		return $this->is_allowed_domain($parse['host']);
	}

	// use in favour of is_allowed($url)
	/// @param $domain Any domain name
	function is_allowed_domain($domain) {
		return ($this->get_registered_domain($domain) != Null);
	}

	// looks up a registered domain. E.g. $any_domain=www.google.de,
	// in the domains array only "google.de", this will return google.de
	// Returns Null if no domain registered.
	/// @param $any_domain Any Domain name
	/// @returns A registered domain (=key in $this->domains) or Null, if not found
	function get_registered_domain($any_domain) {
		$any_domain = strtolower($any_domain);
		foreach($this->conf as $reg_domain => $v) {
			if(strpos($any_domain, $reg_domain) !== false)
				return $reg_domain;
		}
		return Null;
	}

	/// @return all registered domains
	function get_all_domains() {
		return array_keys($this->conf);
	}

	/// @return all registered domains, proxified
	function get_all_domains_proxified() {
		$r = array();
		foreach(array_keys($this->conf) as $domain) {
			$r[] = $domain . $this->proxy_domain;
		}
		return $r;
	}

	/// get deproxified = absolute url
	function deproxify($url) {
		return str_ireplace($this->proxy_domain, '', $url);
	}

	/// make absolute url to proxified url
	function proxify($url) {
		// http_bild_url in pecl-http (dependency for proxy part)
		return http_build_url($url,
			array('host' => parse_url($url, PHP_URL_HOST).$this->proxy_domain)
		);
	}

	/// get request url, deproxified
	function get_request_url() {
		$host = str_ireplace($this->proxy_domain, '', $_SERVER['HTTP_HOST']);
		return 'http://'.$host.$_SERVER['REQUEST_URI'];
	}
}

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
	public $conf; ///< just a local link to MsProxyConfiguration.

	function init() {
		// check and set default parameters
		$this->database->set_default('start_url',   'http://www.google.de');
		$this->database->set_default('domain',      'www.google.de');

		// Trigger initialisieren: Array mit Trigger-Objekten!
		// Nicht hier, erst wenn man sie braucht.

		$this->conf = MsProxyConfiguration::get_instance();
	}
	/// get the Assistant for current rewrite context by
	/// executing all triggers
	/// @returns MsAssistant object
	function get_assistant() {
		$triggers = $this->create_triggers();
		foreach($triggers as $trigger) {
			if($trigger->match($this->rewrite_url, $this->rewrite_content))
				return $trigger->get_assistant();
		}
		// no trigger matched. Return default assistant...
		$default_array = $this->database->get('default');
		return new MsAssistant($default_array);
	}

	/// @returns an array of trigger objects based on the configuration.
	function create_triggers() {
		$trigger_array = array();
		foreach($this->database->get('trigger') as $trigger_conf) {
			$trigger_array[] = new MsProxyAssistantTrigger($trigger_conf);
		}
		return $trigger_array;
	}

	/// The real rewriting page thingy
	public $rewrite_url = Null;
	public $rewrite_content = Null;
	public $rewrite_is_html = true;
	function rewrite_page( $url, &$content, $is_html ) {
		$this->rewrite_url = $url; # for global access
		$this->rewrite_content =& $content; # for global rw(!) access
		$this->rewrite_is_html = $is_html;

		if(!$this->rewrite_before())
			return;


		# General Domain rewriting
		$content = str_ireplace(
			$this->conf->get_all_domains(),
			$this->conf->get_all_domains_proxified(),
			&$content
		);

		# Domain rewriting using regexp:
		#$content = preg_replace(
		#	'#expasy.ch|nlm.nih.gov|ebi.ac.uk|hprd.org|pdb.org|abcam.com|itrust.de|aist.go.jp|atcc.org|atcc.com|wisc.edu|ottobib.com#i',
		#	'\0.proxy.biokemika.svenk.homeip.net',
		#	&$content
		#);

		# Only for perfomance (faster page loading):
		# URL rewriting in images, flash, java:
		$content = preg_replace_callback(                                                        /* .+?> */
			'#(<\s*(?:img|object|embed|applet).+?(?:src|background|codebase|archive)=["\'])(.+?)(["\'])#si',
			array(&$this, 'rewrite_link_helper'),
			&$content
		);

		# Domain *back*writing in CSS and inline CSS:
		$content = preg_replace_callback(
			// [\s:{]; to skip false positives like in
			//    function GetMyUrl(any,javascript,param) {
			// in a javascript. We only like things like
			//   background-image:url(...
			//   @import url("...
			// important is the trailing ";"
			// alternative: [^{]*; after the closing \)
			'#([\s:]url\(\s*["\']?)(.+?)(\s*["\']?\))#si',
			array(&$this, 'rewrite_link_helper'),
			&$content
		);

		# 1. Hook before <body> tag
		/*$content = preg_replace(
			'#<\s*body\s#i',
			$assistant->get_script()."\n<body ",
			&$content
		);*/

		# Hook after <body> tag
		if($is_html) {
			// rewrite only HTML pages (not scripts!)
			$content = preg_replace_callback(
				'#<s*body.*?>#i',
				array(&$this, 'rewrite_assistant_hook'),
				&$content
			);
		}


		/*
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
		*/

		//$this->rewrite_execute();
	}

	/// To be overwritten by extending classes.
	public function rewrite_execute() { }

	/// Like rewrite_execute, can do forework or
	/// stop rewriting.
	/// @returns true if rewrite process shall start
	public function rewrite_before() { return true; }

	/// Will rewrite URLs to absolute ones and
	/// deproxify them. Used in images, flash, java, CSS for
	/// page loading perfomance.
	private function rewrite_link_helper($m) {
		// 1=pre, 2=url, 3=post

		// false positive: Typical javascript like
		//   var foo = '<img src="'+other_variable[i]+'">';
		// skip these things.
		if($m[2]{0} == '"' || $m[2]{0} == "'")
			return $m[1].$m[2].$m[3];

		// 1. step: resolve absolute URL (if e.g. relative given)
		// 2. step: deproxify (only neccessary if absolute was given)
		return $m[1].$this->conf->deproxify(
			resolve_url($this->rewrite_url, $m[2])
		).$m[3];
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

	// The only feature of this helper function is efficency:
	// All the triggers are only evaluated when the </body>
	// regexp matches in the rewrite engine. If not (e.g. Script,
	// CSS, etc. pages), it will never be executed -- no
	// overhead (stupid "lazy evaluation" implementation for PHP ;-) )
	private function rewrite_assistant_hook($m) {
		$assistant = $this->get_assistant();
		return $m[0].$assistant->get_hook();
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

/**
 * Can be used like
 * 
 * $trigger = new MsProxyAssistantTrigger($your_configuration_from_database);
 * if( $trigger->matches($your_url, $your_content) )
 *      $dont_need_to_use_default = $trigger->get_assistant($msg, $text);
 *
 **/
class MsProxyAssistantTrigger {
	/**
	 * The rules array can hold parts like (all lowercased!)
	 * 
	 * special:
	 *  - condition: Some thing that combines trigger entries
	 *               in boolean expressions like AND, OR,
	 *               Parentheses ( ), etc.
	 *               If none given, all rules will be OR
	 *               connected.
	 *
	 * rules:
	 *  - url: on the url
	 *  - title: on the <title> tag content
	 *  - content: on the *complete* content
	 * and for each rule $i
	 *  - $i_type: one of the class's constants TYPE_REGEX,
	 *    TYPE_WILDCARD, TYPE_EXACT
	 *  
	 *  data:
	 *  - assistant (allways have to be a message name)
	 *  - assistant_text, that will be used in favour of
	 *  - assistant_msg
	 *
	 **/
	private $rules;

	/// types for rules
	const TYPE_REGEX = 'regex';
	const TYPE_WILDCARD = 'wildcard';
	const TYPE_EXACT = 'exact';

	// the name of the key for the default type
	const DEFAULT_TYPE = 'default_type';

	/// config array (hash) like typical trigger things.
	function __construct( array $rules ) {
		$this->rules = array_change_key_case($rules, CASE_LOWER);

		// set defaults:
		$this->get_rule(self::DEFAULT_TYPE, self::TYPE_WILDCARD);
	}

	/// does exactly what you think it does.
	/// @param $default_value Also *SETS* this rule to default value :-)
	function get_rule( $key, $default_value=Null ) {
		if(!isset($this->rules[$key])) {
			if($default_value != Null)
				$this->rules[$key] = $default_value;
			return $default_value;
		} else
			return $this->rules[$key];
	}

	/// @returns MsAssistant object created by this trigger
	function get_assistant() {
		return new MsAssistant($this->rules);
	}

	/**
	 * Returns true if this trigger matches the url/content pair.
	 * Will iterate throught all rules and evaluate them until
	 * one matches.
	 * @returns Boolean
	 **/
	function match( $url, &$content ) {
		foreach($this->rules as $rule_key => $rule_value) {
			switch($rule_key) {
				case 'url':
					return $this->exec_rule( $rule_key, $url);
				case 'title':
					if(!preg_match('#title\s*>(.+?)<\s*/\s*title#is', $content, $m))
						 // no title tag found
						continue;
					return $this->exec_rule( $rule_key, $m[1]);
				case 'content':
					return $this->exec_rule( $rule_key, $content);
				default:
					// should be nonfatal, continueing
					//throw new MsException("Rule '$rule_key' (value '$rule_value') not known! Rule should be 'url', 'title' or 'content'",
					//	MsException::BAD_CONFIGURATION);
					// TODO: Generate warning, fetchable
			}
		}
		// when we reach here, no rule matched.
		return false;
	}

	/// exec a rule on a target.
	/// @returns TRUE if rule matches, FALSE otherwise
	function exec_rule( $rule_key, &$target ) {
		if(!isset($this->rules[$rule_key]))
			throw new MsException("$this does not have $rule_key rule key.",
				MsException::BAD_CONFIGURATION);

		$rule_value = $this->get_rule($rule_key);
		$rule_type_key = $rule_key . '_type';
		$rule_type = $this->get_rule($rule_type_key,
			$this->get_rule(self::DEFAULT_TYPE));
		switch($rule_type) {
			case self::TYPE_WILDCARD:
				// urghs... we need a wildcard interpreter
				return match_wildcard($rule_value, $target);
			case self::TYPE_REGEX:
				/// FIXME: Throw an Exception if regexp is not valid!
				return preg_match($rule_value, $target);
			case self::TYPE_EXACT:
				// magic ;-)
				return ($rule_value == $target);
			default:
				throw new MsException("Bad Rule Type: $rule_type (looked up in $rule_type_key) for $rule (value: $rule_value)",
					MsException::BAD_CONFIGURATION);
		}
	}
}

/// An Assistant object. This holds assistant message, assistant
/// type, etc. and can create <script> hooks, etc. -- nice things
/// :-)
class MsAssistant {
	// format of these values: only the name of the message, without
	// "MediaWiki:" in the front.
	// shall contain:
	// assistant        ///<- MediaWiki message for assistant himself
	// assistant_text   ///<- MediaWiki message for the text
	public $conf;

	// just to notify that there's nothing set
	// hmpf, "EMPTY" is reserved in PHP :/
	const EMPTY_VALUE = '!EMPTY!';

	/// construct by configuration array
	function __construct( array $config_array ) {
		/*
		// get assistant text
		if(isset($config_array['assistant_msg']))
			$this->assistant_text = wfMsg('assistant_msg');
		else if(isset($config_array['assistant_text']))
			$this->assistant_text = $config_array['assistant_text'];
		else
			$this->assistant_text = Null;

		// get assistant
		if(isset($config_array['assistant']))
			$this->assistant = wfMsg($config_array['assistant']);
		else
			$this->assistant = Null;
		*/
		$this->conf = $config_array;

		if(!isset($this->conf['assistant']))
			$this->conf['assistant'] = self::EMPTY_VALUE;
		if(!isset($this->conf['assistant_text']))
			$this->conf['assistant_text'] = self::EMPTY_VALUE;
	}

	public function get_hook() {
		// (sub) iframe injection
		$conf = MsProxyConfiguration::get_instance();

		$html = '<!-- BioKemika Assistant Updater Hook: -->';
		$html .= '<iframe style="display: none;" src="'.$conf->proxy_assistant_url.
			'?'. http_build_query($this->conf).
			'"></iframe>';
		$html .= '<!-- End of Hook -->';
		return $html;
	}

	public function print_updater() {
		?><html><title>MetaSearch Assistant Updater Frame</title>
		<body>
		<?php
			extract($this->conf); // I love this stupid PHP kind-of-magic ;-)
			
			if(wfMsgExists($assistant) && wfMsgExists($assistant_text)) {
				echo '<script type="text/javascript">';
				echo 'window.top.msUpdateAssistant("';
				echo Xml::escapeJsString(wfMsg($this->conf['assistant_text']));
				echo '", "';
				echo Xml::escapeJsString(wfMsg($this->conf['assistant']));
				echo '");';
				echo '</script>';
			} else {
				echo "<h3>Won't update assistant</h3>";
				echo '<pre>';
				print_r($this->conf);
				echo '</pre>';
			}
		?>
		This page updates the MetaSearch assistant in the top frame.
		If this text is displayed in your browser, contact the
		MetaSearch developer, since he has done bullshit ;-)
		</body>
		</html>
		<?php
	}
}


/********************* helper functions (quite global) *******************/

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

/**
 * A simple wildcard matcher. Written on myself, posted to php.net
 * @param $wildcard_pattern The wildcard pattern
 * @param $haystack Where to write to
 * @returns TRUE or FALSE
 **/
function match_wildcard( $wildcard_pattern, $haystack ) {
	$regex = str_replace(
		array("\*", "\?"), // wildcard chars
		array('.*','.'),   // regexp chars
		preg_quote($wildcard_pattern, '/')
	);

	return preg_match('/^'.$regex.'$/is', $haystack);
}