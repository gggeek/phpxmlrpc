<?php

include_once __DIR__ . '/../lib/xmlrpc_wrappers.inc';

include_once __DIR__ . '/ServerAwareTestCase.php';

/**
 * Tests which involve interaction with the server - carried out via the client.
 * They are run against the server found in demo/server.php.
 * Includes testing of (some of) the Wrapper class
 */
class ServerTest extends PhpXmlRpc_ServerAwareTestCase
{
    /** @var xmlrpc_client $client */
    protected $client = null;
    protected $method = 'http';
    protected $timeout = 10;
    protected $request_compression = null;
    protected $accepted_compression = '';

    protected static $failed_tests = array();
    protected static $originalInternalEncoding;

    /**
     * @todo instead of overriding fail via _fail, implement Yoast\PHPUnitPolyfills\TestListeners\TestListenerDefaultImplementation
     */
    public static function _fail($message = '')
    {
        // save in a static var that this particular test has failed
        // (but only if not called from subclass objects / multitests)
        if (function_exists('debug_backtrace') && strtolower(get_called_class()) == 'servertest') {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            for ($i = 0; $i < count($trace); $i++) {
                if (strpos($trace[$i]['function'], 'test') === 0) {
                    self::$failed_tests[$trace[$i]['function']] = true;
                    break;
                }
            }
        }

        parent::_fail($message);
    }

    public static function set_up_before_class()
    {
        parent::set_up_before_class();

        self::$originalInternalEncoding = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding;
    }

    public function set_up()
    {
        parent::set_up();

        // these 2 values are injected into the client when calling `send`, and are modified by some tests - reset them
        $this->timeout = 10;
        $this->method = 'http';

        $this->client = $this->getClient();

        /// @todo replace with setOption when dropping the BC layer
        $this->client->request_compression = $this->request_compression;
        $this->client->accepted_compression = $this->accepted_compression;
    }

    public function tear_down()
    {
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = self::$originalInternalEncoding;

        parent::tear_down();
    }

    /**
     * @param PhpXmlRpc\Request|array $msg
     * @param int|int[] $errorCode expected error codes
     * @param bool $returnResponse
     * @return mixed|\PhpXmlRpc\Response|\PhpXmlRpc\Response[]|\PhpXmlRpc\Value|string|null
     * @todo allo callers to disable checking of faultCode
     */
    protected function send($msg, $errorCode = 0, $returnResponse = false)
    {
        /// @todo move to injecting timeout and method into `getClient`, use the non-legacy API calling convention
        $r = $this->client->send($msg, $this->timeout, $this->method);
        // for multicall, return directly array of responses
        if (is_array($r)) {
            return $r;
        }
        $this->validateResponse($r);
        if (is_array($errorCode)) {
            $this->assertContains($r->faultCode(), $errorCode, 'Error ' . $r->faultCode() . ' connecting to server: ' . $r->faultString());
        } else {
            $this->assertEquals($errorCode, $r->faultCode(), 'Error ' . $r->faultCode() . ' connecting to server: ' . $r->faultString());
        }
        if (!$r->faultCode()) {
            if ($returnResponse) {
                return $r;
            } else {
                return $r->value();
            }
        } else {
            return null;
        }
    }

    protected function validateResponse($r)
    {
        // to be implemented in subclasses
    }

    /**
     * Adds (and replaces) query params to the url currently used by the client
     * @param array $data
     */
    protected function addQueryParams($data)
    {
        $query = parse_url($this->client->path, PHP_URL_QUERY);
        parse_str($query, $vars);
        $query = http_build_query(array_merge($vars, $data));
        $this->client->path = parse_url($this->client->path, PHP_URL_PATH) . '?' . $query;
    }

