<?php

include_once __DIR__ . '/WebTestCase.php';

/**
 * Tests for php files in the 'demo' directory.
 *
 * @todo add execution of perl and python demos via usage of 'exec'
 */
class DemoFilesTest extends PhpXmlRpc_WebTestCase
{
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

    public function testLoggerInjection()
    {
        $page = $this->request('?demo=client/loggerinjection.php');
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
        $page = $this->request('?demo=server/codegen.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);

        $c = $this->newClient('?demo=server/codegen.php');
        $r = $c->send(new \PhpXmlRpc\Request('CommentManager.getComments', array(
            new \PhpXmlRpc\Value('aCommentId')
        )));
        $this->assertEquals(0, $r->faultCode());
    }

    public function testDiscussServer()
    {
        $page = $this->request('?demo=server/discuss.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);

        $c = $this->newClient('?demo=server/discuss.php');

        $r = $c->send(new \PhpXmlRpc\Request('discuss.addComment', array(
            new \PhpXmlRpc\Value('aCommentId'),
            new \PhpXmlRpc\Value('aCommentUser'),
            new \PhpXmlRpc\Value('a Comment')
        )));
        $this->assertEquals(0, $r->faultCode());
        $this->assertGreaterThanOrEqual(1, $r->value()->scalarval());

        $r = $c->send(new \PhpXmlRpc\Request('discuss.getComments', array(
            new \PhpXmlRpc\Value('aCommentId')
        )));
        $this->assertEquals(0, $r->faultCode());
        $this->assertEquals(0, $r->faultCode());
        $this->assertGreaterThanOrEqual(1, count($r->value()));
    }

    public function testProxyServer()
    {
        /// @todo add a couple of proper xmlrpc calls, too
        $page = $this->request('?demo=server/proxy.php');
        $this->assertStringContainsString('<name>faultCode</name>', $page);
        $this->assertRegexp('#<int>10(5|3)</int>#', $page);
    }
}
