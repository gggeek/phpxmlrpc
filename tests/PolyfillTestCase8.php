<?php

use PHPUnit\Framework\TestResult;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

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
