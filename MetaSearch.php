<?php
/**
 * Mediawiki MetaSearch Extension Setup file
 *
 * This is the lightweight setup file for the MetaSearch extension
 * according to the Mediawiki coding conventions. Include *this* file into
 * your LocalSettings.php file by putting the following line:
     require_once( "$IP/extensions/MetaSearch/MetaSearch.php" );
 *
 *
 * @file
 * @author Sven Koeppel <sven@technikum29.de>
 * @link http://www.biokemika.de/wiki/BioKemika:MetaSearch Homepage entrypoint
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

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI'))
	die('Not a valid entry point!');

# Extension credits
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'MetaSearch',
	'author' => 'Sven Koeppel',
	'url' => 'http://www.biokemika.de/',
	'svn-date' => '$LastChangedDate: 2009-06-21 22:48:16 +0200 (So, 21 Jun 2009) $',
	'svn-revision' => '$LastChangedRevision: 18 $',
	'version' => '$Id$',
	'description' => 'This is a meta search engine for Mediawiki',
	'descriptionmsg' => 'metasearch-desc',
);

# Configuration
$dir = dirname(__FILE__);

global $msConfiguration;

// this has to be filled by the database driver files. It should
// be used like $wgExtensionCredits above.
/// @DEPRECATED
$msDatabaseCredits = array('database', 'driver');

global $msDatabaseDriver;

# Use this instead:
$msDatabaseDriver = array();

### END OF CONFIGURATION ###
$wgExtensionMessagesFiles['Metasearch'] = "$dir/MetaSearch.i18n.php";
$wgExtensionAliasesFiles['Metasearch'] = "$dir/MetaSearch.alias.php";

$wgAutoloadClasses['MsSpecialPage'] = "$dir/MetaSearch_body.php";
# Name in Special: => Class name
$wgSpecialPages['Metasearch'] = 'MsSpecialPage';

$wgAutoloadClasses = $wgAutoloadClasses + array(
	'MsController' => "$dir/includes/Controller.php",

	'MsDatabase' => "$dir/includes/Database.php",
	'MsDatabaseFactory' => "$dir/includes/Database.php",
	'MsQuery' => "$dir/includes/Database.php",
	'MsResult' => "$dir/includes/Result.php",
	'MsSparseResult' => "$dir/includes/Result.php",
	'MsRecord' => "$dir/includes/Database.php",

	'MsCategory' => "$dir/includes/Category.php",
	'MsCategoryFactory' => "$dir/includes/Category.php",
	'MsCategoryStack' => "$dir/includes/Category.php",

	'MsDispatcher' => "$dir/includes/Dispatcher.php",

	'MsListPage' => "$dir/includes/ListPage.php",
	'MsQueryPage' => "$dir/includes/QueryPage.php",
	'MsSearchMask' => "$dir/includes/SearchMask.php",

	'MsChooserPage' => "$dir/includes/ChooserPage.php",
	'MsChooserTemplate' => "$dir/includes/ChooserTemplate.php",
	'MsException' => "$dir/includes/Controller.php",
	'MsMsgConfiguration' => "$dir/MetaSearch_body.php", # for other entrypoints

	'MsQuickTemplate' => "$dir/includes/QuickTemplate.php",
	'MsExceptionTemplate' => "$dir/includes/QuickTemplate.php",

	'MsDriver' => "$dir/includes/Database.php",
	'MsProxyDatabaseDriver' => "$dir/includes/ProxyDatabase.php",
	'MsProxyTemplate' => "$dir/includes/ProxyPage.php",
	'MsProxyPage' => "$dir/includes/ProxyPage.php",
	'MsProxyConfiguration' => "$dir/includes/ProxyDatabase.php",
	'MsAssistant' => "$dir/includes/ProxyAssistant.php",
	'MsProxyAssistantTrigger' => "$dir/includes/ProxyAssistant.php",
);
