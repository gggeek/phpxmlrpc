<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for php files in the 'demo' directory.
 *
 * @todo add execution of perl and python demos via usage of 'exec'
 */
class DemoFilesTest extends PhpXmlRpc_WebTestCase
{
    public function set_up()
    {
        $this->args = argParser::getArgs();

        // assumes HTTPURI to be in the form /tests/index.php?etc...
        $this->baseUrl = $this->args['HTTPSERVER'] . preg_replace('|\?.+|', '', $this->args['HTTPURI']);
        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . preg_replace('|/tests/index\.php(\?.*)?|', '/tests/phpunit_coverage.php', $this->args['HTTPURI']);
    }

    public function testVardemo()
    {
        $page = $this->request('?demo=vardemo.php');
    }

    // *** client ***

    public function testAgeSort()
    {
        $page = $this->request('?demo=client/agesort.php');
    }

    public function testCodegen()
    {
        $page = $this->request('?demo=client/codegen.php');
    }

    public function testGetStateName()
    {
        $page = $this->request('?demo=client/getstatename.php');
        $page = $this->request('?demo=client/getstatename.php', 'POST', array('stateno' => '1'));
    }

    public function testIntrospect()
    {
        $page = $this->request('?demo=client/introspect.php');
    }

    public function testParallel()
    {
        $page = $this->request('?demo=client/parallel.php');
    }

    public function testProxy()
    {
        $page = $this->request('?demo=client/proxy.php', 'GET', null, true);
    }

    public function testWhich()
    {
        $page = $this->request('?demo=client/which.php');
    }

    public function testWindowsCharset()
    {
        $page = $this->request('?demo=client/windowscharset.php');
    }

    public function testWrap()
    {
        $page = $this->request('?demo=client/wrap.php');
    }

    // *** servers ***

    public function testCodegenServer()
    {
        /// @todo add a couple of proper xmlrpc calls, too
        $page = $this->request('?demo=server/codegen.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);
    }

    public function testDiscussServer()
    {
        /// @todo add a couple of proper xmlrpc calls, too
        $page = $this->request('?demo=server/discuss.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);
    }

    public function testProxyServer()
    {
        /// @todo add a couple of proper xmlrpc calls, too
        $page = $this->request('?demo=server/proxy.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);
    }
}
