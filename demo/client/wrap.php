<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc - Webservice wrapper demo</title></head>
<body>
<h1>Webservice wrapper demo</h1>

<h2>Wrap methods exposed by server into php functions</h2>

<h3>The code demonstrates usage of some of the most automagic client usage possible:<br/>
    1) client that returns php values instead of xml-rpc Value objects<br/>
    2) wrapping of remote methods into php functions<br/>
    See also proxy.php and codegen.php for alternative takes
</h3>
<p>You can see the source to this page here: <a href="wrap.php?showSource=1">wrap.php</a></p>
');

$client = new PhpXmlRpc\Client(XMLRPCSERVER);
$client->setOption(\PhpXmlRpc\Client::OPT_RETURN_TYPE, 'phpvals'); // let client give us back php values instead of xmlrpcvals
$resp = $client->send(new PhpXmlRpc\Request('system.listMethods'));
if ($resp->faultCode()) {
    output("<p>Server methods list could not be retrieved: error {$resp->faultCode()} '" . htmlspecialchars($resp->faultString()) . "'</p>\n");
} else {
    output("<p>Server methods list retrieved, now wrapping it up...</p>\n<ul>\n");
    flush();

    $callable = false;
    $wrapper = new PhpXmlRpc\Wrapper();
    foreach ($resp->value() as $methodName) {
        // $resp->value is an array of strings
        if ($methodName == 'examples.getStateName') {
            $callable = $wrapper->wrapXmlrpcMethod($client, $methodName);
            if ($callable) {
                output("<li>Remote server method " . htmlspecialchars($methodName) . " wrapped into php function</li>\n");
            } else {
                output("<li>Remote server method " . htmlspecialchars($methodName) . " could not be wrapped!</li>\n");
            }
            break;
        }
    }
    output("</ul>\n");
    flush();

    if ($callable) {
        output("Now testing function for remote method to convert U.S. state number into state name");
        $stateNum = rand(1, 51);
        // the 2nd parameter gets added to the closure - it is the debug level to be used for the client
        $stateName = $callable($stateNum, 2);
        output("State $stateNum is ".htmlspecialchars($stateName));
    }
}

output("</body></html>\n");
