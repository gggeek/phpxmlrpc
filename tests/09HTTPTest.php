<?php

include_once __DIR__ . '/08ServerTest.php';

/**
 * Tests which stress http features of the library.
 * Each of these tests iterates over (almost) all the 'Server' tests.
 *
 * @todo refactor:
 *       - pick a smaller subset of 'base tests' to iterate over, for every http feature
 *       - test more exhaustive combinations of compression/auth/ssl/charset/curl-or-socket/proxy/etc.. features
 *       - move SSLVERSION from being passed in as an arg to being something we exhaustively test using a dataprovider
 */
class HTTPTest extends ServerTest
{
    protected $expectHttp2 = false;

    protected $unsafeMethods = array(
        'testCatchExceptions', 'testCatchErrors', 'testUtf8Method', 'testServerComments',
        'testExoticCharsetsRequests', 'testExoticCharsetsRequests2', 'testExoticCharsetsRequests3',
        'testWrapInexistentUrl', 'testNegativeDebug', 'testTimeout'
    );

    /**
     * Returns all test methods from the base class, except the ones which failed already and the ones which make no sense
     * to run with different HTTP options.
     *
     * @todo (re)introduce skipping of tests which failed when executed individually even if test runs happen as separate processes
     * @todo reintroduce skipping of tests within the loop
     * @todo testTimeout is actually good to be tested with proxies etc - but it slows down the testsuite a lot!
     * @todo see also 'refactor' todo at the top of the class declaration
     */
    public function getSingleHttpTestMethods()
    {
        $methods = array();
        // as long as we are descendants, get_class_methods will list private/protected methods
        foreach(get_class_methods('ServerTest') as $method)
        {
            if (strpos($method, 'test') === 0 && !in_array($method, $this->unsafeMethods))
            {
                if (!isset(self::$failed_tests[$method])) {
                    $methods[$method] = array($method);
                }
            }
        }

        return $methods;
    }

    /**
     * @param \PhpXmlRpc\Response $r
     * @return void
     */
    protected function validateResponse($r)
    {
        if ($this->expectHttp2) {
            $hr = $r->httpResponse();
            $this->assertEquals("2", @$hr['protocol_version'], 'Server response not using http version 2');
        }
    }