    public function testString()
    {
        $sendString = "here are 3 \"entities\": < > & " .
            "and here's a dollar sign: \$pretendvarname and a backslash too: " . chr(92) .
            " - isn't that great? \\\"hackery\\\" at it's best " .
            " also don't want to miss out on \$item[0]. " .
            "The real weird stuff follows: CRLF here" . chr(13) . chr(10) .
            "a simple CR here" . chr(13) .
            "a simple LF here" . chr(10) .
            "and then LFCR" . chr(10) . chr(13) .
            "last but not least weird names: G" . chr(252) . "nter, El" . chr(232) . "ne, and an xml comment closing tag: -->";
        $m = new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval($sendString, 'string'),
        ));
        $v = $this->send($m);
        if ($v) {
            // when sending/receiving non-US-ASCII encoded strings, XML says cr-lf can be normalized.
            // so we relax our tests...
            $l1 = strlen($sendString);
            $l2 = strlen($v->scalarval());
            if ($l1 == $l2) {
                $this->assertEquals($sendString, $v->scalarval());
            } else {
                $this->assertEquals(str_replace(array("\r\n", "\r"), array("\n", "\n"), $sendString), $v->scalarval());
            }
        }
    }

    public function testLatin1String()
    {
        $sendString =
            "last but not least weird names: G" . chr(252) . "nter, El" . chr(232) . "ne";
        $x = '<?xml version="1.0" encoding="ISO-8859-1"?><methodCall><methodName>examples.stringecho</methodName><params><param><value>'.
            $sendString.
            '</value></param></params></methodCall>';
        $v = $this->send($x);
        if ($v) {
            $this->assertEquals($sendString, $v->scalarval());
        }
    }

    public function testExoticCharsetsRequests()
    {
        // note that we should disable this call also when mbstring is missing server-side
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Miss mbstring extension to test exotic charsets');
        }

        $sendString = 'κόσμε'; // Greek word 'kosme'
        $str = '<?xml version="1.0" encoding="_ENC_"?>
<methodCall>
    <methodName>examples.stringecho</methodName>
    <params>
        <param>
        <value><string>'.$sendString.'</string></value>
        </param>
    </params>
</methodCall>';

        PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'UTF-8';
        // This test is known to fail with old mbstring versions, at least the ones we get with php 5.4, 5.5 as present
        // in the CI test vms
        if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
            // we have to set the encoding declaration either in the http header or xml prolog, as mb_detect_encoding
            // (used on the server side) will fail recognizing these 2 charsets
            $v = $this->send(mb_convert_encoding(str_replace('_ENC_', 'UCS-4', $str), 'UCS-4', 'UTF-8'));
            $this->assertEquals($sendString, $v->scalarval());
        }
        $v = $this->send(mb_convert_encoding(str_replace('_ENC_', 'UTF-16', $str), 'UTF-16', 'UTF-8'));
        $this->assertEquals($sendString, $v->scalarval());
        //PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'ISO-8859-1';
    }

    public function testExoticCharsetsRequests2()
    {
        // note that we should disable this call also when mbstring is missing server-side
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Miss mbstring extension to test exotic charsets');
        }

        $sendString = '安室奈美恵'; // Japanese name "Namie Amuro"
        $str = '<?xml version="1.0"?>
<methodCall>
    <methodName>examples.stringecho</methodName>
    <params>
        <param>
        <value><string>'.$sendString.'</string></value>
        </param>
    </params>
