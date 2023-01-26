<?php

include_once __DIR__ . '/LogAwareTestCase.php';

/**
 * Tests involving the Client class (and no server).
 */
class ClientTes extends PhpXmlRpc_LogAwareTestCase
{
    /** @var xmlrpc_client $client */
    public $client = null;

    public function set_up()
    {
        parent::set_up();

        $this->client = new xmlrpc_client('/NOTEXIST.php', $this->args['HTTPSERVER'], 80);
        $this->client->setDebug($this->args['DEBUG']);
    }

    public function test404()
    {
        $m = new xmlrpcmsg('examples.echo', array(
            new xmlrpcval('hello', 'string'),
        ));
        $r = $this->client->send($m, 5);
        $this->assertEquals(5, $r->faultCode());
    }

    public function testSrvNotFound()
    {
        $m = new xmlrpcmsg('examples.echo', array(
            new xmlrpcval('hello', 'string'),
        ));
        $this->client->server .= 'XXX';
        $dnsinfo = @dns_get_record($this->client->server);
        if ($dnsinfo) {
            $this->markTestSkipped('Seems like there is a catchall DNS in effect: host ' . $this->client->server . ' found');
        } else {
            $r = $this->client->send($m, 5);
            // make sure there's no freaking catchall DNS in effect
            $this->assertEquals(5, $r->faultCode());
        }
    }

    public function testCurlKAErr()
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('CURL missing: cannot test curl keepalive errors');

            return;
        }
        $m = new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval('hello', 'string'),
        ));
        // test 2 calls w. keepalive: 1st time connection ko, second time ok
        $this->client->server .= 'XXX';
        $this->client->keepalive = true;
        $r = $this->client->send($m, 5, 'http11');
        // in case we have a "universal dns resolver" getting in the way, we might get a 302 instead of a 404
        $this->assertTrue($r->faultCode() === 8 || $r->faultCode() == 5);

        // now test a successful connection
        $server = explode(':', $this->args['HTTPSERVER']);
        if (count($server) > 1) {
            $this->client->port = $server[1];
        }
        $this->client->server = $server[0];
        $this->client->path = $this->args['HTTPURI'];

        $r = $this->client->send($m, 5, 'http11');
        $this->assertEquals(0, $r->faultCode());
        $ro = $r->value();
        is_object($ro) && $this->assertEquals('hello', $ro->scalarVal());
    }
}
