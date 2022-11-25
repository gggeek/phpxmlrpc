<?php

include_once __DIR__ . '/../lib/xmlrpc.inc';
include_once __DIR__ . '/../lib/xmlrpcs.inc';

include_once __DIR__ . '/parse_args.php';

include_once __DIR__ . '/PolyfillTestCase.php';

use PHPUnit\Runner\BaseTestRunner;

/**
 * Tests involving the Value class
 */
class ValueTests extends PhpXmlRpc_PolyfillTestCase
{
    public $args = array();

    protected function set_up()
    {
        $this->args = argParser::getArgs();
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
        // use @ operator in case error_log gets on screen
        $r = @$v->addscalar('c');
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

    public function testUTF8IntString()
    {
        $v = new xmlrpcval(100, 'int');
        $s = $v->serialize('UTF-8');
        $this->assertequals("<value><int>100</int></value>\n", $s);
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
        $v = new xmlrpcval(86401, 'dateTime.iso8601');
        $s = $v->serialize();
        $this->assertequals("<value><dateTime.iso8601>19700102T00:00:01</dateTime.iso8601></value>\n", $s);
        date_default_timezone_set($tz);
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
        /// @todo on php 5.3/win setting locale to german does not seem to set decimal separator to comma...
        if (setlocale(LC_NUMERIC, 'deu', 'de_DE@euro', 'de_DE', 'de', 'ge') !== false) {
            $v = new xmlrpcval(1.1, 'double');
            if (strpos($v->scalarval(), ',') == 1) {
                $r = $v->serialize();
                $this->assertequals(false, strpos($r, ','));
                setlocale(LC_NUMERIC, $locale);
            } else {
                setlocale(LC_NUMERIC, $locale);
                $this->markTestSkipped('did not find a locale which sets decimal separator to comma');
            }
        } else {
            $this->markTestSkipped('did not find a locale which sets decimal separator to comma');
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
        $parser->parse($xml);

        $this->assertequals(0, $parser->_xh['isf']);
    }
}
