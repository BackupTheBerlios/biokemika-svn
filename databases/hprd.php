<?php
/**
 * MediaWiki MetaSearch Extension
 * BioKemika Database: HPRD
 * 
 * Status:
 *     Komplett fertig.
 * Dokumentation:
 *    http://biokemika.uni-frankfurt.de/wiki/BioKemika:Datenbanken/HPRD
 * Record-Daten:
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

$msDatabaseCredits['database'][] = array(
	'path' => __FILE__,
	'id' => 'hprd',
	'page' => 'BioKemika:Datenbanken/HPRD',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'Human Protein Ressource Database',
);

class MsDatabase_hprd extends MsDatabase {
	public static $search_url = 'http://www.hprd.org/resultsQuery?multiplefound=&prot_name=$1&external=Ref_seq&accession_id=&hprd=&gene_symbol=&chromo_locus=&function=&ptm_type=&localization=&domain=&motif=&expression=&prot_start=&prot_end=&limit=0&mole_start=&mole_end=&disease=&query_submit=Search';

	# for links and images, trailing slash included
	public static $ref_base = 'http://www.hprd.org/';

	public function execute(MsQuery $query) {
		$records = $this->fetch($query);

		$result = new MsResult($records);
		return $result;
	}

	function fetch($query) {
		$fetch_url = str_replace('$1', urlencode($query->keyword), self::$search_url);
		$search_page = file_get_contents($fetch_url);

		// check wether this was an *exact* search:
		// that $http_response_header seems to be set automatically...
		foreach($http_response_header as $header) {
			if(preg_match('/^Location: (.+)$/i', $header, $match)) {
				$rec = new MsRecord($this, 'success');
				$rec->set_data['url'] = self::$ref_base.$match[1];
				return array($rec);
			}
		}
		

		if(!$search_page)
			throw new MWException('HPRD: get search page failed.');

		/* Typisches Format der Ausgaben:
          <tr> 
            <td width="13%" rowspan="2" valign="top" bgcolor="#f9f9f9">1</td>
            <td bgcolor="#f9f9f9" valign="top" width="28%" nowrap><span class="boxhead">Name 
              &nbsp;:&nbsp;</span><a href="summary?hprd_id=01284&isoform_id=01284_1&isoform_name=Isoform_1">VAV1</a> </td>
            <td width="59%" valign="top" bgcolor="#f9f9f9"><span class="boxhead">Molecule 
              Function&nbsp;:&nbsp;</span>Guanyl-nucleotide exchange factor activity</td>
          </tr>
          <tr> 
            <td bgcolor="#ffffff" valign="top" colspan="2"><img src="graph/01284_1.png" usemap=#01284 border="0"><br><span class="boxhead">
              Number of Interactions&nbsp;:&nbsp;62</td>
          </tr>
		 */

		// Wenn nix gefunden:
		if(preg_match('/Sorry, the query did not fetch any results/', $search_page)) {
			return array(); # leeres Recordarray.
		}

		// URLs and Names
		if(!preg_match_all('#>Name(?:.+?)<a href="(.+?)">(.+?)</a>#si', $search_page, $urls, PREG_SET_ORDER))
			throw new MWException('HPRD: Regex1 (url names) doesnt match.');
		// Molecular Function (Full Name)
		if(!preg_match_all('#>Molecule\s+Function(?:.+?)</span>(.+?)</td>#i', $search_page, $full_names, PREG_SET_ORDER))
			throw new MWException('HPRD: Regex2 (molecular function) doesnt match');
		// corresponding image
		if(!preg_match_all('#<img src="(graph/(?:.+?))"#i', $search_page, $images, PREG_SET_ORDER))
			throw new MWException('HPRD: Regex3 (image) doesnt match');
		// interactions
		if(!preg_match_all('#Number of Interactions(?:.+?)(\d+)#i', $search_page, $interactions, PREG_SET_ORDER))
			throw new MWException('HPRD: Regex4 (# interaction) doesnt match');

		/*
		// debugging:
		print '<pre>';
		//print $search_page;
		print_r($urls);
		print_r($full_names);
		print_r($images);
		print_r($interactions);
		//exit(0);
		*/
		

		// check out arrays on integrity
		$size = count($urls);
		if($size != count($full_names) || $size != count($images) || $size != count($interactions))
			throw new MWException('HPRD: Result integration failed!');

		// delete page since we don't need it any more
		unset($search_page);

		$records = array($size);
		for($x=0;$x<$size;$x++) {
			$records[$x] = new MsRecord($this);
			$records[$x]->set_data('number', $x+1);
			$records[$x]->set_data('url', self::$ref_base.$urls[$x][1]);
			$records[$x]->set_data('name', $urls[$x][2]);
			$records[$x]->set_data('molecule_function', $full_names[$x][1]);
			$records[$x]->set_data('image', self::$ref_base.$images[$x][1]);
			$records[$x]->set_data('interactions', $interactions[$x][1]);
		}

		//var_dump($records);

		return $records;
	}
}

?> 
