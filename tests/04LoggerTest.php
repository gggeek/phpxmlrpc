<?php

include_once __DIR__ . '/PolyfillTestCase.php';

use PhpXmlRpc\Helper\Charset;
use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;

class LoggerTest extends PhpXmlRpc_PolyfillTestCase
{
    protected $debugBuffer = '';
    protected $errorBuffer = '';

    protected function set_up()
    {
        $this->debugBuffer = '';
        $this->errorBuffer = '';
    }

    public function testCharsetAltLogger()
    {
        $ch = Charset::instance();
        $l = $ch->getLogger();
        Charset::setLogger($this);

        $ch->encodeEntities('hello world', 'UTF-8', 'NOT-A-CHARSET');
        $this->assertStringContainsString("via mbstring: failed", $this->errorBuffer);

        Charset::setLogger($l);
    }

    public function testHttpAltLogger()
    {
        $l = Http::getLogger();
        Http::setLogger($this);

        $h = new Http();
        $s = "HTTP/1.0 200 OK\r\n" .
            "Content-Type: unknown\r\n" .
            "\r\n" .
            "body";
        $h->parseResponseHeaders($s, false, 1);
        $this->assertStringContainsString("HEADER: content-type: unknown", $this->debugBuffer);
        Http::setLogger($l);
    }

    public function testXPAltLogger()
    {
        $xp = new XMLParser();
        $l = $xp->getLogger();
        XMLParser::setLogger($this);

        $xp->parse('<?xml version="1.0" ?><methodResponse><params><param><value><boolean>x</boolean></value></param></params></methodResponse>');
        $this->assertStringContainsString("invalid data received in BOOLEAN value", $this->errorBuffer);

        XMLParser::setLogger($l);
    }

    // logger API

    public function debugMessage($message, $encoding = null)
    {
        $this->debugBuffer .= $message;
    }

    public function errorLog($message)
    {
        $this->errorBuffer .= $message;
    }
}
