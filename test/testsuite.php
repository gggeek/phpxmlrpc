<?php

include(dirname(__FILE__).'/parse_args.php');

require_once('xmlrpc.inc');
require_once('xmlrpcs.inc');
require_once('xmlrpc_wrappers.inc');

require_once 'phpunit.php';
//require_once 'PHPUnit/TestDecorator.php';

// let testuite run for the needed time
if ((int)ini_get('max_execution_time') < 180)
    ini_set('max_execution_time', 180);

$suite = new PHPUnit_TestSuite();

// array with list of failed tests
$failed_tests = array();

class LocalhostTests extends PHPUnit_TestCase
{
    var $client = null;
    var $method = 'http';
    var $timeout = 10;
    var $request_compression = null;
    var $accepted_compression = '';

    function fail($message = '')
    {
        PHPUnit_TestCase::fail($message);
        // save in global var that this particular test has failed
        // (but only if not called from subclass objects / multitests)
        if (function_exists('debug_backtrace') && strtolower(get_class($this)) == 'localhosttests')
        {
            global $failed_tests;
            $trace = debug_backtrace();
            for ($i = 0; $i < count($trace); $i++)
            {
                if (strpos($trace[$i]['function'], 'test') === 0)
                {
                    $failed_tests[$trace[$i]['function']] = true;
                    break;
                }
            }
        }
    }

    function setUp()
    {
        global $DEBUG, $LOCALSERVER, $URI;
        $server = explode(':', $LOCALSERVER);
        if(count($server) > 1)
        {
            $this->client=new xmlrpc_client($URI, $server[0], $server[1]);
        }
        else
        {
            $this->client=new xmlrpc_client($URI, $LOCALSERVER);
        }
        if($DEBUG)
        {
            $this->client->setDebug($DEBUG);
        }
        $this->client->request_compression = $this->request_compression;
        $this->client->accepted_compression = $this->accepted_compression;
    }

    function send($msg, $errrorcode=0, $return_response=false)
    {
        $r = $this->client->send($msg, $this->timeout, $this->method);
        // for multicall, return directly array of responses
        if(is_array($r))
        {
            return $r;
        }
        $this->assertEquals($r->faultCode(), $errrorcode, 'Error '.$r->faultCode().' connecting to server: '.$r->faultString());
        if(!$r->faultCode())
        {
            if($return_response)
                return $r;
            else
                return $r->value();
        }
        else
        {
            return null;
        }
    }

