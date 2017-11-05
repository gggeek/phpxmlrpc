<?php

include_once __DIR__ . '/../lib/xmlrpc.inc';
include_once __DIR__ . '/../lib/xmlrpc_wrappers.inc';

include_once __DIR__ . '/parse_args.php';

include_once __DIR__ . '/3LocalhostTest.php';

/**
 * Tests which stress http features of the library.
 * Each of these tests iterates over (almost) all of the 'localhost' tests
 */
class LocalhostMultiTest extends LocalhostTest
{
    /**
     * Returns all test methods from the base class, except the ones which failed already
     *
     * @todo reintroduce skipping of tests which failed when executed individually if test runs happen as separate processes
     * @todo reintroduce skipping of tests within the loop
     */
    public function getSingleHttpTestMethods()
    {
        $unsafeMethods = array(
            'testCatchExceptions', 'testUtf8Method', 'testServerComments',
            'testExoticCharsetsRequests', 'testExoticCharsetsRequests2', 'testExoticCharsetsRequests3',
        );

        $methods = array();
        foreach(get_class_methods('LocalhostTest') as $method)
        {
            if(strpos($method, 'test') === 0 && !in_array($method, $unsafeMethods))
            {
                if (!isset(self::$failed_tests[$method])) {
                    $methods[$method] = array($method);
                }
            }
        }

        return $methods;
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testDeflate($method)
    {
        if(!function_exists('gzdeflate'))
        {
            $this->markTestSkipped('Zlib missing: cannot test deflate functionality');
            return;
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
        if(!function_exists('gzdeflate'))
        {
            $this->markTestSkipped('Zlib missing: cannot test gzip functionality');
            return;
        }

        $this->client->accepted_compression = array('gzip');
        $this->client->request_compression = 'gzip';

        $this->$method();
    }

    public function testKeepAlives()
    {
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
            return;
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
    public function testProxy($method)
    {
        if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXY definition missing: cannot test proxy');
            return;
        }

        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11($method)
    {
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
            return;
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
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
            return;
        }

        $this->method = 'http10'; // not an error the double assignment!
        $this->client->method = 'http10';
        $this->client->keepalive = false;
        $this->client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_ALWAYS);

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11Gzip($method)
    {
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
            return;
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
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1');
            return;
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->keepalive = false;
        $this->client->accepted_compression = array('deflate');
        $this->client->request_compression = 'deflate';

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttp11Proxy($method)
    {
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test http 1.1 w. proxy');
            return;
        }
        else if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXY definition missing: cannot test proxy w. http 1.1');
            return;
        }

        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        $this->client->keepalive = false;

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttps($method)
    {
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test https functionality');
            return;
        }
        else if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https');
            return;
        }

        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->path = $this->args['HTTPSURI'];
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttpsSocket($method)
    {
        if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https');
            return;
        }

        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->path = $this->args['HTTPSURI'];
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);
        $this->client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_NEVER);

        $this->$method();
    }

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
     */
    public function testHttpsProxy($method)
    {
        if(!function_exists('curl_init'))
        {
            $this->markTestSkipped('CURL missing: cannot test https w. proxy');
            return;
        }
        else if ($this->args['PROXYSERVER'] == '')
        {
            $this->markTestSkipped('PROXY definition missing: cannot test proxy w. https');
            return;
        }
        else if ($this->args['HTTPSSERVER'] == '')
        {
            $this->markTestSkipped('HTTPS SERVER definition missing: cannot test https w. proxy');
            return;
        }

        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        $this->client->path = $this->args['HTTPSURI'];
        $this->client->setSSLVerifyPeer(!$this->args['HTTPSIGNOREPEER']);
        $this->client->setSSLVerifyHost($this->args['HTTPSVERIFYHOST']);
        $this->client->setSSLVersion($this->args['SSLVERSION']);

        $this->$method();
    }

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

    /**
     * @dataProvider getSingleHttpTestMethods
     * @param string $method
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
            return;
        }

        $this->client->setCredentials('test', 'test', CURLAUTH_DIGEST);
        $this->addQueryParams(array('FORCE_AUTH' => 'Digest'));
        $this->method = 'http11';
        $this->client->method = 'http11';

        $this->$method();
    }
}
