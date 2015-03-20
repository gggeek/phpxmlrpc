<?php

include_once __DIR__ . '/../lib/xmlrpc.inc';
include_once __DIR__ . '/../lib/xmlrpc_wrappers.inc';

include_once __DIR__ . '/parse_args.php';

include_once __DIR__ . '/LocalHostTest.php';

class LocalhostMultiTest extends LocalhostTest
{
    /**
     * @todo reintroduce skipping of tests which failed when executed individually if test runs happen as separate processes
     * @todo reintroduce skipping of tests within the loop
     */
    function _runtests()
    {
        foreach(get_class_methods('LocalhostTest') as $method)
        {
            if(strpos($method, 'test') === 0 && $method != 'testHttps' && $method != 'testCatchExceptions')
            {
                if (!isset(self::$failed_tests[$method]))
                    $this->$method();
            }
            /*if ($this->_failed)
            {
                break;
            }*/
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
        if ($this->args['PROXYSERVER'])
        {
            $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
            $this->_runtests();
        }
        else
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
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test http 1.1 w. proxy');
            return;
        }
        else if ($this->args['PROXYSERVER'] == '')
        {
            $this->fail('PROXY definition missing: cannot test proxy w. http 1.1');
            return;
        }
        $this->method = 'http11'; // not an error the double assignment!
        $this->client->method = 'http11';
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        //$this->client->verifyhost = 0;
        //$this->client->verifypeer = 0;
        $this->client->keepalive = false;
        $this->_runtests();
    }

    function testHttps()
    {
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test https functionality');
            return;
        }
        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->path = $this->args['HTTPSURI'];
        $this->client->setSSLVerifyPeer( !$this->args['HTTPSIGNOREPEER'] );
        $this->_runtests();
    }

    function testHttpsProxy()
    {
        if(!function_exists('curl_init'))
        {
            $this->fail('CURL missing: cannot test https functionality');
            return;
        }
        else if ($this->args['PROXYSERVER'] == '')
        {
            $this->fail('PROXY definition missing: cannot test proxy w. http 1.1');
            return;
        }
        $this->client->server = $this->args['HTTPSSERVER'];
        $this->method = 'https';
        $this->client->method = 'https';
        $this->client->setProxy($this->args['PROXYSERVER'], $this->args['PROXYPORT']);
        $this->client->path = $this->args['HTTPSURI'];
        $this->client->setSSLVerifyPeer( !$this->args['HTTPSIGNOREPEER'] );
        $this->_runtests();
    }

    function testUTF8Responses()
    {
        //$this->client->path = strpos($URI, '?') === null ? $URI.'?RESPONSE_ENCODING=UTF-8' : $URI.'&RESPONSE_ENCODING=UTF-8';
        $this->client->path = $this->args['URI'].'?RESPONSE_ENCODING=UTF-8';
        $this->_runtests();
    }

    function testUTF8Requests()
    {
        $this->client->request_charset_encoding = 'UTF-8';
        $this->_runtests();
    }

    function testISOResponses()
    {
        //$this->client->path = strpos($URI, '?') === null ? $URI.'?RESPONSE_ENCODING=UTF-8' : $URI.'&RESPONSE_ENCODING=UTF-8';
        $this->client->path = $this->args['URI'].'?RESPONSE_ENCODING=ISO-8859-1';
        $this->_runtests();
    }

    function testISORequests()
    {
        $this->client->request_charset_encoding = 'ISO-8859-1';
        $this->_runtests();
    }
}