    function testString()
    {
        $sendstring="here are 3 \"entities\": < > & " .
            "and here's a dollar sign: \$pretendvarname and a backslash too: " . chr(92) .
            " - isn't that great? \\\"hackery\\\" at it's best " .
            " also don't want to miss out on \$item[0]. ".
            "The real weird stuff follows: CRLF here".chr(13).chr(10).
            "a simple CR here".chr(13).
            "a simple LF here".chr(10).
            "and then LFCR".chr(10).chr(13).
            "last but not least weird names: G".chr(252)."nter, El".chr(232)."ne, and an xml comment closing tag: -->";
        $f=new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval($sendstring, 'string')
        ));
        $v=$this->send($f);
        if($v)
        {
            // when sending/receiving non-US-ASCII encoded strings, XML says cr-lf can be normalized.
            // so we relax our tests...
            $l1 = strlen($sendstring);
            $l2 = strlen($v->scalarval());
            if ($l1 == $l2)
                $this->assertEquals($sendstring, $v->scalarval());
            else
                $this->assertEquals(str_replace(array("\r\n", "\r"), array("\n", "\n"), $sendstring), $v->scalarval());
        }
    }

    function testAddingDoubles()
    {
        // note that rounding errors mean we
        // keep precision to sensible levels here ;-)
        $a=12.13; $b=-23.98;
        $f=new xmlrpcmsg('examples.addtwodouble',array(
            new xmlrpcval($a, 'double'),
            new xmlrpcval($b, 'double')
        ));
        $v=$this->send($f);
        if($v)
        {
            $this->assertEquals($a+$b,$v->scalarval());
        }
    }

    function testAdding()
    {
        $f=new xmlrpcmsg('examples.addtwo',array(
            new xmlrpcval(12, 'int'),
            new xmlrpcval(-23, 'int')
        ));
        $v=$this->send($f);
        if($v)
        {
            $this->assertEquals(12-23, $v->scalarval());
        }
    }

    function testInvalidNumber()
    {
        $f=new xmlrpcmsg('examples.addtwo',array(
            new xmlrpcval('fred', 'int'),
            new xmlrpcval("\"; exec('ls')", 'int')
        ));
        $v=$this->send($f);
        /// @todo a fault condition should be generated here
        /// by the server, which we pick up on
        if($v)
        {
            $this->assertEquals(0, $v->scalarval());
        }
    }

    function testBoolean()
    {
        $f=new xmlrpcmsg('examples.invertBooleans', array(
            new xmlrpcval(array(
                new xmlrpcval(true, 'boolean'),
                new xmlrpcval(false, 'boolean'),
                new xmlrpcval(1, 'boolean'),
                new xmlrpcval(0, 'boolean'),
                //new xmlrpcval('true', 'boolean'),
                //new xmlrpcval('false', 'boolean')
            ),
            'array'
            )));
        $answer='0101';
        $v=$this->send($f);
        if($v)
        {
            $sz=$v->arraysize();
            $got='';
            for($i=0; $i<$sz; $i++)
            {
                $b=$v->arraymem($i);
                if($b->scalarval())
                {
                    $got.='1';
                }
                else
                {
                    $got.='0';
                }
            }
            $this->assertEquals($answer, $got);
        }
    }

    function testBase64()
    {
        $sendstring='Mary had a little lamb,
Whose fleece was white as snow,
And everywhere that Mary went
the lamb was sure to go.

Mary had a little lamb
She tied it to a pylon
Ten thousand volts went down its back
And turned it into nylon';
        $f=new xmlrpcmsg('examples.decode64',array(
            new xmlrpcval($sendstring, 'base64')
        ));
        $v=$this->send($f);
        if($v)
        {
            if (strlen($sendstring) == strlen($v->scalarval()))
                $this->assertEquals($sendstring, $v->scalarval());
            else
                $this->assertEquals(str_replace(array("\r\n", "\r"), array("\n", "\n"), $sendstring), $v->scalarval());
        }
    }

    function testDateTime()
    {
        $time = time();
        $t1 = new xmlrpcval($time, 'dateTime.iso8601');
        $t2 = new xmlrpcval(iso8601_encode($time), 'dateTime.iso8601');
        $this->assertEquals($t1->serialize(), $t2->serialize());
        if (class_exists('DateTime'))
        {
            $datetime = new DateTime();
            // skip this test for php 5.2. It is a bit harder there to build a DateTime from unix timestamp with proper TZ info
            if(is_callable(array($datetime,'setTimestamp')))
            {
                $t3 = new xmlrpcval($datetime->setTimestamp($time), 'dateTime.iso8601');
                $this->assertEquals($t1->serialize(), $t3->serialize());
            }
        }
    }

    function testCountEntities()
    {
        $sendstring = "h'fd>onc>>l>>rw&bpu>q>e<v&gxs<ytjzkami<";
        $f = new xmlrpcmsg('validator1.countTheEntities',array(
            new xmlrpcval($sendstring, 'string')
        ));
        $v = $this->send($f);
        if($v)
        {
            $got = '';
            $expected = '37210';
            $expect_array = array('ctLeftAngleBrackets','ctRightAngleBrackets','ctAmpersands','ctApostrophes','ctQuotes');
            while(list(,$val) = each($expect_array))
            {
                $b = $v->structmem($val);
                $got .= $b->me['int'];
            }
            $this->assertEquals($expected, $got);
        }
    }

    function _multicall_msg($method, $params)
    {
        $struct['methodName'] = new xmlrpcval($method, 'string');
        $struct['params'] = new xmlrpcval($params, 'array');
        return new xmlrpcval($struct, 'struct');
    }

    function testServerMulticall()
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

        $f = new xmlrpcmsg('system.multicall', array($arg));
        $v = $this->send($f);
        if($v)
        {
            //$this->assertTrue($r->faultCode() == 0, "fault from system.multicall");
            $this->assertTrue($v->arraysize() == 4, "bad number of return values");

            $r1 = $v->arraymem(0);
            $this->assertTrue(
                $r1->kindOf() == 'array' && $r1->arraysize() == 1,
                "did not get array of size 1 from good1"
            );

            $r2 = $v->arraymem(1);
            $this->assertTrue(
                $r2->kindOf() == 'struct',
                "no fault from bad"
            );

            $r3 = $v->arraymem(2);
            $this->assertTrue(
                $r3->kindOf() == 'struct',
                "recursive system.multicall did not fail"
            );

            $r4 = $v->arraymem(3);
            $this->assertTrue(
                $r4->kindOf() == 'array' && $r4->arraysize() == 1,
                "did not get array of size 1 from good2"
            );
        }
    }

    function testClientMulticall1()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

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
        if($r)
        {
            $this->assertTrue(count($r) == 4, "wrong number of return values");
        }

        $this->assertTrue($r[0]->faultCode() == 0, "fault from good1");
        if(!$r[0]->faultCode())
        {
            $val = $r[0]->value();
            $this->assertTrue(
                $val->kindOf() == 'scalar' && $val->scalartyp() == 'string',
                "good1 did not return string"
            );
        }
        $this->assertTrue($r[1]->faultCode() != 0, "no fault from bad");
        $this->assertTrue($r[2]->faultCode() != 0, "no fault from recursive system.multicall");
        $this->assertTrue($r[3]->faultCode() == 0, "fault from good2");
        if(!$r[3]->faultCode())
        {
            $val = $r[3]->value();
            $this->assertTrue($val->kindOf() == 'array', "good2 did not return array");
        }
        // This is the only assert in this test which should fail
        // if the test server does not support system.multicall.
        $this->assertTrue($this->client->no_multicall == false,
            "server does not support system.multicall"
        );
    }

    function testClientMulticall2()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

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
        if($r)
        {
            $this->assertTrue(count($r) == 4, "wrong number of return values");
        }

        $this->assertTrue($r[0]->faultCode() == 0, "fault from good1");
        if(!$r[0]->faultCode())
        {
            $val = $r[0]->value();
            $this->assertTrue(
                $val->kindOf() == 'scalar' && $val->scalartyp() == 'string',
                "good1 did not return string");
        }
        $this->assertTrue($r[1]->faultCode() != 0, "no fault from bad");
        $this->assertTrue($r[2]->faultCode() == 0, "fault from (non recursive) system.multicall");
        $this->assertTrue($r[3]->faultCode() == 0, "fault from good2");
        if(!$r[3]->faultCode())
        {
            $val = $r[3]->value();
            $this->assertTrue($val->kindOf() == 'array', "good2 did not return array");
        }
    }

    function testClientMulticall3()
    {
        // NB: This test will NOT pass if server does not support system.multicall.

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
        if($r)
        {
            $this->assertTrue(count($r) == 4, "wrong number of return values");
        }
        $this->assertTrue($r[0]->faultCode() == 0, "fault from good1");
        if(!$r[0]->faultCode())
        {
            $val = $r[0]->value();
            $this->assertTrue(
                is_string($val) , "good1 did not return string");
        }
        $this->assertTrue($r[1]->faultCode() != 0, "no fault from bad");
        $this->assertTrue($r[2]->faultCode() != 0, "no fault from recursive system.multicall");
        $this->assertTrue($r[3]->faultCode() == 0, "fault from good2");
        if(!$r[3]->faultCode())
        {
            $val = $r[3]->value();
            $this->assertTrue(is_array($val), "good2 did not return array");
        }
        $this->client->return_type = 'xmlrpcvals';
    }

    function testCatchWarnings()
    {
        $f = new xmlrpcmsg('examples.generatePHPWarning', array(
            new xmlrpcval('whatever', 'string')
        ));
        $v = $this->send($f);
        if($v)
        {
            $this->assertEquals($v->scalarval(), true);
        }
    }

    function testCatchExceptions()
    {
        global $URI;
        $f = new xmlrpcmsg('examples.raiseException', array(
            new xmlrpcval('whatever', 'string')
        ));
        $v = $this->send($f, $GLOBALS['xmlrpcerr']['server_error']);
        $this->client->path = $URI.'?EXCEPTION_HANDLING=1';
        $v = $this->send($f, 1);
        $this->client->path = $URI.'?EXCEPTION_HANDLING=2';
        $v = $this->send($f, $GLOBALS['xmlrpcerr']['invalid_return']);
    }

    function testZeroParams()
    {
        $f = new xmlrpcmsg('system.listMethods');
        $v = $this->send($f);
    }

    function testCodeInjectionServerSide()
    {
        $f = new xmlrpcmsg('system.MethodHelp');
        $f->payload = "<?xml version=\"1.0\"?><methodCall><methodName>validator1.echoStructTest</methodName><params><param><value><struct><member><name>','')); echo('gotcha!'); die(); //</name></member></struct></value></param></params></methodCall>";
        $v = $this->send($f);
        //$v = $r->faultCode();
        if ($v)
        {
            $this->assertEquals(0, $v->structsize());
        }
    }

    function testAutoRegisteredFunction()
    {
        $f=new xmlrpcmsg('examples.php.getStateName',array(
            new xmlrpcval(23, 'int')
        ));
        $v=$this->send($f);
        if($v)
        {
            $this->assertEquals('Michigan', $v->scalarval());
        }
        else
        {
            $this->fail('Note: server can only auto register functions if running with PHP 5.0.3 and up');
        }
    }

    function testAutoRegisteredClass()
    {
        $f=new xmlrpcmsg('examples.php2.getStateName',array(
            new xmlrpcval(23, 'int')
        ));
        $v=$this->send($f);
        if($v)
        {
            $this->assertEquals('Michigan', $v->scalarval());
            $f=new xmlrpcmsg('examples.php3.getStateName',array(
            new xmlrpcval(23, 'int')
        ));
            $v=$this->send($f);
            if($v)
            {
                $this->assertEquals('Michigan', $v->scalarval());
            }
        }
        else
        {
            $this->fail('Note: server can only auto register class methods if running with PHP 5.0.3 and up');
        }
    }

    function testAutoRegisteredMethod()
    {
        // make a 'deep client copy' as the original one might have many properties set
        $func=wrap_xmlrpc_method($this->client, 'examples.getStateName', array('simple_client_copy' => 1));
        if($func == '')
        {
            $this->fail('Registration of examples.getStateName failed');
        }
        else
        {
            $v=$func(23);
            // work around bug in current version of phpunit
            if(is_object($v))
            {
                $v = var_export($v, true);
            }
            $this->assertEquals('Michigan', $v);
        }
    }

    function testGetCookies()
    {
        // let server set to us some cookies we tell it
        $cookies = array(
            //'c1' => array(),
            'c2' => array('value' => 'c2'),
            'c3' => array('value' => 'c3', 'expires' => time()+60*60*24*30),
            'c4' => array('value' => 'c4', 'expires' => time()+60*60*24*30, 'path' => '/'),
            'c5' => array('value' => 'c5', 'expires' => time()+60*60*24*30, 'path' => '/', 'domain' => 'localhost'),
        );
        $cookiesval = php_xmlrpc_encode($cookies);
        $f=new xmlrpcmsg('examples.setcookies',array($cookiesval));
        $r=$this->send($f, 0, true);
        if($r)
        {
            $v = $r->value();
            $this->assertEquals(1, $v->scalarval());
            // now check if we decoded the cookies as we had set them
            $rcookies = $r->cookies();
            // remove extra cookies which might have been set by proxies
            foreach($rcookies as $c => $v)
                if(!in_array($c, array('c2', 'c3', 'c4', 'c5')))
                    unset($rcookies[$c]);
            foreach($cookies as $c => $v)
                // format for date string in cookies: 'Mon, 31 Oct 2005 13:50:56 GMT'
                // but PHP versions differ on that, some use 'Mon, 31-Oct-2005 13:50:56 GMT'...
                if(isset($v['expires']))
                {
                    if (isset($rcookies[$c]['expires']) && strpos($rcookies[$c]['expires'], '-'))
                    {
                        $cookies[$c]['expires'] = gmdate('D, d\-M\-Y H:i:s \G\M\T' ,$cookies[$c]['expires']);
                    }
                    else
                    {
                        $cookies[$c]['expires'] = gmdate('D, d M Y H:i:s \G\M\T' ,$cookies[$c]['expires']);
                    }
                }
            $this->assertEquals($cookies, $rcookies);
        }
    }

    function testSetCookies()
    {
        // let server set to us some cookies we tell it
        $cookies = array(
            'c0' => null,
            'c1' => 1,
            'c2' => '2 3',
            'c3' => '!@#$%^&*()_+|}{":?><,./\';[]\\=-'
        );
        $f=new xmlrpcmsg('examples.getcookies',array());
        foreach ($cookies as $cookie => $val)
        {
            $this->client->setCookie($cookie, $val);
            $cookies[$cookie] = (string) $cookies[$cookie];
        }
        $r = $this->client->send($f, $this->timeout, $this->method);
        $this->assertEquals($r->faultCode(), 0, 'Error '.$r->faultCode().' connecting to server: '.$r->faultString());
        if(!$r->faultCode())
        {
            $v = $r->value();
            $v = php_xmlrpc_decode($v);
            // on IIS and Apache getallheaders returns something slightly different...
            $this->assertEquals($v, $cookies);
        }
    }

    function testSendTwiceSameMsg()
    {
        $f=new xmlrpcmsg('examples.stringecho', array(
            new xmlrpcval('hello world', 'string')
        ));
        $v1 = $this->send($f);
        $v2 = $this->send($f);
        //$v = $r->faultCode();
        if ($v1 && $v2)
        {
            $this->assertEquals($v2, $v1);
        }
    }
}

