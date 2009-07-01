<?php
error_reporting(E_ALL);

global $msDatabaseDriver;
$msDatabaseDriver['pubmed'] = array(
	'class' => 'MsPubMedDatabaseDriver',
	'view' => 'MsProxyPage',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'PubMed simple rewrites',
);

class MsPubMedDatabaseDriver extends MsProxyDatabaseDriver {
	function rewrite_before() {
		/*
		 * ALTER REWRITE, alles, was jetzt nach Domains aussieht,
		 * wird automatisch umgeschrieben, ist also UNNOETIG.
		 */

		if($this->rewrite_url == 'http://www.ncbi.nlm.nih.gov/portal/js/portal.js') {
			$this->rewrite_content = str_replace(
				'document.cookie=x+"nlm.nih.gov"',
				'document.cookie=x+"biokemika.svenk.homeip.net"',
				$this->rewrite_content
			);
			return false;
		}
		return true;
	}
}

/********************************************************************/

 /*
 ** This is an OLD database/database driver for the QueryDatabase
 ** system (or even much older). There's no need any more for this
 ** file in the current setup.
 */

// This needs the SimpleXML extension activated!!!!
// Remember whiel reemerging php!!!

// Steps:
// 1) Get IDs via eSearch
// 2) Get Title, Abstract, Author, ... via eFetch
// 3) Get References and URLs via eLink
// etc.

/*
$msDriver['pubmed'] = array(
	'class' => 'MsDatabase_pubmed',
	'view' => 'MsQueryPage',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'The NCBI PubMed Database (Entrez interface)',
);

class MsDatabase_pubmed extends MsDatabase {
	public static $email = 'biokemika@gmx.de';

	public function execute(MsQuery $query) {
		$id_array = $this->eSearch('pubmed', $query->keyword);
		# count($id_array) sollte auf groesse gecheckt werden -- overflow!
		$records = $this->eFetch($id_array);
		return new MsResult($records);
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

	public static $elink_base_url = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?';
	function eLink($array_of_ids) {
		$url = self::$elink_base_url.http_build_query(
			array(
				'db' => 'pubmed',
				'email' => self::$email,
				'id' => implode(',',$array_of_ids),
				'cmd' => 'prlinks',
				'retmode' => 'xml',
			)
		);

		$contents = file_get_contents($url);
		if(!$contents)
			throw new MWException('NCBI eLink: GET error');

		try {
			// ID => String (all composed together)
			$ret = array();
			$xml = simplexml_load_string($contents);
			//print_r($xml); exit();
			foreach($xml->LinkSet->IdUrlList->IdUrlSet as $entry) {
				if(isset($entry->ObjUrl)) {
					#var_dump((string)$entry->Id);exit();
					$ret[ (string)$entry->Id ] =
						'<a href="'.$entry->ObjUrl->Url.'">'.
						'<img src="'.
						$entry->ObjUrl->IconUrl
						.'"></a>';
				} else {
					// no links found!
					$ret[(string)$entry->Id] = '';
				}
			}
			#print_r($ret); exit();
			return $ret;
		} catch(Exception $e) {
			throw new MWException('NCBI eLink: XML parsing failed');
		}

		
		// http://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?dbfrom=pubmed&id=10611131&cmd=prlinks
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

		//print_r($xml); exit();
		$records = array();
		foreach($xml->PubmedArticle as $article) {
			$new_record = new MsRecord($this);
			$id = (string) $article->MedlineCitation->PMID[0];
			$new_record->set_data('id', $id);
			$new_record->set_data('title', $article->MedlineCitation->Article->ArticleTitle[0]);
			$new_record->set_data('abstract', $article->MedlineCitation->Article->Abstract->AbstractText);
			$records[$id] = $new_record;
		}

		// get Links (like read-online-icons)
		$links = $this->eLink( array_keys($records) );
		foreach($links as $id => $link) {
			if(!isset($records[$id])) {
				print "INCONSISTENCE: NONEXISTENT $id!\n";
				continue;
			}
			$records[$id]->set_data('links', $link);
			//$records[$id]->set_format('links', 'html');
		}
		

		//var_dump($records);
		//exit(0);
		// since records was something like PUBMED_ID => record
		return array_values($records);
	}
}

*/
