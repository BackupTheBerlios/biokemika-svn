<?php

# FIXME: There is $ms_dir, but it's empty. Strange!
require_once 'extensions/metasearch/lib/nusoap/lib/nusoap.php';

# to test it:
error_reporting(E_ALL);

abstract class MsWsdlDatabase extends MsDatabase {
	public abstract static $wsdl_file;
	public $soap_client; // object

	public function init() {
		$this->soap_client = new soapclient(self::$wsdl_file, true);
		$err = $this->soap_client->getError();
		if($err)
			throw new MWException("MW Wsdl SOAP Initialisation: $err\n");
	}

	public function debug_soap_result($result) {
		echo '<h2>Request</h2>';
		echo '<pre>' . htmlspecialchars($this->soap_client->request, ENT_QUOTES) . '</pre>';
		echo '<h2>Response</h2>';
		echo '<pre>' . htmlspecialchars($this->soap_client->response, ENT_QUOTES) . '</pre>';
		echo '<h2>Response parsed</h2>';
		print '<pre>'; print_r($result); print '</pre>';
	}
}
