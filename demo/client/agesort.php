<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>xmlrpc - Agesort demo</title></head>
<body>
<h1>Agesort demo</h1>
<h2>Send an array of "name" => "age" pairs to the server that will send it back sorted.</h2>
<h3>The source code demonstrates basic lib usage, including manual creation of xml-rpc arrays and structs</h3>
<p>Have a look at <a href="getstatename.php">getstatename.php</a> for automatic encoding and decoding, and at
    <a href="../vardemo.php">vardemo.php</a> for more examples of manual encoding and decoding</p>
<p>You can see the source to this page here: <a href="agesort.php?showSource=1">agesort.php</a></p>
');

$inAr = array("Dave" => 24, "Edd" => 45, "Joe" => 37, "Fred" => 27);

output("This is the input data:<br/><pre>");
foreach ($inAr as $key => $val) {
    output($key . ", " . $val . "\n");
}
output("</pre>");

// Create parameters from the input array: an xmlrpc array of xmlrpc structs
$p = array();
foreach ($inAr as $key => $val) {
    $p[] = new PhpXmlRpc\Value(
        array(
            "name" => new PhpXmlRpc\Value($key),
            "age" => new PhpXmlRpc\Value($val, "int")
        ),
        "struct"
    );
}
$v = new PhpXmlRpc\Value($p, "array");
output("Encoded into xmlrpc format it looks like this: <pre>\n" . htmlentities($v->serialize()) . "</pre>\n");

// create client and message objects
$req = new PhpXmlRpc\Request('examples.sortByAge', array($v));
$client = new PhpXmlRpc\Client(XMLRPCSERVER);

// set maximum debug level, to have the complete communication printed to screen
$client->setDebug(2);

// send request
output("Now sending request (detailed debug info follows)");
$resp = $client->send($req);

// check response for errors, and take appropriate action
if (!$resp->faultCode()) {
    output("The server gave me these results:<pre>");
    $value = $resp->value();
    foreach ($value as $struct) {
        $name = $struct["name"];
        $age = $struct["age"];
        output(htmlspecialchars($name->scalarval()) . ", " . htmlspecialchars($age->scalarval()) . "\n");
    }

    output("<hr/>For nerds: I got this value back<br/><pre>" .
        htmlentities($resp->serialize()) . "</pre><hr/>\n");
} else {
    output("An error occurred:<pre>");
    output("Code: " . htmlspecialchars($resp->faultCode()) .
        "\nReason: '" . htmlspecialchars($resp->faultString()) . "'</pre><hr/>");
}

output("</body></html>\n");