class LocalHostMultiTests extends LocalhostTests
{
    function _runtests()
    {
        global $failed_tests;
        foreach(get_class_methods('LocalhostTests') as $meth)
        {
            if(strpos($meth, 'test') === 0 && $meth != 'testHttps' && $meth != 'testCatchExceptions')
            {
                if (!isset($failed_tests[$meth]))
                {
                    $this->$meth();
                }
            }
            if ($this->_failed)
            {
                break;
            }
        }
    }

    function testDeflate()
    {
        if(!function_exists('gzdeflate'))
        {
            $this->fail('Zlib missing: cannot test deflate functionality');
            return;
        }
        $this->client->accepted_compression = array('deflate');
        $this->client->request_compression = 'deflate';
        $this->_runtests();
    }

    function testGzip()
    {
        if(!function_exists('gzdeflate'))
        {
            $this->fail('Zlib missing: cannot test gzip functionality');
            return;
        }
        $this->client->accepted_compression = array('gzip');
        $this->client->request_compression = 'gzip';
        $this->_runtests();
    }

    function testKeepAlives()
    {
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test http 1.1');
            return;
        }
        $this->method = 'http11';
        $this->client->keepalive = true;
        $this->_runtests();
    }

    function testProxy()
    {
        global $PROXYSERVER, $PROXYPORT, $NOPROXY;
        if ($PROXYSERVER)
        {
            $this->client->setProxy($PROXYSERVER, $PROXYPORT);
            $this->_runtests();
        }
        else
            if (!$NOPROXY)
                $this->fail('PROXY definition missing: cannot test proxy');
    }

    function testHttp11()
    {
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test http 1.1');
            return;
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        //$this->client->verifyhost = 0;
        //$this->client->verifypeer = 0;
        $this->client->keepalive = false;
        $this->_runtests();
    }

    function testHttp11Gzip()
    {
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test http 1.1');
            return;
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->keepalive = false;
        $this->client->accepted_compression = array('gzip');
        $this->client->request_compression = 'gzip';
        $this->_runtests();
    }

    function testHttp11Deflate()
    {
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test http 1.1');
            return;
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->keepalive = false;
        $this->client->accepted_compression = array('deflate');
        $this->client->request_compression = 'deflate';
        $this->_runtests();
    }

    function testHttp11Proxy()
    {
        global $PROXYSERVER, $PROXYPORT, $NOPROXY;
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test http 1.1 w. proxy');
            return;
        }
        else if ($PROXYSERVER == '')
        {
            if (!$NOPROXY)
                $this->fail('PROXY definition missing: cannot test proxy w. http 1.1');
            return;
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->setProxy($PROXYSERVER, $PROXYPORT);
        //$this->client->verifyhost = 0;
        //$this->client->verifypeer = 0;
        $this->client->keepalive = false;
        $this->_runtests();
    }

    function testHttps()
    {
        global $HTTPSSERVER, $HTTPSURI, $HTTPSIGNOREPEER;
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test https functionality');
            return;
        }
        $this->client->server = $HTTPSSERVER;
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->path = $HTTPSURI;
        $this->client->setSSLVerifyPeer( !$HTTPSIGNOREPEER );
        // silence warning with newish php versions
        $this->client->setSSLVerifyHost(2);
        $this->_runtests();
    }

    function testHttpsProxy()
    {
        global $HTTPSSERVER, $HTTPSURI, $PROXYSERVER, $PROXYPORT, $NOPROXY;
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test https functionality');
            return;
        }
        else if ($PROXYSERVER == '')
        {
            if (!$NOPROXY)
                $this->fail('PROXY definition missing: cannot test proxy w. http 1.1');
            return;
        }
        $this->client->server = $HTTPSSERVER;
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->setProxy($PROXYSERVER, $PROXYPORT);
        $this->client->path = $HTTPSURI;
        $this->_runtests();
    }

    function testUTF8Responses()
    {
        global $URI;
        //$this->client->path = strpos($URI, '?') === null ? $URI.'?RESPONSE_ENCODING=UTF-8' : $URI.'&RESPONSE_ENCODING=UTF-8';
        $this->client->path = $URI.'?RESPONSE_ENCODING=UTF-8';
        $this->_runtests();
    }

    function testUTF8Requests()
    {
        $this->client->request_charset_encoding = 'UTF-8';
        $this->_runtests();
    }

    function testISOResponses()
    {
        global $URI;
        //$this->client->path = strpos($URI, '?') === null ? $URI.'?RESPONSE_ENCODING=UTF-8' : $URI.'&RESPONSE_ENCODING=UTF-8';
        $this->client->path = $URI.'?RESPONSE_ENCODING=ISO-8859-1';
        $this->_runtests();
    }

    function testISORequests()
    {
        $this->client->request_charset_encoding = 'ISO-8859-1';
        $this->_runtests();
    }
}

