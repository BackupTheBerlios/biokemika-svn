<?php
 /*
 ** This is an OLD database/database driver for the QueryDatabase
 ** system (or even much older). There's no need any more for this
 ** file in the current setup.
 */

/**
 * MediaWiki MetaSearch Extension
 * BioKemika Database: ViralZone
 * 
 * Status:
 *     Komplett fertig.
 * Dokumentation:
 *    http://biokemika.uni-frankfurt.de/wiki/BioKemika:Datenbanken/Viral_Zone
 * Record-Daten:
 *   - links
 *   - baltimore, family, genus
 *   - baltimore_link, family_link, genus_link
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
	'id' => 'viralzone',
	'page' => 'BioKemika:Datenbanken/ViralZone',
	'author' => 'Sven Koeppel',
	'version' => '$Id$',
	'description' => 'The Expasy Viralzone database',
);

class MsDatabase_viralzone extends MsDatabase {
	# for all url compositing
	public static $domain = 'http://www.expasy.org';
	# the search url
	public static $url = 'http://www.expasy.org/viralzone/do/search?query=$1';
	# a simple parser regex
	public static $parser = '#<h4.+?>(.+?)\s+<small>.+?</small></h4>|<a href="(.+?)">(.+?)\s*\(([^()]+)\)</a>#i';
	

	public function execute(MsQuery $query) {
		$records = $this->fetch($query);
		$result = new MsResult($records);

		#$result->dump($records); exit(0);

		return $result;
	}

	function fetch($query) {
		$fetch_url = str_replace('$1', urlencode($query->keyword), self::$url);
		$page = file_get_contents($fetch_url);
		if(!$page)
			throw MWException('Performing viral zone search failed');
		// typische Eintraege haben so einen Form:
		// alles ohne Leerzeichen!
		/*
			<h4 class="column_name">VIRUSNAME <small>[SHORTFORM]</small></h4>
			<div class="column_hr"><div class="context">
			<a href="/viralzone/all_by_species/236.html">dsDNA (baltimore)</a><br />
			<a href="/viralzone/all_by_species/176.html">Herpesviridae (family)</a><br />
			<a href="/viralzone/all_by_species/526.html">Macavirus (genus)</a><br /></div></div>
		*/
		// Let's get the genus... don't know if thats right ;-)
		if(!preg_match_all(self::$parser, strstr($page, 'h3'), $matches, PREG_SET_ORDER)) {
			// no matches... so let's check wether we got anything
			if(preg_match('/NO VIRUS FOUND/i', $page))
				// return... an empty record array.
				return array();
			else
				// the parsing seems to be broken
				throw new MWException('Regex on viral zone output did not match!');
		}
		// Don't need to much memory -- delete page
		unset($page);
		// We'll have pretty much matchings. Parse them
		$records = array(); // MsRecord array // wanted format: virusname => array(link label=>full link url)
		$latest_record = false; // will hold the latest record
		#var_dump($matches); exit(0);
		foreach($matches as $entry) {
			// since the regex always contains all parentheses, [1] is only
			// filled when we have matched a h4, starting a new record.
			if(strlen($entry[1])) {
				#print("Adding new record; $entry[1].\n");
				if($latest_record) $records[] = $latest_record;
				$latest_record = new MsRecord($this);
				$latest_record->set_data('name', $entry[1]);

				# fill fields stupidly with default values:
				$latest_record->set_data('baltimore_link', '');
				$latest_record->set_data('family_link', '');
				$latest_record->set_data('genus_link', '');
				$latest_record->set_data('baltimore', "''Baltimore nicht angegeben''");
				$latest_record->set_data('family', "''Familie nicht angegeben''");
				$latest_record->set_data('genus', "''Genus nicht angegeben''");


				#$latest_virus = $entry[1];
				#$records[$latest_virus] = array();
			} else {
				#print("Found nice other information concerning $entry[4]\n");
				# something like 'family' => 'Herpesviridae'
				$latest_record->set_data( $entry[4], $entry[3] );
				# and something like 'family_link' => '/viralzone/all_by_species/...'
				$latest_record->set_data( $entry[4].'_link', self::$domain . $entry[2]);
			}
			#flush();
		}
		# and for the very last item:
		if($latest_record) $records[] = $latest_record;
#		print "These are the records:<pre>"; var_dump($records);
		# create "best links" for each record:
		/*foreach($records as $record) {
			if($record->get_data('genus'))
				$record->set_data('link', $record->get_data('genus_link'));
			else if($record->get_data('family'))
				$record->set_data('link', $record->get_data('family_link'));
			else if($record->get_data('baltimore'))
				$record->set_data('link', $record->get_data('baltimore_link'));
			else	$record->set_data('link', 'http://www.google.dei);
		}*/
		return $records;
	}

} // class