    public function testKeepAlives()
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
        }

        $this->method = 'http11';
        $this->client->method = 'http11';
        $this->client->keepalive = true;

        // to successfully test keepalive, we have to reuse the same client for all tests, we can not recreate one on setup/teardown...
        foreach ($this->getSingleHttpTestMethods() as $methods) {
            $method = $methods[0];
            $this->$method();
        }
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testRedirects($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test redirects');
        }

        /// @todo replace with setOption when dropping the BC layer
        $this->client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_ALWAYS);
        $this->client->setCurlOptions(array(CURLOPT_FOLLOWLOCATION => true, CURLOPT_POSTREDIR => 3));

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
        }

        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->keepalive = false;

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp10Curl($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
        }

        $this->method = 'http10'; // not an error the double assignment!
        $this->client->method = 'http10';
        /// @todo replace with setOption when dropping the BC layer
        $this->client->keepalive = false;
        $this->client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_ALWAYS);

        $this->$method();
    }

    /**
     * @dataProvider getAvailableUseCurlOptions
     */
    public function testTimeout($curlOpt)
    {
        $this->client->setOption(\PhpXmlRpc\Client::OPT_USE_CURL, $curlOpt);

        // decrease the timeout to avoid slowing down the testsuite too much
        $this->timeout = 3;

        // the server will wait for 1 second before sending back the response - should pass
        $m = new xmlrpcmsg('tests.sleep', array(new xmlrpcval(1, 'int')));
        // this checks for a non-failed call
        $time = microtime(true);
        $this->send($m);
        $time = microtime(true) - $time;
        $this->assertGreaterThan(1.0, $time);
        $this->assertLessThan(2.0, $time);

        // the server will wait for 5 seconds before sending back the response - fail
        $m = new xmlrpcmsg('tests.sleep', array(new xmlrpcval(5, 'int')));
        $time = microtime(true);
        $r = $this->send($m, array(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['http_error'], PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['curl_fail']));
        $time = microtime(true) - $time;
        $this->assertGreaterThan(2.0, $time);
        $this->assertLessThan(4.0, $time);

        /*
        // the server will send back the response one chunk per second, waiting 5 seconds in between chunks
        $m = new xmlrpcmsg('examples.addtwo', array(new xmlrpcval(1, 'int'), new xmlrpcval(2, 'int')));
        $this->addQueryParams(array('SLOW_LORIS' => 5));
        $time = microtime(true);
        $this->send($m, array(PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['http_error'], PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['curl_fail']));
        $time = microtime(true) - $time;
        $this->assertGreaterThan(2.0, $time);
        $this->assertLessThan(4.0, $time);
        */

        // pesky case: the server will send back the response one chunk per second, taking 10 seconds in total
        $m = new xmlrpcmsg('examples.addtwo', array(new xmlrpcval(1, 'int'), new xmlrpcval(2, 'int')));
        $this->addQueryParams(array('SLOW_LORIS' => 1));
        $time = microtime(true);
        $this->send($m, array(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['http_error'], PhpXmlRpc\PhpXmlRpc::$xmlrpcerr['curl_fail']));
        $time = microtime(true) - $time;
        $this->assertGreaterThan(2.0, $time);
        $this->assertLessThan(4.0, $time);
    }

    // *** auth tests ***

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     * @todo add a basic-auth test using curl
     */
    public function testBasicAuth($method)
    {
        $this->client->setCredentials('test', 'test');
        $this->addQueryParams(array('FORCE_AUTH' => 'Basic'));

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testDigestAuth($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test digest auth functionality');
        }

        $this->client->setCredentials('test', 'test', CURLAUTH_DIGEST);
        $this->addQueryParams(array('FORCE_AUTH' => 'Digest'));
        $this->method = 'http11';
        $this->client->method = 'http11';

        $this->$method();
    }

    /// @todo if curl is onboard, add a test for NTLM auth - note that that will require server-side support,
    ///       see eg. https://modntlm.sourceforge.net/ , https://blog.mayflower.de/125-Accessing-NTLM-secured-resources-with-PHP.html

    // *** compression tests ***

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testDeflate($method)
    {
        if (!function_exists('gzdeflate'))
        {
            $this->markTestSkipped('Zlib missing: cannot test deflate functionality');
        }

        $this->client->accepted_compression = array('deflate');
        $this->client->request_compression = 'deflate';

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testGzip($method)
    {
        if (!function_exists('gzdeflate'))
        {
            $this->markTestSkipped('Zlib missing: cannot test gzip functionality');
        }

        $this->client->accepted_compression = array('gzip');
        $this->client->request_compression = 'gzip';

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11Gzip($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->keepalive = false;
        $this->client->accepted_compression = array('gzip');
        $this->client->request_compression = 'gzip';

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11Deflate($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->keepalive = false;
        $this->client->accepted_compression = array('deflate');
        $this->client->request_compression = 'deflate';

        $this->$method();
    }

    // *** https tests ***

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     * @todo bring back tests for sslversion values 1 to 5, once we figure out how to make curl actually enforce those
     */
    public function testHttpsCurl($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test https functionality');
        }
        else if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https');
        }

        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->path = $this->args['HTTPSURI'];
        /// @todo replace with setOptions when dropping the BC layer
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);

        // It seems that curl will happily always use http2 whenever it has it compiled in. We thus force http 1.1
        // for this test, as we have a dedicated test for http2.
        /// @todo for completeness, we should run at least one test where we also set CURLOPT_SSL_ENABLE_ALPN = false
        $this->client->setOption(\PhpXmlRpc\Client::OPT_EXTRA_CURL_OPTS, array(CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1));

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     * @todo bring back tests for sslversion values 1 to 5, once we figure out the correct combination of php, ssl and
     *       apache which actually work with those
     */
    public function testHttpsSocket($method)
    {
        if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https');
        }

        /// @todo investigate deeper: can we make this test work with php < 7.2?
        ///       See changes in STREAM_CRYPTO_METHOD_TLS constants in 7.2 at https://wiki.php.net/rfc/improved-tls-constants
        ///       and in 5.6 at https://www.php.net/manual/en/migration56.openssl.php#migration56.openssl.crypto-method
        ///       Take into account also that the issue might in fact relate to the server-side (Apache) ssl config
        if (version_compare(PHP_VERSION, '7.2', '<'))
        {
            if (is_readable('/etc/os-release')) {
                $output = file_get_contents('/etc/os-release');
                preg_match('/VERSION="?([0-9]+)/', $output, $matches);
                $ubuntuVersion = @$matches[1];
            } else {
                exec('uname -a', $output, $retval);
                preg_match('/ubunutu([0-9]+)/', $output[0], $matches);
                $ubuntuVersion = @$matches[1];
            }
            if ($ubuntuVersion >= 20 && $this->args['SSLVERSION'] != 6) {
                $this->markTestSkipped('HTTPS via Socket known to fail on php less than 7.2 on Ubuntu 20 and higher');
            }
        }

        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->path = $this->args['HTTPSURI'];
        /// @todo replace with setOptions when dropping the BC layer
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);
        $this->client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_NEVER);

        /// @todo find a value for OPT_EXTRA_SOCKET_OPTS that we can check via an assertion
        /// @see https://www.php.net/manual/en/context.php

        if (version_compare(PHP_VERSION, '8.0', '>='))
        {
            $version = explode('.', PHP_VERSION);
            /// @see https://docs.openssl.org/1.1.1/man3/SSL_CTX_set_security_level/#default-callback-behaviour for levels
            $this->client->setOption(\PhpXmlRpc\Client::OPT_EXTRA_SOCKET_OPTS,
                array('ssl' => array(
                    // security level is available as of php 7.2.0 + openssl 1.1.0 according to the docs
                    'security_level' => min(1 + $version[1], 5),
                    // capture_session_meta was deprecated in php 7.0
                    //'capture_session_meta' => true,
                ))
            );
        }
        $this->$method();
    }

    // *** http2 tests ***

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp2NoTls($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http/2');
        } else if (!defined('CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE'))
        {
            $this->markTestSkipped('CURL http/2 support missing: cannot test http/2');
        }
        $r = $this->send(new \PhpXmlRpc\Request('tests.hasHTTP2'));
        if ($r->scalarVal() != true) {
            $this->markTestSkipped('Server-side support missing: cannot test http/2');
        }

        $this->method = 'h2c'; // not an error the double assignment!
        $this->client->method = 'h2c';
        //$this->client->keepalive = false; // q: is this a good idea?

        $this->expectHttp2 = true;
        $this->$method();
        $this->expectHttp2 = false;
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp2tls($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http/2 tls');
        } else if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test http/2 tls');
        } else if (!defined('CURL_HTTP_VERSION_2_0'))
        {
            $this->markTestSkipped('CURL http/2 support missing: cannot test http/2 tls');
        }
        $r = $this->send(new \PhpXmlRpc\Request('tests.hasHTTP2'));
        if ($r->scalarVal() != true) {
            $this->markTestSkipped('Server-side support missing: cannot test http/2');
        }

        $this->method = 'h2';
        $this->client->method = 'h2';
        $this->client->server = $this->args['HTTPSSERVER'];
        $this->client->path = $this->args['HTTPSURI'];
        /// @todo replace with setOptions when dropping the BC layer
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);

        $this->expectHttp2 = true;
        $this->$method();
        $this->expectHttp2 = false;
    }

    // *** proxy tests ***

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testProxy($method)
    {
        if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXYSERVER definition missing: cannot test proxy');
        }

        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11Proxy($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1 w. proxy');
        }
        else if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXYSERVER definition missing: cannot test proxy w. http 1.1');
        }

        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        $this->client->keepalive = false;

        $this->$method();
    }

    /**
     * @todo this method is known to fail on bionic/php5.5. Investigate if we can fix that.
     *       Error message: "CURL error: gnutls_handshake() failed: The TLS connection was non-properly terminated"
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttpsProxyCurl($method)
    {
        if (!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test https w. proxy');
        }
        else if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXYSERVER definition missing: cannot test proxy w. https');
        }
        else if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https w. proxy');
        }

        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->server = $this->args['HTTPSSERVER'];
        $this->client->path = $this->args['HTTPSURI'];
        /// @todo replace with setOptions when dropping the BC layer
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);
        /// @todo push this override to the test matrix config?
        if (version_compare(PHP_VERSION, '8.0', '>=') && $this->args['SSLVERSION'] == 0)
        {
            $version = explode('.', PHP_VERSION);
            $this->client->setSSLVersion(min(4 + $version[1], 7));
        }

        $this->$method();
    }

    /*  NB: this is not yet supported by the Client class
    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     * /
    public function testHttpsProxySocket($method)
    {
        if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXYSERVER definition missing: cannot test proxy w. https');
        }
        else if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https w. proxy');
        }

        if (version_compare(PHP_VERSION, '7.2', '<'))
        {
            if (is_readable('/etc/os-release')) {
                $output = file_get_contents('/etc/os-release');
                preg_match('/VERSION="?([0-9]+)/', $output, $matches);
                $ubuntuVersion = @$matches[1];
            } else {
                exec('uname -a', $output, $retval);
                preg_match('/ubunutu([0-9]+)/', $output[0], $matches);
                $ubuntuVersion = @$matches[1];
            }
            if ($ubuntuVersion >= 20 && $this->args['SSLVERSION'] != 6) {
                $this->markTestSkipped('HTTPS via Socket known to fail on php less than 7.2 on Ubuntu 20 and higher');
            }
        }

        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->server = $this->args['HTTPSSERVER'];
        $this->client->path = $this->args['HTTPSURI'];
        /// @todo replace with setOptions when dropping the BC layer
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);
        $this->client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_NEVER);
        /// @todo push this override to the test matrix config?
        if (version_compare(PHP_VERSION, '8.1', '>=') && $this->args['SSLVERSION'] == 0)
        {
            $version = explode('.', PHP_VERSION);
            $this->client->setSSLVersion(min(5 + $version[1], 7));
        }

        $this->$method();
    }
    */

    // *** charset tests ***

    public function testAcceptCharset()
    {
        if (version_compare(PHP_VERSION, '5.6.0', '<'))
        {
            $this->markTestSkipped('Cannot test accept-charset on php < 5.6');
        }
        if (!function_exists('mb_list_encodings'))
        {
            $this->markTestSkipped('mbstring missing: cannot test accept-charset');
        }

        $r = new \PhpXmlRpc\Request('examples.stringecho', array(new \PhpXmlRpc\Value('€'))); // chr(164)

        \PhpXmlRpc\PhpXmlRpc::$xmlrpc_internalencoding = 'UTF-8';

        $this->addQueryParams(array('RESPONSE_ENCODING' => 'auto'));
        $this->client->accepted_charset_encodings = array(
            'utf-1234;q=0.1',
            'windows-1252;q=0.8'
        );
        $v = $this->send($r, 0, true);
        $h = $v->httpResponse();
        $this->assertEquals('text/xml; charset=Windows-1252', $h['headers']['content-type']);
        if ($v) {
            $this->assertEquals('€', $v->value()->scalarval());
        }
    }

    /// @todo a better organization of tests could be to move the 4 tests below and all charset-related tests from
    ///       class ServerTest to a dedicated class - and make sure we iterate over each of those with different
    ///       proxy/auth/compression/etc... settings

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testUTF8Responses($method)
    {
        $this->addQueryParams(array('RESPONSE_ENCODING' => 'UTF-8'));

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testUTF8Requests($method)
    {
        $this->client->request_charset_encoding = 'UTF-8';

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testISOResponses($method)
    {
        $this->addQueryParams(array('RESPONSE_ENCODING' => 'ISO-8859-1'));

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testISORequests($method)
    {
        $this->client->request_charset_encoding = 'ISO-8859-1';

        $this->$method();
    }
}
