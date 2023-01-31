<?php

namespace PhpXmlRpc;

//use PhpXmlRpc\Helper\Charset;
use PhpXmlRpc\Exception\ValueErrorException;
use PhpXmlRpc\Helper\XMLParser;
use PhpXmlRpc\Traits\DeprecationLogger;

/**
 * Used to represent a client of an XML-RPC server.
 */
class Client
{
    use DeprecationLogger;

    const USE_CURL_NEVER = 0;
    const USE_CURL_ALWAYS = 1;
    const USE_CURL_AUTO = 2;

    const OPT_ACCEPTED_CHARSET_ENCODINGS = 'accepted_charset_encodings';
    const OPT_ACCEPTED_COMPRESSION = 'accepted_compression';
    const OPT_AUTH_TYPE = 'authtype';
    const OPT_CA_CERT = 'cacert';
    const OPT_CA_CERT_DIR = 'cacertdir';
    const OPT_CERT = 'cert';
    const OPT_CERT_PASS = 'certpass';
    const OPT_COOKIES = 'cookies';
    const OPT_DEBUG = 'debug';
    const OPT_EXTRA_CURL_OPTS = 'extracurlopts';
    const OPT_KEEPALIVE = 'keepalive';
    const OPT_KEY = 'key';
    const OPT_KEY_PASS = 'keypass';
    const OPT_NO_MULTICALL = 'no_multicall';
    const OPT_PASSWORD = 'password';
    const OPT_PROXY = 'proxy';
    const OPT_PROXY_AUTH_TYPE = 'proxy_authtype';
    const OPT_PROXY_PASS = 'proxy_pass';
    const OPT_PROXY_PORT = 'proxyport';
    const OPT_PROXY_USER = 'proxy_user';
    const OPT_REQUEST_CHARSET_ENCODING = 'request_charset_encoding';
    const OPT_REQUEST_COMPRESSION = 'request_compression';
    const OPT_RETURN_TYPE = 'return_type';
    const OPT_SSL_VERSION = 'sslversion';
    const OPT_TIMEOUT = 'timeout';
    const OPT_USERNAME = 'username';
    const OPT_USER_AGENT = 'user_agent';
    const OPT_USE_CURL = 'use_curl';
    const OPT_VERIFY_HOST = 'verifyhost';
    const OPT_VERIFY_PEER = 'verifypeer';

    /** @var string */
    protected static $requestClass = '\\PhpXmlRpc\\Request';
    /** @var string */
    protected static $responseClass = '\\PhpXmlRpc\\Response';

    /**
     * @var int
     * @deprecated will be removed in the future
     */
    public $errno;
    /**
     * @var string
     * @deprecated will be removed in the future
     */
    public $errstr;

    /// @todo: do all the ones below need to be public?

    /**
     * @var string
     * @internal use getUrl/__construct
     */
    public $method = 'http';
    /**
     * @var string
     * @internal use getUrl/__construct
     */
    public $server;
    /**
     * @var int
     * @internal use getUrl/__construct
     */
    public $port = 0;
    /**
     * @var string
     * @internal use getUrl/__construct
     */
    public $path;

    /**
     * @var int
     * @internal use setOption/getOption
     */
    public $debug = 0;
    /**
     * @var string
     * @internal use setCredentials/getOption
     */
    public $username = '';
    /**
     * @var string
     * @internal use setCredentials/getOption
     */
    public $password = '';
    /**
     * @var int
     * @internal use setCredentials/getOption
     */
    public $authtype = 1;
    /**
     * @var string
     * @internal use setCertificate/getOption
     */
    public $cert = '';
    /**
     * @var string
     * @internal use setCertificate/getOption
     */
    public $certpass = '';
    /**
     * @var string
     * @internal use setCaCertificate/getOption
     */
    public $cacert = '';
    /**
     * @var string
     * @internal use setCaCertificate/getOption
     */
    public $cacertdir = '';
    /**
     * @var string
     * @internal use setKey/getOption
     */
    public $key = '';
    /**
     * @var string
     * @internal use setKey/getOption
     */
    public $keypass = '';
    /**
     * @var bool
     * @internal use setOption/getOption
     */
    public $verifypeer = true;
    /**
     * @var int
     * @internal use setOption/getOption
     */
    public $verifyhost = 2;
    /**
     * @var int
     * @internal use setOption/getOption
     */
    public $sslversion = 0; // corresponds to CURL_SSLVERSION_DEFAULT
    /**
     * @var string
     * @internal use setProxy/getOption
     */
    public $proxy = '';
    /**
     * @var int
     * @internal use setProxy/getOption
     */
    public $proxyport = 0;
    /**
     * @var string
     * @internal use setProxy/getOption
     */
    public $proxy_user = '';
    /**
     * @var string
     * @internal use setProxy/getOption
     */
    public $proxy_pass = '';
    /**
     * @var int
     * @internal use setProxy/getOption
     */
    public $proxy_authtype = 1;
    /**
     * @var array
     * @internal use setCookie/getOption
     */
    public $cookies = array();
    /**
     * @var array
     * @internal use setOption/getOption
     */
    public $extracurlopts = array();
    /**
     * @var int
     * @internal use setOption/getOption
     */
    public $timeout = 0;
    /**
     * @var int
     * @internal use setOption/getOption
     */
    public $use_curl = self::USE_CURL_AUTO;
    /**
     * @var bool
     *
     * This determines whether the multicall() method will try to take advantage of the system.multicall xml-rpc method
     * to dispatch to the server an array of requests in a single http roundtrip or simply execute many consecutive http
     * calls. Defaults to FALSE, but it will be enabled automatically on the first failure of execution of
     * system.multicall.
     *
     * @internal use setOption/getOption
     */
    public $no_multicall = false;
    /**
     * @var array
     *
     * List of http compression methods accepted by the client for responses.
     * NB: PHP supports deflate, gzip compressions out of the box if compiled w. zlib.
     *
     * NNB: you can set it to any non-empty array for HTTP11 and HTTPS, since in those cases it will be up to CURL to
     * decide the compression methods it supports. You might check for the presence of 'zlib' in the output of
     * curl_version() to determine whether compression is supported or not
     *
     * @internal use setAcceptedCompression/getOption
     */
    public $accepted_compression = array();
    /**
     * @var string|null
     *
     * Name of compression scheme to be used for sending requests.
     * Either null, 'gzip' or 'deflate'.
     *
     * @internal use setOption/getOption
     */
    public $request_compression = '';
    /**
     * @var bool
     *
     * Whether to use persistent connections for http 1.1 and https. Value set at constructor time.
     *
     * @internal use setOption/getOption
     */
    public $keepalive = false;
    /**
     * @var string[]
     *
     * Charset encodings that can be decoded without problems by the client. Value set at constructor time
     *
     * @internal use setOption/getOption
     */
    public $accepted_charset_encodings = array();
    /**
     * @var string
     *
     * The charset encoding that will be used for serializing request sent by the client.
     * It defaults to NULL, which means using US-ASCII and encoding all characters outside the ASCII printable range
     * using their xml character entity representation (this has the benefit that line end characters will not be mangled
     * in the transfer, a CR-LF will be preserved as well as a singe LF).
     * Valid values are 'US-ASCII', 'UTF-8' and 'ISO-8859-1'.
     * For the fastest mode of operation, set your both your app internal encoding and this to UTF-8.
     *
     * @internal use setOption/getOption
     */
    public $request_charset_encoding = '';
    /**
     * @var string
     *
     * Decides the content of Response objects returned by calls to send() and multicall().
     * Valid values are 'xmlrpcvals', 'phpvals' or 'xml'.
     *
     * Determines whether the value returned inside a Response object as results of calls to the send() and multicall()
     * methods will be a Value object, a plain php value or a raw xml string.
     * Allowed values are 'xmlrpcvals' (the default), 'phpvals' and 'xml'.
     * To allow the user to differentiate between a correct and a faulty response, fault responses will be returned as
     * Response objects in any case.
     * Note that the 'phpvals' setting will yield faster execution times, but some of the information from the original
     * response will be lost. It will be e.g. impossible to tell whether a particular php string value was sent by the
     * server as an xml-rpc string or base64 value.
     *
     * @internal use setOption/getOption
     */
    public $return_type = XMLParser::RETURN_XMLRPCVALS;
    /**
     * @var string
     *
     * Sent to servers in http headers. Value set at constructor time.
     *
     * @internal use setOption/getOption
     */
    public $user_agent;

