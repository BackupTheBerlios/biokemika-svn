<?php
 /*
 ** This is an OLD database/database driver for the QueryDatabase
 ** system (or even much older). There's no need any more for this
 ** file in the current setup.
 */

/**
 * MediaWiki MetaSearch Extension
 * BioKemika Database Driver: WSDL
 * 
 * Database classes can extend the WSDL driver and use
 * it methods to get WSDL support. Metasearch uses
 * NuSOAP as implementation, it's brought you with
 * the Metasearch instalation.
 *
 * (c) Copyright 2009 Sven Koeppel
 *
 * This program is free software; you can redistribute
 * it and/or modify it under the terms of the GNU General
 * Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General
 * Public License along with this program; if not, see
 * http://www.gnu.org/licenses/
 *
 **/

error_reporting(E_ALL);

$msDatabaseCredits['driver'][] = array(
	'path' => __FILE__,
	'id' => 'wsdl',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'Driver for databases using SOAP/WSDL',
);

# FIXME: There is $ms_dir, but it's empty. Strange!
require_once 'extensions/metasearch/lib/nusoap/lib/nusoap.php';

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
