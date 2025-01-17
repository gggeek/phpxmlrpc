<?php
/**
 * XML-RPC server acting as proxy for requests to other servers
 * (useful e.g. for js-originated calls that can only connect back to the originating server because of the same-domain policy).
 * NB: this is an OPEN RELAY. It is meant as a demo, not to be used in production!
 *
 * For an example of a transparent reverse-proxy, see the ReverseProxy class in package phpxmlrpc/extras.
 *
 * The source code demonstrates:
 * - usage of the PhpXmlRpc\Encoder class to convert between php values and xml-rpc Value objects
 * - setting of options related to the http transport to a Client
 * - usage of multiple signatures for one xml-rpc method
 *
 * @author Gaetano Giunta
 * @copyright (C) 2006-2025 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 */

require_once __DIR__ . "/_prepend.php";

// *** NB: WE BLOCK THIS FROM RUNNING BY DEFAULT IN CASE ACCESS IS GRANTED TO IT IN PRODUCTION BY MISTAKE ***
// Comment out the following safeguard if you want to use it as is, but remember: this is an open relay !!!
// Open relays can easily be abused as trojan horses, allowing access to your private network.
if (!defined('TESTMODE')) {
    die("Server disabled by default for safety");
}

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Request;
use PhpXmlRpc\Server;

/**
 * Forward an xml-rpc request to another server, and return to client the response received.
 *
 * @param PhpXmlRpc\Request $req (see method docs below for a description of the expected parameters)
 * @return PhpXmlRpc\Response
 */
function forward_request($req)
{
    $encoder = new Encoder();

    // create client
    $timeout = 0;
    $url = $req->getParam(0)->scalarVal();
    // *** NB *** here we should validate the received url, using f.e. a whitelist of approved servers _and protocols_...
    //            fe. any url using the 'file://' protocol might be considered a hacking attempt
    $client = new Client($url);

    if ($req->getNumParams() > 3) {
        // We have to set some options onto the client.
        // Note that if we do not untaint the received values, warnings might be generated...
        $options = $encoder->decode($req->getParam(3));
        foreach ($options as $key => $val) {
            switch ($key) {
                case 'authType':
                    /// @todo add support for this if needed
                    break;
                case 'followRedirects':
                    // requires cURL to be enabled
                    if ($val) {
                        $client->setOption(Client::OPT_USE_CURL, Client::USE_CURL_ALWAYS);
                        $client->setOption(Client::OPT_EXTRA_CURL_OPTS, array(CURLOPT_FOLLOWLOCATION => true, CURLOPT_POSTREDIR => 3));
                    }
                case 'Cookies':
                    /// @todo add support for this if needed
                    break;
                case 'Credentials':
                    /// @todo add support for this as well if needed
                    break;
                case 'HTTPProxy':
                case 'HTTPProxyCredentials':
                    /// @todo add support for this as well if needed
                    break;
                case 'RequestCharsetEncoding':
                    // allow the server to work as charset transcoder.
                    // NB: works best with mbstring enabled
                    $client->setOption(Client::OPT_REQUEST_CHARSET_ENCODING, $val);
                    break;
                case 'RequestCompression':
                    $client->setOption(Client::OPT_REQUEST_COMPRESSION, $val);
                    break;
                case 'SSLVerifyHost':
                    $client->setOption(Client::OPT_VERIFY_HOST, $val);
                    break;
                case 'SSLVerifyPeer':
                    $client->setOption(Client::OPT_VERIFY_PEER, $val);
                    break;
                case 'Timeout':
                    $timeout = (integer)$val;
                    break;
            } // switch
        }
    }

    // build call for remote server
    /// @todo find a way to forward client info (such as IP) to the upstream server, either
    ///       - as xml comments in the payload, or
    ///       - using std http header conventions, such as X-forwarded-for (but public servers should strip
    ///         X-forwarded-for anyway, unless they consider this server as trusted...)
    $reqMethod = $req->getParam(1)->scalarVal();
    $req = new Request($reqMethod);
    if ($req->getNumParams() > 1) {
        $pars = $req->getParam(2);
        foreach ($pars as $par) {
            $req->addParam($par);
        }
    }

    // add debug info into response we give back to caller
    Server::xmlrpc_debugmsg("Sending to server $url the payload: " . $req->serialize());

    return $client->send($req, $timeout);
}

// Given that the target server is left to be picked by the caller, it might support the '<NIL/>' xml-rpc extension
PhpXmlRpc\PhpXmlRpc::$xmlrpc_null_extension = true;

// Run the server
// NB: take care not to output anything else after this call, as it will mess up the responses and it will be hard to
// debug. In case you have to do so, at least re-emit a correct Content-Length http header (requires output buffering)
$server = new Server(
    array(
        'xmlrpcproxy.call' => array(
            'function' => 'forward_request',
            'signature' => array(
                array('mixed', 'string', 'string'),
                array('mixed', 'string', 'string', 'array'),
                array('mixed', 'string', 'string', 'array', 'struct'),
            ),
            'docstring' => 'forwards xml-rpc calls to remote servers. Returns remote method\'s response. Accepts params: remote server url (might include basic auth credentials), method name, array of params (optional), and a struct containing call options (optional)',
        ),
    )
);
