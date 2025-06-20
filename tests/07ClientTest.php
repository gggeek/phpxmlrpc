<?php

include_once __DIR__ . '/ServerAwareTestCase.php';

/**
 * Tests involving the Client class features (and mostly no server).
 * @todo review: are there any tests which belong to the ServerTest class?
 */
class ClientTest extends PhpXmlRpc_ServerAwareTestCase
{
    /** @var xmlrpc_client $client */
    public $client = null;
    protected $timeout = 10;

    public function set_up()
    {
        parent::set_up();

        $this->client = $this->getClient();
    }

    public function test404()
    {
        $this->client->path = '/NOTEXIST.php';

        $m = new xmlrpcmsg('examples.echo', array(
            new xmlrpcval('hello', 'string'),
        ));
        $r = $this->client->send($m, $this->timeout);
        $this->assertEquals(5, $r->faultCode());
    }

    public function test404Interop()
    {
        $this->client->path = '/NOTEXIST.php';

        $m = new xmlrpcmsg('examples.echo', array(
            new xmlrpcval('hello', 'string'),
        ));
        $orig = \PhpXmlRpc\PhpXmlRpc::$xmlrpcerr;
        \PhpXmlRpc\PhpXmlRpc::useInteropFaults();
        $r = $this->client->send($m, $this->timeout);
        $this->assertEquals(-32300, $r->faultCode());
        /// @todo reset this via tear_down
        \PhpXmlRpc\PhpXmlRpc::$xmlrpcerr = $orig;
    }

    public function testUnsupportedAuth()
    {
        $m = new xmlrpcmsg('examples.echo', array(
            new xmlrpcval('hello', 'string'),
        ));
        $this->client->setOption(\PhpXmlRpc\Client::OPT_USERNAME, 'user');
        $this->client->setOption(\PhpXmlRpc\Client::OPT_AUTH_TYPE, 2);
        $this->client->setOption(\PhpXmlRpc\Client::OPT_USE_CURL, \PhpXmlRpc\Client::USE_CURL_NEVER);
        $r = $this->client->send($m);
        $this->assertEquals(\PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['unsupported_option'], $r->faultCode());
    }

    public function testSrvNotFound()
    {
        $this->client->server .= 'XXX';
        $dnsinfo = @dns_get_record($this->client->server);
        if ($dnsinfo) {
            $this->markTestSkipped('Seems like there is a catchall DNS in effect: host ' . $this->client->server . ' found');
        } else {
            $m = new xmlrpcmsg('examples.echo', array(
                new xmlrpcval('hello', 'string'),
            ));
            $r = $this->client->send($m, $this->timeout);
            // make sure there's no freaking catchall DNS in effect
            $this->assertEquals(5, $r->faultCode());
        }
    }

    public function testCurlKAErr()
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('CURL missing: cannot test curl keepalive errors');
        }

        $m = new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval('hello', 'string'),
        ));
        // test 2 calls w. keepalive: 1st time connection ko, second time ok
        $this->client->server .= 'XXX';
        $this->client->keepalive = true;
        $r = $this->client->send($m, $this->timeout, 'http11');
        // in case we have a "universal dns resolver" getting in the way, we might get a 302 instead of a 404
        $this->assertTrue($r->faultCode() === 8 || $r->faultCode() == 5);

        // now test a successful connection
        $server = explode(':', $this->args['HTTPSERVER']);
        if (count($server) > 1) {
            $this->client->port = $server[1];
        }
        $this->client->server = $server[0];
        //$this->client->path = $this->args['HTTPURI'];
        //$this->client->setCookie('PHPUNIT_RANDOM_TEST_ID', static::$randId);
        $r = $this->client->send($m, $this->timeout, 'http11');
        $this->assertEquals(0, $r->faultCode());
        $ro = $r->value();
        is_object($ro) && $this->assertEquals('hello', $ro->scalarVal());
    }

    /**
     * @dataProvider getAvailableUseCurlOptions
     */
    public function testCustomHeaders($curlOpt)
    {
        $this->client->setOption(\PhpXmlRpc\Client::OPT_USE_CURL, $curlOpt);
        $this->client->setOption(\PhpXmlRpc\Client::OPT_EXTRA_HEADERS, array('X-PXR-Test: yes'));
        $r = new \PhpXmlRpc\Request('tests.getallheaders');
        $r = $this->client->send($r);
        $this->assertEquals(0, $r->faultCode());
        $ro = $r->value();
        $this->assertArrayHasKey('X-Pxr-Test', $ro->scalarVal(), "Testing with curl mode: $curlOpt");
    }

    /// @todo add more permutations, eg. check that PHP_URL_SCHEME is ok with http10, http11, h2 etc...
    public function testGetUrl()
    {
        $m = $this->client->getUrl(PHP_URL_SCHEME);
        $this->assertEquals($m, $this->client->method);
        $h = $this->client->getUrl(PHP_URL_HOST);
        $this->assertEquals($h, $this->client->server);
        $p = $this->client->getUrl(PHP_URL_PORT);
        $this->assertEquals($p, $this->client->port);
        $p = $this->client->getUrl(PHP_URL_PATH);
        $this->assertEquals($p, $this->client->path);
    }
}
