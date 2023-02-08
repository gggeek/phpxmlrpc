<?php
require_once __DIR__ . "/client/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc</title></head>
<body>
');

output("<h3>Testing value serialization</h3>\n");
output("<p>Please note that in most cases you are better off using `new PhpXmlRpc\Encoder()->encode()` to create nested Value objects</p>\n");

$v = new PhpXmlRpc\Value(1234, 'int');
output("Int: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value('Are the following characters escaped? < & >');
output("String <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value(true, 'boolean');
output("Boolean: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value(1234.5678, 'double');
output("Double: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value(time(), 'dateTime.iso8601');
output("Datetime (from timestamp): <PRE>" . htmlentities($v->serialize()) . "</PRE>");
$v = new PhpXmlRpc\Value(new DateTime(), 'dateTime.iso8601');
output("Datetime (from php DateTime): <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value('hello world', 'base64');
output("Base64: <PRE>" . htmlentities($v->serialize()) . "</PRE>");
output("(value of base64 string is: '" . $v->scalarVal() . "')<BR><BR>");

$v = new PhpXmlRpc\Value(
    array(
        new PhpXmlRpc\Value('1234', 'i4'),
        new PhpXmlRpc\Value("Can you spot the greek letter beta? β", 'string'),
        new PhpXmlRpc\Value(1, 'boolean'),
        new PhpXmlRpc\Value(1234, 'double'),
        new PhpXmlRpc\Value(new DateTime(), 'dateTime.iso8601'),
        new PhpXmlRpc\Value('', 'base64'),
    ),
    "array"
);
output("Array: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value(
    array(
        "anInt" => new PhpXmlRpc\Value(23, 'int'),
        "aString" => new PhpXmlRpc\Value('foobarwhizz'),
        "anEmptyArray" => new PhpXmlRpc\Value(
            array(),
            "array"
        ),
        "aNestedStruct" => new PhpXmlRpc\Value(
            array(
                "one" => new PhpXmlRpc\Value(1, 'int'),
                "two" => new PhpXmlRpc\Value(2, 'int'),
            ),
            "struct"
        ),
    ),
    "struct"
);
output("Struct: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$w = new PhpXmlRpc\Value(array($v), 'array');
output("Array containing a struct: <PRE>" . htmlentities($w->serialize()) . "</PRE>");

class MyClass
{
    public $public = 'a public property';
    protected $protected = 'a protected one';
    private $private = 'a private one';
}
$myObject = new MyClass();
// the public property is the only one which will be serialized. As such, it has to be of type Value
$myObject->public = new \PhpXmlRpc\Value('a public property, wrapped');
$w = new PhpXmlRpc\Value($myObject, 'struct');
output("Struct encoding a php object: <PRE>" . htmlentities($w->serialize()) . "</PRE>");

output("<h3>Testing value serialization - xml-rpc extensions</h3>\n");
$v = new PhpXmlRpc\Value(1234, 'i8');
output("I8: <PRE>" . htmlentities($v->serialize()) . "</PRE>");
$v = new PhpXmlRpc\Value(null, 'null');
output("Null: <PRE>" . htmlentities($v->serialize()) . "</PRE>");
\PhpXmlRpc\PhpXmlRpc::$xmlrpc_null_apache_encoding = true;
output("Null, alternative: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

output("<h3>Testing value serialization - character encoding</h3>\n");
// The greek word 'kosme'
$v = new PhpXmlRpc\Value('κόσμε');
output("Greek (default encoding): <PRE>" . htmlentities($v->serialize()) . "</PRE>");
output("Greek (utf8 encoding): <PRE>" . htmlentities($v->serialize('UTF-8')) . "</PRE>");
if (function_exists('mb_convert_encoding')) {
    output("Greek (ISO-8859-7 encoding): <PRE>" . htmlentities($v->serialize('ISO-8859-7')) . "</PRE>");
}

output("<h3>Testing request serialization</h3>\n");
$req = new PhpXmlRpc\Request('examples.getStateName');
$req->method('examples.getStateName');
$req->addParam(new PhpXmlRpc\Value(42, 'int'));
output("<PRE>" . htmlentities($req->serialize()) . "</PRE>");

output("<h3>Testing response serialization</h3>\n");
$resp = new PhpXmlRpc\Response(new PhpXmlRpc\Value('The meaning of life'));
output("<PRE>" . htmlentities($resp->serialize()) . "</PRE>");

output("<h3>Testing ISO date formatting</h3><pre>\n");
$t = time();
$date = PhpXmlRpc\Helper\Date::iso8601Encode($t);
output("Now is $t --> $date\n");
output("Or in UTC, that is " . PhpXmlRpc\Helper\Date::iso8601Encode($t, 1) . "\n");
$tb = PhpXmlRpc\Helper\Date::iso8601Decode($date);
output("That is to say $date --> $tb\n");
output("Which comes out at " . PhpXmlRpc\Helper\Date::iso8601Encode($tb) . "\n");
output("Which was the time in UTC at " . PhpXmlRpc\Helper\Date::iso8601Encode($tb, 1) . "\n");
output("</pre>\n");

output("<h3>Testing reduced-precision formatting for doubles</h3><pre>\n");
$v = new PhpXmlRpc\Value(1234.56789, 'double');
\PhpXmlRpc\PhpXmlRpc::$xmlpc_double_precision = 2;
output("Double, limited precision: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

output('</body></html>');
