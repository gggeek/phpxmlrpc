<?php
require_once __DIR__ . "/_prepend.php";

use PhpXmlRpc\Encoder;
use PhpXmlRpc\Client;
use PhpXmlRpc\PhpXmlRpc;
use PhpXmlRpc\Request;
use PhpXmlRpc\Response;

/// @todo add an html header with links to view-source

/**
 * A class taking advantage of cURL to send many requests in parallel (to a single server), for when the given server
 * does not support the system.multicall method
 */
class ParallelClient extends Client
{
    public function sendParallel($requests, $timeout = 0, $method = '')
    {
        if ($method == '') {
            $method = $this->method;
        }

        if ($timeout == 0) {
            $timeout = $this->timeout;
        }

        $opts = $this->getOptions();
        $opts['timeout'] = $timeout;
        // this is required to avoid $this->createCurlHandle reusing the same handle
        $opts['keepalive'] = false;

        /// @todo validate that $method can be handled by the current curl install

        $handles = array();
        $curl = curl_multi_init();

        foreach($requests as $k => $req) {
            $req->setDebug($this->debug);
            $handle = $this->createCurlHandle($req, $method, $this->server, $this->port, $this->path, $opts);
            if (($error = curl_multi_add_handle($curl, $handle)) !== 0) {
                throw new \Exception("Curl multi error: $error");
            }
            $handles[$k] = $handle;
        }

        // loop code taken from php manual
        $running = 0;
        do {
            $status = curl_multi_exec($curl, $running);
            if ($status !== CURLM_OK) {
                throw new \Exception("Curl multi error");
            }
            while (($info = curl_multi_info_read($curl)) !== false) {
                if ($info['msg'] === CURLMSG_DONE) {
                    $handle = $info['handle'];
                    curl_multi_remove_handle($curl, $handle);
                    if ($info['result'] !== CURLE_OK) {
                        /// @todo should we handle this, or is it enough to call curl_error in the loop below?
                    }
                }
            }
            if ($running > 0) {
                if (curl_multi_select($curl) === -1) {
                    throw new \Exception("Curl multi error");
                }
            }
        } while ($running > 0);

        curl_multi_close($curl);

        $responses = array();
        $errors = array();
        foreach($handles as $k => $h) {
            $responses[$k] = curl_multi_getcontent($h);

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

            if (!$responses[$k]) {
                $errors[$k] = curl_error($h);
            }
        }

        foreach($responses as $k => $resp) {
            if (!$resp) {
                $responses[$k] = new Response(0, PhpXmlRpc::$xmlrpcerr['curl_fail'], PhpXmlRpc::$xmlrpcstr['curl_fail'] . ': ' . $errors[$k]);
            } else {
                $responses[$k] = $requests[$k]->parseResponse($resp, true, $this->return_type);
            }
        }

        return $responses;
    }
}

// a minimal benchmark - use 4 strategies to execute 25 similar calls: sequentially, sequentially w. http keep-alive,
// using parallel http requests, and using a single system.multiCall request

$num_tests = 25;

$encoder = new Encoder();
$reqs = array();
for ($i = 0; $i < $num_tests; $i++) {
    $data = array($i, 1.0, 'hello world', true, '20051021T23:43:00', -1, 11.0, '~!@#$%^&*()_+|', false, '20051021T23:43:00');
    $value = $encoder->encode($data, array('auto_dates'));
    $req = new Request('interopEchoTests.echoValue', array($value));
    $reqs[] = $req;
}

$client = new ParallelClient(XMLRPCSERVER);

// avoid storing http info in the responses, to make the checksums comparable
$client->setDebug(-1);

echo "Making $num_tests calls to method interopEchoTests.echoValue on server " . XMLRPCSERVER . " ...\n";
flush();

$client->setOption(Client::OPT_NO_MULTICALL,  true);
$t = microtime(true);
$resp = $client->send($reqs);
$t = microtime(true) - $t;
echo "Sequential send: " . sprintf('%.3f', $t) . " secs.\n";
echo "Response checksum: " . md5(var_export($resp, true)) . "\n";
flush();

if (strpos(XMLRPCSERVER, 'http://') === 0) {
    $client->setOption(Client::OPT_USE_CURL,  Client::USE_CURL_ALWAYS);
    $t = microtime(true);
    $resp = $client->send($reqs);
    $t = microtime(true) - $t;
    echo "Sequential send, curl (w. keepalive): " . sprintf('%.3f', $t) . " secs.\n";
    echo "Response checksum: " . md5(var_export($resp, true)) . "\n";
    flush();
}

$t = microtime(true);
$resp = $client->sendParallel($reqs);
$t = microtime(true) - $t;
echo "Parallel send: " . sprintf('%.3f', $t) . " secs.\n";
echo "Response checksum: " . md5(var_export($resp, true)) . "\n";
flush();

$client->setOption(Client::OPT_NO_MULTICALL, false);
// make sure we don't reuse the keepalive handle
$client->setOption(Client::OPT_USE_CURL,  Client::USE_CURL_NEVER);
$t = microtime(true);
$resp = $client->send($reqs);
$t = microtime(true) - $t;
echo "Multicall send: " . sprintf('%.3f', $t) . " secs.\n";
echo "Response checksum: " . md5(var_export($resp, true)) . "\n";
flush();
