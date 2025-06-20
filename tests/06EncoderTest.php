<?php

include_once __DIR__ . '/LoggerAwareTestCase.php';

/**
 * Tests involving automatic encoding/decoding of php values into xmlrpc values (the Encoder class).
 *
 * @todo add tests for encoding options: 'encode_php_objs', 'auto_dates', 'null_extension' and 'extension_api'
 * @todo add tests for php_xmlrpc_decode options
 */
class EncoderTest extends PhpXmlRpc_LoggerAwareTestCase
{
    public function testEncodeArray()
    {
        $v = php_xmlrpc_encode(array());
        $this->assertEquals('array', $v->kindof());

        $r = range(1, 10);
        $v = php_xmlrpc_encode($r);
        $this->assertEquals('array', $v->kindof());

        $r['.'] = '...';
        $v = php_xmlrpc_encode($r);
        $this->assertEquals('struct', $v->kindof());
    }

    public function testEncodeDate()
    {
        $r = new DateTime();
        $v = php_xmlrpc_encode($r);
        $this->assertEquals('dateTime.iso8601', $v->scalartyp());
    }

    public function testEncodeRecursive()
    {
        $v = php_xmlrpc_encode(php_xmlrpc_encode('a simple string'));
        $this->assertEquals('scalar', $v->kindof());
    }

    public function testAutoCoDec()
    {
        $data1 = array(1, 1.0, 'hello world', true, '20051021T23:43:00', -1, 11.0, '~!@#$%^&*()_+|', false, '20051021T23:43:00');
        $data2 = array('zero' => $data1, 'one' => $data1, 'two' => $data1, 'three' => $data1, 'four' => $data1, 'five' => $data1, 'six' => $data1, 'seven' => $data1, 'eight' => $data1, 'nine' => $data1);
        $data = array($data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2);
        //$keys = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine');
        $v1 = php_xmlrpc_encode($data, array('auto_dates'));
        $v2 = php_xmlrpc_decode_xml($v1->serialize());
        $this->assertEquals($v1, $v2);
        $r1 = new PhpXmlRpc\Response($v1);
        $r2 = php_xmlrpc_decode_xml($r1->serialize());
        $r2->serialize(); // needed to set internal member payload
        $this->assertEquals($r1, $r2);
        $m1 = new PhpXmlRpc\Request('hello dolly', array($v1));
        $m2 = php_xmlrpc_decode_xml($m1->serialize());
        $m2->serialize(); // needed to set internal member payload
        $this->assertEquals($m1, $m2);
    }

    public function testLatin15InternalEncoding()
    {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Miss mbstring extension to test exotic charsets');
        }

        $string = chr(164);
        $e = new \PhpXmlRpc\Encoder();

        $originalEncoding = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'ISO-8859-15';

        $a = $e->decodeXml('<?xml version="1.0" encoding="US-ASCII" ?><value><string>&#8364;</string></value>');
        $this->assertEquals($string, $a->scalarVal());

        /// @todo it seems that old php versions can not automatically transform latin to utf8 upon xml parsing.
        ///       We should fix that, then re-enable this test
        if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
            $i = $e->decodeXml('<?xml version="1.0" encoding="ISO-8859-15" ?><value><string>' . $string . '</string></value>');
            $this->assertEquals($string, $i->scalarVal());
        }

        $u = $e->decodeXml('<?xml version="1.0" encoding="UTF-8" ?><value><string>€</string></value>');
        $this->assertEquals($string, $u->scalarVal());

        /// @todo move to tear_down(), so that we reset this even in case of test failure
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = $originalEncoding;
    }
}