class ParsingBugsTests extends PHPUnit_TestCase
{
    function testMinusOneString()
    {
        $v=new xmlrpcval('-1');
        $u=new xmlrpcval('-1', 'string');
        $this->assertEquals($u->scalarval(), $v->scalarval());
    }

    function testUnicodeInMemberName(){
        $str = "G".chr(252)."nter, El".chr(232)."ne";
        $v = array($str => new xmlrpcval(1));
        $r = new xmlrpcresp(new xmlrpcval($v, 'struct'));
        $r = $r->serialize();
        $m = new xmlrpcmsg('dummy');
        $r = $m->parseResponse($r);
        $v = $r->value();
        $this->assertEquals($v->structmemexists($str), true);
    }

    function testUnicodeInErrorString()
    {
        $response = utf8_encode(
'<?xml version="1.0"?>
<!-- $Id -->
<!-- found by G. giunta, covers what happens when lib receives
  UTF8 chars in response text and comments -->
<!-- ï¿½ï¿½ï¿½&#224;&#252;&#232; -->
<methodResponse>
<fault>
<value>
<struct>
<member>
<name>faultCode</name>
<value><int>888</int></value>
</member>
<member>
<name>faultString</name>
<value><string>ï¿½ï¿½ï¿½&#224;&#252;&#232;</string></value>
</member>
</struct>
</value>
</fault>
</methodResponse>');
        $m=new xmlrpcmsg('dummy');
        $r=$m->parseResponse($response);
        $v=$r->faultString();
        $this->assertEquals('ï¿½ï¿½ï¿½àüè', $v);
    }

    function testValidNumbers()
    {
        $m=new xmlrpcmsg('dummy');
        $fp=
'<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value>
<struct>
<member>
<name>integer1</name>
<value><int>01</int></value>
</member>
<member>
<name>float1</name>
<value><double>01.10</double></value>
</member>
<member>
<name>integer2</name>
<value><int>+1</int></value>
</member>
<member>
<name>float2</name>
<value><double>+1.10</double></value>
</member>
<member>
<name>float3</name>
<value><double>-1.10e2</double></value>
</member>
</struct>
</value>
</param>
</params>
</methodResponse>';
        $r=$m->parseResponse($fp);
        $v=$r->value();
        $s=$v->structmem('integer1');
        $t=$v->structmem('float1');
        $u=$v->structmem('integer2');
        $w=$v->structmem('float2');
        $x=$v->structmem('float3');
        $this->assertEquals(1, $s->scalarval());
        $this->assertEquals(1.1, $t->scalarval());
        $this->assertEquals(1, $u->scalarval());
        $this->assertEquals(1.1, $w->scalarval());
        $this->assertEquals(-110.0, $x->scalarval());
    }

    function testAddScalarToStruct()
    {
        $v=new xmlrpcval(array('a' => 'b'), 'struct');
        // use @ operator in case error_log gets on screen
        $r= @$v->addscalar('c');
        $this->assertEquals(0, $r);
    }

    function testAddStructToStruct()
    {
        $v=new xmlrpcval(array('a' => new xmlrpcval('b')), 'struct');
        $r=$v->addstruct(array('b' => new xmlrpcval('c')));
        $this->assertEquals(2, $v->structsize());
        $this->assertEquals(1, $r);
        $r=$v->addstruct(array('b' => new xmlrpcval('b')));
        $this->assertEquals(2, $v->structsize());
    }

    function testAddArrayToArray()
    {
        $v=new xmlrpcval(array(new xmlrpcval('a'), new xmlrpcval('b')), 'array');
        $r=$v->addarray(array(new xmlrpcval('b'), new xmlrpcval('c')));
        $this->assertEquals(4, $v->arraysize());
        $this->assertEquals(1, $r);
    }

    function testEncodeArray()
    {
        $r=range(1, 100);
        $v = php_xmlrpc_encode($r);
        $this->assertEquals('array', $v->kindof());
    }

    function testEncodeRecursive()
    {
        $v = php_xmlrpc_encode(php_xmlrpc_encode('a simple string'));
        $this->assertEquals('scalar', $v->kindof());
    }

    function testBrokenRequests()
    {
        $s = new xmlrpc_server();
        // omitting the 'params' tag: not tolerated by the lib anymore
$f = '<?xml version="1.0"?>
<methodCall>
<methodName>system.methodHelp</methodName>
<param>
<value><string>system.methodHelp</string></value>
</param>
</methodCall>';
        $r = $s->parserequest($f);
        $this->assertEquals(15, $r->faultCode());
        // omitting a 'param' tag
$f = '<?xml version="1.0"?>
<methodCall>
<methodName>system.methodHelp</methodName>
<params>
<value><string>system.methodHelp</string></value>
</params>
</methodCall>';
        $r = $s->parserequest($f);
        $this->assertEquals(15, $r->faultCode());
        // omitting a 'value' tag
$f = '<?xml version="1.0"?>
<methodCall>
<methodName>system.methodHelp</methodName>
<params>
<param><string>system.methodHelp</string></param>
</params>
</methodCall>';
        $r = $s->parserequest($f);
        $this->assertEquals(15, $r->faultCode());
    }

    function testBrokenResponses()
    {
        $m=new xmlrpcmsg('dummy');
        //$m->debug = 1;
        // omitting the 'params' tag: no more tolerated by the lib...
$f = '<?xml version="1.0"?>
<methodResponse>
<param>
<value><string>system.methodHelp</string></value>
</param>
</methodResponse>';
        $r = $m->parseResponse($f);
        $this->assertEquals(2, $r->faultCode());
        // omitting the 'param' tag: no more tolerated by the lib...
$f = '<?xml version="1.0"?>
<methodResponse>
<params>
<value><string>system.methodHelp</string></value>
</params>
</methodResponse>';
        $r = $m->parseResponse($f);
        $this->assertEquals(2, $r->faultCode());
        // omitting a 'value' tag: KO
$f = '<?xml version="1.0"?>
<methodResponse>
<params>
<param><string>system.methodHelp</string></param>
</params>
</methodResponse>';
        $r = $m->parseResponse($f);
        $this->assertEquals(2, $r->faultCode());
    }

    function testBuggyHttp()
    {
        $s = new xmlrpcmsg('dummy');
$f = 'HTTP/1.1 100 Welcome to the jungle

HTTP/1.0 200 OK
X-Content-Marx-Brothers: Harpo
        Chico and Groucho
Content-Length: who knows?



<?xml version="1.0"?>
<!-- First of all, let\'s check out if the lib properly handles a commented </methodResponse> tag... -->
<methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 2 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
<script type="text\javascript">document.write(\'Hello, my name is added nag, I\\\'m happy to serve your content for free\');</script>
 ';
        $r = $s->parseResponse($f);
        $v = $r->value();
        $s = $v->structmem('content');
        $this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s->scalarval());
    }

    function testStringBug()
    {
        $s = new xmlrpcmsg('dummy');
$f = '<?xml version="1.0"?>
<!-- $Id -->
<!-- found by 2z69xks7bpy001@sneakemail.com, amongst others
 covers what happens when there\'s character data after </string>
 and before </value> -->
<methodResponse>
<params>
<param>
<value>
<struct>
<member>
<name>success</name>
<value>
<boolean>1</boolean>
</value>
</member>
<member>
<name>sessionID</name>
<value>
<string>S300510007I</string>
</value>
</member>
</struct>
</value>
</param>
</params>
</methodResponse> ';
        $r = $s->parseResponse($f);
        $v = $r->value();
        $s = $v->structmem('sessionID');
        $this->assertEquals('S300510007I', $s->scalarval());
    }

    function testWhiteSpace()
    {
        $s = new xmlrpcmsg('dummy');
$f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 2 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->value();
        $s = $v->structmem('content');
        $this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s->scalarval());
    }

