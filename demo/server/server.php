<?php
/**
 * Demo server for phpxmlrpc library.
 *
 * Implements a lot of webservices, including a suite of services used for interoperability testing (validator1 and
 * interopEchoTests methods), and some whose only purpose is to be used for testing the library.
 * It also allows the caller to configure specific server features by using "out of band" query string parameters when
 * in test mode.
 *
 * Please _do not_ copy this file verbatim into your production server.
 */

// We answer to CORS preflight requests, to allow browsers which are visiting a site on a different domain to send
// xml-rpc requests (generated via javascript) to this server.
// Doing so has serious security implications, so we lock it by default to only be enabled on the well-known demo server.
// If enabling it on your server, you most likely want to set up an allowed domains whitelist, rather than using'*'
if ($_SERVER['SERVER_ADMIN'] == 'info@altervista.org') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Accept, Accept-Charset, Accept-Encoding, Content-Type, User-Agent");
    header("Access-Control-Expose-Headers: Content-Encoding");
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        die();
    }
}

require_once __DIR__ . "/_prepend.php";

use PhpXmlRpc\PhpXmlRpc;
use PhpXmlRpc\Server;

// Most of the code used to implement the webservices, and their signatures, are stowed away in neatly organized files,
// each demoing a different topic

// One of the simplest ways of implementing webservices: as xml-rpc-aware methods of a php object
$signatures1 = include(__DIR__.'/methodProviders/functions.php');

// Even simpler? webservices defined using php functions in the global scope: definitions of webservices used for
// interoperability testing
$signatures2 = include(__DIR__.'/methodProviders/interop.php');
$signatures3 = include(__DIR__.'/methodProviders/validator1.php');

// See also discuss.php for a different take: implementing webservices out of php code which is not aware of xml-rpc

$signatures = array_merge($signatures1, $signatures2, $signatures3);

// Webservices used only by the testsuite - do not use them in production
if (defined('TESTMODE')) {
    $signatures4 = include(__DIR__.'/methodProviders/testsuite.php');
    $signatures5 = include(__DIR__.'/methodProviders/wrapper.php');

    $signatures = array_merge($signatures, $signatures4, $signatures5);
}

// Enable support for the xml-rpc NULL extension
PhpXmlRpc::$xmlrpc_null_extension = true;

$s = new Server($signatures, false);

// Crank up the server debugging capabilities to the max. You most likely do not want this in production
$s->setOption(Server::OPT_DEBUG, 3);

// Out-of-band information: let the client manipulate the server operations.
// (the `preflight` function is defined in the same file defining the TESTMODE constant)
// We do this to help the testsuite script: *** do not reproduce in production or public environments! ***
if (defined('TESTMODE')) {
    preflight($s);
}

$s->service();

// That should do all we need!
