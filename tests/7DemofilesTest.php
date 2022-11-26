<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for php files in the 'demo' directory
 */
class DemoFilesTest extends PhpXmlRpc_WebTestCase
{
    public function set_up()
    {
        $this->args = argParser::getArgs();

        $this->baseUrl = $this->args['HTTPSERVER'] . str_replace( '/demo/server/server.php', '/tests/index.php', $this->args['HTTPURI'] );

        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . str_replace( '/demo/server/server.php', '/tests/phpunit_coverage.php', $this->args['HTTPURI'] );
    }

    public function testAgeSort()
    {
        $page = $this->request('?demo=client/agesort.php');
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

    public function testMail()
    {
        $page = $this->request('?demo=client/mail.php');
        $page = $this->request('?demo=client/mail.php', 'POST', array(
            "mailto" => '',
            "mailsub" => '',
            "mailmsg" => '',
            "mailfrom" => '',
            "mailcc" => '',
            "mailbcc" => '',
        ));
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

    public function testWrap()
    {
        $page = $this->request('?demo=client/wrap.php');
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
