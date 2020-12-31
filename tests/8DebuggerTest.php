<?php

include_once __DIR__ . '/WebTestCase.php';

class DebuggerTest extends PhpXmlRpc_WebTestCase
{
    public function set_up()
    {
        $this->args = argParser::getArgs();

        $this->baseUrl = $this->args['HTTPSERVER'] . str_replace( '/demo/server/server.php', '/debugger/', $this->args['HTTPURI'] );

        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . '/' . str_replace( '/demo/server/server.php', 'tests/phpunit_coverage.php', $this->args['HTTPURI'] );
    }

    public function testIndex()
    {
        $page = $this->request('index.php');
    }

    public function testController()
    {
        $page = $this->request('controller.php');
    }

    /**
     * @todo test:
     * - list methods
     * - describe a method
     * - execute a method
     * - wrap a method
     */
    public function testAction()
    {
        $page = $this->request('action.php');
    }
}
