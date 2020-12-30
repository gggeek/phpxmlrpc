<?php
/**
 * @author Gaetano Giunta
 * @copyright (C) 2005-2020 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 *
 * Parses GET/POST variables
 *
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 * @todo do some more sanitization of received parameters
 * @todo move parameters away from global namespace
 */

// handle class autoloading:
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    // if the debugger is installed as top-level project with Composer, allow finding classes from dependencies
    include_once(__DIR__.'/../vendor/autoload.php');
} else {
    // assume this is either a standalone install, or installed as Composer dependency
    /// @todo if the latter is true, should we just not skip using the custom Autoloader, and let a top-level
    ///       debugger include this one, taking care of autoloading ?
    include_once __DIR__ . "/../src/Autoloader.php";
    PhpXmlRpc\Autoloader::register();
}

// work around register globals - @see https://www.php.net/manual/en/faq.misc.php#faq.misc.registerglobals
if (ini_get('register_globals')) {
    function unregister_globals()
    {
        // Might want to change this perhaps to a nicer error
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            die('GLOBALS overwrite attempt detected');
        }

        // Variables that shouldn't be unset
        $noUnset = array('GLOBALS',  '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

        $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES,
            isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array()
        );

        foreach ($input as $k => $v) {
            if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
                unset($GLOBALS[$k]);
            }
        }
    }
    unregister_globals();
}

// work around magic quotes
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);

        return $value;
    }

    $_GET = array_map('stripslashes_deep', $_GET);
}

$preferredEncodings = 'UTF-8, ASCII, ISO-8859-1, UTF-7, EUC-JP, SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP';
$inputcharset = mb_detect_encoding(urldecode($_SERVER['REQUEST_URI']), $preferredEncodings);
if (isset($_GET['usepost']) && $_GET['usepost'] === 'true') {
    $_GET = $_POST;
    $inputcharset = mb_detect_encoding(implode('', $_GET), $preferredEncodings);
}

/// @todo if $inputcharset is not UTF8, we should probably re-encode $_GET to make it UTF-8

// recover input parameters
/// @todo instead of using globals, move them to an array. Also: use a class for this parsing...
$debug = false;
$protocol = 0;
$run = false;
$wstype = defined('DEFAULT_WSTYPE') ? DEFAULT_WSTYPE : 0;
$id = '';
if (isset($_GET['action'])) {
    if (isset($_GET['wstype']) && $_GET['wstype'] == '1') {
        $wstype = 1;
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
        }
    }
    $host = isset($_GET['host']) ? $_GET['host'] : 'localhost'; // using '' will trigger an xmlrpc error...
    if (isset($_GET['protocol']) && ($_GET['protocol'] == '1' || $_GET['protocol'] == '2')) {
        $protocol = $_GET['protocol'];
    }
    if (strpos($host, 'http://') === 0) {
        $host = substr($host, 7);
    } elseif (strpos($host, 'https://') === 0) {
        $host = substr($host, 8);
        $protocol = 2;
    }
    $port = isset($_GET['port']) ? $_GET['port'] : '';
    $path = isset($_GET['path']) ? $_GET['path'] : '';
    // in case user forgot initial '/' in xmlrpc server path, add it back
    if ($path && ($path[0]) != '/') {
        $path = '/' . $path;
    }

    if (isset($_GET['debug']) && ($_GET['debug'] == '1' || $_GET['debug'] == '2')) {
        $debug = $_GET['debug'];
    }

    $verifyhost = (isset($_GET['verifyhost']) && ($_GET['verifyhost'] == '1' || $_GET['verifyhost'] == '2')) ? $_GET['verifyhost'] : 0;
    if (isset($_GET['verifypeer']) && $_GET['verifypeer'] == '1') {
        $verifypeer = true;
    } else {
        $verifypeer = false;
    }
    $cainfo = isset($_GET['cainfo']) ? $_GET['cainfo'] : '';
    $proxy = isset($_GET['proxy']) ? $_GET['proxy'] : 0;
    if (strpos($proxy, 'http://') === 0) {
        $proxy = substr($proxy, 7);
    }
    $proxyuser = isset($_GET['proxyuser']) ? $_GET['proxyuser'] : '';
    $proxypwd = isset($_GET['proxypwd']) ? $_GET['proxypwd'] : '';
    $timeout = isset($_GET['timeout']) ? $_GET['timeout'] : 0;
    if (!is_numeric($timeout)) {
        $timeout = 0;
    }
    $action = $_GET['action'];

    $method = isset($_GET['method']) ? $_GET['method'] : '';
    $methodsig = isset($_GET['methodsig']) ? $_GET['methodsig'] : 0;
    $payload = isset($_GET['methodpayload']) ? $_GET['methodpayload'] : '';
    $alt_payload = isset($_GET['altmethodpayload']) ? $_GET['altmethodpayload'] : '';

    if (isset($_GET['run']) && $_GET['run'] == 'now') {
        $run = true;
    }

    $username = isset($_GET['username']) ? $_GET['username'] : '';
    $password = isset($_GET['password']) ? $_GET['password'] : '';

    $authtype = (isset($_GET['authtype']) && ($_GET['authtype'] == '2' || $_GET['authtype'] == '8')) ? $_GET['authtype'] : 1;

    if (isset($_GET['requestcompression']) && ($_GET['requestcompression'] == '1' || $_GET['requestcompression'] == '2')) {
        $requestcompression = $_GET['requestcompression'];
    } else {
        $requestcompression = 0;
    }
    if (isset($_GET['responsecompression']) && ($_GET['responsecompression'] == '1' || $_GET['responsecompression'] == '2' || $_GET['responsecompression'] == '3')) {
        $responsecompression = $_GET['responsecompression'];
    } else {
        $responsecompression = 0;
    }

    $clientcookies = isset($_GET['clientcookies']) ? $_GET['clientcookies'] : '';
} else {
    $host = '';
    $port = '';
    $path = '';
    $action = '';
    $method = '';
    $methodsig = 0;
    $payload = '';
    $alt_payload = '';
    $username = '';
    $password = '';
    $authtype = 1;
    $verifyhost = 0;
    $verifypeer = false;
    $cainfo = '';
    $proxy = '';
    $proxyuser = '';
    $proxypwd = '';
    $timeout = 0;
    $requestcompression = 0;
    $responsecompression = 0;
    $clientcookies = '';
}

// check input for known XMLRPC attacks against this or other libs
function payload_is_safe($input)
{
    return true;
}
