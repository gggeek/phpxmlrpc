<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc - Which toolkit demo</title></head>
<body>
<h1>Which toolkit demo</h1>
<h2>Query server for toolkit information</h2>
<h3>The code demonstrates support for http redirects, the `interopEchoTests.whichToolkit` xml-rpc methods, request compression and use of pre-built xml</h3>
<p>You can see the source to this page here: <a href="which.php?showSource=1">which.php</a></p>
');

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;

// use a pre-built request payload
$payload = '<?xml version="1.0"?>
<methodCall>
    <methodName>interopEchoTests.whichToolkit</methodName>
    <params/>
</methodCall>';
output("XML custom request:<br/><pre>" . htmlspecialchars($payload) . "</pre>\n");

$client = new Client(XMLRPCSERVER);

// to support http redirects we have to force usage of cURL even for http 1.0 requests
$client->setOption(Client::OPT_USE_CURL, Client::USE_CURL_ALWAYS);
$client->setOption(Client::OPT_EXTRA_CURL_OPTS, array(CURLOPT_FOLLOWLOCATION => true, CURLOPT_POSTREDIR => 3));

// if we know that the server supports them, we can enable sending of compressed requests
$client->setOption(Client::OPT_REQUEST_COMPRESSION, 'gzip');

// ask the client to give us back xml
$client->setOption(Client::OPT_RETURN_TYPE, 'xml');

$client->setDebug(1);

$resp = $client->send($payload);

if (!$resp->faultCode()) {

    $xml = $resp->value();
    output("XML response:<br/><pre>" . htmlspecialchars($xml) . "</pre>\n");

    $encoder = new Encoder();
    // from xml to xml-rpc Response
    $response = $encoder->decodeXml($xml);
    // from Response to Value
    $value = $response->value();
    // from Value to php
    $value = $encoder->decode($value);

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
