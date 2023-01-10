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
    $encoder = new Encoder();

    if (function_exists('getallheaders')) {
        return new Response($encoder->encode(getallheaders()));
    } else {
        $headers = array();
        // poor man's version of getallheaders
        foreach ($_SERVER as $key => $val) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = ucfirst(str_replace('_', '-', strtolower(substr($key, 5))));
                $headers[$key] = $val;
            }
        }

        return new Response($encoder->encode($headers));
    }
}

$setcookies_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$setcookies_doc = 'Sends to client a response containing a single \'1\' digit, and sets to it http cookies as received in the request (array of structs describing a cookie)';
function setCookies($req)
{
    $encoder = new Encoder();
    $cookies = $req->getParam(0);
    foreach ($cookies as $name => $value) {
        $cookieDesc = $encoder->decode($value);
        setcookie($name, @$cookieDesc['value'], @$cookieDesc['expires'], @$cookieDesc['path'], @$cookieDesc['domain'], @$cookieDesc['secure']);
    }

    return new Response(new Value(1, Value::$xmlrpcInt));
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

    if ($a->scalartyp() == Value::$xmlrpcNull)
        return new Response(new Value(plain_findstate($b->scalarval())));
    else
        return new Response(new Value(plain_findstate($a->scalarval())));
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
);
