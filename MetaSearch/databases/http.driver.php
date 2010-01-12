<?php
 /*
 ** This is an OLD database/database driver for the QueryDatabase
 ** system (or even much older). There's no need any more for this
 ** file in the current setup.
 */


/** DB driver for HTTP "brute force" databases. This will
 * set up the Pear module HTTP_Client.
 */

// set up local PEAR directory
set_include_path(get_include_path() . PATH_SEPARATOR . 'extensions/metasearch/lib/'); 

require_once 'HTTP/Client.php';

// Well... since we have like ordinary users, we also can
// pretend to be such ones.
ini_set('user_agent', 'Mozilla/5.0 (X11; U; Linux i686; de; rv:1.9.0.6) Gecko/2009011912 Firefox/3.0.6');

abstract class MsHttpDatabase extends MsDatabase {

}
