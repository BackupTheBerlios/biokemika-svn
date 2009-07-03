<?php
/**
 * Assistant Updater Script
 * only to be called from main host, since that's the system idea ;-)
 *
 * We need only the wfMsg() function from mediawiki, so set up a
 * lightweight MediaWiki system, setup lightweight MetaSearch
 * and run the MsAssistant class's method.
 *
 *
 **/

// lightweight MediaWiki Environment
error_reporting(E_ALL);
putenv('MW_INSTALL_PATH='.dirname( __FILE__ ) . "/../..");
require_once( '../../includes/WebStart.php' );

// lightweight MetaSearch system
// (will be loaded automatically)
require_once('MetaSearch_body.php'); // important defs

// check params
#$conf = array();
#$conf['assistant'] = $wgRequest->getVal('assistant', MsAssistant::EMPTY_VALUE);
#$conf['assistant_text'] = $wgRequest->getVal('assistant_text', MsAssistant::EMPTY_VALUE);
# simply take ALL params. This is no security risk at all, just
# much more flexible.

// create assistant
$assistant = new MsAssistant( $_GET );
$assistant->print_updater();

// EOF.
