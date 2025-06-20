<?php
/**
 * Defines functions and signatures which can be registered as methods exposed by an XML-RPC Server.
 *
 * To use this, use something akin to:
 * $signatures = include('tests.php');
 * NB: requires 'functions.php' to be included first
 *
 * Methods used by the phpxmlrpc testsuite
 */

use PhpXmlRpc\Encoder;
use PhpXmlRpc\Response;
use PhpXmlRpc\Value;

$getallheaders_sig = array(array(Value::$xmlrpcStruct));
$getallheaders_doc = 'Returns a struct containing all the HTTP headers received with the request. Provides limited functionality with IIS';
function getAllHeaders_xmlrpc($req)
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        // poor man's version of getallheaders. Thanks ralouphie/getallheaders
        $headers = array();
        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }
        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }
    }

    $encoder = new Encoder();
    return new Response($encoder->encode($headers));
}

// used to test mixed-convention calling
$setcookies_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$setcookies_doc = 'Sends to client a response containing a single \'1\' digit, and sets to it http cookies as received in the request (array of structs describing a cookie)';
function setCookies($cookies)
{
    foreach ($cookies as $name => $cookieDesc) {
        if (is_array($cookieDesc)) {
            setcookie($name, @$cookieDesc['value'], @$cookieDesc['expires'], @$cookieDesc['path'], @$cookieDesc['domain'], @$cookieDesc['secure']);
        } else {
            /// @todo
        }
    }

    return 1;
}

$getcookies_sig = array(array(Value::$xmlrpcStruct));
$getcookies_doc = 'Sends to client a response containing all http cookies as received in the request (as struct)';
function getCookies($req)
{
    $encoder = new Encoder();
    return new Response($encoder->encode($_COOKIE));
}

// used to test signatures with NULL params
$findstate12_sig = array(
    array(Value::$xmlrpcString, Value::$xmlrpcInt, Value::$xmlrpcNull),
    array(Value::$xmlrpcString, Value::$xmlrpcNull, Value::$xmlrpcInt),
);
function findStateWithNulls($req)
{
    $a = $req->getParam(0);
    $b = $req->getParam(1);

    if ($a->scalarTyp() == Value::$xmlrpcNull)
        return new Response(new Value(plain_findstate($b->scalarVal())));
    else
        return new Response(new Value(plain_findstate($a->scalarVal())));
}

$sleep_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcInt));
$sleep_doc = 'Sleeps for the requested number of seconds (between 1 and 60), before sending back the response';
function sleepSeconds($secs) {
    if ($secs > 0 && $secs < 61) {
        sleep($secs);
    }
    return $secs;
}

$hashttp2_sig = array(array(Value::$xmlrpcBoolean));
$hashttp2_doc = 'Checks whether the server supports http2';
function hasHTTP2() {
    // NB: only works for apache2 on debian/ubuntu, that we know of...!
    return is_file('/etc/apache2/mods-enabled/http2.load');
}

return array(
    "tests.getallheaders" => array(
        "function" => 'getAllHeaders_xmlrpc',
        "signature" => $getallheaders_sig,
        "docstring" => $getallheaders_doc,
    ),
    "tests.setcookies" => array(
        "function" => 'setCookies',
        "signature" => $setcookies_sig,
        "docstring" => $setcookies_doc,
        "parameters_type" => 'phpvals',
    ),
    "tests.getcookies" => array(
        "function" => 'getCookies',
        "signature" => $getcookies_sig,
        "docstring" => $getcookies_doc,
    ),

    // Greek word 'kosme'. NB: NOT a valid ISO8859 string!
    // NB: we can only register this when setting internal encoding to UTF-8, or it will break system.listMethods
    "tests.utf8methodname." . 'κόσμε' => array(
        "function" => "exampleMethods::stringEcho",
        "signature" => exampleMethods::$stringecho_sig,
        "docstring" => exampleMethods::$stringecho_doc,
    ),
    /*"tests.iso88591methodname." . chr(224) . chr(252) . chr(232) => array(
        "function" => "stringEcho",
        "signature" => $stringecho_sig,
        "docstring" => $stringecho_doc,
    ),*/

    'tests.getStateName.12' => array(
        "function" => "findStateWithNulls",
        "signature" => $findstate12_sig,
        "docstring" => exampleMethods::$findstate_doc,
    ),

    'tests.sleep' => array(
        "function" => 'sleepSeconds',
        "signature" => $sleep_sig,
        "docstring" => $sleep_doc,
        "parameters_type" => 'phpvals',
    ),

    'tests.hasHTTP2' => array(
        "function" => 'hasHTTP2',
        "signature" => $hashttp2_sig,
        "docstring" => $hashttp2_doc,
        "parameters_type" => 'phpvals',
    ),
);