</methodCall>';

        PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'UTF-8';
        // no encoding declaration either in the http header or xml prolog, let mb_detect_encoding
        // (used on the server side) sort it out
        $this->addQueryParams(array('DETECT_ENCODINGS' => array('EUC-JP', 'UTF-8')));
        $v = $this->send(mb_convert_encoding($str, 'EUC-JP', 'UTF-8'));
        //PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'ISO-8859-1';
        $this->assertEquals($sendString, $v->scalarval());
    }

    public function testExoticCharsetsRequests3()
    {
        // note that we should disable this call also when mbstring is missing server-side
        if (!function_exists('mb_convert_encoding')) {
            $this->markTestSkipped('Miss mbstring extension to test exotic charsets');
        }

        // the warning suppression is due to utf8_decode being deprecated in php 8.2
        $sendString = @utf8_decode('élève');
        $str = '<?xml version="1.0"?>
<methodCall>
    <methodName>examples.stringecho</methodName>
    <params>
        <param>
        <value><string>'.$sendString.'</string></value>
        </param>
    </params>
</methodCall>';

        // no encoding declaration either in the http header or xml prolog, let mb_detect_encoding
        // (used on the server side) sort it out
        $this->addQueryParams(array('DETECT_ENCODINGS' => array('ISO-8859-1', 'UTF-8')));
        $v = $this->send($str);
        $this->assertEquals($sendString, $v->scalarval());
    }

    /*public function testLatin1Method()
    {
        $f = new xmlrpcmsg("tests.iso88591methodname." . chr(224) . chr(252) . chr(232), array(
            new xmlrpcval('hello')
        ));
        $v = $this->send($f);
        if ($v) {
            $this->assertEquals('hello', $v->scalarval());
        }
    }*/

    public function testUtf8Method()
    {
        PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'UTF-8';
        $m = new xmlrpcmsg("tests.utf8methodname." . 'κόσμε', array(
            new xmlrpcval('hello')
        ));
        $v = $this->send($m);
        //PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'ISO-8859-1';
        if ($v) {
            $this->assertEquals('hello', $v->scalarval());
        }
    }

    public function testAddingDoubles()
    {
        // note that rounding errors mean we keep precision to sensible levels here ;-)
        $a = 12.13;
        $b = -23.98;
        $m = new xmlrpcmsg('examples.addtwodouble', array(
            new xmlrpcval($a, 'double'),
            new xmlrpcval($b, 'double'),
        ));
        $v = $this->send($m);
        if ($v) {
            $this->assertEquals($a + $b, $v->scalarval());
        }
    }

    public function testAdding()
    {
        $m = new xmlrpcmsg('examples.addtwo', array(
            new xmlrpcval(12, 'int'),
            new xmlrpcval(-23, 'int'),
        ));
        $v = $this->send($m);
        if ($v) {
            $this->assertEquals(12 - 23, $v->scalarval());
        }
    }

    public function testInvalidNumber()
    {
        $m = new xmlrpcmsg('examples.addtwo', array(
            new xmlrpcval('fred', 'int'),
            new xmlrpcval("\"; exec('ls')", 'int'),
        ));
        $v = $this->send($m);
        /// @todo a specific fault should be generated here by the server, which we can check
        if ($v) {
            $this->assertEquals(0, $v->scalarval());
        }
    }

    public function testUnknownMethod()
    {
        $m = new xmlrpcmsg('examples.a_very_unlikely.method', array());
        $this->send($m, \PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['unknown_method']);
    }

    public function testBoolean()
    {
        $m = new xmlrpcmsg('examples.invertBooleans', array(
            new xmlrpcval(array(
                new xmlrpcval(true, 'boolean'),
                new xmlrpcval(false, 'boolean'),
                new xmlrpcval(1, 'boolean'),
                new xmlrpcval(0, 'boolean')
            ),
                'array'
            ),));
        $answer = '0101';
        $v = $this->send($m);
        if ($v) {
            $sz = $v->arraysize();
            $got = '';
            for ($i = 0; $i < $sz; $i++) {
                $b = $v->arraymem($i);
                if ($b->scalarval()) {
                    $got .= '1';
                } else {
                    $got .= '0';
                }
            }
            $this->assertEquals($answer, $got);
        }
    }

    public function testBase64()
    {
        $sendString = 'Mary had a little lamb,
Whose fleece was white as snow,
And everywhere that Mary went
the lamb was sure to go.

Mary had a little lamb
She tied it to a pylon
Ten thousand volts went down its back
And turned it into nylon';
        $m = new xmlrpcmsg('examples.decode64', array(
            new xmlrpcval($sendString, 'base64'),
        ));
        $v = $this->send($m);
        if ($v) {
            if (strlen($sendString) == strlen($v->scalarval())) {
                $this->assertEquals($sendString, $v->scalarval());
            } else {
                $this->assertEquals(str_replace(array("\r\n", "\r"), array("\n", "\n"), $sendString), $v->scalarval());
            }
        }
    }

    public function testCountEntities()
    {
        $sendString = "h'fd>onc>>l>>rw&bpu>q>e<v&gxs<ytjzkami<";
        $m = new xmlrpcmsg('validator1.countTheEntities', array(
            new xmlrpcval($sendString, 'string'),
        ));
        $v = $this->send($m);
        if ($v) {
            $got = '';
            $expected = '37210';
            $expect_array = array('ctLeftAngleBrackets', 'ctRightAngleBrackets', 'ctAmpersands', 'ctApostrophes', 'ctQuotes');
            foreach($expect_array as $val) {
                $b = $v->structmem($val);
                $got .= $b->scalarVal();
            }
            $this->assertEquals($expected, $got);
        }
    }

    protected function _multicall_msg($method, $params)
    {
        $struct = array();
        $struct['methodName'] = new xmlrpcval($method, 'string');
        $struct['params'] = new xmlrpcval($params, 'array');

        return new xmlrpcval($struct, 'struct');
    }

    public function testServerMulticall()
    {
        // We manually construct a system.multicall() call to ensure
        // that the server supports it.

        // NB: This test will NOT pass if server does not support system.multicall.

        // Based on http://xmlrpc-c.sourceforge.net/hacks/test_multicall.py
        $good1 = $this->_multicall_msg(
            'system.methodHelp',
            array(php_xmlrpc_encode('system.listMethods')));
        $bad = $this->_multicall_msg(
            'test.nosuch',
            array(php_xmlrpc_encode(1), php_xmlrpc_encode(2)));
        $recursive = $this->_multicall_msg(
            'system.multicall',
            array(new xmlrpcval(array(), 'array')));
        $good2 = $this->_multicall_msg(
            'system.methodSignature',
            array(php_xmlrpc_encode('system.listMethods')));
        $arg = new xmlrpcval(
            array($good1, $bad, $recursive, $good2),
            'array'
        );

        $m = new xmlrpcmsg('system.multicall', array($arg));
        $v = $this->send($m);
        if ($v) {
            //$this->assertEquals(0, $r->faultCode(), "fault from system.multicall");
            $this->assertEquals(4, $v->arraysize(), "bad number of return values");

            $r1 = $v->arraymem(0);
            $this->assertTrue(
                $r1->kindOf() == 'array' && $r1->arraysize() == 1,
                "did not get array of size 1 from good1"
            );

            $r2 = $v->arraymem(1);
            $this->assertEquals('struct', $r2->kindOf(), "no fault from bad");

            $r3 = $v->arraymem(2);
            $this->assertEquals('struct', $r3->kindOf(), "recursive system.multicall did not fail");

            $r4 = $v->arraymem(3);
            $this->assertTrue(
                $r4->kindOf() == 'array' && $r4->arraysize() == 1,
                "did not get array of size 1 from good2"
            );
        }
    }

    public function testClientMulticall1()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

        $noMultiCall = $this->client->no_multicall;
        $this->client->no_multicall = false;

        $good1 = new xmlrpcmsg('system.methodHelp',
            array(php_xmlrpc_encode('system.listMethods')));
        $bad = new xmlrpcmsg('test.nosuch',
            array(php_xmlrpc_encode(1), php_xmlrpc_encode(2)));
        $recursive = new xmlrpcmsg('system.multicall',
            array(new xmlrpcval(array(), 'array')));
        $good2 = new xmlrpcmsg('system.methodSignature',
            array(php_xmlrpc_encode('system.listMethods'))
        );

        $r = $this->send(array($good1, $bad, $recursive, $good2));
        if ($r) {
            $this->assertEquals(4, count($r), "wrong number of return values");
        }

        $this->assertEquals(0, $r[0]->faultCode(), "fault from good1");
        if (!$r[0]->faultCode()) {
            $val = $r[0]->value();
            $this->assertTrue(
                $val->kindOf() == 'scalar' && $val->scalartyp() == 'string',
                "good1 did not return string"
            );
        }
        $this->assertNotEquals(0, $r[1]->faultCode(), "no fault from bad");
        $this->assertNotEquals(0, $r[2]->faultCode(), "no fault from recursive system.multicall");
        $this->assertEquals(0, $r[3]->faultCode(), "fault from good2");
        if (!$r[3]->faultCode()) {
            $val = $r[3]->value();
            $this->assertEquals('array', $val->kindOf(), "good2 did not return array");
        }
        // This is the only assert in this test which should fail
        // if the test server does not support system.multicall.
        $this->assertEquals(false, $this->client->no_multicall, "server does not support system.multicall");

        $this->client->no_multicall = $noMultiCall;
    }

    public function testClientMulticall2()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

        $noMultiCall = $this->client->no_multicall;
        $this->client->no_multicall = true;

        $good1 = new xmlrpcmsg('system.methodHelp',
            array(php_xmlrpc_encode('system.listMethods')));
        $bad = new xmlrpcmsg('test.nosuch',
            array(php_xmlrpc_encode(1), php_xmlrpc_encode(2)));
        $recursive = new xmlrpcmsg('system.multicall',
            array(new xmlrpcval(array(), 'array')));
        $good2 = new xmlrpcmsg('system.methodSignature',
            array(php_xmlrpc_encode('system.listMethods'))
        );

        $r = $this->send(array($good1, $bad, $recursive, $good2));
        if ($r) {
            $this->assertEquals(4, count($r), "wrong number of return values");
        }

        $this->assertEquals(0, $r[0]->faultCode(), "fault from good1");
        if (!$r[0]->faultCode()) {
            $val = $r[0]->value();
            $this->assertTrue(
                $val->kindOf() == 'scalar' && $val->scalartyp() == 'string',
                "good1 did not return string");
        }
        $this->assertNotEquals(0, $r[1]->faultCode(), "no fault from bad");
        $this->assertEquals(0, $r[2]->faultCode(), "fault from (non recursive) system.multicall");
        $this->assertEquals(0, $r[3]->faultCode(), "fault from good2");
        if (!$r[3]->faultCode()) {
            $val = $r[3]->value();
            $this->assertEquals('array', $val->kindOf(), "good2 did not return array");
        }

        $this->client->no_multicall = $noMultiCall;
    }

    public function testClientMulticall3()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

        $noMultiCall = $this->client->no_multicall;
        $returnType = $this->client->return_type;

        $this->client->return_type = 'phpvals';
        $this->client->no_multicall = false;

        $good1 = new xmlrpcmsg('system.methodHelp',
            array(php_xmlrpc_encode('system.listMethods')));
        $bad = new xmlrpcmsg('test.nosuch',
            array(php_xmlrpc_encode(1), php_xmlrpc_encode(2)));
        $recursive = new xmlrpcmsg('system.multicall',
            array(new xmlrpcval(array(), 'array')));
        $good2 = new xmlrpcmsg('system.methodSignature',
            array(php_xmlrpc_encode('system.listMethods'))
        );

        $r = $this->send(array($good1, $bad, $recursive, $good2));
        if ($r) {
            $this->assertEquals(4, count($r), "wrong number of return values");
        }
        $this->assertEquals(0, $r[0]->faultCode(), "fault from good1");
        if (!$r[0]->faultCode()) {
            $val = $r[0]->value();
            $this->assertIsString($val, "good1 did not return string");
        }
        $this->assertNotEquals(0, $r[1]->faultCode(), "no fault from bad");
        $this->assertNotEquals(0, $r[2]->faultCode(), "no fault from recursive system.multicall");
        $this->assertEquals(0, $r[3]->faultCode(), "fault from good2");
        if (!$r[3]->faultCode()) {
            $val = $r[3]->value();
            $this->assertIsArray($val, "good2 did not return array");
        }

        $this->client->return_type = $returnType;
        $this->client->no_multicall = $noMultiCall;
    }

    public function testClientMulticall4()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

        $noMultiCall = $this->client->no_multicall;
        $returnType = $this->client->return_type;

        $this->client->return_type = 'xml';
        $this->client->no_multicall = false;

        $good1 = new xmlrpcmsg('system.methodHelp',
            array(php_xmlrpc_encode('system.listMethods')));
        $good2 = new xmlrpcmsg('system.methodSignature',
            array(php_xmlrpc_encode('system.listMethods'))
        );

        $r = $this->send(array($good1, $good2));
        if ($r) {
            $this->assertEquals(2, count($r), "wrong number of return values");
        }
        $this->assertEquals(0, $r[0]->faultCode(), "fault from good1");
        $this->assertEquals(0, $r[1]->faultCode(), "fault from good2");

        $hr = $r[0]->httpResponse();
        $this->assertEquals(200, $hr['status_code'], "http response of multicall has no status code");
        $this->assertEquals($r[0]->httpResponse(), $r[1]->httpResponse(), "http response of multicall items differs");

        $this->client->return_type = $returnType;
        $this->client->no_multicall = $noMultiCall;
    }

    public function testCatchWarnings()
    {
        $m = new xmlrpcmsg('tests.generatePHPWarning', array(
            new xmlrpcval('whatever', 'string'),
        ));
        $this->addQueryParams(array('FORCE_DEBUG' => 0));
        $v = $this->send($m);
        if ($v) {
            $this->assertEquals(true, $v->scalarval());
        }
    }

    public function testCatchExceptions()
    {
        // this tests for the server to catch exceptions with error code 0
        $m = new xmlrpcmsg('tests.raiseException', array(
            new xmlrpcval(0, 'int'),
        ));
        $v = $this->send($m, $GLOBALS['xmlrpcerr']['server_error']);

        // these test for the different server exception catching modes
        $m = new xmlrpcmsg('tests.raiseException', array(
            new xmlrpcval(3, 'int'),
        ));
        $v = $this->send($m, $GLOBALS['xmlrpcerr']['server_error']);
        $this->addQueryParams(array('EXCEPTION_HANDLING' => 1));
        $v = $this->send($m, 3); // the error code of the expected exception
        $this->addQueryParams(array('EXCEPTION_HANDLING' => 2));
        // depending on whether display_errors is ON or OFF on the server, we will get back a different error here,
        // as php will generate an http status code of either 200 or 500...
        $v = $this->send($m, array($GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcerr']['http_error']));
    }

    public function testCatchErrors()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<'))
        {
            $this->markTestSkipped('Cannot test php Error on php < 7.0');
        }

        // these test for the different server error catching modes
        $m = new xmlrpcmsg('tests.raiseError');
        $v = $this->send($m, $GLOBALS['xmlrpcerr']['server_error']);
        $this->addQueryParams(array('EXCEPTION_HANDLING' => 1));
        $v = $this->send($m, 1); // the error code of the expected exception
        $this->addQueryParams(array('EXCEPTION_HANDLING' => 2));
        // depending on whether display_errors is ON or OFF on the server, we will get back a different error here,
        // as php will generate an http status code of either 200 or 500...
        $v = $this->send($m, array($GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcerr']['http_error']));
    }

    public function testZeroParams()
    {
        $m = new xmlrpcmsg('system.listMethods');
        $v = $this->send($m);
    }

    public function testNullParams()
    {
        $m = new xmlrpcmsg('tests.getStateName.12', array(
            new xmlrpcval('whatever', 'null'),
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        if ($v) {
            $this->assertEquals('Michigan', $v->scalarval());
        }
        $m = new xmlrpcmsg('tests.getStateName.12', array(
            new xmlrpcval(23, 'int'),
            new xmlrpcval('whatever', 'null'),
        ));
        $v = $this->send($m);
        if ($v) {
            $this->assertEquals('Michigan', $v->scalarval());
        }
        $m = new xmlrpcmsg('tests.getStateName.12', array(
            new xmlrpcval(23, 'int')
        ));
        $v = $this->send($m, array($GLOBALS['xmlrpcerr']['incorrect_params']));
    }

    public function testCodeInjectionServerSide()
    {
        $m = new xmlrpcmsg('system.MethodHelp');
        $m->payload = "<?xml version=\"1.0\"?><methodCall><methodName>validator1.echoStructTest</methodName><params><param><value><struct><member><name>','')); echo('gotcha!'); die(); //</name></member></struct></value></param></params></methodCall>";
        $v = $this->send($m);
        if ($v) {
            $this->assertEquals(0, $v->structsize());
        }
    }

    public function testServerWrappedFunction()
    {
        $m = new xmlrpcmsg('tests.getStateName.2', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        // this generates an exception in the function which was wrapped, which is by default wrapped in a known error response
        $m = new xmlrpcmsg('tests.getStateName.2', array(
            new xmlrpcval(0, 'int'),
        ));
        $v = $this->send($m, $GLOBALS['xmlrpcerr']['server_error']);

        // check if the generated function dispatch map is fine, by checking if the server registered it
        $m = new xmlrpcmsg('system.methodSignature', array(
            new xmlrpcval('tests.getStateName.2'),
        ));
        $v = $this->send($m);
        $encoder = new \PhpXmlRpc\Encoder();
        $this->assertEquals(array(array('string', 'int')), $encoder->decode($v));
    }

    public function testServerWrappedFunctionAsSource()
    {
        $m = new xmlrpcmsg('tests.getStateName.6', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        // this generates an exception in the function which was wrapped, which is by default wrapped in a known error response
        $m = new xmlrpcmsg('tests.getStateName.6', array(
            new xmlrpcval(0, 'int'),
        ));
        $v = $this->send($m, $GLOBALS['xmlrpcerr']['server_error']);
    }

    public function testServerWrappedObjectMethods()
    {
        $m = new xmlrpcmsg('tests.getStateName.3', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.4', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.5', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.7', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.8', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.9', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());
    }

    public function testServerWrappedObjectMethodsAsSource()
    {
        $m = new xmlrpcmsg('tests.getStateName.7', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.8', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());

        $m = new xmlrpcmsg('tests.getStateName.9', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());
    }

    public function testServerClosure()
    {
        $m = new xmlrpcmsg('tests.getStateName.10', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());
    }

    public function testServerWrappedClosure()
    {
        $m = new xmlrpcmsg('tests.getStateName.11', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());
    }

    public function testServerWrappedClass()
    {
        $m = new xmlrpcmsg('tests.handlersContainer.findState', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());
    }

    public function testServerWrappedClassWithNamespace()
    {
        $m = new xmlrpcmsg('namespacetest.findState', array(
            new xmlrpcval(23, 'int'),
        ));
        $v = $this->send($m);
        $this->assertEquals('Michigan', $v->scalarval());
    }

    public function testWrapInexistentMethod()
    {
        // make a 'deep client copy' as the original one might have many properties set
        $func = wrap_xmlrpc_method($this->client, 'examples.getStateName.notexisting', array('simple_client_copy' => 0));
        $this->assertEquals(false, $func);
    }

    public function testWrapInexistentUrl()
    {
        $this->client->path = '/notexisting';
        // make a 'deep client copy' as the original one might have many properties set
        $func = wrap_xmlrpc_method($this->client, 'examples.getStateName', array('simple_client_copy' => 0));
        $this->assertEquals(false, $func);
    }

    public function testWrappedMethod()
    {
        // make a 'deep client copy' as the original one might have many properties set
        $func = wrap_xmlrpc_method($this->client, 'examples.getStateName', array('simple_client_copy' => 0));
        if ($func == false) {
            $this->fail('Registration of examples.getStateName failed');
        } else {
            $v = $func(23);
            // work around bug in current (or old?) version of phpunit when reporting the error
            /*if (is_object($v)) {
                $v = var_export($v, true);
            }*/
            $this->assertEquals('Michigan', $v);
        }
    }

    public function testWrappedMethodAsSource()
    {
        // make a 'deep client copy' as the original one might have many properties set
        $func = wrap_xmlrpc_method($this->client, 'examples.getStateName', array('simple_client_copy' => 0, 'return_source' => true));
        if ($func == false) {
            $this->fail('Registration of examples.getStateName failed');
        } else {
            eval($func['source']);
            $func = $func['function'];
            $v = $func(23);
            // work around bug in current (or old?) version of phpunit when reporting the error
            /*if (is_object($v)) {
                $v = var_export($v, true);
            }*/
            $this->assertEquals('Michigan', $v);
        }
    }

    public function testWrappedClass()
    {
        // make a 'deep client copy' as the original one might have many properties set
        // also for speed only wrap one method of the whole server
        $class = wrap_xmlrpc_server($this->client, array('simple_client_copy' => 0, 'method_filter' => '/examples\.getStateName/' ));
        if ($class == '') {
            $this->fail('Registration of remote server failed');
        } else {
            $obj = new $class();
            if (!is_callable(array($obj, 'examples_getStateName'))) {
                $this->fail('Registration of remote server failed to import method "examples_getStateName"');
            } else {
                $v = $obj->examples_getStateName(23);
                // work around bug in current (or old?) version of phpunit when reporting the error
                /*if (is_object($v)) {
                    $v = var_export($v, true);
                }*/
                $this->assertEquals('Michigan', $v);
            }
        }
    }

    public function testTransferOfObjectViaWrapping()
    {
        // make a 'deep client copy' as the original one might have many properties set
        $func = wrap_xmlrpc_method($this->client, 'tests.returnPhpObject', array('simple_client_copy' => 0,
            'decode_php_objs' => true));
        if ($func == false) {
            $this->fail('Registration of tests.returnPhpObject failed');
        } else {
            $v = $func();
            $obj = new stdClass();
            $obj->hello = 'world';
            $this->assertEquals($obj, $v);
        }
    }

    public function testGetCookies()
    {
        // let server set to us some cookies we tell it
        $cookies = array(
            //'c1' => array(),
            'c2' => array('value' => 'c2'),
            'c3' => array('value' => 'c3', 'expires' => time() + 60 * 60 * 24 * 30),
            'c4' => array('value' => 'c4', 'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/'),
            'c5' => array('value' => 'c5', 'expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'domain' => 'localhost'),
        );
        $cookiesval = php_xmlrpc_encode($cookies);
        $m = new xmlrpcmsg('tests.setcookies', array($cookiesval));
        $r = $this->send($m, 0, true);
        if ($r) {
            $v = $r->value();
            $this->assertEquals(1, $v->scalarval());
            // now check if we decoded the cookies as we had set them
            $rcookies = $r->cookies();
            // remove extra cookies which might have been set by proxies
            foreach ($rcookies as $c => $v) {
                if (!in_array($c, array('c2', 'c3', 'c4', 'c5'))) {
                    unset($rcookies[$c]);
                }
                // Seems like we get this when using php-fpm and php 5.5+ ...
                if (isset($rcookies[$c]['Max-Age'])) {
                    unset($rcookies[$c]['Max-Age']);
                }
            }
            foreach ($cookies as $c => $v) {
                // format for date string in cookies: 'Mon, 31 Oct 2005 13:50:56 GMT'
                // but PHP versions differ on that, some use 'Mon, 31-Oct-2005 13:50:56 GMT'...
                if (isset($v['expires'])) {
                    if (isset($rcookies[$c]['expires']) && strpos($rcookies[$c]['expires'], '-')) {
                        $cookies[$c]['expires'] = gmdate('D, d\-M\-Y H:i:s \G\M\T', $cookies[$c]['expires']);
                    } else {
                        $cookies[$c]['expires'] = gmdate('D, d M Y H:i:s \G\M\T', $cookies[$c]['expires']);
                    }
                }
            }

            $this->assertEquals($cookies, $rcookies);
        }
    }

    public function testSetCookies()
    {
        // let server set to us some cookies we tell it
        $cookies = array(
            'c0' => null,
            'c1' => 1,
            'c2' => '2 3',
            'c3' => '!@#$%^&*()_+|}{":?><,./\';[]\\=-',
        );
        $m = new xmlrpcmsg('tests.getcookies', array());
        foreach ($cookies as $cookie => $val) {
            $this->client->setCookie($cookie, $val);
            $cookies[$cookie] = (string)$cookies[$cookie];
        }
        $r = $this->client->send($m, $this->timeout, $this->method);
        $this->assertEquals(0, $r->faultCode(), 'Error ' . $r->faultCode() . ' connecting to server: ' . $r->faultString());
        if (!$r->faultCode()) {
            $v = $r->value();
            $v = php_xmlrpc_decode($v);

            // take care of the extra cookies used for coverage collection and test mechanics
            if (isset($v['PHPUNIT_SELENIUM_TEST_ID'])) {
                unset($v['PHPUNIT_SELENIUM_TEST_ID']);
            }
            if (isset($v['PHPUNIT_RANDOM_TEST_ID'])) {
                unset($v['PHPUNIT_RANDOM_TEST_ID']);
            }

            // on IIS and Apache getallheaders returns something slightly different...
            $this->assertEquals($cookies, $v);
        }
    }

    public function testServerComments()
    {
        $m = new xmlrpcmsg('tests.handlersContainer.debugMessageGenerator', array(
            new xmlrpcval('hello world', 'string'),
        ));
        $r = $this->send($m, 0, true);
        $this->assertStringContainsString('hello world', $r->raw_data);
    }

    public function testSendTwiceSameMsg()
    {
        $m = new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval('hello world', 'string'),
        ));
        $v1 = $this->send($m);
        $v2 = $this->send($m);
        if ($v1 && $v2) {
            $this->assertEquals($v1, $v2);
        }
    }

    public function testNegativeDebug()
    {
        $m = new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval('hello world', 'string'),
        ));
        $v1 = $this->send($m, 0, true);
        $h = $v1->httpResponse();
        $this->assertEquals('200', $h['status_code']);
        $this->assertNotEmpty($h['headers']);

        $d = $this->client->getOption('debug');
        $this->client->setDebug(-1);
        $v2 = $this->send($m, 0, true);
        $this->client->setDebug($d);
        $h = $v2->httpResponse();
        $this->assertEmpty($h['headers']);
        $this->assertEmpty($h['raw_data']);
    }
}
