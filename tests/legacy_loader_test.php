<?php

/**
 * A test file designed to test the legacy API class-loading mechanism, ie. not using phpunit/composer's autoload_
 */

include_once __DIR__ . '/../lib/xmlrpc.inc';
include_once __DIR__ . '/../lib/xmlrpcs.inc';

include_once __DIR__ . '/parse_args.php';

$args = argParser::getArgs();
$baseurl = 'http://' . $args['HTTPSERVER'] . str_replace('/server.php', '/legacy.php', $args['HTTPURI']);

$randId = uniqid();
file_put_contents(sys_get_temp_dir() . '/phpunit_rand_id.txt', $randId);

$client = new xmlrpc_client($baseurl);
$client->setCookie('PHPUNIT_RANDOM_TEST_ID', $randId);

$req = new xmlrpcmsg('system.listMethods', array());
$resp = $client->send($req);
if ($resp->faultCode() !== 0) {
    throw new \Exception("system.listMethods returned fault " . $resp->faultCode());
}

unlink(sys_get_temp_dir() . '/phpunit_rand_id.txt');
