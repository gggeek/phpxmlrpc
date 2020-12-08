<?php

use PHPUnit\Runner\Version as PHPUnit_Version;

/// @todo we should do the opposite - write test code to the 'new' phpunit API, and alias it to the 'old' class name
///       when old classes are present...
if (!class_exists('PHPUnit_Extensions_SeleniumCommon_RemoteCoverage')) {
    class PHPUnit_Extensions_SeleniumCommon_RemoteCoverage extends PHPUnit\Extensions\SeleniumCommon\RemoteCoverage {}
}

if (class_exists(PHPUnit_Version::class) === false || version_compare(PHPUnit_Version::id(), '8.0.0', '<')) {
    include_once __DIR__ . '/PolyfillTestCase7.php';
} else {
    include_once __DIR__ . '/PolyfillTestCase8.php';
}