    function testDoubleDataInArrayTag()
    {
        $s = new xmlrpcmsg('dummy');
$f = '<?xml version="1.0"?><methodResponse><params><param><value><array>
<data></data>
<data></data>
</array></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->faultCode();
        $this->assertEquals(2, $v);
$f = '<?xml version="1.0"?><methodResponse><params><param><value><array>
<data><value>Hello world</value></data>
<data></data>
</array></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->faultCode();
        $this->assertEquals(2, $v);
    }

    function testDoubleStuffInValueTag()
    {
        $s = new xmlrpcmsg('dummy');
$f = '<?xml version="1.0"?><methodResponse><params><param><value>
<string>hello world</string>
<array><data></data></array>
</value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->faultCode();
        $this->assertEquals(2, $v);
$f = '<?xml version="1.0"?><methodResponse><params><param><value>
<string>hello</string>
<string>world</string>
</value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->faultCode();
        $this->assertEquals(2, $v);
$f = '<?xml version="1.0"?><methodResponse><params><param><value>
<string>hello</string>
<struct><member><name>hello><value>world</value></member></struct>
</value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->faultCode();
        $this->assertEquals(2, $v);
    }

    function testAutodecodeResponse()
    {
        $s = new xmlrpcmsg('dummy');
$f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 2 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, true, 'phpvals');
        $v = $r->value();
        $s = $v['content'];
        $this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s);
    }

    function testNoDecodeResponse()
    {
        $s = new xmlrpcmsg('dummy');
$f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 2 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>';
        $r = $s->parseResponse($f, true, 'xml');
        $v = $r->value();
        $this->assertEquals($f, $v);
    }

    function testAutoCoDec()
    {
        $data1 = array(1, 1.0, 'hello world', true, '20051021T23:43:00', -1, 11.0, '~!@#$%^&*()_+|', false, '20051021T23:43:00');
        $data2 = array('zero' => $data1, 'one' => $data1, 'two' => $data1, 'three' => $data1, 'four' => $data1, 'five' => $data1, 'six' => $data1, 'seven' => $data1, 'eight' => $data1, 'nine' => $data1);
        $data = array($data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2);
        //$keys = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine');
        $v1 = php_xmlrpc_encode($data, array('auto_dates'));
        $v2 = php_xmlrpc_decode_xml($v1->serialize());
        $this->assertEquals($v1, $v2);
        $r1 = new xmlrpcresp($v1);
        $r2 = php_xmlrpc_decode_xml($r1->serialize());
        $r2->serialize(); // needed to set internal member payload
        $this->assertEquals($r1, $r2);
        $m1 = new xmlrpcmsg('hello dolly', array($v1));
        $m2 = php_xmlrpc_decode_xml($m1->serialize());
        $m2->serialize(); // needed to set internal member payload
        $this->assertEquals($m1, $m2);
    }

    function testUTF8Request()
    {
        $sendstring='Îºá½¹ÏƒÎ¼Îµ'; // Greek word 'kosme'. NB: NOT a valid ISO8859 string!
        $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
        $f = new xmlrpcval($sendstring, 'string');
        $v=$f->serialize();
        $this->assertEquals("<value><string>&#954;&#8057;&#963;&#956;&#949;</string></value>\n", $v);
        $GLOBALS['xmlrpc_internalencoding'] = 'ISO-8859-1';
    }

    function testUTF8Response()
    {
        $s = new xmlrpcmsg('dummy');
$f = "HTTP/1.1 200 OK\r\nContent-type: text/xml; charset=UTF-8\r\n\r\n".'<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>'.utf8_encode('ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½').'</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, false, 'phpvals');
        $v = $r->value();
        $v = $v['content'];
        $this->assertEquals("ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½", $v);
$f = '<?xml version="1.0" encoding="utf-8"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>'.utf8_encode('ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½').'</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, false, 'phpvals');
        $v = $r->value();
        $v = $v['content'];
        $this->assertEquals("ï¿½ï¿½ï¿½ï¿½ï¿½ï¿½", $v);
    }

    function testUTF8IntString()
    {
        $v=new xmlrpcval(100, 'int');
        $s=$v->serialize('UTF-8');
        $this->assertequals("<value><int>100</int></value>\n", $s);
    }

    function testStringInt()
    {
        $v=new xmlrpcval('hello world', 'int');
        $s=$v->serialize();
        $this->assertequals("<value><int>0</int></value>\n", $s);
    }

    function testStructMemExists()
    {
        $v=php_xmlrpc_encode(array('hello' => 'world'));
        $b=$v->structmemexists('hello');
        $this->assertequals(true, $b);
        $b=$v->structmemexists('world');
        $this->assertequals(false, $b);
    }

    function testNilvalue()
    {
        // default case: we do not accept nil values received
        $v = new xmlrpcval('hello', 'null');
        $r = new xmlrpcresp($v);
        $s = $r->serialize();
        $m = new xmlrpcmsg('dummy');
        $r = $m->parseresponse($s);
        $this->assertequals(2, $r->faultCode());
        // enable reception of nil values
        $GLOBALS['xmlrpc_null_extension'] = true;
        $r = $m->parseresponse($s);
        $v = $r->value();
        $this->assertequals('null', $v->scalartyp());
        // test with the apache version: EX:NIL
        $GLOBALS['xmlrpc_null_apache_encoding'] = true;
        // serialization
        $v = new xmlrpcval('hello', 'null');
        $s = $v->serialize();
        $this->assertequals(1, preg_match( '#<value><ex:nil/></value>#', $s ));
        // deserialization
        $r = new xmlrpcresp($v);
        $s = $r->serialize();
        $r = $m->parseresponse($s);
        $v = $r->value();
        $this->assertequals('null', $v->scalartyp());
        $GLOBALS['xmlrpc_null_extension'] = false;
        $r = $m->parseresponse($s);
        $this->assertequals(2, $r->faultCode());
    }

    function TestLocale()
    {
        $locale = setlocale(LC_NUMERIC, 0);
        /// @todo on php 5.3/win setting locale to german does not seem to set decimal separator to comma...
        if (setlocale(LC_NUMERIC,'deu', 'de_DE@euro', 'de_DE', 'de', 'ge') !== false)
        {
            $v = new xmlrpcval(1.1, 'double');
            if (strpos($v->scalarval(), ',') == 1)
            {
                $r = $v->serialize();
                $this->assertequals(false, strpos($r, ','));
            }
            setlocale(LC_NUMERIC, $locale);
        }
    }
}

