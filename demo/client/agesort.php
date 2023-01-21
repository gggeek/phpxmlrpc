<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc - Agesort demo</title></head>
<body>
<h1>Agesort demo</h1>
<h2>Send an array of "name" => "age" pairs to the server that will send it back sorted.</h2>
<h3>The code demonstrates usage of automatic encoding/decoding of php variables into xml-rpc values such as arrays and structs</h3>
<p>Have a look at <a href="../vardemo.php">vardemo.php</a> for more examples of manual encoding and decoding</p>
<p>You can see the source to this page here: <a href="agesort.php?showSource=1">agesort.php</a></p>
');

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Request;

$inAr = array(
    array('name' => 'Dave', 'age' => 24),
    array('name' => 'Edd',  'age' => 45),
    array('name' => 'Joe',  'age' => 37),
    array('name' => 'Fred', 'age' => 27),
);

output('This is the input data:<br/><pre>');
foreach ($inAr as $val) {
    output($val['name'] . ", " . $val['age'] . "\n");
}
output('</pre>');

// Create xml-rpc parameters from the input array: an array of structs
$encoder = new Encoder();
$v = $encoder->encode($inAr);
output("Encoded into xml-rpc format it looks like this: <pre>\n" . htmlentities($v->serialize()) . "</pre>\n");

// create client and request objects
$req = new Request('examples.sortByAge', array($v));
$client = new Client(XMLRPCSERVER);

// set maximum debug level, to have the complete communication printed to screen
$client->setDebug(2);

// send request
output('Now sending the request... (very detailed debug info follows. Scroll to the bottom of the page for results!)<hr/>');
$resp = $client->send($req);
output('<hr/>');

// check response for errors, and take appropriate action
if (!$resp->faultCode()) {
    output('The server gave me these results:<pre>');
    $value = $resp->value();
    foreach ($encoder->decode($value) as $struct) {
        // note: here we are trusting the server's response to have the expected format
        output(htmlspecialchars($struct['name']) . ", " . htmlspecialchars($struct['age']) . "\n");
    }

    output('</pre><hr/>For nerds: I got this value back<br/><pre>' .
        htmlentities($resp->serialize()) . "</pre><hr/>\n");
} else {
    output('An error occurred:<pre>');
    output('Code: ' . htmlspecialchars($resp->faultCode()) .
        "\nReason: '" . htmlspecialchars($resp->faultString()) . "'</pre><hr/>");
}

output("</body></html>\n");
