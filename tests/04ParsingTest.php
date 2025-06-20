<?php

include_once __DIR__ . '/LoggerAwareTestCase.php';

/**
 * Tests involving xml parsing.
 *
 * @todo some tests are here even though they logically belong elsewhere...
 */
class ParsingTest extends PhpXmlRpc_LoggerAwareTestCase
{
    protected function newRequest($methodName, $params = array())
    {
        $msg = new xmlrpcmsg($methodName, $params);
        $msg->setDebug($this->args['DEBUG']);
        return $msg;
    }

    public function testValidNumbers()
    {
        $m = $this->newRequest('dummy');
        $fp =
            '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value>
<struct>
<member><name>integer1</name><value><int>01</int></value></member>
<member><name>integer2</name><value><int>+1</int></value></member>
<member><name>integer3</name><value><i4>1</i4></value></member>
<member><name>integer4</name><value><int> 1 </int></value></member>
<member><name>float1</name><value><double>01.10</double></value></member>
<member><name>float2</name><value><double>+1.10</double></value></member>
<member><name>float3</name><value><double>-1.10e2</double></value></member>
<member><name>float4</name><value><double> -1.10e2 </double></value></member>
</struct>
</value>
</param>
</params>
</methodResponse>';
        $r = $m->parseResponse($fp);
        $v = $r->value();
        $s = $v->structmem('integer1');
        $t = $v->structmem('integer2');
        $u = $v->structmem('integer3');
        $u2 = $v->structmem('integer4');
        $x = $v->structmem('float1');
        $y = $v->structmem('float2');
        $z = $v->structmem('float3');
        $z2 = $v->structmem('float4');
        $this->assertEquals(1, $s->scalarval());
        $this->assertEquals(1, $t->scalarval());
        $this->assertEquals(1, $u->scalarval());
        $this->assertEquals(1, $u2->scalarval());
        $this->assertEquals('int', $u->scalartyp());

        $this->assertEquals(1.1, $x->scalarval());
        $this->assertEquals(1.1, $y->scalarval());
        $this->assertEquals(-110.0, $z->scalarval());
        $this->assertEquals(-110.0, $z2->scalarval());
    }

    public function testBooleans()
    {
        $m = $this->newRequest('dummy');
        $fp =
            '<?xml version="1.0"?>
<methodResponse><params><param><value><struct>
<member><name>b1</name>
<value><boolean>1</boolean></value></member>
<member><name>b2</name>
<value><boolean> 1 </boolean></value></member>
<member><name>b3</name>
<value><boolean>tRuE</boolean></value></member>
<member><name>b4</name>
<value><boolean>0</boolean></value></member>
<member><name>b5</name>
<value><boolean> 0 </boolean></value></member>
<member><name>b6</name>
<value><boolean>fAlSe</boolean></value></member>
</struct></value></param></params></methodResponse>';
        $r = $m->parseResponse($fp);
        $v = $r->value();

        $s = $v->structmem('b1');
        $t = $v->structmem('b2');
        $u = $v->structmem('b3');
        $x = $v->structmem('b4');
        $y = $v->structmem('b5');
        $z = $v->structmem('b6');

        /// @todo this test fails with phpunit, but the same code works elsewhere! It makes string-int casting stricter??
        $this->assertEquals(true, $s->scalarval());
        //$this->assertEquals(true, $t->scalarval());
        $this->assertEquals(true, $u->scalarval());
        $this->assertEquals(false, $x->scalarval());
        //$this->assertEquals(false, $y->scalarval());
        $this->assertEquals(false, $z->scalarval());
    }

    public function testI8()
    {
        if (PHP_INT_SIZE == 4 ) {
            $this->markTestSkipped('Can not test i8 as php is compiled in 32 bit mode');
        }

        $m = $this->newRequest('dummy');
        $fp =
            '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value>
<struct>
<member>
<name>integer1</name>
<value><i8>1</i8></value>
</member>
<member>
<name>integer2</name>
<value><ex:i8>1</ex:i8></value>
</member>
</struct>
</value>
</param>
</params>
</methodResponse>';
        $r = $m->parseResponse($fp);
        $v = $r->value();
        $s = $v->structmem('integer1');
        $this->assertEquals(1, $s->scalarval());
        $s = $v->structmem('integer2');
        $this->assertEquals(1, $s->scalarval());
        $this->assertEquals('i8', $s->scalartyp());
    }

