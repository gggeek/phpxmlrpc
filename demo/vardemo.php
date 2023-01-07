<?php
require_once __DIR__ . "/client/_prepend.php";

output('<html lang="en">
<head><title>xmlrpc</title></head>
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
output("Datetime: <PRE>" . htmlentities($v->serialize()) . "</PRE>");

$v = new PhpXmlRpc\Value('hello world', 'base64');
output("Base64: <PRE>" . htmlentities($v->serialize()) . "</PRE>");
output("(value of base64 string is: '" . $v->scalarval() . "')<BR><BR>");

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

/*$w = new PhpXmlRpc\Value("Mary had a little lamb,
Whose fleece was white as snow,
And everywhere that Mary went
the lamb was sure to go.

Mary had a little lamb
She tied it to a pylon
Ten thousand volts went down its back
And turned it into nylon", "base64"
);
output("<PRE>" . htmlentities($w->serialize()) . "</PRE>");
output("<PRE>Value of base64 string is: '" . $w->scalarval() . "'</PRE>");*/

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

output('</body></html>');
