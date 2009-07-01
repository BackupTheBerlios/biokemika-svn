<?php
 /*
 ** This is an OLD database/database driver for the QueryDatabase
 ** system (or even much older). There's no need any more for this
 ** file in the current setup.
 */

# FIXME: There is $ms_dir, but it's empty. Strange!
require_once 'extensions/metasearch/lib/nusoap/lib/nusoap.php';

# to test it:
error_reporting(E_ALL);

class MsDatabase_pdb extends MsDatabase {
	public static $wsdl_file = 'http://www.pdb.org/pdb/services/pdbws?wsdl';


	public $soap_client; // object

	public function init() {
		$this->soap_client = new soapclient(self::$wsdl_file);
	}

	public function debug_soap_result($result) {
		echo '<h2>Request</h2>';
		echo '<pre>' . htmlspecialchars($this->soap_client->request, ENT_QUOTES) . '</pre>';
		echo '<h2>Response</h2>';
		echo '<pre>' . htmlspecialchars($this->soap_client->response, ENT_QUOTES) . '</pre>';
		echo '<h2>Response parsed</h2>';
		print '<pre>'; print_r($result); print '</pre>';
	}

	public function execute(MsQuery $query) {
		$result = new MsSparseResult($this);
		$result->abstract_records = $this->soap_client->call('keywordQuery', array('in0' => $query->keyword, 'in1' => false, 'in2' => false));
		$result->set_sparse_number( count($result->abstract_records) );

		//$records = $this->fetch($query);
		//var_dump($records); exit();
		return $result;
	}

	function fetch($query) {
		// folgende Daten brauchen wir fuer jeden record:
		// * PDB_ID
		// * Name: getPrimaryCitationTitle(PDB_ID)
		// * 

		// keywordQuery(java.lang.String keywordExpression, boolean exactMatch, boolean restrictToAuthors) 
		$pdbid_array = $this->soap_client->call('keywordQuery', array('in0' => $query->keyword, 'in1' => false, 'in2' => false));
		$this->debug_soap_result($pdbid_array);
		flush();

		$records = array();
		foreach($pdbid_array as $pdbid) {
			$new_record = new MsRecord();
			$new_record->set_data('pdb_id', $pdbid);

			/*echo "Performing name call for $pdbid\n"; flush();
			$name = $this->soap_client->call('getPrimaryCitationTitle', array('in0' => $pdbid));
			echo "Got name: $name\n"; flush();
			$new_record->set_data('name', $name);*/

			$records[] = $new_record;
		}
		return $records;

		/*
		$fetch_url = str_replace('$1', urlencode($query->keyword), self::$url);
		$page = file($fetch_url);
		var_dump($fetch_url, $page); exit();
		if(!$page)
			throw MWException('Performing PDB search failed');
		// typische Zeilen haben so eine Form:
		// [PDB-ID]\t[Label oder sowas]
		$records = array(); // MSRecord array
		foreach($page as $line) {
			$field = explode("\t", $line);
			$new_record = new MsRecord();
			$new_record->set_data('pdb_id', $field[0]);
			$new_record->set_data('name', $field[1]);
			$records[] = $new_record;
		}

		#print "These are the records:<pre>"; var_dump($records); exit;
		return $records;
		*/
	}

	public function generate_record($id, $result) {
		// Well... really generate some record.
		$record = new MsRecord($this);
		$record->set_data('msdb_generator_id', $id); // just for debugging
		$record->set_data('pdb_id', $result->abstract_records[$id]);

		// generate the neccessary data on the fly
		$name = $this->soap_client->call('getPrimaryCitationTitle', array('in0' => 
			$result->abstract_records[$id]));
		$record->set_data('name', $name);

		return $record;
	}

}
