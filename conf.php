<?php
/* this file hold configuration as long as it doesn't
   reside in mediawiki namespace */

global $msCategories,$msDatabases,$msCategoryHits, $msConfiguration;

$msConfiguration = array(
	#'default-database-record-message' => 
	'default-record-message' => 'Ms-record',
	'root-category-name'  => 'root'
);

/*
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

$msDatabases['pdbsum'] = array(
	'include' => 'databases/pdbsum.php'
);

$msDatabases['sdbs'] = array(
	'include' => 'databases/sdbs.php'
);

$msDatabases['hprd'] = array(
	'include' => 'databases/hprd.php'
);

$msDatabases['pubmed'] = array(
	'include' => 'databases/pubmed.php'
);
*/
/*

$msCategoryHits = array();

//   => if string => only one database
//   => if array: array(name of db, priority, relevance)
$msCategories['viren'] = array(
	'viralzone'
);
$msCategoryHits['viren'] = 10;

$msCategories['protein (pdb)'] = array(
	 'pdb'
);
$msCategoryHits['protein (pdb)'] = 15;

$msCategories['protein (pdbsum)'] = array(
	'pdbsum'
);
$msCategoryHits['protein (pdbsum)'] = 15;

$msCategories['spectre'] = array(
	'sdbs'
);
$msCategoryHits['spectre'] = 15;

$msCategories['protein (human)'] = array(
	'hprd'
);
$msCategoryHits['protein (human)'] = 20;

$msCategories['ncbi pubmed'] = array(
	'pubmed'
);
$msCategoryHits['ncbi pubmed'] = 20;


$msCategories['example1'] = array(
	array('example', 0.2, 0.7),
	array('viralzone', 0.8, 0.3)
);
$msCategoryHits['example1'] = 10;

$msCategories['example2'] = array(
	// alle wie example1... + protein
);
*/