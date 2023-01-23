<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for the bundled debugger.
 */
class DebuggerTest extends PhpXmlRpc_WebTestCase
{
    public function set_up()
    {
        $this->args = argParser::getArgs();

        // assumes HTTPURI to be in the form /tests/index.php?etc...
        $this->baseUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|\?.+|', '', $this->args['HTTPURI']);
        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|/tests/index\.php(\?.*)?|', '/tests/phpunit_coverage.php', $this->args['HTTPURI']);
    }

    public function testIndex()
    {
        $page = $this->request('?debugger=index.php');
    }

    public function testController()
    {
        $page = $this->request('?debugger=controller.php');
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
        $page = $this->request('?debugger=action.php');
    }
}
