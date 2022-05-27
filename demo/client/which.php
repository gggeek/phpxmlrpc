<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>xmlrpc - Which toolkit demo</title></head>
<body>
<h1>Which toolkit demo</h1>
<h2>Query server for toolkit information</h2>
<h3>The code demonstrates usage of the PhpXmlRpc\Encoder class</h3>
<p>You can see the source to this page here: <a href="which.php?showSource=1">which.php</a></p>
');

$req = new PhpXmlRpc\Request('interopEchoTests.whichToolkit', array());
$client = new PhpXmlRpc\Client(XMLRPCSERVER);
$resp = $client->send($req);
if (!$resp->faultCode()) {
    $encoder = new PhpXmlRpc\Encoder();
    $value = $encoder->decode($resp->value());
    output("Toolkit info:<br/>\n");
    output("<pre>");
    output("name: " . htmlspecialchars($value["toolkitName"]) . "\n");
    output("version: " . htmlspecialchars($value["toolkitVersion"]) . "\n");
    output("docs: " . htmlspecialchars($value["toolkitDocsUrl"]) . "\n");
    output("os: " . htmlspecialchars($value["toolkitOperatingSystem"]) . "\n");
    output("</pre>");
} else {
    output("An error occurred: ");
    output("Code: " . htmlspecialchars($resp->faultCode()) . " Reason: '" . htmlspecialchars($resp->faultString()) . "'\n");
}

output("</body></html>\n");

require_once __DIR__ . "/_append.php";