    /**
     * CURL handle: used for keep-alive
     * @internal
     */
    public $xmlrpc_curl_handle = null;

    /**
     * @var array
     */
    protected $options = array(
        self::OPT_ACCEPTED_CHARSET_ENCODINGS,
        self::OPT_ACCEPTED_COMPRESSION,
        self::OPT_AUTH_TYPE,
        self::OPT_CA_CERT,
        self::OPT_CA_CERT_DIR,
        self::OPT_CERT,
        self::OPT_CERT_PASS,
        self::OPT_COOKIES,
        self::OPT_DEBUG,
        self::OPT_EXTRA_CURL_OPTS,
        self::OPT_KEEPALIVE,
        self::OPT_KEY,
        self::OPT_KEY_PASS,
        self::OPT_NO_MULTICALL,
        self::OPT_PASSWORD,
        self::OPT_PROXY,
        self::OPT_PROXY_AUTH_TYPE,
        self::OPT_PROXY_PASS,
        self::OPT_PROXY_USER,
        self::OPT_PROXY_PORT,
        self::OPT_REQUEST_CHARSET_ENCODING,
        self::OPT_REQUEST_COMPRESSION,
        self::OPT_RETURN_TYPE,
        self::OPT_SSL_VERSION,
        self::OPT_TIMEOUT,
        self::OPT_USE_CURL,
        self::OPT_USER_AGENT,
        self::OPT_USERNAME,
        self::OPT_VERIFY_HOST,
        self::OPT_VERIFY_PEER,
    );

    /**
     * @param string $path either the PATH part of the xml-rpc server URL, or complete server URL (in which case you
     *                     should use an empty string for all other parameters)
     *                     e.g. /xmlrpc/server.php
     *                     e.g. http://phpxmlrpc.sourceforge.net/server.php
     *                     e.g. https://james:bond@secret.service.com:444/xmlrpcserver?agent=007
     *                     e.g. h2://fast-and-secure-services.org/endpoint
     * @param string $server the server name / ip address
     * @param integer $port the port the server is listening on, when omitted defaults to 80 or 443 depending on
     *                      protocol used
     * @param string $method the http protocol variant: defaults to 'http'; 'https', 'http11', 'h2' and 'h2c' can
     *                       be used if CURL is installed. The value set here can be overridden in any call to $this->send().
     *                       Use 'h2' to make the lib attempt to use http/2 over a secure connection, and 'h2c'
     *                       for http/2 without tls. Note that 'h2c' will not use the h2c 'upgrade' method, and be
     *                       thus incompatible with any server/proxy not supporting http/2. This is because POST
     *                       request are not compatible with h2c upgrade.
     */
    public function __construct($path, $server = '', $port = '', $method = '')
    {
        // allow user to specify all params in $path
        if ($server == '' && $port == '' && $method == '') {
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
                function_exists('curl_version') && (($info = curl_version()) &&
                    ((is_string($info) && strpos($info, 'zlib') !== null) || isset($info['libz_version'])))
            )
        ) {
            $this->accepted_compression = array('gzip', 'deflate');
        }

        // keepalives: enabled by default
        $this->keepalive = true;

        // by default the xml parser can support these 3 charset encodings
        $this->accepted_charset_encodings = array('UTF-8', 'ISO-8859-1', 'US-ASCII');

        // NB: this is disabled to avoid making all the requests sent huge... mbstring supports more than 80 charsets!
        //$ch = Charset::instance();
        //$this->accepted_charset_encodings = $ch->knownCharsets();