    // struct with value before name, with no name, with no value, etc...
    public function testQuirkyStruct()
    {
        $m = $this->newRequest('dummy');
        $fp =
            '<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value>
<struct>
<member>
<value><int>1</int></value>
<name>Gollum</name>
</member>
<member>
<name>Bilbo</name>
</member>
<member>
<value><int>9</int></value>
</member>
<member>
<value><int>1</int></value>
</member>
</struct>
</value>
</param>
</params>
</methodResponse>';
        $r = $m->parseResponse($fp);
        $v = $r->value();
        $this->assertEquals(2, count($v));
        $s = $v['Gollum'];
        $this->assertEquals(1, $s->scalarval());
        $s = $v[''];
        $this->assertEquals(1, $s->scalarval());
    }

    public function testUnicodeInMemberName()
    {
        $str = "G" . chr(252) . "nter, El" . chr(232) . "ne";
        $v = array($str => new xmlrpcval(1));
        $r = new xmlrpcresp(new xmlrpcval($v, 'struct'));
        $r = $r->serialize();
        $m = $this->newRequest('dummy');
        $r = $m->parseResponse($r);
        $v = $r->value();
        $this->assertEquals(true, $v->structmemexists($str));
    }

    public function testUnicodeInErrorString()
    {
        // the warning suppression is due to utf8_decode being deprecated in php 8.2
        $response = @utf8_encode(
            '<?xml version="1.0"?>
<!-- covers what happens when lib receives UTF8 chars in response text and comments -->
<!-- ' . chr(224) . chr(252) . chr(232) . '&#224;&#252;&#232; -->
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
<value><string>' . chr(224) . chr(252) . chr(232) . '&#224;&#252;&#232;</string></value>
</member>
</struct>
</value>
</fault>
</methodResponse>');
        $m = $this->newRequest('dummy');
        $r = $m->parseResponse($response);
        $v = $r->faultString();
        $this->assertEquals(chr(224) . chr(252) . chr(232) . chr(224) . chr(252) . chr(232), $v);
    }

    public function testBrokenRequests()
    {
        $s = new xmlrpc_server();

        // omitting the 'methodName' tag: not tolerated by the lib anymore
        $f = '<?xml version="1.0"?>
<methodCall>
<params>
<value><string>system.methodHelp</string></value>
</params>
</methodCall>';
        $r = $s->parserequest($f);
        $this->assertEquals(15, $r->faultCode());

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

    public function testBrokenResponses()
    {
        $m = $this->newRequest('dummy');

        // omitting the 'params' tag: no more tolerated by the lib...
        $f = '<?xml version="1.0"?>
<methodResponse>
</methodResponse>';
        $r = $m->parseResponse($f);
        $this->assertEquals(2, $r->faultCode());
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
</params>
</methodResponse>';
        $r = $m->parseResponse($f);
        $this->assertEquals(2, $r->faultCode());
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

        // having both 'params' and 'fault'
        $f = '<?xml version="1.0"?>
<methodResponse>
<params>
<param><value><string>system.methodHelp</string></value></param>
</params>
<fault><value><struct>
<member><name>faultCode</name><value><int>888</int></value></member>
<member><name>faultString</name><value><string>yolo</string></value></member>
</struct></value></fault>
</methodResponse>';
        $r = $m->parseResponse($f);
        $this->assertEquals(2, $r->faultCode());
    }

    public function testBuggyXML()
    {
        $m = $this->newRequest('dummy');
        $r = $m->parseResponse("<\000\000\000\au\006");
        $this->assertEquals(2, $r->faultCode());
        //$this->assertStringContainsString('XML error', $r->faultString());
    }

    public function testBuggyHttp()
    {
        $s = $this->newRequest('dummy');
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

    public function testStringBug()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?>
<!-- found by 2z69xks7bpy001@sneakemail.com, amongst others covers what happens when there\'s character data after </string>
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

    public function testBase64()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?><methodResponse><params><param><value><base64>
aGk=
</base64></value></param></params></methodResponse> ';
        $r = $s->parseResponse($f);
        $v = $r->value();
        $this->assertEquals('hi', $v->scalarval());
    }

    public function testInvalidValues()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?><methodResponse><params><param><value><struct>
<member>
<name>bool</name>
<value><boolean>
yes
</boolean></value>
</member>
<member>
<name>double</name>
<value><double>
1.01
</double></value>
</member>
<member>
<name>int</name>
<value><int>
1
</int></value>
</member>
<member>
<name>date</name>
<value><dateTime.iso8601>
20011126T09:17:52
</dateTime.iso8601></value>
</member>
<member>
<name>base64</name>
<value><base64>
!
</base64></value>
</member>
</struct></value></param></params></methodResponse> ';
        $r = $s->parseResponse($f);
        $v = $r->value();
        // NB: this is the status-quo of the xml parser, rather than something we want the library to always be returning...
        $this->assertEquals(false, $v['bool']->scalarval());
        $this->assertEquals("ERROR_NON_NUMERIC_FOUND", $v['double']->scalarval());
        $this->assertEquals("ERROR_NON_NUMERIC_FOUND", $v['int']->scalarval());
        $this->assertEquals("\n20011126T09:17:52\n", $v['date']->scalarval());
        $this->assertEquals("", $v['base64']->scalarval());
    }

    public function testInvalidValuesStrictMode()
    {
        $s = $this->newRequest('dummy');

        $values = array(
            '<boolean>x</boolean>',
            '<double>x</double>',
            '<double>1..</double>',
            '<double>..1</double>',
            '<double>1.0.1</double>',
            '<int>x</int>',
            '<int>1.0</int>',
            '<dateTime.iso8601> 20011126T09:17:52</dateTime.iso8601>',
            '<dateTime.iso8601>20011126T09:17:52 </dateTime.iso8601>',
            '<base64>!</base64>'
        );

        $i = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_reject_invalid_values;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_reject_invalid_values = true;

        foreach($values as $value) {
            $f = '<?xml version="1.0"?><methodResponse><params><param><value>' . $value . '</value></param></params></methodResponse> ';
            $r = $s->parseResponse($f);
            $v = $r->faultCode();
            $this->assertEquals(2, $v, "Testing $value");
        }

        /// @todo move to tear_down(), so that we reset this even in case of test failure
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_reject_invalid_values = $i;
    }

    public function testNewlines()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 2 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f);
        $v = $r->value();
        $s = $v->structmem('content');
        $this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s->scalarval());
    }

    public function testDoubleDataInArrayTag()
    {
        $s = $this->newRequest('dummy');
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

    public function testDoubleStuffInValueTag()
    {
        $s = $this->newRequest('dummy');
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

    public function testAutoDecodeResponse()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 3 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, true, 'phpvals');
        $v = $r->value();
        $s = $v['content'];
        $this->assertEquals("hello world. 3 newlines follow\n\n\nand there they were.", $s);
    }

    public function testNoDecodeResponse()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 3 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>';
        $r = $s->parseResponse($f, true, 'xml');
        $v = $r->value();
        $this->assertEquals($f, $v);
    }

    public function testUTF8Response()
    {
        $string = chr(224) . chr(252) . chr(232);

        $s = $this->newRequest('dummy');
        $f = "HTTP/1.1 200 OK\r\nContent-type: text/xml; charset=UTF-8\r\n\r\n" . '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>' . @utf8_encode($string) . '</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, false, 'phpvals');
        $v = $r->value();
        $v = $v['content'];
        $this->assertEquals($string, $v);

        $f = '<?xml version="1.0" encoding="UTF-8"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>' . @utf8_encode($string) . '</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, false, 'phpvals');
        $v = $r->value();
        $v = $v['content'];
        $this->assertEquals($string, $v);

        /// @todo move to EncoderTest
        $r = php_xmlrpc_decode_xml($f);
        $v = $r->value();
        $v = $v->structmem('content')->scalarval();
        $this->assertEquals($string, $v);
    }

    public function testLatin1Response()
    {
        $string = chr(224) . chr(252) . chr(232);

        $s = $this->newRequest('dummy');
        $f = "HTTP/1.1 200 OK\r\nContent-type: text/xml; charset=ISO-8859-1\r\n\r\n" . '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>' . $string . '</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, false, 'phpvals');
        $v = $r->value();
        $v = $v['content'];
        $this->assertEquals($string, $v);

        $f = '<?xml version="1.0" encoding="ISO-8859-1"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>' . $string . '</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>
';
        $r = $s->parseResponse($f, false, 'phpvals');
        $v = $r->value();
        $v = $v['content'];
        $this->assertEquals($string, $v);

        /// @todo move to EncoderTest
        $r = php_xmlrpc_decode_xml($f);
        $v = $r->value();
        $v = $v->structmem('content')->scalarval();
        $this->assertEquals($string, $v);
    }

    public function testDatetimeAsObject()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?>
