<html>
<head><title>xmlrpc - Zope test demo</title></head>
<body>
<h1>Zope test demo</h1>

<h3>The code demonstrates usage of basic authentication to connect to the server</h3>
<?php

include_once __DIR__ . "/../../src/Autoloader.php";
PhpXmlRpc\Autoloader::register();

$req = new PhpXmlRpc\Request('document_src', array());
$client = new PhpXmlRpc\Client("pingu.heddley.com:9080/index_html");
$client->setCredentials("username", "password");
$client->setDebug(2);
$resp = $client->send($req);
if (!$resp->faultCode()) {
    $value = $resp->value();
    print "I received:" . htmlspecialchars($value->scalarval()) . "<br/>";
    print "<hr/>I got this value back<br/>pre>" .
        htmlentities($resp->serialize()) . "</pre>\n";
} else {
    print "An error occurred: ";
    print "Code: " . htmlspecialchars($resp->faultCode())
        . " Reason: '" . ($resp->faultString()) . "'<br/>";
}
?>
</body>
</html>
