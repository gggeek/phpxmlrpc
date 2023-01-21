<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

abstract class PhpXmlRpc_PolyfillTestCase extends TestCase
{
    public function _run($result = null) {
        return parent::run($result);
    }

    public static function _fail() {}

    public function run(PHPUnit_Framework_TestResult $result = null) {
        return $this->_run($result);
    }

    public static function fail($message = '') {
        static::_fail($message);
        parent::fail($message);
    }
}
