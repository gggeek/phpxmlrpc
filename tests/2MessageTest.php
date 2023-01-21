<?php

include_once __DIR__ . '/../lib/xmlrpc.inc';
include_once __DIR__ . '/../lib/xmlrpcs.inc';

include_once __DIR__ . '/parse_args.php';

include_once __DIR__ . '/PolyfillTestCase.php';

use PHPUnit\Runner\BaseTestRunner;

/**
 * Tests involving the Request and Response classes.
 *
 * @todo many tests are here only because we use a Response to trigger parsing of xml for a single Value, but they
 *       logically belong elsewhere...
 */
class MessageTests extends PhpXmlRpc_PolyfillTestCase
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

    protected function newMsg($methodName, $params = array())
    {
        $msg = new xmlrpcmsg($methodName, $params);
        $msg->setDebug($this->args['DEBUG']);
        return $msg;
    }

    public function testValidNumbers()
    {
        $m = $this->newMsg('dummy');
        $fp =
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
<name>integer2</name>
<value><int>+1</int></value>
</member>
<member>
<name>integer3</name>
<value><i4>1</i4></value>
</member>
<member>
<name>float1</name>
<value><double>01.10</double></value>
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
        $r = $m->parseResponse($fp);
        $v = $r->value();
        $s = $v->structmem('integer1');
        $t = $v->structmem('integer2');
        $u = $v->structmem('integer3');
        $x = $v->structmem('float1');
        $y = $v->structmem('float2');
        $z = $v->structmem('float3');
        $this->assertEquals(1, $s->scalarval());
        $this->assertEquals(1, $t->scalarval());
        $this->assertEquals(1, $u->scalarval());

        $this->assertEquals(1.1, $x->scalarval());
        $this->assertEquals(1.1, $y->scalarval());
        $this->assertEquals(-110.0, $z->scalarval());
    }

    public function testI8()
    {
        if (PHP_INT_SIZE == 4 ) {
            $this->markTestSkipped('Can not test i8 as php is compiled in 32 bit mode');
            return;
        }

        $m = $this->newMsg('dummy');
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
</struct>
</value>
</param>
</params>
</methodResponse>';
        $r = $m->parseResponse($fp);
        $v = $r->value();
        $s = $v->structmem('integer1');
        $this->assertEquals(1, $s->scalarval());
    }

    public function testUnicodeInMemberName()
    {
        $str = "G" . chr(252) . "nter, El" . chr(232) . "ne";
        $v = array($str => new xmlrpcval(1));
        $r = new xmlrpcresp(new xmlrpcval($v, 'struct'));
        $r = $r->serialize();
        $m = $this->newMsg('dummy');
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
        $m = $this->newMsg('dummy');
        $r = $m->parseResponse($response);
        $v = $r->faultString();
        $this->assertEquals(chr(224) . chr(252) . chr(232) . chr(224) . chr(252) . chr(232), $v);
    }

    public function testBrokenRequests()
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

    public function testBrokenResponses()
    {
        $m = $this->newMsg('dummy');
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

    public function testBuggyHttp()
    {
        $s = $this->newMsg('dummy');
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
        $s = $this->newMsg('dummy');
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

    public function testWhiteSpace()
    {
        $s = $this->newMsg('dummy');
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
        $s = $this->newMsg('dummy');
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
        $s = $this->newMsg('dummy');
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
        $s = $this->newMsg('dummy');
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
        $s = $this->newMsg('dummy');
        $f = '<?xml version="1.0"?><methodResponse><params><param><value><struct><member><name>userid</name><value>311127</value></member>
<member><name>dateCreated</name><value><dateTime.iso8601>20011126T09:17:52</dateTime.iso8601></value></member><member><name>content</name><value>hello world. 3 newlines follow


and there they were.</value></member><member><name>postid</name><value>7414222</value></member></struct></value></param></params></methodResponse>';
        $r = $s->parseResponse($f, true, 'xml');
        $v = $r->value();
        $this->assertEquals($f, $v);
    }

    public function testUTF8Request()
    {
        $sendstring = 'κόσμε'; // Greek word 'kosme'
        $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
        $f = new xmlrpcval($sendstring, 'string');
        $v = $f->serialize();
        $this->assertEquals("<value><string>&#954;&#8057;&#963;&#956;&#949;</string></value>\n", $v);
        $GLOBALS['xmlrpc_internalencoding'] = 'ISO-8859-1';
        \PhpXmlRpc\PhpXmlRpc::importGlobals();
    }

    public function testUTF8Response()
    {
        $string = chr(224) . chr(252) . chr(232);

        $s = $this->newMsg('dummy');
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

        $s = $this->newMsg('dummy');
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

    /// @todo can we change this test to purely using the Value class ?
    public function testNilvalue()
    {
        // default case: we do not accept nil values received
        $v = new xmlrpcval('hello', 'null');
        $r = new xmlrpcresp($v);
        $s = $r->serialize();
        $m = $this->newMsg('dummy');
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
}
