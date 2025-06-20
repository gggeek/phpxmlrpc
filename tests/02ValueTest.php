<?php

include_once __DIR__ . '/LoggerAwareTestCase.php';

/**
 * Tests involving the Value class.
 * NB: these tests do not involve the parsing of xml into Value objects - look in 04ParsingTest for that
 */
class ValueTest extends PhpXmlRpc_LoggerAwareTestCase
{
    public function testMinusOneString()
    {
        $v = new xmlrpcval('-1');
        $u = new xmlrpcval('-1', 'string');
        $t = new xmlrpcval(-1, 'string');
        $this->assertEquals($v->scalarval(), $u->scalarval());
        $this->assertEquals($v->scalarval(), $t->scalarval());
    }

    /**
     * This looks funny, and we might call it a bug. But we strive for 100 backwards compat...
     */
    public function testMinusOneInt()
    {
        $u = new xmlrpcval();
        $v = new xmlrpcval(-1);
        $this->assertEquals($u->scalarval(), $v->scalarval());
    }

    public function testAddScalarToStruct()
    {
        $v = new xmlrpcval(array('a' => 'b'), 'struct');
        $r = $v->addscalar('c');
        $this->assertEquals(0, $r);
    }

    public function testAddStructToStruct()
    {
        $v = new xmlrpcval(array('a' => new xmlrpcval('b')), 'struct');
        $r = $v->addstruct(array('b' => new xmlrpcval('c')));
        $this->assertEquals(2, $v->structsize());
        $this->assertEquals(1, $r);
        $r = $v->addstruct(array('b' => new xmlrpcval('b')));
        $this->assertEquals(2, $v->structsize());
    }

    public function testAddArrayToArray()
    {
        $v = new xmlrpcval(array(new xmlrpcval('a'), new xmlrpcval('b')), 'array');
        $r = $v->addarray(array(new xmlrpcval('b'), new xmlrpcval('c')));
        $this->assertEquals(4, $v->arraysize());
        $this->assertEquals(1, $r);
    }

    /// @todo does this test check something useful at all?
    public function testUTF8IntString()
    {
        $v = new xmlrpcval(100, 'int');
        $s = $v->serialize('UTF-8');
        $this->assertequals("<value><int>100</int></value>\n", $s);
    }

    public function testUTF8String()
    {
        $sendstring = 'κόσμε'; // Greek word 'kosme'
        $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
        $f = new xmlrpcval($sendstring, 'string');
        $v = $f->serialize();
        $this->assertEquals("<value><string>&#954;&#8057;&#963;&#956;&#949;</string></value>\n", $v);
        $v = $f->serialize('UTF-8');
        $this->assertEquals("<value><string>$sendstring</string></value>\n", $v);
        $GLOBALS['xmlrpc_internalencoding'] = 'ISO-8859-1';
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
    }

    public function testStringInt()
    {
        $v = new xmlrpcval('hello world', 'int');
        $s = $v->serialize();
        $this->assertequals("<value><int>0</int></value>\n", $s);
    }

    public function testDate()
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $ts = 86401;
        $dt = new DateTime('@86401');

        $v = new xmlrpcval(86401, 'dateTime.iso8601');
        $s = $v->serialize();
        $this->assertequals("<value><dateTime.iso8601>19700102T00:00:01</dateTime.iso8601></value>\n", $s);

        $v = new xmlrpcval($dt, 'dateTime.iso8601');
        $s = $v->serialize();
        $this->assertequals("<value><dateTime.iso8601>19700102T00:00:01</dateTime.iso8601></value>\n", $s);

        $v = new xmlrpcval(\PhpXmlRpc\Helper\Date::iso8601Encode($ts), 'dateTime.iso8601');
        $s = $v->serialize();
        $this->assertequals("<value><dateTime.iso8601>19700102T00:00:01</dateTime.iso8601></value>\n", $s);

        $v = new xmlrpcval(\PhpXmlRpc\Helper\Date::iso8601Encode($dt), 'dateTime.iso8601');
        $s = $v->serialize();
        $this->assertequals("<value><dateTime.iso8601>19700102T00:00:01</dateTime.iso8601></value>\n", $s);

