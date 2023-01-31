<?php
require_once __DIR__ . "/_prepend.php";

output('<html lang="en">
<head><title>phpxmlrpc - Proxy demo</title></head>
<body>
<h1>proxy demo</h1>
<h2>Query server using a "proxy" object</h2>
<h3>The code demonstrates usage for the terminally lazy. For a more complete proxy, look at the Wrapper class</h3>
<p>You can see the source to this page here: <a href="proxy.php?showSource=1">proxy.php</a></p>
');

class XmlRpcProxy
{
    protected $client;
    protected $prefix;
    protected $encoder;
    protected $encodingOptions = array();

    /**
     * We rely on injecting a fully-formed Client, so that all the necessary http/debugging options can be set into it
     * without the need for this class to reimplement support for that configuration.
     */
    public function __construct(PhpXmlRpc\Client $client, $prefix = 'examples.', $encodingOptions = array())
    {
        $this->client = $client;
        $this->prefix = $prefix;
        $this->encodingOptions = $encodingOptions;
        $this->encoder = new PhpXmlRpc\Encoder();
    }

    /**
     * Translates any php method call to an xml-rpc call.
     * Note that the server might expose methods which can not be called directly this way, because their name includes
     * characters which are not allowed in a php method. That's why we implement as well method `call`
     *
     * @author Toth Istvan
     *
     * @param string $name remote function name. Will be prefixed
     * @param array $arguments any php value will do. For xml-rpc base64 values, wrap them in a Value object
     * @return mixed
     *
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $args = array();
        foreach ($arguments as $parameter) {
            $args[] = $this->encoder->encode($parameter, $this->encodingOptions);
        }

        // just in case this was set to something else
        $originalReturnType = $this->client->getOption(\PhpXmlRpc\Client::OPT_RETURN_TYPE);
        $this->client->setOption(\PhpXmlRpc\Client::OPT_RETURN_TYPE, 'phpvals');

        $resp = $this->client->send(new PhpXmlRpc\Request($this->prefix.$name, $args));

        $this->client->setOption(\PhpXmlRpc\Client::OPT_RETURN_TYPE, $originalReturnType);

        if ($resp->faultCode()) {
            throw new \Exception($resp->faultString(), $resp->faultCode());
        } else {
            return $resp->value();
        }
    }

    /**
     * In case the remote method name has characters which are not valid as php method names, use this.
     * (note that, in theory this is unlikely, as php has a broader definition for identifiers than xml-rpc method names)
     *
     * @param string $name remote function name. Will be prefixed
     * @param array $arguments any php value will do. For xml-rpc base64 values, wrap them in a Value object
     * @return mixed
     *
     * @throws Exception
     */
    public function call($name, $arguments)
    {
        return $this->__call($name, $arguments);
    }
}

$proxy = new XmlRpcProxy(new PhpXmlRpc\Client(XMLRPCSERVER));

$stateNo = rand(1, 51);
// sadly, no IDE will be able to assist with autocompletion for this method, unless you manually add an equivalent phpdoc comment...
$stateName = $proxy->getStateName($stateNo);

output("State $stateNo is ".htmlspecialchars($stateName));

output("</body></html>\n");