        // initialize user_agent string
        $this->user_agent = PhpXmlRpc::$xmlrpcName . ' ' . PhpXmlRpc::$xmlrpcVersion;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws ValueErrorException on unsupported option
     */
    public function setOption($name, $value)
    {
        switch ($name) {
            case self::OPT_ACCEPTED_CHARSET_ENCODINGS:
                $this->accepted_charset_encodings = $value;
                break;
            case self::OPT_ACCEPTED_COMPRESSION:
                $this->accepted_compression = $value;
                break;
            case self::OPT_AUTH_TYPE:
                $this->authtype = $value;
                break;
            case self::OPT_CA_CERT:
                $this->cacert = $value;
                break;
            case self::OPT_CA_CERT_DIR:
                $this->cacertdir = $value;
                break;
            case self::OPT_CERT:
                $this->cert = $value;
                break;
            case self::OPT_CERT_PASS:
                $this->certpass = $value;
                break;
            case self::OPT_COOKIES:
                $this->cookies = $value;
                break;
            case self::OPT_DEBUG:
                $this->debug = $value;
                break;
            case self::OPT_EXTRA_CURL_OPTS:
                $this->extracurlopts = $value;
                break;
            case self::OPT_KEEPALIVE:
                $this->keepalive = $value;
                break;
            case self::OPT_KEY:
                $this->key = $value;
                break;
            case self::OPT_KEY_PASS:
                $this->keypass = $value;
                break;
            case self::OPT_NO_MULTICALL:
                $this->no_multicall = $value;
                break;
            case self::OPT_PASSWORD:
                $this->password = $value;
                break;
            case self::OPT_PROXY:
                $this->proxy = $value;
                break;
            case self::OPT_PROXY_AUTH_TYPE:
                $this->proxy_authtype = $value;
                break;
            case self::OPT_PROXY_PASS:
                $this->proxy_pass = $value;
                break;
            case self::OPT_PROXY_PORT:
                $this->proxyport = $value;
                break;
            case self::OPT_PROXY_USER:
                $this->proxy_user = $value;
                break;
            case self::OPT_REQUEST_CHARSET_ENCODING:
                $this->request_charset_encoding = $value;
                break;
            case self::OPT_REQUEST_COMPRESSION:
                $this->request_compression = $value;
                break;
            case self::OPT_RETURN_TYPE:
                $this->return_type = $value;
                break;
            case self::OPT_SSL_VERSION:
                $this->sslversion = $value;
                break;
            case self::OPT_TIMEOUT:
                $this->timeout = $value;
                break;
            case self::OPT_USERNAME:
                $this->username = $value;
                break;
            case self::OPT_USER_AGENT:
                $this->user_agent = $value;
                break;
            case self::OPT_USE_CURL:
                $this->use_curl = $value;
                break;
            case self::OPT_VERIFY_HOST:
                $this->verifyhost = $value;
                break;
            case self::OPT_VERIFY_PEER:
                $this->verifypeer = $value;
                break;
            default:
                throw new ValueErrorException("Unsupported option '$name'");
        }

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws ValueErrorException on unsupported option
     */
    public function getOption($name)
    {
        switch ($name) {
            case self::OPT_ACCEPTED_CHARSET_ENCODINGS:
                return $this->accepted_charset_encodings;
            case self::OPT_ACCEPTED_COMPRESSION:
                return $this->accepted_compression;
            case self::OPT_AUTH_TYPE:
                return $this->authtype;
            case self::OPT_CA_CERT:
                return $this->cacert;
            case self::OPT_CA_CERT_DIR:
                return $this->cacertdir;
            case self::OPT_CERT:
                return $this->cert;
            case self::OPT_CERT_PASS:
                return $this->certpass;
            case self::OPT_COOKIES:
                return $this->cookies;
            case self::OPT_DEBUG:
                return $this->debug;
            case self::OPT_EXTRA_CURL_OPTS:
                return $this->extracurlopts;
            case self::OPT_KEEPALIVE:
                return $this->keepalive;
            case self::OPT_KEY:
                return $this->key;
            case self::OPT_KEY_PASS:
                return $this->keypass;
            case self::OPT_NO_MULTICALL:
                return $this->no_multicall;
            case self::OPT_PASSWORD:
                return $this->password;
            case self::OPT_PROXY:
                return $this->proxy;
            case self::OPT_PROXY_AUTH_TYPE:
                return $this->proxy_authtype;
            case self::OPT_PROXY_PASS:
                return $this->proxy_pass;
            case self::OPT_PROXY_PORT:
                return $this->proxyport;
            case self::OPT_PROXY_USER:
                return $this->proxy_user;
            case self::OPT_REQUEST_CHARSET_ENCODING:
                return $this->request_charset_encoding;
            case self::OPT_REQUEST_COMPRESSION:
                return $this->request_compression;
            case self::OPT_RETURN_TYPE:
                return $this->return_type;
            case self::OPT_SSL_VERSION:
                return $this->sslversion;
            case self::OPT_TIMEOUT:
                return $this->timeout;
            case self::OPT_USERNAME:
                return $this->username;
            case self::OPT_USER_AGENT:
                return $this->user_agent;
            case self::OPT_USE_CURL:
                return $this->use_curl;
            case self::OPT_VERIFY_HOST:
                return $this->verifyhost;
            case self::OPT_VERIFY_PEER:
                return $this->verifypeer;
            default:
                throw new ValueErrorException("Unsupported option '$name'");
        }
    }

    /**
     * Returns the complete list of Client options.
     * @return array
     */
    public function getOptions()
    {
        $values = array();
        foreach($this->options as $opt) {
            $values[$opt] = $this->getOption($opt);
        }
        return $values;
    }

    /**
     * @param array $options
     * @return $this
     * @throws ValueErrorException on unsupported option
     */
    public function setOptions($options)
    {
        foreach($options as $name => $value) {
            $this->setOption($name, $value);
        }

        return $this;
    }

    /**
     * Enable/disable the echoing to screen of the xml-rpc responses received. The default is not to output anything.
     *
     * The debugging information at level 1 includes the raw data returned from the XML-RPC server it was querying
     * (including bot HTTP headers and the full XML payload), and the PHP value the client attempts to create to
     * represent the value returned by the server.
     * At level 2, the complete payload of the xml-rpc request is also printed, before being sent to the server.
     * At level -1, the Response objects returned by send() calls will not carry information about the http response's
     * cookies, headers and body, which might save some memory
     *
     * This option can be very useful when debugging servers as it allows you to see exactly what the client sends and
     * the server returns. Never leave it enabled for production!
     *
     * @param integer $level values -1, 0, 1 and 2 are supported
     * @return $this
     */
    public function setDebug($level)
    {
        $this->debug = $level;
        return $this;
    }

    /**
     * Sets the username and password for authorizing the client to the server.
     *
     * With the default (HTTP) transport, this information is used for HTTP Basic authorization.
     * Note that username and password can also be set using the class constructor.
     * With HTTP 1.1 and HTTPS transport, NTLM and Digest authentication protocols are also supported. To enable them use
     * the constants CURLAUTH_DIGEST and CURLAUTH_NTLM as values for the auth type parameter.
     *
     * @param string $user username
     * @param string $password password
     * @param integer $authType auth type. See curl_setopt man page for supported auth types. Defaults to CURLAUTH_BASIC
     *                          (basic auth). Note that auth types NTLM and Digest will only work if the Curl php
     *                          extension is enabled.
     * @return $this
     */
    public function setCredentials($user, $password, $authType = 1)
    {
        $this->username = $user;
        $this->password = $password;
        $this->authtype = $authType;
        return $this;
    }

    /**
     * Set the optional certificate and passphrase used in SSL-enabled communication with a remote server.
     *
     * Note: to retrieve information about the client certificate on the server side, you will need to look into the
     * environment variables which are set up by the webserver. Different webservers will typically set up different
     * variables.
     *
     * @param string $cert the name of a file containing a PEM formatted certificate
     * @param string $certPass the password required to use it
     * @return $this
     */
    public function setCertificate($cert, $certPass = '')
    {
        $this->cert = $cert;
        $this->certpass = $certPass;
        return $this;
    }

    /**
     * Add a CA certificate to verify server with in SSL-enabled communication when SetSSLVerifypeer has been set to TRUE.
     *
     * See the php manual page about CURLOPT_CAINFO for more details.
     *
     * @param string $caCert certificate file name (or dir holding certificates)
     * @param bool $isDir set to true to indicate cacert is a dir. defaults to false
     * @return $this
     */
    public function setCaCertificate($caCert, $isDir = false)
    {
        if ($isDir) {
            $this->cacertdir = $caCert;
        } else {
            $this->cacert = $caCert;
        }
        return $this;
    }

    /**
     * Set attributes for SSL communication: private SSL key.
     *
     * NB: does not work in older php/curl installs.
     * Thanks to Daniel Convissor.
     *
     * @param string $key The name of a file containing a private SSL key
     * @param string $keyPass The secret password needed to use the private SSL key
     * @return $this
     */
    public function setKey($key, $keyPass)
    {
        $this->key = $key;
        $this->keypass = $keyPass;
        return $this;
    }

    /**
     * Set attributes for SSL communication: verify the remote host's SSL certificate, and cause the connection to fail
     * if the cert verification fails.
     *
     * By default, verification is enabled.
     * To specify custom SSL certificates to validate the server with, use the setCaCertificate method.
     *
     * @param bool $i enable/disable verification of peer certificate
     * @return $this
     * @deprecated use setOption
     */
    public function setSSLVerifyPeer($i)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->verifypeer = $i;
        return $this;
    }

