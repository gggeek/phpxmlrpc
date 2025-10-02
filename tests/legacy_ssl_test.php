<?php

/**
 * A test file designed to test the legacy API class-loading mechanism, ie. not using phpunit/composer's autoload_
 */

echo "Legacy SSL Test\n\n";

include_once __DIR__ . '/../lib/xmlrpc.inc';

include_once __DIR__ . '/parse_args.php';

$args = argParser::getArgs();
$baseurl = 'https://' . $args['HTTPSSERVER'] . str_replace('/server.php', '/legacy.php', $args['HTTPSURI']);
//$baseurl = 'http://' . $args['HTTPSERVER'] . str_replace('/server.php', '/legacy.php', $args['HTTPURI']);

$randId = uniqid();
file_put_contents(sys_get_temp_dir() . '/phpunit_rand_id.txt', $randId);

$client = new xmlrpc_client($baseurl);
$client->setCookie('PHPUNIT_RANDOM_TEST_ID', $randId);

$client->method = 'https';
$client->setSSLVerifyPeer(0);
$client->setSSLVerifyHost(0);
//$client->setUseCurl(\PhpXmlRpc\Client::USE_CURL_NEVER);
// NB: 6 is the only ssl version working ok with php 5.6 and our apache2 config!
$client->setSSLVersion(3); // 3 = sslv3
// Force curl _not_ to use http2 (it defaults to doing that)
$client->setOption(\PhpXmlRpc\Client::OPT_EXTRA_CURL_OPTS, array(
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Force curl _not_ to use http2 (it defaults to doing that)
    CURLOPT_SSL_ENABLE_ALPN => false // Tried to force using the desired old ssl version... this did not change anything
));
// NB: capture_session_meta might have been dropped in php 8.0 or earlier
$client->setOption(\PhpXmlRpc\Client::OPT_EXTRA_SOCKET_OPTS, array('ssl' => array('capture_session_meta' => TRUE)));
// improve debuggability
$client->setAcceptedCompression(false);

/// @todo fix calling via proxy - then uncomment `testHttpsProxySocket` in test 09
//var_dump($args);
//$client->setProxy($args['PROXYSERVER'], $args['PROXYPORT']);

$client->setDebug(2);

$req = new xmlrpcmsg('system.listMethods', array());
$resp = $client->send($req);

//var_dump($resp);

if ($resp->faultCode() !== 0) {
    unlink(sys_get_temp_dir() . '/phpunit_rand_id.txt');
    throw new \Exception("system.listMethods returned fault " . $resp->faultCode());
}
echo ". 1/1 (100%)\n\n";

echo "OK (1 test, 1 assertion)\n";

unlink(sys_get_temp_dir() . '/phpunit_rand_id.txt');
