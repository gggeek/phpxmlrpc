<?php

use PHPUnit\Runner\Version as PHPUnit_Version;

if ( class_exists( 'PHPUnit_Extensions_SeleniumCommon_RemoteCoverage' ) === true
    && class_exists( 'PHPUnit\Extensions\SeleniumCommon\RemoteCoverage' ) === false
) {
    class_alias( 'PHPUnit_Extensions_SeleniumCommon_RemoteCoverage', 'PHPUnit\Extensions\SeleniumCommon\RemoteCoverage' );
}

if ( class_exists( 'PHPUnit_Runner_BaseTestRunner' ) === true
    && class_exists( 'PHPUnit\Runner\BaseTestRunner' ) === false
) {
    class_alias( 'PHPUnit_Runner_BaseTestRunner', 'PHPUnit\Runner\BaseTestRunner' );
}

if (class_exists('PHPUnit\Runner\Version') === false || version_compare(PHPUnit_Version::id(), '8.0.0', '<')) {
    include_once __DIR__ . '/PolyfillTestCase7.php';
} else {
    include_once __DIR__ . '/PolyfillTestCase8.php';
}
