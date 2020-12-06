<?php

use PHPUnit\Framework\TestResult;
use PHPUnit\Runner\Version as PHPUnit_Version;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

if (class_exists('PhpXmlRpc_PolyfillTestCase')) {
    return;
}

if (class_exists(PHPUnit_Version::class) === false || version_compare(PHPUnit_Version::id(), '8.0.0', '<')) {
    abstract class PhpXmlRpc_PolyfillTestCase extends TestCase
    {
        public function _run($result = null) {
            return parent::run($result);
        }

        public static function _fail() {}

        public function run($result = null) {
            return $this->_run($result);
        }

        public static function fail($message = '') {
            static::_fail($message);
            self::fail($message);
        }
    }
} else {
    abstract class PhpXmlRpc_PolyfillTestCase extends TestCase
    {
        public function _run(TestResult $result = null) {
            return parent::run($result);
        }

        public static function _fail() {}

        public function run(TestResult $result = null): TestResult {
            return $this->_run($result);
        }

        public static function fail(string $message = ''): void {
            static::_fail($message);
            parent::fail($message);
        }
    }
}
