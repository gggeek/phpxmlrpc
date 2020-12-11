<?php require_once __DIR__ . "/_bootstrap.php"; ?><html lang="en">
<head><title>xmlrpc - Which toolkit demo</title></head>
<body>
<h1>Which toolkit demo</h1>
<h2>Query server for toolkit information</h2>
<h3>The code demonstrates usage of the PhpXmlRpc\Encoder class</h3>
<p>You can see the source to this page here: <a href="which.php?showSource=1">which.php</a></p>
<?php

$req = new PhpXmlRpc\Request('interopEchoTests.whichToolkit', array());
$client = new PhpXmlRpc\Client(XMLRPCSERVER);
$resp = $client->send($req);
if (!$resp->faultCode()) {
    $encoder = new PhpXmlRpc\Encoder();
    $value = $encoder->decode($resp->value());
    print "Toolkit info:<br/>\n";
    print "<pre>";
    print "name: " . htmlspecialchars($value["toolkitName"]) . "\n";
    print "version: " . htmlspecialchars($value["toolkitVersion"]) . "\n";
    print "docs: " . htmlspecialchars($value["toolkitDocsUrl"]) . "\n";
    print "os: " . htmlspecialchars($value["toolkitOperatingSystem"]) . "\n";
    print "</pre>";
} else {
    print "An error occurred: ";
    print "Code: " . htmlspecialchars($resp->faultCode()) . " Reason: '" . htmlspecialchars($resp->faultString()) . "'\n";
}
?>
</body>
</html>
