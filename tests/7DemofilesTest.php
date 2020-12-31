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

        $this->baseUrl = $this->args['HTTPSERVER'] . str_replace( '/demo/server/server.php', '/demo/', $this->args['HTTPURI'] );

        $this->coverageScriptUrl = 'http://' . $this->args['HTTPSERVER'] . '/' . str_replace( '/demo/server/server.php', 'tests/phpunit_coverage.php', $this->args['HTTPURI'] );
    }

    public function testAgeSort()
    {
        $page = $this->request('client/agesort.php');
    }

    public function testGetStateName()
    {
        $page = $this->request('client/getstatename.php');
        $page = $this->request('client/getstatename.php', 'POST', array('stateno' => '1'));
    }

    public function testIntrospect()
    {
        $page = $this->request('client/introspect.php');
    }

    public function testMail()
    {
        $page = $this->request('client/mail.php');
        $page = $this->request('client/mail.php', 'POST', array(
            "mailto" => '',
            "mailsub" => '',
            "mailmsg" => '',
            "mailfrom" => '',
            "mailcc" => '',
            "mailbcc" => '',
        ));
    }

    public function testProxy()
    {
        $page = $this->request('client/proxy.php', 'GET', null, true);
    }

    public function testWhich()
    {
        $page = $this->request('client/which.php');
    }

    public function testWrap()
    {
        $page = $this->request('client/wrap.php');
    }

    public function testDiscussServer()
    {
        /// @todo add a couple of proper xmlrpc calls, too
        $page = $this->request('server/discuss.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);
    }

    public function testProxyServer()
    {
        /// @todo add a couple of proper xmlrpc calls, too
        $page = $this->request('server/proxy.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);
    }
}