        date_default_timezone_set($tz);
    }

    /// @todo is this included in the above?
    public function testDateTime()
    {
        $time = time();
        $t1 = new xmlrpcval($time, 'dateTime.iso8601');
        $t2 = new xmlrpcval(iso8601_encode($time), 'dateTime.iso8601');
        $this->assertEquals($t1->serialize(), $t2->serialize());
        $datetime = new DateTime();
        $t3 = new xmlrpcval($datetime->setTimestamp($time), 'dateTime.iso8601');
        $this->assertEquals($t1->serialize(), $t3->serialize());
    }

    public function testStructMemExists()
    {
        $v = new xmlrpcval(array('hello' => new xmlrpcval('world')), 'struct');
        $b = $v->structmemexists('hello');
        $this->assertequals(true, $b);
        $b = $v->structmemexists('world');
        $this->assertequals(false, $b);
    }

    public function testLocale()
    {
        $locale = setlocale(LC_NUMERIC, 0);
        /// @todo on php 5.3/win, possibly later versions, setting locale to german does not seem to set decimal separator to comma...
        if (setlocale(LC_NUMERIC, 'deu', 'de_DE@euro', 'de_DE', 'de', 'ge') !== false) {
            $v = new xmlrpcval(1.1, 'double');
            if (version_compare(PHP_VERSION, '8.0', '>=')) {
                $str = sprintf("%f", $v->scalarval());
            } else {
                $str = (string)$v->scalarval();
            }
            if (strpos($str, ',') == 1) {
                $r = $v->serialize();
                $this->assertequals(false, strpos($r, ','));
                setlocale(LC_NUMERIC, $locale);
            } else {
                setlocale(LC_NUMERIC, $locale);
                $this->markTestSkipped('Did not find a locale which sets decimal separator to comma');
            }
        } else {
            $this->markTestSkipped('Did not find a locale which sets decimal separator to comma');
        }
    }

    public function testArrayAccess()
    {
        $v1 = new xmlrpcval(array(new xmlrpcval('one'), new xmlrpcval('two')), 'array');
        $this->assertequals(1, count($v1));
        $out = array('me' => array(), 'mytype' => 2, '_php_class' => null);

        foreach($v1 as $key => $val)
        {
            $this->assertArrayHasKey($key, $out);
            $expected = $out[$key];
            if (gettype($expected) == 'array') {
                $this->assertequals('array', gettype($val));
            } else {
                $this->assertequals($expected, $val);
            }
        }

        $v2 = new \PhpXmlRpc\Value(array(new \PhpXmlRpc\Value('one'), new \PhpXmlRpc\Value('two')), 'array');
        $this->assertequals(2, count($v2));
        $out = array(array('key' => 0, 'value'  => 'object'), array('key' => 1, 'value'  => 'object'));
        $i = 0;
        foreach($v2 as $key => $val)
        {
            $expected = $out[$i];
            $this->assertequals($expected['key'], $key);
            $this->assertequals($expected['value'], gettype($val));
            $i++;
        }

        $v3 = new \PhpXmlRpc\Value(10, 'i4');
        $this->assertEquals(1, count($v3));
        $this->assertEquals(true, isset($v3['int']));
        $this->assertEquals(true, isset($v3['i4']));
        $this->assertEquals(10, $v3['int']);
        $this->assertEquals(10, $v3['i4']);
        $v3['int'] = 100;
        $this->assertEquals(100, $v3['int']);
        $this->assertEquals(100, $v3['i4']);
        $v3['i4'] = 1000;
        $this->assertEquals(1000, $v3['int']);
        $this->assertEquals(1000, $v3['i4']);
    }

    /// @todo do not use \PhpXmlRpc\Encoder for this test
    public function testBigXML()
    {
        // nb: make sure that  the serialized xml corresponding to this is > 10MB in size
        $data = array();
        for ($i = 0; $i < 500000; $i++ ) {
            $data[] = 'hello world';
        }

        $encoder = new \PhpXmlRpc\Encoder();
        $val = $encoder->encode($data);
        $req = new \PhpXmlRpc\Request('test', array($val));
        $xml = $req->serialize();
        $parser = new \PhpXmlRpc\Helper\XMLParser();
        $_xh = $parser->parse($xml);

        $this->assertequals(0, $_xh['isf']);
    }

    public function testLatin15InternalEncoding()
    {
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Miss mbstring extension to test exotic charsets');
        }

        $string = chr(164);
        $v = new \PhpXmlRpc\Value($string);

        $originalEncoding = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'ISO-8859-15';

        $this->assertEquals("<value><string>&#8364;</string></value>", trim($v->serialize('US-ASCII')));
        $this->assertEquals("<value><string>$string</string></value>", trim($v->serialize('ISO-8859-15')));
        $this->assertEquals("<value><string>€</string></value>", trim($v->serialize('UTF-8')));

        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = $originalEncoding;
    }
}
