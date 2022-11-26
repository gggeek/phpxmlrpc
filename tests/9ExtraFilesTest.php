<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for php files in the 'extras' directory
 *
 */
class ExtraFilesTest extends PhpXmlRpc_WebTestCase
{
    public function set_up()
    {
        $this->args = argParser::getArgs();

        $this->baseUrl = $this->args['HTTPSERVER'] . str_replace( '/demo/server/server.php', '/tests/index.php', $this->args['HTTPURI'] );

        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . str_replace( '/demo/server/server.php', '/tests/phpunit_coverage.php', $this->args['HTTPURI'] );
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
