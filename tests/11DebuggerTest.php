<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for the bundled debugger.
 */
class DebuggerTest extends PhpXmlRpc_WebTestCase
{
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
