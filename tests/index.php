<?php

// File accessed by http requests sent by the test suite, enabling testing of demo, debugger, extras files.
// It makes all errors visible, triggers generation of code-coverage information, and runs the target file,
// which is specified as GET param.

// In case this file is made available on an open-access server, avoid it being useable by anyone who can not also
// write a specific file to disk.
// NB: keep filename, cookie name in sync with the code within the TestCase classes sending http requests to this file
$idFile = sys_get_temp_dir() . '/phpunit_rand_id.txt';
$randId = isset($_COOKIE['PHPUNIT_RANDOM_TEST_ID']) ? $_COOKIE['PHPUNIT_RANDOM_TEST_ID'] : '';
$fileId = file_exists($idFile) ? file_get_contents($idFile) : '';
if ($randId == '' || $fileId == '' || $fileId !== $randId) {
    die('This url can only be accessed by the test suite');
}

// Make errors visible
ini_set('display_errors', true);
error_reporting(E_ALL);

// Set up a constant which can be used by demo code to tell if the testuite is in action.
// We use a constant because it can not be injected via GET/POST/COOKIE/ENV
const TESTMODE = true;

// Out-of-band information: let the client manipulate the page operations
if (isset($_COOKIE['PHPUNIT_SELENIUM_TEST_ID']) && extension_loaded('xdebug')) {
    // NB: this has to be kept in sync with phunit_coverage.php
    $GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'] = '/tmp/phpxmlrpc_coverage';
    if (!is_dir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'])) {
        mkdir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY']);
        chmod($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'], 0777);
    }

    include_once __DIR__ . "/../vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/prepend.php";
}

$targetFile = null;
$rootDir = dirname(__DIR__);
if (isset($_GET['debugger'])) {
    if (strpos(realpath($rootDir.'/debugger/'.$_GET['debugger']), realpath($rootDir.'/debugger/')) === 0) {
        $targetFile = realpath($rootDir.'/debugger/'.$_GET['debugger']);
    }
} elseif (isset($_GET['demo'])) {
    if (strpos(realpath($rootDir.'/demo/'.$_GET['demo']), realpath($rootDir.'/demo/')) === 0) {
        $targetFile = realpath($rootDir.'/demo/'.$_GET['demo']);
    }
} elseif (isset($_GET['extras'])) {
    if (strpos(realpath($rootDir.'/extras/'.$_GET['extras']), realpath($rootDir.'/extras/')) === 0) {
        $targetFile = realpath($rootDir.'/extras/'.$_GET['extras']);
    }
}
if ($targetFile) {
    include $targetFile;
}

if (isset($_COOKIE['PHPUNIT_SELENIUM_TEST_ID']) && extension_loaded('xdebug')) {
    include_once __DIR__ . "/../vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/append.php";
}

/**
 * @param PhpXmlRpc\Server $s
 * @return void
 */
function preflight($s) {
    if (isset($_GET['FORCE_DEBUG'])) {
        $s->setOption(PhpXmlRpc\Server::OPT_DEBUG, $_GET['FORCE_DEBUG']);
    }
    if (isset($_GET['RESPONSE_ENCODING'])) {
        $s->setOption(PhpXmlRpc\Server::OPT_RESPONSE_CHARSET_ENCODING, $_GET['RESPONSE_ENCODING']);
    }
    if (isset($_GET['DETECT_ENCODINGS'])) {
        PhpXmlRpc\PhpXmlRpc::$xmlrpc_detectencodings = $_GET['DETECT_ENCODINGS'];
    }
    if (isset($_GET['EXCEPTION_HANDLING'])) {
        $s->setOption(PhpXmlRpc\Server::OPT_EXCEPTION_HANDLING, $_GET['EXCEPTION_HANDLING']);
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
    if (isset($_GET['SLOW_LORIS']) && $_GET['SLOW_LORIS'] > 0) {
        slowLoris((int)$_GET['SLOW_LORIS'], $s);
        die();
    }
}

/**
 * Used to test timeouts: send out the payload one chunk every $secs second (10 chunks in total)
 * @param int $secs between 1 and 60
 * @param PhpXmlrpc\Server $s
 */
function slowLoris($secs, $s)
{
    /// @todo as is, this method can not be used by eg. jsonrpc servers. We could look at the value $s::$responseClass
    ///       to improve that
    $strings = array('<?xml version="1.0"?>','<methodResponse>','<params>','<param>','<value>','<string></string>','</value>','</param>','</params>','</methodResponse>');

    header('Content-type: xml; charset=utf-8');
    foreach($strings as $i => $string) {
        echo $string;
        flush();
        if ($i < count($strings) && $secs > 0 && $secs <= 60) {
            sleep($secs);
        }
    }
}
