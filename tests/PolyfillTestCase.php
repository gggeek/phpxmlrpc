<?php

use PHPUnit\Runner\Version as PHPUnit_Version;

if (class_exists('PhpXmlRpc_PolyfillTestCase')) {
    return;
}

if (class_exists(PHPUnit_Version::class) === false || version_compare(PHPUnit_Version::id(), '8.0.0', '<')) {
    include_once __DIR__ . '/PolyfillTestCase7.php';
} else {
    include_once __DIR__ . '/PolyfillTestCase8.php';
}
