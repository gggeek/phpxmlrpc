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

// One of the simplest way of implementing webservices: as xml-rpc-aware methods of a php object
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

// Enable support for the NULL extension
PhpXmlRpc::$xmlrpc_null_extension = true;

$s = new Server($signatures, false);
$s->setDebug(3);

// Out-of-band information: let the client manipulate the server operations.
// We do this to help the testsuite script: *** do not reproduce in production or public environments! ***
if (defined('TESTMODE')) {
    if (isset($_GET['RESPONSE_ENCODING'])) {
        $s->response_charset_encoding = $_GET['RESPONSE_ENCODING'];
    }
    if (isset($_GET['DETECT_ENCODINGS'])) {
        PhpXmlRpc::$xmlrpc_detectencodings = $_GET['DETECT_ENCODINGS'];
    }
    if (isset($_GET['EXCEPTION_HANDLING'])) {
        $s->exception_handling = $_GET['EXCEPTION_HANDLING'];
    }
    if (isset($_GET['FORCE_AUTH'])) {
        // We implement both  Basic and Digest auth in php to avoid having to set it up in a vhost.
        // Code taken from php.net
        // NB: we do NOT check for valid credentials!
        if ($_GET['FORCE_AUTH'] == 'Basic') {
            if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['REMOTE_USER']) && !isset($_SERVER['REDIRECT_REMOTE_USER'])) {
                header('HTTP/1.0 401 Unauthorized');
                header('WWW-Authenticate: Basic realm="Phpxmlrpc Basic Realm"');
                die('Text visible if user hits Cancel button');
            }
        } elseif ($_GET['FORCE_AUTH'] == 'Digest') {
            if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: Digest realm="Phpxmlrpc Digest Realm",qop="auth",nonce="' . uniqid() . '",opaque="' . md5('Phpxmlrpc Digest Realm') . '"');
                die('Text visible if user hits Cancel button');
            }
        }
    }
    if (isset($_GET['FORCE_REDIRECT'])) {
        header('HTTP/1.0 302 Found');
        unset($_GET['FORCE_REDIRECT']);
        header('Location: ' . $_SERVER['REQUEST_URI'] . (count($_GET) ? '?' . http_build_query($_GET) : ''));
        die();
    }
}

$s->service();
// That should do all we need!
