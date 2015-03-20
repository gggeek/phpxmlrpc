<?php

namespace PhpXmlRpc;

class Client
{
    /// @todo: do these need to be public?
    public $path;
    public $server;
    public $port = 0;
    public $method = 'http';
    public $errno;
    public $errstr;
    public $debug = 0;
    public $username = '';
    public $password = '';
    public $authtype = 1;
    public $cert = '';
    public $certpass = '';
    public $cacert = '';
    public $cacertdir = '';
    public $key = '';
    public $keypass = '';
    public $verifypeer = true;
    public $verifyhost = 2;
    public $no_multicall = false;
    public $proxy = '';
    public $proxyport = 0;
    public $proxy_user = '';
    public $proxy_pass = '';
    public $proxy_authtype = 1;
    public $cookies = array();
    public $extracurlopts = array();

    /**
     * List of http compression methods accepted by the client for responses.
     * NB: PHP supports deflate, gzip compressions out of the box if compiled w. zlib.
     *
     * NNB: you can set it to any non-empty array for HTTP11 and HTTPS, since
     * in those cases it will be up to CURL to decide the compression methods
     * it supports. You might check for the presence of 'zlib' in the output of
     * curl_version() to determine wheter compression is supported or not
     */
    public $accepted_compression = array();
    /**
     * Name of compression scheme to be used for sending requests.
     * Either null, gzip or deflate.
     */
    public $request_compression = '';
    /**
     * CURL handle: used for keep-alive connections (PHP 4.3.8 up, see:
     * http://curl.haxx.se/docs/faq.html#7.3).
     */
    public $xmlrpc_curl_handle = null;
    /// Whether to use persistent connections for http 1.1 and https
    public $keepalive = false;
    /// Charset encodings that can be decoded without problems by the client
    public $accepted_charset_encodings = array();
    /// Charset encoding to be used in serializing request. NULL = use ASCII
    public $request_charset_encoding = '';
    /**
     * Decides the content of Response objects returned by calls to send()
     * valid strings are 'xmlrpcvals', 'phpvals' or 'xml'.
     */
    public $return_type = 'xmlrpcvals';
    /**
     * Sent to servers in http headers.
     */
    public $user_agent;

    /**
     * @param string $path either the complete server URL or the PATH part of the xmlrc server URL, e.g. /xmlrpc/server.php
     * @param string $server the server name / ip address
     * @param integer $port the port the server is listening on, defaults to 80 or 443 depending on protocol used
     * @param string $method the http protocol variant: defaults to 'http', 'https' and 'http11' can be used if CURL is installed
     */
    public function __construct($path, $server = '', $port = '', $method = '')
    {
        // allow user to specify all params in $path
        if ($server == '' and $port == '' and $method == '') {
            $parts = parse_url($path);
            $server = $parts['host'];
            $path = isset($parts['path']) ? $parts['path'] : '';
            if (isset($parts['query'])) {
                $path .= '?' . $parts['query'];
            }
            if (isset($parts['fragment'])) {
                $path .= '#' . $parts['fragment'];
            }
            if (isset($parts['port'])) {
                $port = $parts['port'];
            }
            if (isset($parts['scheme'])) {
                $method = $parts['scheme'];
            }
            if (isset($parts['user'])) {
                $this->username = $parts['user'];
            }
            if (isset($parts['pass'])) {
                $this->password = $parts['pass'];
            }
        }
        if ($path == '' || $path[0] != '/') {
            $this->path = '/' . $path;
        } else {
            $this->path = $path;
        }
        $this->server = $server;
        if ($port != '') {
            $this->port = $port;
        }
        if ($method != '') {
            $this->method = $method;
        }

        // if ZLIB is enabled, let the client by default accept compressed responses
        if (function_exists('gzinflate') || (
                function_exists('curl_init') && (($info = curl_version()) &&
                    ((is_string($info) && strpos($info, 'zlib') !== null) || isset($info['libz_version'])))
            )
        ) {
            $this->accepted_compression = array('gzip', 'deflate');
        }

        // keepalives: enabled by default
        $this->keepalive = true;

        // by default the xml parser can support these 3 charset encodings
        $this->accepted_charset_encodings = array('UTF-8', 'ISO-8859-1', 'US-ASCII');

        // initialize user_agent string
        $this->user_agent = PhpXmlRpc::$xmlrpcName . ' ' . PhpXmlRpc::$xmlrpcVersion;
    }

