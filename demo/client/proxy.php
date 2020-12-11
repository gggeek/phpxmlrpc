<?php require_once __DIR__ . "/_bootstrap.php"; ?><html lang="en">
<head><title>xmlrpc - Proxy demo</title></head>
<body>
<h1>proxy demo</h1>
<h2>Query server using a 'proxy' object</h2>
<h3>The code demonstrates usage for the terminally lazy. For a more complete proxy, look at at the Wrapper class</h3>
<p>You can see the source to this page here: <a href="proxy.php?showSource=1">proxy.php</a></p>
<?php

class PhpXmlRpcProxy
{
    protected $client;
    protected $prefix = 'examples.';

    public function __construct(PhpXmlRpc\Client $client)
    {
        $this->client = $client;
    }

    /**
     * Translates any method call to an xmlrpc call.
     *
     * @author Toth Istvan
     *
     * @param string $name remote function name. Will be prefixed
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $encoder = new PhpXmlRpc\Encoder();
        $valueArray = array();
        foreach ($arguments as $parameter) {
            $valueArray[] = $encoder->encode($parameter);
        }

        // just in case this was set to something else
        $this->client->return_type = 'phpvals';

        $resp = $this->client->send(new PhpXmlRpc\Request($this->prefix.$name, $valueArray));

        if ($resp->faultCode()) {
            throw new Exception($resp->faultString(), $resp->faultCode());
        } else {
            return $resp->value();
        }
    }
}

$stateNo = rand(1, 51);
$proxy = new PhpXmlRpcProxy(new \PhpXmlRpc\Client(XMLRPCSERVER));
$stateName = $proxy->getStateName($stateNo);

echo "State $stateNo is ".htmlspecialchars($stateName);