class InvalidHostTests extends PHPUnit_TestCase
{
    var $client = null;

    function setUp()
    {
        global $DEBUG,$LOCALSERVER;
        $this->client=new xmlrpc_client('/NOTEXIST.php', $LOCALSERVER, 80);
        if($DEBUG)
        {
            $this->client->setDebug($DEBUG);
        }
    }

    function test404()
    {
        $f = new xmlrpcmsg('examples.echo',array(
            new xmlrpcval('hello', 'string')
        ));
        $r = $this->client->send($f, 5);
        $this->assertEquals(5, $r->faultCode());
    }

    function testSrvNotFound()
    {
        $f = new xmlrpcmsg('examples.echo',array(
            new xmlrpcval('hello', 'string')
        ));
        $this->client->server .= 'XXX';
        $r = $this->client->send($f, 5);
        $this->assertEquals(5, $r->faultCode());
    }

    function testCurlKAErr()
    {
        global $LOCALSERVER, $URI;
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test curl keepalive errors');
            return;
        }
        $f = new xmlrpcmsg('examples.stringecho',array(
            new xmlrpcval('hello', 'string')
        ));
        // test 2 calls w. keepalive: 1st time connection ko, second time ok
        $this->client->server .= 'XXX';
        $this->client->keepalive = true;
        $r = $this->client->send($f, 5, 'http11');
        // in case we have a "universal dns resolver" getting in the way, we might get a 302 instead of a 404
        $this->assertTrue($r->faultCode() === 8 || $r->faultCode() == 5);

