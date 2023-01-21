<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for php files in the 'extras' directory.
 */
class ExtraFilesTest extends PhpXmlRpc_WebTestCase
{
    public function set_up()
    {
        $this->args = argParser::getArgs();

        // assumes HTTPURI to be in the form /tests/index.php?etc...
        $this->baseUrl = $this->args['HTTPSERVER'] . preg_replace('|\?.+|', '', $this->args['HTTPURI']);
        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|/tests/index\.php(\?.*)?|', '/tests/phpunit_coverage.php', $this->args['HTTPURI']);
    }

    public function testBenchmark()
    {
        $page = $this->request('?extras=benchmark.php');
    }

    public function testVerifyCompat()
    {
        $page = $this->request('?extras=verify_compat.php');
    }

    public function testVarDemo()
    {
        $page = $this->request('?demo=vardemo.php');
    }
}
