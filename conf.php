<?php
/* this file hold configuration as long as it doesn't
   reside in mediawiki namespace */

global $msCategories,$msDatabases,$msCategoryHits, $msConfiguration;

$msConfiguration = array(
	#'default-database-record-message' => 
	'default-record-message' => 'Ms-record'
);

$msDatabases = array();

$msDatabases['example'] = array(
	'name' => 'Metasearch example database',
	'article' => 'NCBI',
);

$msDatabases['viralzone'] = array(
	'include' => 'databases/viralzone.php',
);

$msDatabases['pdb'] = array(
	'include' => 'databases/pdb.php'
);

$msCategories = array();
$msCategoryHits = array();

//   => if string => only one database
//   => if array: array(name of db, priority, relevance)
$msCategories['viren'] = array(
	'viralzone'
);
$msCategoryHits['viren'] = 10;

$msCategories['protein'] = array(
	 'pdb'
);

$msCategoryHits['protein'] = 15;

$msCategoryHits['example1'] = 10;

$msCategories['example1'] = array(
	array('example', 0.2, 0.7),
	array('viralzone', 0.8, 0.3)
);
$msCategoryHits['example1'] = 10;

$msCategories['example2'] = array(
	// alle wie example1... + protein
);