        // now test a successful connection
        $server = explode(':', $LOCALSERVER);
        if(count($server) > 1)
        {
            $this->client->port = $server[1];
        }
        $this->client->server = $server[0];
        $this->client->path = $URI;

        $r = $this->client->send($f, 5, 'http11');
        $this->assertEquals(0, $r->faultCode());
        $ro = $r->value();
        is_object( $ro ) && $this->assertEquals('hello', $ro->scalarVal());
    }
}


$suite->addTest(new LocalhostTests('testString'));
$suite->addTest(new LocalhostTests('testAdding'));
$suite->addTest(new LocalhostTests('testAddingDoubles'));
$suite->addTest(new LocalhostTests('testInvalidNumber'));
$suite->addTest(new LocalhostTests('testBoolean'));
$suite->addTest(new LocalhostTests('testCountEntities'));
$suite->addTest(new LocalhostTests('testBase64'));
$suite->addTest(new LocalhostTests('testDateTime'));
$suite->addTest(new LocalhostTests('testServerMulticall'));
$suite->addTest(new LocalhostTests('testClientMulticall1'));
$suite->addTest(new LocalhostTests('testClientMulticall2'));
$suite->addTest(new LocalhostTests('testClientMulticall3'));
$suite->addTest(new LocalhostTests('testCatchWarnings'));
$suite->addTest(new LocalhostTests('testCatchExceptions'));
$suite->addTest(new LocalhostTests('testZeroParams'));
$suite->addTest(new LocalhostTests('testCodeInjectionServerSide'));
$suite->addTest(new LocalhostTests('testAutoRegisteredFunction'));
$suite->addTest(new LocalhostTests('testAutoRegisteredMethod'));
$suite->addTest(new LocalhostTests('testSetCookies'));
$suite->addTest(new LocalhostTests('testGetCookies'));
$suite->addTest(new LocalhostTests('testSendTwiceSameMsg'));

$suite->addTest(new LocalhostMultiTests('testUTF8Requests'));
$suite->addTest(new LocalhostMultiTests('testUTF8Responses'));
$suite->addTest(new LocalhostMultiTests('testISORequests'));
$suite->addTest(new LocalhostMultiTests('testISOResponses'));
$suite->addTest(new LocalhostMultiTests('testGzip'));
$suite->addTest(new LocalhostMultiTests('testDeflate'));
$suite->addTest(new LocalhostMultiTests('testProxy'));
$suite->addTest(new LocalhostMultiTests('testHttp11'));
$suite->addTest(new LocalhostMultiTests('testHttp11Gzip'));
$suite->addTest(new LocalhostMultiTests('testHttp11Deflate'));
$suite->addTest(new LocalhostMultiTests('testKeepAlives'));
$suite->addTest(new LocalhostMultiTests('testHttp11Proxy'));
$suite->addTest(new LocalhostMultiTests('testHttps'));
$suite->addTest(new LocalhostMultiTests('testHttpsProxy'));

$suite->addTest(new InvalidHostTests('test404'));
//$suite->addTest(new InvalidHostTests('testSrvNotFound'));
$suite->addTest(new InvalidHostTests('testCurlKAErr'));

