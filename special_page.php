<?php
/**
 * Mediawiki MetaSearch Extension: special_page.php
 *
 * This is the lightweight setup file for the MetaSearch extension
 * according to the Mediawiki coding conventions.
 *
 *
 **/

if (!defined('MEDIAWIKI')) {
        echo <<<EOT
This is the MetaSearch setup file. This is not a valid entry point
to Mediawiki.
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/special_page/special_page.php" );
EOT;
        exit( 1 );
}
 
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'MetaSearch',
	'author' => 'Sven Koeppel',
	'url' => 'http://biokemika.svenk.homeip.net/',
	'description' => 'This is a meta search engine for Mediawiki',
	'descriptionmsg' => 'metasearch-desc',
	'version' => '0.0.1',
);
 
$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['msSpecialPage'] = $dir . 'special_page.body.php';
$wgExtensionMessagesFiles['Metasearch'] = $dir . 'special_page.i18n.php';
$wgExtensionAliasesFiles['Metasearch'] = $dir . 'special_page.alias.php';

# Name in Special: => Class name
$wgSpecialPages['Metasearch'] = 'msSpecialPage';
