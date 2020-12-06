<?php

use PHPUnit\Framework\TestResult;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

abstract class PhpXmlRpc_PolyfillTestCase extends TestCase
{
    public function _run($result = null) {
        return parent::run($result);
    }

    public static function _fail() {}

    public function run(TestResult $result = null) {
        return $this->_run($result);
    }

    public static function fail($message = '') {
        static::_fail($message);
        self::fail($message);
    }
}
