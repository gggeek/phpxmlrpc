<?php

// File accessed by http requests sent by the test suite, enabling testing of demo, debugger, extras files.
// It makes all errors visible, triggers generation of code-coverage information, and runs the target file,
// which is specified as GET param.

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
