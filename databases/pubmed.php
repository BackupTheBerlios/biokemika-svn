<?php
error_reporting(E_ALL);

// This needs the SimpleXML extension activated!!!!
// Remember whiel reemerging php!!!

// Steps:
// 1) Get IDs via eSearch
// 2) Get Title, Abstract, Author, ... via eFetch
// 3) Get References and URLs via eLink
// etc.

class MsDatabase_pubmed extends MsDatabase {
	public static $email = 'biokemika@gmx.de';

	public function execute(MsQuery $query) {
		$id_array = $this->eSearch('pubmed', $query->keyword);
		# count($id_array) sollte auf groesse gecheckt werden -- overflow!
		$records = $this->eFetch($id_array);
		return new MsResult($this, $records);
	}


	# including trailing ?
	public static $esearch_base_url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?';
	function eSearch($db, $term) {
		$url = self::$esearch_base_url.http_build_query(
			array('db' => $db, 'term' => $term, 'email' => self::$email)
		);

		$contents = file_get_contents($url);
		if(!$contents)
			throw new MWException('NCBI eSearch: GET error');
		if(!preg_match_all('#<Id>(\d+)</Id>#i', $contents, $matching))
			throw new MWException('NCBI eSearch: Bad output layout');

		return $matching[1];
	}

	public static $efetch_base_url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?';
	// will return MsRecords!
	function eFetch($array_of_ids) {
		$url = self::$efetch_base_url.http_build_query(
			array('db' => 'pubmed', 'email' => self::$email,
				'id' => implode(',',$array_of_ids),
				'retmode' => 'xml')
		);

		$contents = file_get_contents($url);
		if(!$contents)
			throw new MWException('NCBI eFetch: GET error');

		//var_dump($url, $contents);

		try {
			$xml = simplexml_load_string($contents);
		} catch(Exception $e) {
			throw new MwException('NCBI eFetch: XML parsing failed');
		}

		//print_r($xml);
		$records = array();
		foreach($xml->PubmedArticle as $article) {
			$new_record = new MsRecord($this);
			$new_record->set_data('id', $article->MedlineCitation->PMID[0]);
			$new_record->set_data('title', $article->MedlineCitation->Article->ArticleTitle[0]);
			$new_record->set_data('abstract', $article->MedlineCitation->Article->Abstract->AbstractText);
			$records[] = $new_record;
		}

		//var_dump($records);
		//exit(0);
		return $records;
	}


}



?>