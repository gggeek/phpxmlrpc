<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc - Getstatename demo</title></head>
<body>
<h1>Getstatename demo</h1>
<h2>Send a U.S. state number to the server and get back the state name</h2>
<h3>The source code demonstrates basic lib usage, including manual creation and decoding of xml-rpc values</h3>
<p>You can see the source to this page here: <a href="getstatename.php?showSource=1">getstatename.php</a></p>
');

use PhpXmlRpc\Client;
use PhpXmlRpc\Request;
use PhpXmlRpc\Value;

$stateNo = "";

if (isset($_POST['stateno']) && $_POST['stateno'] != "") {
    $stateNo = (integer)$_POST['stateno'];
    $method = 'examples.getStateName';
    $arguments = array(
        new Value($stateNo, Value::$xmlrpcInt),
    );
    $req = new Request($method, $arguments);
    output("Sending the following request:<pre>\n\n" . htmlentities($req->serialize()) .
        "\n\n</pre>Debug info of server data follows...\n\n");
    $client = new Client(XMLRPCSERVER);
    $client->setOption(Client::OPT_DEBUG, 1);
    $resp = $client->send($req);
    if (!$resp->faultCode()) {
        $val = $resp->value();
        // NB: we are _assuming_ that the server did return a scalar xml-rpc value here.
        // If the server is not trusted, we might check that via `$val->kindOf() == 'scalar'`
        output('<br/>State number <b>' . $stateNo . '</b> is <b>'
            . htmlspecialchars($val->scalarVal()) . '</b><br/><br/>');
    } else {
        output('An error occurred: ');
        output('<pre>Code: ' . htmlspecialchars($resp->faultCode())
            . " Reason: '" . htmlspecialchars($resp->faultString()) . "'</pre>");
    }
}

output("<form action=\"getstatename.php\" method=\"POST\">
<input name=\"stateno\" value=\"$stateNo\">
<input type=\"submit\" value=\"go\" name=\"submit\">
</form>
<p>Enter a state number to query its name</p>");

output("</body></html>\n");
