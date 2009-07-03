<?php
/**
 * This file holds the classes
 *  - MsProxyAssistantTrigger
 *  - MsAssistant
 *
 **/

error_reporting(E_ALL);

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
	const TYPE_SIMPLE = 'simple';

	// the name of the key for the default type
	const DEFAULT_TYPE = 'default_type';

	// the name of the key for the boolean connector entry
	const CONNECT_KEY = 'require';

	/// valid values for the connector entry
	const CONNECT_AND = 'and';
	const CONNECT_OR = 'or';


	/// config array (hash) like typical trigger things.
	function __construct( array $rules ) {
		$this->rules = array_change_key_case($rules, CASE_LOWER);

		// set defaults:
		$this->get_rule(self::DEFAULT_TYPE, self::TYPE_SIMPLE);
		$this->get_rule(self::CONNECT_KEY,  self::CONNECT_OR);

		// lowercase the connect key entry
		$this->rules[self::CONNECT_KEY] = strtolower($this->rules[self::CONNECT_KEY]);
	}

	/// for debugging
	function __toString() {
		return "[TRIGGER: ".var_dump_ret($this->rules).']';
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
	 * Will iterate, depending on 'require' setting, throught all
	 * rules and evaluate them.
	 * @returns Boolean
	 **/
	function match( $url, &$content ) {
		$connect = $this->get_rule(self::CONNECT_KEY);

		foreach($this->rules as $rule_key => $rule_value) {
			$result = false;
			switch($rule_key) {
				case 'url':
					$result = $this->exec_rule( $rule_key, $url);
					break;
				case 'title':
					if(!preg_match('#title\s*>(.+?)<\s*/\s*title#is', $content, $m))
						 // no title tag found.
						 // We will skip this rule.
						 // should we (not)?
						continue;
					$result = $this->exec_rule( $rule_key, $m[1]);
					break;
				case 'content':
					$result = $this->exec_rule( $rule_key, $content);
					break;
				default:
					// this will be called whenever something like
					// url_type, title_type, content_type, require,
					// assistant_msg, ... is the current key.
					// Simply skip them:
					continue 2; // continue the foreach loop.
					// without the 2 continue behaves like break.
					// This is PHP logic ;-)
			}

			#print "For $rule_key => result= $result\n";

			switch($connect) {
				case self::CONNECT_OR:
					// implicant => stop evaluating and return true
					if( $result )  return true;
					else           continue 2;
				case self::CONNECT_AND:
					// negative implicant => ANDing got FALSE
					if( !$result ) return false;
					else           continue 2;
				default:
					// illegal value
					throw new MsException("ProxyAssistantTrigger: <i>require</i> field for trigger is bad (must be <tt>AND</tt> or <tt>OR</tt>, is <b>$connect</b>), evaluating on URL <i>$url</i>.",
						MsException::BAD_CONFIGURATION);
			}
		}
		// when we reach here and
		// if $connect == self::CONNECT_OR
		//    then no rule matched (all were false) => return false
		// else if $connect == self::CONNECT_AND
		//    all rules matched (all were true) => return true
		// so
		return ($connect == self::CONNECT_AND);
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

		#print "EXEC RULE $rule_key: type $rule_type, value $rule_value\n";

		switch($rule_type) {
			case self::TYPE_SIMPLE:
				// the most simple string test, case insensitive.
				return (stripos($target, $rule_value) !== false);
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
	/// @param $config_array Must be an Array, else throws Exception.
	function __construct( $config_array ) {
		// using __construct( array $config_array) was not so nice :(
		if(!is_array($config_array))
			throw new MsException("AssistantTrigger: $config_array is not an array.");

		$this->conf = $config_array;

		if(!isset($this->conf['assistant']))
			$this->conf['assistant'] = self::EMPTY_VALUE;
		if(!isset($this->conf['assistant_text']))
			$this->conf['assistant_text'] = self::EMPTY_VALUE;

		# haesslicher hack, um falsche konfiguration umzubiegen.
		# TODO: an richtiger stelle implementieren bzw. generell
		# mal Klarheit bringen in _msg, _text-Dschungel.
		if(isset($this->conf['assistant_msg']))
			$this->conf['assistant_text'] = $this->conf['assistant_msg'];
	}

	public function get_hook() {
		// (sub) iframe injection
		$conf = MsProxyConfiguration::get_instance();

		$html = '<!-- BioKemika Assistant Updater Hook: -->';
		$html .= '<iframe style="display: none;" src="'.$conf->proxy_assistant_url.
			'?'. http_build_query($this->conf).
			'"></iframe>';
		$html .= '<!-- BioKemika Auto Iframe height updater hook -->';
		$target_url = $conf->proxy_assistant_url.'?height=';
		$html .= <<<SCRIPT
<script type="text/javascript">
// This is the MetaSearch iframe height updater injection
//try{
	var old_onload = window.onload;
	var target_url = "$target_url";
	window.onload = function(){
		if (typeof(old_onload)=="function"){
			old_onload();
		}
		height = document.body.scrollHeight + 10;
		document.getElementById('ms-proxy-iframe-height-updater-injection').src =
			target_url + height;
	};
//} catch(e) {}
</script><iframe style="display:none;" id="ms-proxy-iframe-height-updater-injection" 
  src=""></iframe>
SCRIPT
		;
		$html .= '<!-- End of Hook -->';
		return $html;
	}

	public function print_updater() {
		?><html><title>MetaSearch Assistant Updater Frame</title>
		<body>
		<?php
			// gut... Ja... Texte halt durch den Parser jagen.
			// Immerhin soll wiki-markup verwendet werden!
			global $wgParser, $wgOut;
			$wgTitle = Title::newFromText('MetaSearch', NS_SPECIAL); # voellig egal
			if(isset($this->conf['assistant_text'])
				&& $this->conf['assistant_text'] != self::EMPTY_VALUE) {
				$parserOutput = $wgParser->parse( wfMsg($this->conf['assistant_text']),
					$wgTitle, $wgOut->parserOptions(), true);
				$this->conf['assistant_text_content'] = $parserOutput->getText();
			}
			if(isset($this->conf['assistant']) &&
				$this->conf['assistant'] != self::EMPTY_VALUE) {
				$parserOutput = $wgParser->parse( wfMsg($this->conf['assistant']),
					$wgTitle, $wgOut->parserOptions(), true);
				$this->conf['assistant_content'] = $parserOutput->getText();
			}
			$this->conf['empty_value'] = self::EMPTY_VALUE; // quasi meine "NULL"-Referenz.

			//if(wfMsgExists($assistant) && wfMsgExists($assistant_text)) {
				echo '<script type="text/javascript">';
				echo 'window.top.msUpdateProxyPage(';
				$json = json_encode( $this->conf );
				echo $json;
				# vor nutzung von json:
				# echo '"';
				# echo Xml::escapeJsString( $assistant_text );
				# echo '", "';
				# echo Xml::escapeJsString( $assistant );
				# echo '"'";
				echo ');';
				echo '</script>';
				echo "<h3>Debug output</h3>";
				echo '<pre>';
				echo nl2br(htmlentities($json));
				#var_dump( htmlentities($assistant_text), htmlentities($assistant) );
				echo "</pre>\n";
			/*} else {
				echo "<h3>Won't update assistant</h3>";
				echo '<pre>';
				print_r($this->conf);
				echo '</pre>';
			}*/
		?>
		This page updates the MetaSearch assistant in the top frame.
		If this text is displayed in your browser, contact the
		MetaSearch developer, since he has done bullshit ;-)
		</body>
		</html>
		<?php
	}
} // class MsUpdater


/**
 * JSON Encoding
 *
 **/
if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}