$suite->addTest(new ParsingBugsTests('testMinusOneString'));
$suite->addTest(new ParsingBugsTests('testUnicodeInMemberName'));
$suite->addTest(new ParsingBugsTests('testUnicodeInErrorString'));
$suite->addTest(new ParsingBugsTests('testValidNumbers'));
$suite->addTest(new ParsingBugsTests('testAddScalarToStruct'));
$suite->addTest(new ParsingBugsTests('testAddStructToStruct'));
$suite->addTest(new ParsingBugsTests('testAddArrayToArray'));
$suite->addTest(new ParsingBugsTests('testEncodeArray'));
$suite->addTest(new ParsingBugsTests('testEncodeRecursive'));
$suite->addTest(new ParsingBugsTests('testBrokenrequests'));
$suite->addTest(new ParsingBugsTests('testBrokenresponses'));
$suite->addTest(new ParsingBugsTests('testBuggyHttp'));
$suite->addTest(new ParsingBugsTests('testStringBug'));
$suite->addTest(new ParsingBugsTests('testWhiteSpace'));
$suite->addTest(new ParsingBugsTests('testAutodecodeResponse'));
$suite->addTest(new ParsingBugsTests('testNoDecodeResponse'));
$suite->addTest(new ParsingBugsTests('testAutoCoDec'));
$suite->addTest(new ParsingBugsTests('testUTF8Response'));
$suite->addTest(new ParsingBugsTests('testUTF8Request'));
$suite->addTest(new ParsingBugsTests('testUTF8IntString'));
$suite->addTest(new ParsingBugsTests('testStringInt'));
$suite->addTest(new ParsingBugsTests('testStructMemExists'));
$suite->addTest(new ParsingBugsTests('testDoubleDataInArrayTag'));
$suite->addTest(new ParsingBugsTests('testDoubleStuffInValueTag'));
$suite->addTest(new ParsingBugsTests('testNilValue'));
$suite->addTest(new ParsingBugsTests('testLocale'));

$title = 'XML-RPC Unit Tests';

if(isset($only))
{
    $suite = new PHPUnit_TestSuite($only);
}

if(isset($_SERVER['REQUEST_METHOD']))
{
    echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">\n<head>\n<title>$title</title>\n</head>\n<body>\n<h1>$title</h1>\n";
}
else
{
    echo "$title\n\n";
}

if(isset($_SERVER['REQUEST_METHOD']))
{
    echo "<h3>Using lib version: $xmlrpcVersion on PHP version: ".phpversion()."</h3>\n";
    echo '<h3>Running '.$suite->testCount().' tests (some of which are multiple) against servers: http://'.htmlspecialchars($LOCALSERVER.$URI).' and https://'.htmlspecialchars($HTTPSSERVER.$HTTPSURI)."\n ...</h3>\n";
    flush();
    @ob_flush();
}
else
{
    echo "Using lib version: $xmlrpcVersion on PHP version: ".phpversion()."\n";
    echo 'Running '.$suite->testCount().' tests (some of which are multiple) against servers: http://'.$LOCALSERVER.$URI.' and https://'.$HTTPSSERVER.$HTTPSURI."\n\n";
}

// do some basic timing measurement
list($micro, $sec) = explode(' ', microtime());
$start_time = $sec + $micro;

$PHPUnit = new PHPUnit;
$result = $PHPUnit->run($suite, ($DEBUG == 0 ? '.' : '<hr/>'));

list($micro, $sec) = explode(' ', microtime());
$end_time = $sec + $micro;

if(!isset($_SERVER['REQUEST_METHOD']))
{
    echo $result->toString()."\n";
}

if(isset($_SERVER['REQUEST_METHOD']))
{
    echo '<h3>'.$result->failureCount()." test failures</h3>\n";
    printf("Time spent: %.2f secs<br/>\n", $end_time - $start_time);
}
else
{
    echo $result->failureCount()." test failures\n";
    printf("Time spent: %.2f secs\n", $end_time - $start_time);
}

if($result->failureCount() && !$DEBUG)
{
    $target = strpos($_SERVER['PHP_SELF'], '?') ? $_SERVER['PHP_SELF'].'&amp;DEBUG=1' : $_SERVER['PHP_SELF'].'?DEBUG=1';
    $t2 = strpos($_SERVER['PHP_SELF'], '?') ? $_SERVER['PHP_SELF'].'&amp;DEBUG=2' : $_SERVER['PHP_SELF'].'?DEBUG=2';
    if(isset($_SERVER['REQUEST_METHOD']))
    {
        echo '<p>Run testsuite with <a href="'.$target.'">DEBUG=1</a> to have more detail about tests results. Or with <a href="'.$t2.'">DEBUG=2</a> for even more.</p>'."\n";
    }
    else
    {
        echo "Run testsuite with DEBUG=1 (or 2) to have more detail about tests results\n";
    }
}

if(isset($_SERVER['REQUEST_METHOD']))
{
?>
<a href="#" onclick="if (document.getElementById('opts').style.display == 'block') document.getElementById('opts').style.display = 'none'; else document.getElementById('opts').style.display = 'block';">More options...</a>
<div id="opts" style="display: none;">
<form method="GET" style="border: 1px solid silver; margin: 5px; padding: 5px; font-family: monospace;">
HTTP Server:&nbsp;&nbsp;<input name="LOCALSERVER" size="30" value="<?php echo htmlspecialchars($LOCALSERVER); ?>"/> Path: <input name="URI"  size="30" value="<?php echo htmlspecialchars($URI); ?>"/><br/>
HTTPS Server: <input name="HTTPSSERVER" size="30" value="<?php echo htmlspecialchars($HTTPSSERVER); ?>"/> Path: <input name="HTTPSURI"  size="30" value="<?php echo htmlspecialchars($HTTPSURI); ?>"/> Do not verify cert: <input name="HTTPSIGNOREPEER" value="true" type="checkbox" <?php if ($HTTPSIGNOREPEER) echo 'checked="checked"'; ?>/><br/>

Proxy Server: <input name="PROXY" size="30" value="<?php echo isset($PROXY) ? htmlspecialchars($PROXY) : ''; ?>"/> <input type="submit" value="Run Testsuite"/>
</form>
</div>
<?php
    echo $result->toHTML()."\n</body>\n</html>\n";
}
else
{
    exit($result->failureCount());
}
?>