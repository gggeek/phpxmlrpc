<?php

require_once __DIR__ . "/_prepend.php";

use PhpXmlRpc\Encoder;
use PhpXmlRpc\Client;
use PhpXmlRpc\PhpXmlRpc;
use PhpXmlRpc\Request;
use PhpXmlRpc\Response;

/**
 * A class taking advantage of cURL to send many requests in parallel (to a single server), for when the given server
 * does not support system.multicall method
 */
class ParallelClient extends Client
{
    public function sendParallel($requests, $timeout = 0, $method = '')
    {
        if ($method == '') {
            $method = $this->method;
        }

        /// @todo validate that $method can be handled by the current curl install

        $handles = array();
        $curl = curl_multi_init();

        foreach($requests as $k => $req) {
            $req->setDebug($this->debug);

            $handle = $this->prepareCurlHandle(
                $req,
                $this->server,
                $this->port,
                $timeout,
                $this->username,
                $this->password,
                $this->authtype,
                $this->cert,
                $this->certpass,
                $this->cacert,
                $this->cacertdir,
                $this->proxy,
                $this->proxyport,
                $this->proxy_user,
                $this->proxy_pass,
                $this->proxy_authtype,
                $method,
                false,
                $this->key,
                $this->keypass,
                $this->sslversion
            );
            curl_multi_add_handle($curl, $handle);
            $handles[$k] = $handle;
        }

        $running = 0;
        do {
            curl_multi_exec($curl, $running);
        } while($running > 0);

        $responses = array();
        foreach($handles as $k => $h) {
            $responses[$k] = curl_multi_getcontent($handles[$k]);

            if ($this->debug > 1) {
                $message = "---CURL INFO---\n";
                foreach (curl_getinfo($h) as $name => $val) {
                    if (is_array($val)) {
                        $val = implode("\n", $val);
                    }
                    $message .= $name . ': ' . $val . "\n";
                }
                $message .= '---END---';
                $this->getLogger()->debugMessage($message);
            }

            //curl_close($h);
            curl_multi_remove_handle($curl, $h);
        }
        curl_multi_close($curl);

        foreach($responses as $k => $resp) {
            if (!$resp) {
                $responses[$k] = new Response(0, PhpXmlRpc::$xmlrpcerr['curl_fail'], PhpXmlRpc::$xmlrpcstr['curl_fail'] . ': ' . curl_error($curl));
            } else {
                $responses[$k] = $requests[$k]->parseResponse($resp, true, $this->return_type);
            }
        }

        return $responses;
    }
}

$num_tests = 25;

$data = array(1, 1.0, 'hello world', true, '20051021T23:43:00', -1, 11.0, '~!@#$%^&*()_+|', false, '20051021T23:43:00');
$encoder = new Encoder();
$value = $encoder->encode($data, array('auto_dates'));
$req = new Request('interopEchoTests.echoValue', array($value));
$reqs = array();
for ($i = 0; $i < $num_tests; $i++) {
    $reqs[] = $req;
}

$client = new ParallelClient(XMLRPCSERVER);
$client->no_multicall = true;

$t = microtime(true);
$resp = $client->send($reqs);
$t = microtime(true) - $t;
echo "Sequential send: $t secs\n";

$t = microtime(true);
$resp = $client->sendParallel($reqs);
$t = microtime(true) - $t;
echo "Parallel send: $t secs\n";

$client->no_multicall = false;
$t = microtime(true);
$resp = $client->send($reqs);
$t = microtime(true) - $t;
echo "Multicall send: $t secs\n";

require_once __DIR__ . "/_append.php";
