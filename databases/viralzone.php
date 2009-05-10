<?php

# to test it:
error_reporting(E_ALL);

/*
require "../database.php";
*/

class MsDatabase_viralzone extends MsDatabase {
	# for all url compositing
	public static $domain = 'http://www.expasy.org';
	# the search url
	public static $url = 'http://www.expasy.org/viralzone/do/search?query=$1';
	# a simple parser regex
	public static $parser = '#<h4.+?>(.+?)\s+<small>.+?</small></h4>|<a href="(.+?)">(.+?)\s*\(([^()]+)\)</a>#i';
	

	public function execute(MsQuery $query) {
		$records = $this->fetch($query);
		#var_dump($records); exit(0);
		return new MsResult($this, $records);
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
		if(!preg_match_all(self::$parser, strstr($page, 'h3'), $matches, PREG_SET_ORDER))
			throw new Exception('Regex on viral zone output did not match!');
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

/*
# test:
$db = new MsDatabase_viralzone();
$query = new MsQuery();
$query->keyword = 'Herpes';
$query->database = $db;
$query->run();
print "\ndone";
*/
