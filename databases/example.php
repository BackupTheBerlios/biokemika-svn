<?php
/**
 * This is an example Metaserach "database driver". That sounds
 * worse than it is: It just implements these classes:
 *  - MsQuery: How to perform that query.
 * Generally, these drivers are abstract concepts like
 * "XML access" or "HTTP Access".
 **/ 

class MsDatabase_example extends MsDatabase {
	#function __construct($fields) {
	#	parent::__construct($fields);
	#	if(!isset($this->fields['keyword']))
	#		throw MWException('Missing "keyword" field in MSExampleQuery instance!');
	#}
	

	function execute(MsQuery $query) {
		$keyword = $query->keyword;

		$result = new MsResult($this);
		foreach(preg_split('/[\s,]+/', $keyword) as $word) {
			switch(strtolower($word)) {
				case 'wagner':
				case 'andre':
				case 'sven':
					$result->records[] = new MsRecord('Biokemika developers', "http://www.biokemika.org/", 'Das sind sie! ;-)');
					break;
				case 'google':
					$result->records[] = new MsRecord('The almighty muellhalde', 'http://www.google.de');
					break;
				case 'bio':
				case 'chemie':
				case 'protein':
					$result->records[] = new MsRecord('BioChemie for Dummys', 'http://download.from.andre/exclusively', ';-)');
					break;
			}
		} // for
		return $result;
	} // function
} // class