    /**
     * Set attributes for SSL communication: verify the remote host's SSL certificate's common name (CN).
     *
     * Note that support for value 1 has been removed in cURL 7.28.1
     *
     * @param int $i Set to 1 to only the existence of a CN, not that it matches
     * @return $this
     * @deprecated use setOption
     */
    public function setSSLVerifyHost($i)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->verifyhost = $i;
        return $this;
    }

    /**
     * Set attributes for SSL communication: SSL version to use. Best left at 0 (default value): let cURL decide
     *
     * @param int $i
     * @return $this
     * @deprecated use setOption
     */
    public function setSSLVersion($i)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->sslversion = $i;
        return $this;
    }

    /**
     * Set proxy info.
     *
     * NB: CURL versions before 7.11.10 cannot use a proxy to communicate with https servers.
     *
     * @param string $proxyHost
     * @param string $proxyPort Defaults to 8080 for HTTP and 443 for HTTPS
     * @param string $proxyUsername Leave blank if proxy has public access
     * @param string $proxyPassword Leave blank if proxy has public access
     * @param int $proxyAuthType defaults to CURLAUTH_BASIC (Basic authentication protocol); set to constant CURLAUTH_NTLM
     *                           to use NTLM auth with proxy (has effect only when the client uses the HTTP 1.1 protocol)
     * @return $this
     */
    public function setProxy($proxyHost, $proxyPort, $proxyUsername = '', $proxyPassword = '', $proxyAuthType = 1)
    {
        $this->proxy = $proxyHost;
        $this->proxyport = $proxyPort;
        $this->proxy_user = $proxyUsername;
        $this->proxy_pass = $proxyPassword;
        $this->proxy_authtype = $proxyAuthType;
        return $this;
    }

    /**
     * Enables/disables reception of compressed xml-rpc responses.
     *
     * This requires the "zlib" extension to be enabled in your php install. If it is, by default xmlrpc_client
     * instances will enable reception of compressed content.
     * Note that enabling reception of compressed responses merely adds some standard http headers to xml-rpc requests.
     * It is up to the xml-rpc server to return compressed responses when receiving such requests.
     *
     * @param string $compMethod either 'gzip', 'deflate', 'any' or ''
     * @return $this
     */
    public function setAcceptedCompression($compMethod)
    {
        if ($compMethod == 'any') {
            $this->accepted_compression = array('gzip', 'deflate');
        } elseif ($compMethod == false) {
            $this->accepted_compression = array();
        } else {
            $this->accepted_compression = array($compMethod);
        }
        return $this;
    }

    /**
     * Enables/disables http compression of xml-rpc request.
     *
     * This requires the "zlib" extension to be enabled in your php install.
     * Take care when sending compressed requests: servers might not support them (and automatic fallback to
     * uncompressed requests is not yet implemented).
     *
     * @param string $compMethod either 'gzip', 'deflate' or ''
     * @return $this
     * @deprecated use setOption
     */
    public function setRequestCompression($compMethod)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->request_compression = $compMethod;
        return $this;
    }

    /**
     * Adds a cookie to list of cookies that will be sent to server with every further request (useful e.g. for keeping
     * session info outside the xml-rpc payload).
     *
     * NB: by default all cookies set via this method are sent to the server, regardless of path/domain/port. Taking
     * advantage of those values is left to the single developer.
     *
     * @param string $name nb: will not be escaped in the request's http headers. Take care not to use CTL chars or
     *                     separators!
     * @param string $value
     * @param string $path
     * @param string $domain
     * @param int $port do not use! Cookies are not separated by port
     * @return $this
     *
     * @todo check correctness of urlencoding cookie value (copied from php way of doing it, but php is generally sending
     *       response not requests. We do the opposite...)
     * @todo strip invalid chars from cookie name? As per RFC6265, we should follow RFC2616, Section 2.2
     * @todo drop/rename $port parameter. Cookies are not isolated by port!
     * @todo feature-creep allow storing 'expires', 'secure', 'httponly' and 'samesite' cookie attributes
     */
    public function setCookie($name, $value = '', $path = '', $domain = '', $port = null)
    {
        $this->cookies[$name]['value'] = rawurlencode($value);
        if ($path || $domain || $port) {
            $this->cookies[$name]['path'] = $path;
            $this->cookies[$name]['domain'] = $domain;
            $this->cookies[$name]['port'] = $port;
        }
        return $this;
    }

    /**
     * Directly set cURL options, for extra flexibility (when in cURL mode).
     *
     * It allows e.g. to bind client to a specific IP interface / address.
     *
     * @param array $options
     * @return $this
     * @deprecated use setOption
     */
    public function setCurlOptions($options)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->extracurlopts = $options;
        return $this;
    }

    /**
     * @param int $useCurlMode self::USE_CURL_ALWAYS, self::USE_CURL_AUTO or self::USE_CURL_NEVER
     * @return $this
     * @deprecated use setOption
     */
    public function setUseCurl($useCurlMode)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->use_curl = $useCurlMode;
        return $this;
    }


    /**
     * Set user-agent string that will be used by this client instance in http headers sent to the server.
     *
     * The default user agent string includes the name of this library and the version number.
     *
     * @param string $agentString
     * @return $this
     * @deprecated use setOption
     */
    public function setUserAgent($agentString)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        $this->user_agent = $agentString;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $url = $this->method . '://' . $this->server;
        if (($this->port = 80 && in_array($this->method, array('http', 'http10', 'http11', 'h2c'))) &&
            ($this->port = 443 && in_array($this->method, array('https', 'h2')))) {
            return $url . $this->path;
        } else {
            return $url . ':' . $this->port . $this->path;
        }
    }

    /**
     * Send an xml-rpc request to the server.
     *
     * @param Request|Request[]|string $req The Request object, or an array of requests for using multicall, or the
     *                                      complete xml representation of a request.
     *                                      When sending an array of Request objects, the client will try to make use of
     *                                      a single 'system.multicall' xml-rpc method call to forward to the server all
     *                                      the requests in a single HTTP round trip, unless $this->no_multicall has
     *                                      been previously set to TRUE (see the multicall method below), in which case
     *                                      many consecutive xml-rpc requests will be sent. The method will return an
     *                                      array of Response objects in both cases.
     *                                      The third variant allows to build by hand (or any other means) a complete
     *                                      xml-rpc request message, and send it to the server. $req should be a string
     *                                      containing the complete xml representation of the request. It is e.g. useful
     *                                      when, for maximal speed of execution, the request is serialized into a
     *                                      string using the native php xml-rpc functions (see http://www.php.net/xmlrpc)
     * @param integer $timeout deprecated. Connection timeout, in seconds, If unspecified, the timeout set with setOption
     *                         will be used. If that is 0, a platform specific timeout will apply.
     *                         This timeout value is passed to fsockopen(). It is also used for detecting server
     *                         timeouts during communication (i.e. if the server does not send anything to the client
     *                         for $timeout seconds, the connection will be closed).
     * @param string $method deprecated. Use the same value in the constructor instead.
     *                       Valid values are 'http', 'http11', 'https', 'h2' and 'h2c'. If left empty,
     *                       the http protocol chosen during creation of the object will be used.
     *                       Use 'h2' to make the lib attempt to use http/2 over a secure connection, and 'h2c'
     *                       for http/2 without tls. Note that 'h2c' will not use the h2c 'upgrade' method, and be
     *                       thus incompatible with any server/proxy not supporting http/2. This is because POST
     *                       request are not compatible with h2c upgrade.
     * @return Response|Response[] Note that the client will always return a Response object, even if the call fails
     *
     * @todo allow throwing exceptions instead of returning responses in case of failed calls and/or Fault responses
     * @todo refactor: we now support many options besides connection timeout and http version to use. Why only privilege those?
     */
    public function send($req, $timeout = 0, $method = '')
    {
        if ($method !== '' || $timeout !== 0) {
            $this->logDeprecation("Using non-default values for arguments 'method' and 'timeout' when calling method " . __METHOD__ . ' is deprecated');
        }

        // if user does not specify http protocol, use native method of this client
        // (i.e. method set during call to constructor)
        if ($method == '') {
            $method = $this->method;
        }

        if ($timeout == 0) {
            $timeout = $this->timeout;
        }

        if (is_array($req)) {
            // $req is an array of Requests
            $r = $this->multicall($req, $timeout, $method);

            return $r;
        } elseif (is_string($req)) {
            $n = new static::$requestClass('');
            $n->payload = $req;
            $req = $n;
        }

        // where req is a Request
        $req->setDebug($this->debug);

        /// @todo we could be smarter about this and force usage of curl in scenarios where it is both available and
        ///       needed, such as digest or ntlm auth. Do not attempt to use it for https if not present
        $useCurl = ($this->use_curl == self::USE_CURL_ALWAYS) || ($this->use_curl == self::USE_CURL_AUTO &&
            (in_array($method, array('https', 'http11', 'h2c', 'h2'))));

        if ($useCurl) {
            $r = $this->sendPayloadCURL(
                $req,
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
                // bc
                $method == 'http11' ? 'http' : $method,
                $this->keepalive,
                $this->key,
                $this->keypass,
                $this->sslversion
            );
        } else {
            $r = $this->sendPayloadSocket(
                $req,
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
                $method,
                $this->key,
                $this->keypass,
                $this->sslversion
            );
        }

        return $r;
    }

    /**
     * @deprecated
     *
     * @param Request $req
     * @param string $server
     * @param int $port
     * @param int $timeout
     * @param string $username
     * @param string $password
     * @param int $authType
     * @param string $proxyHost
     * @param int $proxyPort
     * @param string $proxyUsername
     * @param string $proxyPassword
     * @param int $proxyAuthType
     * @param string $method
     * @return Response
     */
    protected function sendPayloadHTTP10($req, $server, $port, $timeout = 0, $username = '', $password = '',
        $authType = 1, $proxyHost = '', $proxyPort = 0, $proxyUsername = '', $proxyPassword = '', $proxyAuthType = 1,
        $method='http')
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        return $this->sendPayloadSocket($req, $server, $port, $timeout, $username, $password, $authType, null, null,
            null, null, $proxyHost, $proxyPort, $proxyUsername, $proxyPassword, $proxyAuthType, $method);
    }

    /**
     * @deprecated
     *
     * @param Request $req
     * @param string $server
     * @param int $port
     * @param int $timeout
     * @param string $username
     * @param string $password
     * @param int $authType
     * @param string $cert
     * @param string $certPass
     * @param string $caCert
     * @param string $caCertDir
     * @param string $proxyHost
     * @param int $proxyPort
     * @param string $proxyUsername
     * @param string $proxyPassword
     * @param int $proxyAuthType
     * @param bool $keepAlive
     * @param string $key
     * @param string $keyPass
     * @param int $sslVersion
     * @return Response
     */
    protected function sendPayloadHTTPS($req, $server, $port, $timeout = 0, $username = '',  $password = '',
        $authType = 1, $cert = '', $certPass = '', $caCert = '', $caCertDir = '', $proxyHost = '', $proxyPort = 0,
        $proxyUsername = '', $proxyPassword = '', $proxyAuthType = 1, $keepAlive = false, $key = '', $keyPass = '',
        $sslVersion = 0)
    {
        $this->logDeprecation('Method ' . __METHOD__ . ' is deprecated');

        return $this->sendPayloadCURL($req, $server, $port, $timeout, $username,
            $password, $authType, $cert, $certPass, $caCert, $caCertDir, $proxyHost, $proxyPort,
            $proxyUsername, $proxyPassword, $proxyAuthType, 'https', $keepAlive, $key, $keyPass, $sslVersion);
    }

    /**
     * @param Request $req
     * @param string $server
     * @param int $port
     * @param int $timeout
     * @param string $username
     * @param string $password
     * @param int $authType only value supported is 1
     * @param string $cert
     * @param string $certPass
     * @param string $caCert
     * @param string $caCertDir
     * @param string $proxyHost
     * @param int $proxyPort
     * @param string $proxyUsername
     * @param string $proxyPassword
     * @param int $proxyAuthType only value supported is 1
     * @param string $method 'http' (synonym for 'http10'), 'http10' or 'https'
     * @param string $key
     * @param string $keyPass @todo not implemented yet.
     * @param int $sslVersion @todo not implemented yet. See http://php.net/manual/en/migration56.openssl.php
     * @return Response
     *
     * @todo refactor: we get many options for the call passed in, but some we use from $this. We should clean that up
     */
    protected function sendPayloadSocket($req, $server, $port, $timeout = 0, $username = '', $password = '',
        $authType = 1, $cert = '', $certPass = '', $caCert = '', $caCertDir = '', $proxyHost = '', $proxyPort = 0,
        $proxyUsername = '', $proxyPassword = '', $proxyAuthType = 1, $method='http', $key = '', $keyPass = '',
        $sslVersion = 0)
    {
        /// @todo log a warning if passed an unsupported method

        // Only create the payload if it was not created previously
        /// @todo what if the request's payload was created with a different encoding?
        if (empty($req->payload)) {
            $req->serialize($this->request_charset_encoding);
        }
        $payload = $req->payload;

        // Deflate request body and set appropriate request headers
        $encodingHdr = '';
        if (function_exists('gzdeflate') && ($this->request_compression == 'gzip' || $this->request_compression == 'deflate')) {
            if ($this->request_compression == 'gzip') {
                $a = @gzencode($payload);
                if ($a) {
                    $payload = $a;
                    $encodingHdr = "Content-Encoding: gzip\r\n";
                }
            } else {
                $a = @gzcompress($payload);
                if ($a) {
                    $payload = $a;
                    $encodingHdr = "Content-Encoding: deflate\r\n";
                }
            }
        }

        // thanks to Grant Rauscher <grant7@firstworld.net> for this
        $credentials = '';
        if ($username != '') {
            $credentials = 'Authorization: Basic ' . base64_encode($username . ':' . $password) . "\r\n";
            if ($authType != 1) {
                /// @todo make this a proper error, i.e. return a failure
                $this->getLogger()->error('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth is supported with HTTP 1.0');
            }
        }

        $acceptedEncoding = '';
        if (is_array($this->accepted_compression) && count($this->accepted_compression)) {
            $acceptedEncoding = 'Accept-Encoding: ' . implode(', ', $this->accepted_compression) . "\r\n";
        }

        if ($port == 0) {
            $port = ($method === 'https') ? 443 : 80;
        }

        $proxyCredentials = '';
        if ($proxyHost) {
            if ($proxyPort == 0) {
                $proxyPort = 8080;
            }
            $connectServer = $proxyHost;
            $connectPort = $proxyPort;
            $transport = 'tcp';
            /// @todo check: should we not use https in some cases?
            $uri = 'http://' . $server . ':' . $port . $this->path;
            if ($proxyUsername != '') {
                if ($proxyAuthType != 1) {
                    /// @todo make this a proper error, i.e. return a failure
                    $this->getLogger()->error('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth to proxy is supported with HTTP 1.0');
                }
                $proxyCredentials = 'Proxy-Authorization: Basic ' . base64_encode($proxyUsername . ':' . $proxyPassword) . "\r\n";
            }
        } else {
            $connectServer = $server;
            $connectPort = $port;
            $transport = ($method === 'https') ? 'tls' : 'tcp';
            $uri = $this->path;
        }

        // Cookie generation, as per RFC6265
        // NB: the following code does not honour 'expires', 'path' and 'domain' cookie attributes set to client obj by the user...
        $cookieHeader = '';
        if (count($this->cookies)) {
            $version = '';
            foreach ($this->cookies as $name => $cookie) {
                /// @todo should we sanitize the cookie value on behalf of the user? See setCookie comments
                $cookieHeader .= ' ' . $name . '=' . $cookie['value'] . ";";
            }
            $cookieHeader = 'Cookie:' . $version . substr($cookieHeader, 0, -1) . "\r\n";
        }

        // omit port if default
        if (($port == 80 && in_array($method, array('http', 'http10'))) || ($port == 443 && $method == 'https')) {
            $port =  '';
        } else {
            $port = ':' . $port;
        }

        $op = 'POST ' . $uri . " HTTP/1.0\r\n" .
            'User-Agent: ' . $this->user_agent . "\r\n" .
            'Host: ' . $server . $port . "\r\n" .
            $credentials .
            $proxyCredentials .
            $acceptedEncoding .
            $encodingHdr .
            'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings) . "\r\n" .
            $cookieHeader .
            'Content-Type: ' . $req->content_type . "\r\nContent-Length: " .
            strlen($payload) . "\r\n\r\n" .
            $payload;

        if ($this->debug > 1) {
            $this->getLogger()->debug("---SENDING---\n$op\n---END---");
        }

        $contextOptions = array();
        if ($method == 'https') {
            if ($cert != '') {
                $contextOptions['ssl']['local_cert'] = $cert;
                if ($certPass != '') {
                    $contextOptions['ssl']['passphrase'] = $certPass;
                }
            }
            if ($caCert != '') {
                $contextOptions['ssl']['cafile'] = $caCert;
            }
            if ($caCertDir != '') {
                $contextOptions['ssl']['capath'] = $caCertDir;
            }
            if ($key != '') {
                $contextOptions['ssl']['local_pk'] = $key;
            }
            $contextOptions['ssl']['verify_peer'] = $this->verifypeer;
            $contextOptions['ssl']['verify_peer_name'] = $this->verifypeer;
        }

        $context = stream_context_create($contextOptions);

        if ($timeout <= 0) {
            $connectTimeout = ini_get('default_socket_timeout');
        } else {
            $connectTimeout = $timeout;
        }

        $this->errno = 0;
        $this->errstr = '';

        $fp = @stream_socket_client("$transport://$connectServer:$connectPort", $this->errno, $this->errstr, $connectTimeout,
            STREAM_CLIENT_CONNECT, $context);
        if ($fp) {
            if ($timeout > 0) {
                stream_set_timeout($fp, $timeout, 0);
            }
        } else {
            if ($this->errstr == '') {
                $err = error_get_last();
                $this->errstr = $err['message'];
            }

            $this->errstr = 'Connect error: ' . $this->errstr;
            $r = new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['http_error'], $this->errstr . ' (' . $this->errno . ')');

            return $r;
        }

        if (!fputs($fp, $op, strlen($op))) {
            fclose($fp);
            $this->errstr = 'Write error';
            $r = new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['http_error'], $this->errstr);

            return $r;
        }

        // Close socket before parsing.
        // It should yield slightly better execution times, and make easier recursive calls (e.g. to follow http redirects)
        $ipd = '';
        do {
            // shall we check for $data === FALSE?
            // as per the manual, it signals an error
            $ipd .= fread($fp, 32768);
        } while (!feof($fp));
        fclose($fp);

        $r = $req->parseResponse($ipd, false, $this->return_type);

        return $r;
    }

    /**
     * Contributed by Justin Miller <justin@voxel.net>
     * Requires curl to be built into PHP
     * NB: CURL versions before 7.11.10 cannot use proxy to talk to https servers!
     *
     * @param Request $req
     * @param string $server
     * @param int $port
     * @param int $timeout
     * @param string $username
     * @param string $password
     * @param int $authType
     * @param string $cert
     * @param string $certPass
     * @param string $caCert
     * @param string $caCertDir
     * @param string $proxyHost
     * @param int $proxyPort
     * @param string $proxyUsername
     * @param string $proxyPassword
     * @param int $proxyAuthType
     * @param string $method 'http' (let curl decide), 'http10', 'http11', 'https', 'h2c' or 'h2'
     * @param bool $keepAlive
     * @param string $key
     * @param string $keyPass
     * @param int $sslVersion
     * @return Response
     *
     * @todo refactor: we get many options for the call passed in, but some we use from $this. We should clean that up
     */
    protected function sendPayloadCURL($req, $server, $port, $timeout = 0, $username = '', $password = '',
        $authType = 1, $cert = '', $certPass = '', $caCert = '', $caCertDir = '', $proxyHost = '', $proxyPort = 0,
        $proxyUsername = '', $proxyPassword = '', $proxyAuthType = 1, $method = 'https', $keepAlive = false, $key = '',
        $keyPass = '', $sslVersion = 0)
    {
        if (!function_exists('curl_init')) {
            $this->errstr = 'CURL unavailable on this install';
            return new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['no_curl'], PhpXmlRpc::$xmlrpcstr['no_curl']);
        }
        if ($method == 'https' || $method == 'h2') {
            // q: what about installs where we get back a string, but curl is linked to other ssl libs than openssl?
            if (($info = curl_version()) &&
                ((is_string($info) && strpos($info, 'OpenSSL') === null) || (is_array($info) && !isset($info['ssl_version'])))
            ) {
                $this->errstr = 'SSL unavailable on this install';
                return new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['no_ssl'], PhpXmlRpc::$xmlrpcstr['no_ssl']);
            }
        }
        if (($method == 'h2' && !defined('CURL_HTTP_VERSION_2_0')) ||
            ($method == 'h2c' && !defined('CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE'))) {
            $this->errstr = 'HTTP/2 unavailable on this install';
            return new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['no_http2'], PhpXmlRpc::$xmlrpcstr['no_http2']);
        }

        $curl = $this->prepareCurlHandle($req, $server, $port, $timeout, $username, $password,
            $authType, $cert, $certPass, $caCert, $caCertDir, $proxyHost, $proxyPort,
            $proxyUsername, $proxyPassword, $proxyAuthType, $method, $keepAlive, $key,
            $keyPass, $sslVersion);

        if (!$curl) {
            return new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['curl_fail'], PhpXmlRpc::$xmlrpcstr['curl_fail'] . ': error during curl initialization. Check php error log for details');
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
            $message .= '---END---';
            $this->getLogger()->debug($message);
        }

        if (!$result) {
            /// @todo we should use a better check here - what if we get back '' or '0'?

            $this->errstr = 'no response';
            $resp = new static::$responseClass(0, PhpXmlRpc::$xmlrpcerr['curl_fail'], PhpXmlRpc::$xmlrpcstr['curl_fail'] . ': ' . curl_error($curl));
            curl_close($curl);
            if ($keepAlive) {
                $this->xmlrpc_curl_handle = null;
            }
        } else {
            if (!$keepAlive) {
                curl_close($curl);
            }
            $resp = $req->parseResponse($result, true, $this->return_type);
            if ($keepAlive) {
                /// @todo if we got back a 302 or 308, we should not reuse the curl handle for later calls
                if ($resp->faultCode() == PhpXmlRpc::$xmlrpcerr['http_error']) {
                    curl_close($curl);
                    $this->xmlrpc_curl_handle = null;
                }
            }
        }

        return $resp;
    }

    /**
     * @param $req
     * @param $server
     * @param $port
     * @param $timeout
     * @param $username
     * @param $password
     * @param $authType
     * @param $cert
     * @param $certPass
     * @param $caCert
     * @param $caCertDir
     * @param $proxyHost
     * @param $proxyPort
     * @param $proxyUsername
     * @param $proxyPassword
     * @param $proxyAuthType
     * @param $method
     * @param $keepAlive
     * @param $key
     * @param $keyPass
     * @param $sslVersion
     * @return false|\CurlHandle|resource
     *
     * @todo refactor: we get many options for the call passed in, but some we use from $this. We should clean that up
     */
    protected function prepareCurlHandle($req, $server, $port, $timeout = 0, $username = '', $password = '',
         $authType = 1, $cert = '', $certPass = '', $caCert = '', $caCertDir = '', $proxyHost = '', $proxyPort = 0,
         $proxyUsername = '', $proxyPassword = '', $proxyAuthType = 1, $method = 'https', $keepAlive = false, $key = '',
         $keyPass = '', $sslVersion = 0)
    {
        if ($port == 0) {
            if (in_array($method, array('http', 'http10', 'http11', 'h2c'))) {
                $port = 80;
            } else {
                $port = 443;
            }
        }

        // Only create the payload if it was not created previously
        if (empty($req->payload)) {
            $req->serialize($this->request_charset_encoding);
        }

        // Deflate request body and set appropriate request headers
        $payload = $req->payload;
        $encodingHdr = '';
        /// @todo test for existence of proper function, in case of polyfills
        if (function_exists('gzdeflate') && ($this->request_compression == 'gzip' || $this->request_compression == 'deflate')) {
            if ($this->request_compression == 'gzip') {
                $a = @gzencode($payload);
                if ($a) {
                    $payload = $a;
                    $encodingHdr = 'Content-Encoding: gzip';
                }
            } else {
                $a = @gzcompress($payload);
                if ($a) {
                    $payload = $a;
                    $encodingHdr = 'Content-Encoding: deflate';
                }
            }
        }

        if (!$keepAlive || !$this->xmlrpc_curl_handle) {
            if ($method == 'http11' || $method == 'http10' || $method == 'h2c') {
                $protocol = 'http';
            } else {
                if ($method == 'h2') {
                    $protocol = 'https';
                } else {
                    // http, https
                    $protocol = $method;
                    if (strpos($protocol, ':') !== false) {
                        $this->getLogger()->error('XML-RPC: ' . __METHOD__ . ": warning - attempted hacking attempt?. The curl protocol requested for the call is: '$protocol'");
                        return false;
                    }
                }
            }
            $curl = curl_init($protocol . '://' . $server . ':' . $port . $this->path);
            if (!$curl) {
                return false;
            }
            if ($keepAlive) {
                $this->xmlrpc_curl_handle = $curl;
            }
        } else {
            $curl = $this->xmlrpc_curl_handle;
        }

        // results into variable
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($this->debug > 1) {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
            /// @todo redirect curlopt_stderr to some stream which can be piped to the logger
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $this->user_agent);
        // required for XMLRPC: post the data
        curl_setopt($curl, CURLOPT_POST, 1);
        // the data
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

        // return the header too
        curl_setopt($curl, CURLOPT_HEADER, 1);

        // NB: if we set an empty string, CURL will add http header indicating
        // ALL methods it is supporting. This is possibly a better option than letting the user tell what curl can / cannot do...
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
        $headers = array('Content-Type: ' . $req->content_type, 'Accept-Charset: ' . implode(',', $this->accepted_charset_encodings));
        // if no keepalive is wanted, let the server know it in advance
        if (!$keepAlive) {
            $headers[] = 'Connection: close';
        }
        // request compression header
        if ($encodingHdr) {
            $headers[] = $encodingHdr;
        }

        // Fix the HTTP/1.1 417 Expectation Failed Bug (curl by default adds a 'Expect: 100-continue' header when POST
        // size exceeds 1025 bytes, apparently)
        $headers[] = 'Expect:';

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        // timeout is borked
        if ($timeout) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout == 1 ? 1 : $timeout - 1);
        }

        switch ($method) {
            case 'http10':
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
                break;
            case 'http11':
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                break;
            case 'h2c':
                if (defined('CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE')) {
                    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
                } else {
                    /// @todo make this a proper error, i.e. return a failure
                    $this->getLogger()->error('XML-RPC: ' . __METHOD__ . ': warning. HTTP2 is not supported by the current PHP/curl install');
                }
                break;
            case 'h2':
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
                break;
        }

        if ($username && $password) {
            curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
            if (defined('CURLOPT_HTTPAUTH')) {
                curl_setopt($curl, CURLOPT_HTTPAUTH, $authType);
            } elseif ($authType != 1) {
                /// @todo make this a proper error, i.e. return a failure
                $this->getLogger()->error('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth is supported by the current PHP/curl install');
            }
        }

        // note: h2c is http2 without the https. No need to have it in this IF
        if ($method == 'https' || $method == 'h2') {
            // set cert file
            if ($cert) {
                curl_setopt($curl, CURLOPT_SSLCERT, $cert);
            }
            // set cert password
            if ($certPass) {
                curl_setopt($curl, CURLOPT_SSLCERTPASSWD, $certPass);
            }
            // whether to verify remote host's cert
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
            // set ca certificates file/dir
            if ($caCert) {
                curl_setopt($curl, CURLOPT_CAINFO, $caCert);
            }
            if ($caCertDir) {
                curl_setopt($curl, CURLOPT_CAPATH, $caCertDir);
            }
            // set key file (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
            if ($key) {
                curl_setopt($curl, CURLOPT_SSLKEY, $key);
            }
            // set key password (shall we catch errors in case CURLOPT_SSLKEY undefined ?)
            if ($keyPass) {
                curl_setopt($curl, CURLOPT_SSLKEYPASSWD, $keyPass);
            }
            // whether to verify cert's common name (CN); 0 for no, 1 to verify that it exists, and 2 to verify that
            // it matches the hostname used
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verifyhost);
            // allow usage of different SSL versions
            curl_setopt($curl, CURLOPT_SSLVERSION, $sslVersion);
        }

        // proxy info
        if ($proxyHost) {
            if ($proxyPort == 0) {
                $proxyPort = 8080; // NB: even for HTTPS, local connection is on port 8080
            }
            curl_setopt($curl, CURLOPT_PROXY, $proxyHost . ':' . $proxyPort);
            if ($proxyUsername) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyUsername . ':' . $proxyPassword);
                if (defined('CURLOPT_PROXYAUTH')) {
                    curl_setopt($curl, CURLOPT_PROXYAUTH, $proxyAuthType);
                } elseif ($proxyAuthType != 1) {
                    /// @todo make this a proper error, i.e. return a failure
                    $this->getLogger()->error('XML-RPC: ' . __METHOD__ . ': warning. Only Basic auth to proxy is supported by the current PHP/curl install');
                }
            }
        }

        // NB: should we build cookie http headers by hand rather than let CURL do it?
        // NB: the following code does not honour 'expires', 'path' and 'domain' cookie attributes set to client obj by the user...
        if (count($this->cookies)) {
            $cookieHeader = '';
            foreach ($this->cookies as $name => $cookie) {
                $cookieHeader .= $name . '=' . $cookie['value'] . '; ';
            }
            curl_setopt($curl, CURLOPT_COOKIE, substr($cookieHeader, 0, -2));
        }

        foreach ($this->extracurlopts as $opt => $val) {
            curl_setopt($curl, $opt, $val);
        }

        if ($this->debug > 1) {
            $this->getLogger()->debug("---SENDING---\n$payload\n---END---");
        }

        return $curl;
    }

    /**
     * Send an array of requests and return an array of responses.
     *
     * Unless $this->no_multicall has been set to true, it will try first to use one single xml-rpc call to server method
     * system.multicall, and revert to sending many successive calls in case of failure.
     * This failure is also stored in $this->no_multicall for subsequent calls.
     * Unfortunately, there is no server error code universally used to denote the fact that multicall is unsupported,
     * so there is no way to reliably distinguish between that and a temporary failure.
     * If you are sure that server supports multicall and do not want to fallback to using many single calls, set the
     * fourth parameter to FALSE.
     *
     * NB: trying to shoehorn extra functionality into existing syntax has resulted
     * in pretty much convoluted code...
     *
     * @param Request[] $reqs an array of Request objects
     * @param integer $timeout deprecated - connection timeout (in seconds). See the details in the docs for the send() method
     * @param string $method deprecated - the http protocol variant to be used. See the details in the docs for the send() method
     * @param boolean $fallback When true, upon receiving an error during multicall, multiple single calls will be
     *                         attempted
     * @return Response[]
     */
    public function multicall($reqs, $timeout = 0, $method = '', $fallback = true)
    {
        if ($method == '') {
            $method = $this->method;
        }

        if (!$this->no_multicall) {
            $results = $this->_try_multicall($reqs, $timeout, $method);
            /// @todo how to handle the case of $this->return_type = xml?
            if (is_array($results)) {
                // System.multicall succeeded
                return $results;
            } else {
                // either system.multicall is unsupported by server, or the call failed for some other reason.
                // Feature creep: is there a way to tell apart unsupported multicall from other faults?
                if ($fallback) {
                    // Don't try it next time...
                    $this->no_multicall = true;
                } else {
                    $result = $results;
                }
            }
        } else {
            // override fallback, in case careless user tries to do two
            // opposite things at the same time
            $fallback = true;
        }

        $results = array();
        if ($fallback) {
            // system.multicall is (probably) unsupported by server: emulate multicall via multiple requests
            /// @todo use curl multi_ functions to make this quicker (see the implementation in the parallel.php demo)
            foreach ($reqs as $req) {
                $results[] = $this->send($req, $timeout, $method);
            }
        } else {
            // user does NOT want to fallback on many single calls: since we should always return an array of responses,
            // we return an array with the same error repeated n times
            foreach ($reqs as $req) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Attempt to boxcar $reqs via system.multicall.
     *
     * @param Request[] $reqs
     * @param int $timeout
     * @param string $method
     * @return Response[]|Response a single Response when the call returned a fault / does not conform to what we expect
     *                             from a multicall response
     */
    private function _try_multicall($reqs, $timeout, $method)
    {
        // Construct multicall request
        $calls = array();
        foreach ($reqs as $req) {
            $call['methodName'] = new Value($req->method(), 'string');
            $numParams = $req->getNumParams();
            $params = array();
            for ($i = 0; $i < $numParams; $i++) {
                $params[$i] = $req->getParam($i);
            }
            $call['params'] = new Value($params, 'array');
            $calls[] = new Value($call, 'struct');
        }
        $multiCall = new Request('system.multicall');
        $multiCall->addParam(new Value($calls, 'array'));

        // Attempt RPC call
        $result = $this->send($multiCall, $timeout, $method);

        if ($result->faultCode() != 0) {
            // call to system.multicall failed
            return $result;
        }

        // Unpack responses.
        $rets = $result->value();
        $response = array();

        if ($this->return_type == 'xml') {
            for ($i = 0; $i < count($reqs); $i++) {
                $response[] = new Response($rets, 0, '', 'xml', $result->httpResponse());
            }

        } elseif ($this->return_type == 'phpvals') {
            if (!is_array($rets)) {
                // bad return type from system.multicall
                return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                    PhpXmlRpc::$xmlrpcstr['multicall_error'] . ': not an array', 'phpvals', $result->httpResponse());
            }
            $numRets = count($rets);
            if ($numRets != count($reqs)) {
                // wrong number of return values.
                return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                    PhpXmlRpc::$xmlrpcstr['multicall_error'] . ': incorrect number of responses', 'phpvals',
                    $result->httpResponse());
            }

            for ($i = 0; $i < $numRets; $i++) {
                $val = $rets[$i];
                if (!is_array($val)) {
                    return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                        PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i is not an array or struct",
                        'phpvals', $result->httpResponse());
                }
                switch (count($val)) {
                    case 1:
                        if (!isset($val[0])) {
                            // Bad value
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has no value",
                                'phpvals', $result->httpResponse());
                        }
                        // Normal return value
                        $response[$i] = new Response($val[0], 0, '', 'phpvals', $result->httpResponse());
                        break;
                    case 2:
                        /// @todo remove usage of @: it is apparently quite slow
                        $code = @$val['faultCode'];
                        if (!is_int($code)) {
                            /// @todo should we check that it is != 0?
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has invalid or no faultCode",
                                'phpvals', $result->httpResponse());
                        }
                        $str = @$val['faultString'];
                        if (!is_string($str)) {
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has invalid or no FaultString",
                                'phpvals', $result->httpResponse());
                        }
                        $response[$i] = new Response(0, $code, $str, 'phpvals', $result->httpResponse());
                        break;
                    default:
                        return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                            PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has too many items",
                            'phpvals', $result->httpResponse());
                }
            }

        } else {
            // return type == 'xmlrpcvals'
            if ($rets->kindOf() != 'array') {
                return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                    PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i is not an array", 'xmlrpcvals',
                    $result->httpResponse());
            }
            $numRets = $rets->count();
            if ($numRets != count($reqs)) {
                // wrong number of return values.
                return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                    PhpXmlRpc::$xmlrpcstr['multicall_error'] . ': incorrect number of responses', 'xmlrpcvals',
                    $result->httpResponse());
            }

            foreach ($rets as $i => $val) {
                switch ($val->kindOf()) {
                    case 'array':
                        if ($val->count() != 1) {
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has too many items",
                                'phpvals', $result->httpResponse());
                        }
                        // Normal return value
                        $response[] = new Response($val[0], 0, '', 'xmlrpcvals', $result->httpResponse());
                        break;
                    case 'struct':
                        if ($val->count() != 2) {
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has too many items",
                                'phpvals', $result->httpResponse());
                        }
                        /** @var Value $code */
                        $code = $val['faultCode'];
                        if ($code->kindOf() != 'scalar' || $code->scalarTyp() != 'int') {
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has invalid or no faultCode",
                                'xmlrpcvals', $result->httpResponse());
                        }
                        /** @var Value $str */
                        $str = $val['faultString'];
                        if ($str->kindOf() != 'scalar' || $str->scalarTyp() != 'string') {
                            return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                                PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i has invalid or no faultCode",
                                'xmlrpcvals', $result->httpResponse());
                        }
                        $response[] = new Response(0, $code->scalarVal(), $str->scalarVal(), 'xmlrpcvals', $result->httpResponse());
                        break;
                    default:
                        return new Response(0, PhpXmlRpc::$xmlrpcerr['multicall_error'],
                            PhpXmlRpc::$xmlrpcstr['multicall_error'] . ": response element $i is not an array or struct",
                            'xmlrpcvals', $result->httpResponse());
                }
            }
        }

        return $response;
    }
}