    /**
     * Enables/disables the echoing to screen of the xmlrpc responses received.
     *
     * @param integer $in values 0, 1 and 2 are supported (2 = echo sent msg too, before received response)
     */
    public function setDebug($in)
    {
        $this->debug = $in;
    }

    /**
     * Add some http BASIC AUTH credentials, used by the client to authenticate.
     *
     * @param string $u username
     * @param string $p password
     * @param integer $t auth type. See curl_setopt man page for supported auth types. Defaults to CURLAUTH_BASIC (basic auth)
     */
    public function setCredentials($u, $p, $t = 1)
    {
        $this->username = $u;
        $this->password = $p;
        $this->authtype = $t;
    }

    /**
     * Add a client-side https certificate.
     *
     * @param string $cert
     * @param string $certpass
     */
    public function setCertificate($cert, $certpass)
    {
        $this->cert = $cert;
        $this->certpass = $certpass;
    }

    /**
     * Add a CA certificate to verify server with (see man page about
     * CURLOPT_CAINFO for more details).
     *
     * @param string $cacert certificate file name (or dir holding certificates)
     * @param bool $is_dir set to true to indicate cacert is a dir. defaults to false
     */
    public function setCaCertificate($cacert, $is_dir = false)
    {
        if ($is_dir) {
            $this->cacertdir = $cacert;
        } else {
            $this->cacert = $cacert;
        }
    }

    /**
     * Set attributes for SSL communication: private SSL key
     * NB: does not work in older php/curl installs
     * Thanks to Daniel Convissor.
     *
     * @param string $key The name of a file containing a private SSL key
     * @param string $keypass The secret password needed to use the private SSL key
     */
    public function setKey($key, $keypass)
    {
        $this->key = $key;
        $this->keypass = $keypass;
    }

    /**
     * Set attributes for SSL communication: verify server certificate.
     *
     * @param bool $i enable/disable verification of peer certificate
     */
    public function setSSLVerifyPeer($i)
    {
        $this->verifypeer = $i;
    }

    /**
     * Set attributes for SSL communication: verify match of server cert w. hostname.
     *
     * @param int $i
     */
    public function setSSLVerifyHost($i)
    {
        $this->verifyhost = $i;
    }

