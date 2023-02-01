<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc - Introspect demo</title></head>
<body>
<h1>Introspect demo</h1>
<h2>Query server for available methods, their description and their signatures</h2>
<h3>The code demonstrates usage of multicall, introspection methods `system.listMethods` and co., and `$client->return_type`</h3>
<p>You can see the source to this page here: <a href="introspect.php?showSource=1">introspect.php</a></p>
');

use PhpXmlRpc\Client;
use PhpXmlRpc\Helper\XMLParser as XMLRPCParser;
use PhpXmlRpc\Request;

function display_error($r)
{
    output("An error occurred: ");
    output("Code: " . $r->faultCode() . " Reason: '" . $r->faultString() . "'<br/>");
}

$client = new Client(XMLRPCSERVER);
// tell the client we want back plain php values
$client->setOption(Client::OPT_RETURN_TYPE, XMLRPCParser::RETURN_PHP);

// First off, let's retrieve the list of methods available on the remote server
output("<h3>methods available at http://" . $client->getUrl(PHP_URL_HOST) . $client->getUrl(PHP_URL_PATH) . "</h3>\n");
$req = new Request('system.listMethods');
$resp = $client->send($req);

if ($resp->faultCode()) {
    display_error($resp);
} else {
    $v = $resp->value();

    // check if the server supports 'system.multicall', and configure the client accordingly
    $avoidMulticall = true;
    foreach ($v as $methodName) {
        if ($methodName == 'system.multicall') {
            $avoidMulticall = false;
            break;
        }
    }

    $client->setOption(Client::OPT_NO_MULTICALL, $avoidMulticall);

    // Then, retrieve the signature and help text of each available method
    foreach ($v as $methodName) {
        output("<h4>" . htmlspecialchars($methodName) . "</h4>\n");
        // build requests first, add params later
        $r1 = new PhpXmlRpc\Request('system.methodHelp');
        $r2 = new PhpXmlRpc\Request('system.methodSignature');
        $val = new PhpXmlRpc\Value($methodName, "string");
        $r1->addParam($val);
        $r2->addParam($val);
        // Send multiple requests in one/many http calls.
        $reqs = array($r1, $r2);
        $resps = $client->send($reqs);
        if ($resps[0]->faultCode()) {
            display_error($resps[0]);
        } else {
            output("<h5>Documentation</h5><p>\n");
            $txt = $resps[0]->value();
            if ($txt != "") {
                // NB: we explicitly avoid escaping the received data because the spec says that html _can be_ in methodHelp.
                // That is not a very good practice nevertheless!
                output("<p>$txt</p>\n");
            } else {
                output("<p>No documentation available.</p>\n");
            }
        }
        if ($resps[1]->faultCode()) {
            display_error($resps[1]);
        } else {
            output("<h5>Signature(s)</h5><p>\n");
            $sigs = $resps[1]->value();
            if (is_array($sigs)) {
                foreach ($sigs as $sn => $sig) {
                    // can we trust the server to be fully compliant with the spec?
                    if (!is_array($sig)) {
                        output("Signature $sn: unknown\n");
                        continue;
                    }
                    $ret = $sig[0];
                    output("<code>" . htmlspecialchars($ret) . " "
                        . htmlspecialchars($methodName) . "(");
                    if (count($sig) > 1) {
                        for ($k = 1; $k < count($sig); $k++) {
                            output(htmlspecialchars($sig[$k]));
                            if ($k < count($sig) - 1) {
                                output(", ");
                            }
                        }
                    }
                    output(")</code><br/>\n");
                }
            } else {
                output("Signature unknown\n");
            }
            output("</p>\n");
        }
    }
}

output("</body></html>\n");
