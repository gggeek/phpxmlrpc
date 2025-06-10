<?php
/**
 * Demo server for phpxmlrpc library - legacy API (v3).
 *
 * It mimics server.php, but does not rely on other autoload mechanisms than the loading of xmlrpc.inc and xmlrpcs.inc
 */

// NB: this server does not answer to CORS preflight requests, preventing browsers which are visiting a site on a
// different domain to send xml-rpc requests (generated via javascript) to this server.
// That is generally a good idea security-wise, but you might want to allow it in case of specific needs.
// Look at the code in demo/server.php for an example of how to enable that.

require_once __DIR__ . "/../../lib/xmlrpc.inc";
require_once __DIR__ . "/../../lib/xmlrpcs.inc";

$signatures1 = include(__DIR__.'/methodProviders/functions.php');
$signatures2 = include(__DIR__.'/methodProviders/interop.php');
$signatures3 = include(__DIR__.'/methodProviders/validator1.php');
$signatures = array_merge($signatures1, $signatures2, $signatures3);

$s = new xmlrpc_server($signatures, false);
$s->setDebug(3);
$s->service();