    /**
     * Set proxy info.
     *
     * @param string $proxyhost
     * @param string $proxyport Defaults to 8080 for HTTP and 443 for HTTPS
     * @param string $proxyusername Leave blank if proxy has public access
     * @param string $proxypassword Leave blank if proxy has public access
     * @param int $proxyauthtype set to constant CURLAUTH_NTLM to use NTLM auth with proxy
     */
    public function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1)
    {
        $this->proxy = $proxyhost;
        $this->proxyport = $proxyport;
        $this->proxy_user = $proxyusername;
        $this->proxy_pass = $proxypassword;
        $this->proxy_authtype = $proxyauthtype;
    }

    /**
     * Enables/disables reception of compressed xmlrpc responses.
     * Note that enabling reception of compressed responses merely adds some standard
     * http headers to xmlrpc requests. It is up to the xmlrpc server to return
     * compressed responses when receiving such requests.
     *
     * @param string $compmethod either 'gzip', 'deflate', 'any' or ''
     */
    public function setAcceptedCompression($compmethod)
    {
        if ($compmethod == 'any') {
            $this->accepted_compression = array('gzip', 'deflate');
        } elseif ($compmethod == false) {
            $this->accepted_compression = array();
        } else {
            $this->accepted_compression = array($compmethod);
        }
    }

    /**
     * Enables/disables http compression of xmlrpc request.
     * Take care when sending compressed requests: servers might not support them
     * (and automatic fallback to uncompressed requests is not yet implemented).
     *
     * @param string $compmethod either 'gzip', 'deflate' or ''
     */
    public function setRequestCompression($compmethod)
    {
        $this->request_compression = $compmethod;
    }

    /**
     * Adds a cookie to list of cookies that will be sent to server.
     * NB: setting any param but name and value will turn the cookie into a 'version 1' cookie:
     * do not do it unless you know what you are doing.
     *
     * @param string $name
     * @param string $value
     * @param string $path
     * @param string $domain
     * @param int $port
     *
     * @todo check correctness of urlencoding cookie value (copied from php way of doing it...)
     */
    public function setCookie($name, $value = '', $path = '', $domain = '', $port = null)
    {
        $this->cookies[$name]['value'] = urlencode($value);
        if ($path || $domain || $port) {
            $this->cookies[$name]['path'] = $path;
            $this->cookies[$name]['domain'] = $domain;
            $this->cookies[$name]['port'] = $port;
            $this->cookies[$name]['version'] = 1;
        } else {
            $this->cookies[$name]['version'] = 0;
        }
    }

    /**
     * Directly set cURL options, for extra flexibility
     * It allows eg. to bind client to a specific IP interface / address.
     *
     * @param array $options
     */
    public function SetCurlOptions($options)
    {
        $this->extracurlopts = $options;
    }

    /**
     * Set user-agent string that will be used by this client instance
     * in http headers sent to the server.
     */
    public function SetUserAgent($agentstring)
    {
        $this->user_agent = $agentstring;
    }

    /**
     * Send an xmlrpc request.
     *
     * @param mixed $msg The request object, or an array of requests for using multicall, or the complete xml representation of a request
     * @param integer $timeout Connection timeout, in seconds, If unspecified, a platform specific timeout will apply
     * @param string $method if left unspecified, the http protocol chosen during creation of the object will be used
     *
     * @return Response
     */
    public function & send($msg, $timeout = 0, $method = '')
    {
        // if user does not specify http protocol, use native method of this client
        // (i.e. method set during call to constructor)
        if ($method == '') {
            $method = $this->method;
        }

        if (is_array($msg)) {
            // $msg is an array of Requests
            $r = $this->multicall($msg, $timeout, $method);

            return $r;
        } elseif (is_string($msg)) {
            $n = new Request('');
            $n->payload = $msg;
            $msg = $n;
        }

        // where msg is a Request
        $msg->debug = $this->debug;

        if ($method == 'https') {
            $r = $this->sendPayloadHTTPS(
                $msg,
                $this->server,
                $this->port,
                $timeout,
                $this->username,
                $this->password,
                $this->authtype,
                $this->cert,
                $this->certpass,
                $this->cacert,
                $this->cacertdir,
                $this->proxy,
                $this->proxyport,
                $this->proxy_user,
                $this->proxy_pass,
                $this->proxy_authtype,
                $this->keepalive,
                $this->key,
                $this->keypass
            );
        } elseif ($method == 'http11') {
            $r = $this->sendPayloadCURL(
                $msg,
                $this->server,
                $this->port,
                $timeout,
                $this->username,
                $this->password,
                $this->authtype,
                null,
                null,
                null,
                null,
                $this->proxy,
                $this->proxyport,
                $this->proxy_user,
                $this->proxy_pass,
                $this->proxy_authtype,
                'http',
                $this->keepalive
            );
        } else {
            $r = $this->sendPayloadHTTP10(
                $msg,
                $this->server,
                $this->port,
                $timeout,
                $this->username,
                $this->password,
                $this->authtype,
                $this->proxy,
                $this->proxyport,
                $this->proxy_user,
                $this->proxy_pass,
                $this->proxy_authtype
            );
        }

        return $r;
    }

    private function sendPayloadHTTP10($msg, $server, $port, $timeout = 0,
                                       $username = '', $password = '', $authtype = 1, $proxyhost = '',
                                       $proxyport = 0, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1)
    {
        if ($port == 0) {
            $port = 80;
        }

        // Only create the payload if it was not created previously
        if (empty($msg->payload)) {
            $msg->createPayload($this->request_charset_encoding);
        }

        $payload = $msg->payload;
        // Deflate request body and set appropriate request headers
        if (function_exists('gzdeflate') && ($this->request_compression == 'gzip' || $this->request_compression == 'deflate')) {
            if ($this->request_compression == 'gzip') {
                $a = @gzencode($payload);
                if ($a) {
                    $payload = $a;
                    $encoding_hdr = "Content-Encoding: gzip\r\n";
                }
            } else {
                $a = @gzcompress($payload);
                if ($a) {
                    $payload = $a;
                    $encoding_hdr = "Content-Encoding: deflate\r\n";
                }
            }
        } else {
            $encoding_hdr = '';
        }

        // thanks to Grant Rauscher <grant7@firstworld.net> for this
        $credentials = '';
        if ($username != '') {
            $credentials = 'Authorization: Basic ' . base64_encode($username . ':' . $password) . "\r\n";
            if ($authtype != 1) {
                error_log('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth is supported with HTTP 1.0');
            }
        }

        $accepted_encoding = '';
        if (is_array($this->accepted_compression) && count($this->accepted_compression)) {
            $accepted_encoding = 'Accept-Encoding: ' . implode(', ', $this->accepted_compression) . "\r\n";
        }

        $proxy_credentials = '';
        if ($proxyhost) {
            if ($proxyport == 0) {
                $proxyport = 8080;
            }
            $connectserver = $proxyhost;
            $connectport = $proxyport;
            $uri = 'http://' . $server . ':' . $port . $this->path;
            if ($proxyusername != '') {
                if ($proxyauthtype != 1) {
                    error_log('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth to proxy is supported with HTTP 1.0');
                }
                $proxy_credentials = 'Proxy-Authorization: Basic ' . base64_encode($proxyusername . ':' . $proxypassword) . "\r\n";
            }
        } else {
            $connectserver = $server;
            $connectport = $port;
            $uri = $this->path;
        }

        // Cookie generation, as per rfc2965 (version 1 cookies) or
        // netscape's rules (version 0 cookies)
        $cookieheader = '';
        if (count($this->cookies)) {
            $version = '';
            foreach ($this->cookies as $name => $cookie) {
                if ($cookie['version']) {
                    $version = ' $Version="' . $cookie['version'] . '";';
                    $cookieheader .= ' ' . $name . '="' . $cookie['value'] . '";';
                    if ($cookie['path']) {
                        $cookieheader .= ' $Path="' . $cookie['path'] . '";';
                    }
                    if ($cookie['domain']) {
                        $cookieheader .= ' $Domain="' . $cookie['domain'] . '";';
                    }
                    if ($cookie['port']) {
                        $cookieheader .= ' $Port="' . $cookie['port'] . '";';
                    }
                } else {
                    $cookieheader .= ' ' . $name . '=' . $cookie['value'] . ";";
                }
            }
            $cookieheader = 'Cookie:' . $version . substr($cookieheader, 0, -1) . "\r\n";
        }

        // omit port if 80
        $port = ($port == 80) ? '' : (':' . $port);

        $op = 'POST ' . $uri . " HTTP/1.0\r\n" .
            'User-Agent: ' . $this->user_agent . "\r\n" .
            'Host: ' . $server . $port . "\r\n" .
            $credentials .
            $proxy_credentials .
            $accepted_encoding .
            $encoding_hdr .
            'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings) . "\r\n" .
            $cookieheader .
            'Content-Type: ' . $msg->content_type . "\r\nContent-Length: " .
            strlen($payload) . "\r\n\r\n" .
            $payload;

        if ($this->debug > 1) {
            $this->debugMessage("---SENDING---\n$op\n---END---");
        }

        if ($timeout > 0) {
            $fp = @fsockopen($connectserver, $connectport, $this->errno, $this->errstr, $timeout);
        } else {
            $fp = @fsockopen($connectserver, $connectport, $this->errno, $this->errstr);
        }
        if ($fp) {
            if ($timeout > 0 && function_exists('stream_set_timeout')) {
                stream_set_timeout($fp, $timeout);
            }
        } else {
            $this->errstr = 'Connect error: ' . $this->errstr;
            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['http_error'], $this->errstr . ' (' . $this->errno . ')');

            return $r;
        }

        if (!fputs($fp, $op, strlen($op))) {
            fclose($fp);
            $this->errstr = 'Write error';
            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['http_error'], $this->errstr);

            return $r;
        } else {
            // reset errno and errstr on successful socket connection
            $this->errstr = '';
        }
        // G. Giunta 2005/10/24: close socket before parsing.
        // should yield slightly better execution times, and make easier recursive calls (e.g. to follow http redirects)
        $ipd = '';
        do {
            // shall we check for $data === FALSE?
            // as per the manual, it signals an error
            $ipd .= fread($fp, 32768);
        } while (!feof($fp));
        fclose($fp);
        $r = $msg->parseResponse($ipd, false, $this->return_type);

        return $r;
    }

    private function sendPayloadHTTPS($msg, $server, $port, $timeout = 0, $username = '',
                                      $password = '', $authtype = 1, $cert = '', $certpass = '', $cacert = '', $cacertdir = '',
                                      $proxyhost = '', $proxyport = 0, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1,
                                      $keepalive = false, $key = '', $keypass = '')
    {
        $r = $this->sendPayloadCURL($msg, $server, $port, $timeout, $username,
            $password, $authtype, $cert, $certpass, $cacert, $cacertdir, $proxyhost, $proxyport,
            $proxyusername, $proxypassword, $proxyauthtype, 'https', $keepalive, $key, $keypass);

        return $r;
    }

    /**
     * Contributed by Justin Miller <justin@voxel.net>
     * Requires curl to be built into PHP
     * NB: CURL versions before 7.11.10 cannot use proxy to talk to https servers!
     */
    private function sendPayloadCURL($msg, $server, $port, $timeout = 0, $username = '',
                                     $password = '', $authtype = 1, $cert = '', $certpass = '', $cacert = '', $cacertdir = '',
                                     $proxyhost = '', $proxyport = 0, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1, $method = 'https',
                                     $keepalive = false, $key = '', $keypass = '')
    {
        if (!function_exists('curl_init')) {
            $this->errstr = 'CURL unavailable on this install';
            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['no_curl'], PhpXmlRpc::$xmlrpcstr['no_curl']);

            return $r;
        }
        if ($method == 'https') {
            if (($info = curl_version()) &&
                ((is_string($info) && strpos($info, 'OpenSSL') === null) || (is_array($info) && !isset($info['ssl_version'])))
            ) {
                $this->errstr = 'SSL unavailable on this install';
                $r = new Response(0, PhpXmlRpc::$xmlrpcerr['no_ssl'], PhpXmlRpc::$xmlrpcstr['no_ssl']);

                return $r;
            }
        }

        if ($port == 0) {
            if ($method == 'http') {
                $port = 80;
            } else {
                $port = 443;
            }
        }

        // Only create the payload if it was not created previously
        if (empty($msg->payload)) {
            $msg->createPayload($this->request_charset_encoding);
        }

        // Deflate request body and set appropriate request headers
        $payload = $msg->payload;
        if (function_exists('gzdeflate') && ($this->request_compression == 'gzip' || $this->request_compression == 'deflate')) {
            if ($this->request_compression == 'gzip') {
                $a = @gzencode($payload);
                if ($a) {
                    $payload = $a;
                    $encoding_hdr = 'Content-Encoding: gzip';
                }
            } else {
                $a = @gzcompress($payload);
                if ($a) {
                    $payload = $a;
                    $encoding_hdr = 'Content-Encoding: deflate';
                }
            }
        } else {
            $encoding_hdr = '';
        }

        if ($this->debug > 1) {
            $this->debugMessage("---SENDING---\n$payload\n---END---");
            // let the client see this now in case http times out...
            flush();
        }

        if (!$keepalive || !$this->xmlrpc_curl_handle) {
            $curl = curl_init($method . '://' . $server . ':' . $port . $this->path);
            if ($keepalive) {
                $this->xmlrpc_curl_handle = $curl;
            }
        } else {
            $curl = $this->xmlrpc_curl_handle;
        }

        // results into variable
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if ($this->debug) {
            curl_setopt($curl, CURLOPT_VERBOSE, 1);
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
        // required for XMLRPC: post the data
        curl_setopt($curl, CURLOPT_POST, 1);
        // the data
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

        // return the header too
        curl_setopt($curl, CURLOPT_HEADER, 1);

        // NB: if we set an empty string, CURL will add http header indicating
        // ALL methods it is supporting. This is possibly a better option than
        // letting the user tell what curl can / cannot do...
        if (is_array($this->accepted_compression) && count($this->accepted_compression)) {
            //curl_setopt($curl, CURLOPT_ENCODING, implode(',', $this->accepted_compression));
            // empty string means 'any supported by CURL' (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
            if (count($this->accepted_compression) == 1) {
                curl_setopt($curl, CURLOPT_ENCODING, $this->accepted_compression[0]);
            } else {
                curl_setopt($curl, CURLOPT_ENCODING, '');
            }
        }
        // extra headers
        $headers = array('Content-Type: ' . $msg->content_type, 'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings));
        // if no keepalive is wanted, let the server know it in advance
        if (!$keepalive) {
            $headers[] = 'Connection: close';
        }
        // request compression header
        if ($encoding_hdr) {
            $headers[] = $encoding_hdr;
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        // timeout is borked
        if ($timeout) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout == 1 ? 1 : $timeout - 1);
        }

        if ($username && $password) {
            curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
            if (defined('CURLOPT_HTTPAUTH')) {
                curl_setopt($curl, CURLOPT_HTTPAUTH, $authtype);
            } elseif ($authtype != 1) {
                error_log('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth is supported by the current PHP/curl install');
            }
        }

        if ($method == 'https') {
            // set cert file
            if ($cert) {
                curl_setopt($curl, CURLOPT_SSLCERT, $cert);
            }
            // set cert password
            if ($certpass) {
                curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $certpass);
            }
            // whether to verify remote host's cert
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
            // set ca certificates file/dir
            if ($cacert) {
                curl_setopt($curl, CURLOPT_CAINFO, $cacert);
            }
            if ($cacertdir) {
                curl_setopt($curl, CURLOPT_CAPATH, $cacertdir);
            }
            // set key file (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
            if ($key) {
                curl_setopt($curl, CURLOPT_SSLKEY, $key);
            }
            // set key password (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
            if ($keypass) {
                curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $keypass);
            }
            // whether to verify cert's common name (CN); 0 for no, 1 to verify that it exists, and 2 to verify that it matches the hostname used
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verifyhost);
        }

        // proxy info
        if ($proxyhost) {
            if ($proxyport == 0) {
                $proxyport = 8080; // NB: even for HTTPS, local connection is on port 8080
            }
            curl_setopt($curl, CURLOPT_PROXY, $proxyhost . ':' . $proxyport);
            //curl_setopt($curl, CURLOPT_PROXYPORT,$proxyport);
            if ($proxyusername) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyusername . ':' . $proxypassword);
                if (defined('CURLOPT_PROXYAUTH')) {
                    curl_setopt($curl, CURLOPT_PROXYAUTH, $proxyauthtype);
                } elseif ($proxyauthtype != 1) {
                    error_log('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth to proxy is supported by the current PHP/curl install');
                }
            }
        }

        // NB: should we build cookie http headers by hand rather than let CURL do it?
        // the following code does not honour 'expires', 'path' and 'domain' cookie attributes
        // set to client obj the the user...
        if (count($this->cookies)) {
            $cookieheader = '';
            foreach ($this->cookies as $name => $cookie) {
                $cookieheader .= $name . '=' . $cookie['value'] . '; ';
            }
            curl_setopt($curl, CURLOPT_COOKIE, substr($cookieheader, 0, -2));
        }

        foreach ($this->extracurlopts as $opt => $val) {
            curl_setopt($curl, $opt, $val);
        }

        $result = curl_exec($curl);

        if ($this->debug > 1) {
            $message = "---CURL INFO---\n";
            foreach (curl_getinfo($curl) as $name => $val) {
                if (is_array($val)) {
                    $val = implode("\n", $val);
                }
                $message .= $name . ': ' . $val . "\n";
            }
            $message .= "---END---";
            $this->debugMessage($message);
        }

        if (!$result) {
            /// @todo we should use a better check here - what if we get back '' or '0'?

            $this->errstr = 'no response';
            $resp = new Response(0, PhpXmlRpc::$xmlrpcerr['curl_fail'], PhpXmlRpc::$xmlrpcstr['curl_fail'] . ': ' . curl_error($curl));
            curl_close($curl);
            if ($keepalive) {
                $this->xmlrpc_curl_handle = null;
            }
        } else {
            if (!$keepalive) {
                curl_close($curl);
            }
            $resp = $msg->parseResponse($result, true, $this->return_type);
            // if we got back a 302, we can not reuse the curl handle for later calls
            if ($resp->faultCode() == PhpXmlRpc::$xmlrpcerr['http_error'] && $keepalive) {
                curl_close($curl);
                $this->xmlrpc_curl_handle = null;
            }
        }

        return $resp;
    }

    /**
     * Send an array of requests and return an array of responses.
     * Unless $this->no_multicall has been set to true, it will try first
     * to use one single xmlrpc call to server method system.multicall, and
     * revert to sending many successive calls in case of failure.
     * This failure is also stored in $this->no_multicall for subsequent calls.
     * Unfortunately, there is no server error code universally used to denote
     * the fact that multicall is unsupported, so there is no way to reliably
     * distinguish between that and a temporary failure.
     * If you are sure that server supports multicall and do not want to
     * fallback to using many single calls, set the fourth parameter to FALSE.
     *
     * NB: trying to shoehorn extra functionality into existing syntax has resulted
     * in pretty much convoluted code...
     *
     * @param Request[] $msgs an array of Request objects
     * @param integer $timeout connection timeout (in seconds)
     * @param string $method the http protocol variant to be used
     * @param boolean fallback When true, upon receiving an error during multicall, multiple single calls will be attempted
     *
     * @return array
     */
    public function multicall($msgs, $timeout = 0, $method = '', $fallback = true)
    {
        if ($method == '') {
            $method = $this->method;
        }
        if (!$this->no_multicall) {
            $results = $this->_try_multicall($msgs, $timeout, $method);
            if (is_array($results)) {
                // System.multicall succeeded
                return $results;
            } else {
                // either system.multicall is unsupported by server,
                // or call failed for some other reason.
                if ($fallback) {
                    // Don't try it next time...
                    $this->no_multicall = true;
                } else {
                    if (is_a($results, '\PhpXmlRpc\Response')) {
                        $result = $results;
                    } else {
                        $result = new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'], PhpXmlRpc::$xmlrpcstr['multicall_error']);
                    }
                }
            }
        } else {
            // override fallback, in case careless user tries to do two
            // opposite things at the same time
            $fallback = true;
        }

        $results = array();
        if ($fallback) {
            // system.multicall is (probably) unsupported by server:
            // emulate multicall via multiple requests
            foreach ($msgs as $msg) {
                $results[] = $this->send($msg, $timeout, $method);
            }
        } else {
            // user does NOT want to fallback on many single calls:
            // since we should always return an array of responses,
            // return an array with the same error repeated n times
            foreach ($msgs as $msg) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Attempt to boxcar $msgs via system.multicall.
     * Returns either an array of xmlrpcreponses, an xmlrpc error response
     * or false (when received response does not respect valid multicall syntax).
     */
    private function _try_multicall($msgs, $timeout, $method)
    {
        // Construct multicall request
        $calls = array();
        foreach ($msgs as $msg) {
            $call['methodName'] = new Value($msg->method(), 'string');
            $numParams = $msg->getNumParams();
            $params = array();
            for ($i = 0; $i < $numParams; $i++) {
                $params[$i] = $msg->getParam($i);
            }
            $call['params'] = new Value($params, 'array');
            $calls[] = new Value($call, 'struct');
        }
        $multicall = new Request('system.multicall');
        $multicall->addParam(new Value($calls, 'array'));

        // Attempt RPC call
        $result = $this->send($multicall, $timeout, $method);

        if ($result->faultCode() != 0) {
            // call to system.multicall failed
            return $result;
        }

        // Unpack responses.
        $rets = $result->value();

        if ($this->return_type == 'xml') {
            return $rets;
        } elseif ($this->return_type == 'phpvals') {
            ///@todo test this code branch...
            $rets = $result->value();
            if (!is_array($rets)) {
                return false;       // bad return type from system.multicall
            }
            $numRets = count($rets);
            if ($numRets != count($msgs)) {
                return false;       // wrong number of return values.
            }

            $response = array();
            for ($i = 0; $i < $numRets; $i++) {
                $val = $rets[$i];
                if (!is_array($val)) {
                    return false;
                }
                switch (count($val)) {
                    case 1:
                        if (!isset($val[0])) {
                            return false;       // Bad value
                        }
                        // Normal return value
                        $response[$i] = new Response($val[0], 0, '', 'phpvals');
                        break;
                    case 2:
                        /// @todo remove usage of @: it is apparently quite slow
                        $code = @$val['faultCode'];
                        if (!is_int($code)) {
                            return false;
                        }
                        $str = @$val['faultString'];
                        if (!is_string($str)) {
                            return false;
                        }
                        $response[$i] = new Response(0, $code, $str);
                        break;
                    default:
                        return false;
                }
            }

            return $response;
        } else {
            // return type == 'xmlrpcvals'

            $rets = $result->value();
            if ($rets->kindOf() != 'array') {
                return false;       // bad return type from system.multicall
            }
            $numRets = $rets->arraysize();
            if ($numRets != count($msgs)) {
                return false;       // wrong number of return values.
            }

            $response = array();
            for ($i = 0; $i < $numRets; $i++) {
                $val = $rets->arraymem($i);
                switch ($val->kindOf()) {
                    case 'array':
                        if ($val->arraysize() != 1) {
                            return false;       // Bad value
                        }
                        // Normal return value
                        $response[$i] = new Response($val->arraymem(0));
                        break;
                    case 'struct':
                        $code = $val->structmem('faultCode');
                        if ($code->kindOf() != 'scalar' || $code->scalartyp() != 'int') {
                            return false;
                        }
                        $str = $val->structmem('faultString');
                        if ($str->kindOf() != 'scalar' || $str->scalartyp() != 'string') {
                            return false;
                        }
                        $response[$i] = new Response(0, $code->scalarval(), $str->scalarval());
                        break;
                    default:
                        return false;
                }
            }

            return $response;
        }
    }

    /**
     * Echoes a debug message, taking care of escaping it when not in console mode
     *
     * @param string $message
     */
    protected function debugMessage($message)
    {
        if (PHP_SAPI != 'cli') {
            print "<PRE>\n".htmlentities($message)."\n</PRE>";
        }
        else {
            print "\n$message\n";
        }
        // let the client see this now in case http times out...
        flush();
    }
}
