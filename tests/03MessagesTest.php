<?php

include_once __DIR__ . '/LoggerAwareTestCase.php';

use PhpXmlRpc\Response;

/**
 * Tests involving Requests and Responses, except for the parsing part
 */
class MessagesTest extends PhpXmlRpc_LoggerAwareTestCase
{
    public function testSerializePHPValResponse()
    {
        $r = new Response(array('hello' => 'world'), 0, '', 'phpvals');
        $v = $r->serialize();
        $this->assertStringContainsString('<member><name>hello</name>', $v);
        $this->assertStringContainsString('<value><string>world</string></value>', $v);
    }
}
