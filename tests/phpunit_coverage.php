<?php
/**
 * Used to serve back the server-side code coverage results to phpunit-selenium
 *
 * @copyright (C) 2007-2025 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 **/

$coverageFile = realpath(__DIR__ . "/../vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/phpunit_coverage.php");

// has to be the same value as used in index.php
$GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'] = '/tmp/phpxmlrpc_coverage';

if (!is_dir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY'])) {
    mkdir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY']);
}

chdir($GLOBALS['PHPUNIT_COVERAGE_DATA_DIRECTORY']);

include_once $coverageFile;
