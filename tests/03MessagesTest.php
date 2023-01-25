<?php

include_once __DIR__ . '/../lib/xmlrpc.inc';
include_once __DIR__ . '/../lib/xmlrpcs.inc';

include_once __DIR__ . '/parse_args.php';

include_once __DIR__ . '/PolyfillTestCase.php';

use PHPUnit\Runner\BaseTestRunner;

/**
 * Tests involving Requests and Responses, except for the parsing part
 */
class MessagesTest extends PhpXmlRpc_PolyfillTestCase
{
    public $args = array();

    protected function set_up()
    {
        $this->args = argParser::getArgs();
        // hide parsing errors unless in debug mode
        if ($this->args['DEBUG'] == 1)
            ob_start();
    }

    protected function tear_down()
    {
        if ($this->args['DEBUG'] != 1)
            return;
        $out = ob_get_clean();
        $status = $this->getStatus();
        if ($status == BaseTestRunner::STATUS_ERROR
            || $status == BaseTestRunner::STATUS_FAILURE) {
            echo $out;
        }
    }

    public function testSerializePHPValResponse()
    {
        $r = new \PhpXmlRpc\Response(array('hello' => 'world'), 0, '', 'phpvals');
        $v = $r->serialize();
        $this->assertStringContainsString('<member><name>hello</name>', $v);
        $this->assertStringContainsString('<value><string>world</string></value>', $v);
    }
}