<methodResponse><params><param><value>
<dateTime.iso8601>20011126T09:17:52</dateTime.iso8601>
</value></param></params></methodResponse>';

        $o = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_return_datetimes;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_return_datetimes = true;

        $r = $s->parseResponse($f);
        $v = $r->value();
        $this->assertInstanceOf('\DateTime', $v->scalarval());

        /// @todo move to tear_down(), so that we reset this even in case of test failure
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_return_datetimes = $o;
    }

    public function testCustomDatetimeFormat()
    {
        $s = $this->newRequest('dummy');
        $f = '<?xml version="1.0"?>
<methodResponse><params><param><value>
<dateTime.iso8601>20011126T09:17:52+01:00</dateTime.iso8601>
</value></param></params></methodResponse>';

        $o = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_return_datetimes;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_return_datetimes = true;
        $i = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_reject_invalid_values;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_reject_invalid_values = true;

        $r = $s->parseResponse($f);
        $v = $r->faultCode();
        $this->assertNotEquals(0, $v);

        $d = \PhpXmlRpc\PhpXmlRpc::$xmlrpc_datetime_format;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_datetime_format = '/^([0-9]{4})(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-4]):([0-5][0-9]):([0-5][0-9]|60)(Z|[+-][0-9]{2}(:?[0-9]{2})?)?$/';

        $r = $s->parseResponse($f);
        $v = $r->value();
        $this->assertInstanceOf('\DateTime', $v->scalarval());

        /// @todo move to tear_down(), so that we reset these even in case of test failure
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_return_datetimes = $o;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_reject_invalid_values = $i;
        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_datetime_format = $d;
    }

    /// @todo can we change this test to purely using the Value class ?
    /// @todo move test to its own class
    public function testNilSupport()
    {
        // default case: we do not accept nil values received
        $v = new xmlrpcval('hello', 'null');
        $r = new xmlrpcresp($v);
        $s = $r->serialize();
        $m = $this->newRequest('dummy');
        $r = $m->parseresponse($s);
        $this->assertequals(2, $r->faultCode());
        // enable reception of nil values
        $GLOBALS['xmlrpc_null_extension'] = true;
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
        $r = $m->parseresponse($s);
        $v = $r->value();
        $this->assertequals('null', $v->scalartyp());
        // test with the apache version: EX:NIL
        $GLOBALS['xmlrpc_null_apache_encoding'] = true;
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
        // serialization
        $v = new xmlrpcval('hello', 'null');
        $s = $v->serialize();
        $this->assertequals(1, preg_match('#<value><ex:nil/></value>#', $s));
        // deserialization
        $r = new xmlrpcresp($v);
        $s = $r->serialize();
        $r = $m->parseresponse($s);
        $v = $r->value();
        $this->assertequals('null', $v->scalartyp());
        $GLOBALS['xmlrpc_null_extension'] = false;
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
        $r = $m->parseresponse($s);
        $this->assertequals(2, $r->faultCode());
    }

    public function testXXE()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE foo [
    <!ENTITY xxe SYSTEM "https://www.google.com/favicon.ico">
]>
<methodResponse>
    <params>
        <param>
            <value><string>&xxe;</string></value>
        </param>
    </params>
</methodResponse>
';
        $req = new \PhpXmlRpc\Request('hi');
        $resp = $req->parseResponse($xml, true);
        $val = $resp->value();
        if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
            $this->assertequals('', $val->scalarVal());
        } else {
            $this->assertequals('&xxe;', $val->scalarVal());
        }
    }
}
