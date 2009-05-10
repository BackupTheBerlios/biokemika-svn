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

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI'))
	die('Not a valid entry point!');

# Extension credits
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'MetaSearch',
	'author' => 'Sven Koeppel',
	'url' => 'http://www.biokemika.de/',
	'svn-date' => '$LastChangedDate$',
	'svn-revision' => '$LastChangedRevision$',
	'version' => '$Id$',
	'description' => 'This is a meta search engine for Mediawiki',
	'descriptionmsg' => 'metasearch-desc',
);

# Configuration

$msConfiguration = array(
	'default-record-message' => 'Ms-record',
	'root-category-name'  => 'root'
);

### END OF CONFIGURATION ###
$dir = dirname(__FILE__);
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
	'MsResult' => "$dir/includes/Database.php",
	'MsRecord' => "$dir/includes/Database.php",

	'MsCategory' => "$dir/includes/Category.php",
	'MsCategoryFactory' => "$dir/includes/Category.php",

	'MsDispatcher' => "$dir/includes/Dispatcher.php",
);