<?php
/**
 * MediaWiki MetaSearch Extension
 * class MsController
 * 
 * This is the Controller class of the MetaSearch extension,
 * it is some kind of "central part" in the search process.
 * The MetaSearch object model implements the (at least a
 * bit ;-) ) a model-view-controller pattern, where
 * MsPage (especially MsQueryPage with MsSearchMask) plays
 * the part of the view, MsQuery, MsDatabase and MsResult
 * objects play the part of the models and this, MsController,
 * is the ... controller! ;-)
 * 
 * Well, this is not a really clean implemention of that
 * approach, since the metasearch extension is somewhat
 * quick and dirty in some circumstances. Actually, the job
 * of Controller.php is:
 *  - implementing some global functions that are missing
 *    to PHP or Mediawiki, like some wfMsg extensions
 *  - performing the search, by calling all the neccessary
 *    functions.
 * 
 * For the later part, this is an abstract how such a search
 * works:
 *
 *  1. MsController->execute() is called
 *  2. Neccessary parts of the category tree are built,
 *     via MsCategory
 *  3. The databases to search in are collected and set up
 *  4. Queries for all databases are constructed
 *  5. A Dispatcher is initialized and called
 *  6. The Dispatcher executes the Queries for the Databases
 *  7. Each Database executes the queries and returns a
 *     Result
 *  8. The controller takes all these results and merge them
 *     together to one big result
 *  9. execute() returns the big result.
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

/********************* CONTROLLER CLASS ****************************/

//class MsController {

//} // Class
