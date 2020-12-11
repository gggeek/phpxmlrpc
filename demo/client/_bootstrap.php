<?php
/**
 * Hackish code used to make the demos both viewable as source, runnable, and viewable as html
 */

// Make errors visible
ini_set('display_errors', true);
error_reporting(E_ALL);

if (isset($_GET['showSource']) && $_GET['showSource']) {
    $file = debug_backtrace()[0]['file'];
    highlight_file($file);
    die();
}

// Use the custom class autoloader. These two lines not needed when the phpxmlrpc library is installed using Composer
include_once __DIR__ . '/../../src/Autoloader.php';
PhpXmlRpc\Autoloader::register();

// Let unit tests run against localhost, 'plain' demos against a known public server
if (isset($_SERVER['LOCALSERVER'])) {
    define('XMLRPCSERVER', 'http://'.$_SERVER['LOCALSERVER'].'/demo/server/server.php');
} else {
    define('XMLRPCSERVER', 'http://phpxmlrpc.sourceforge.net/server.php');
}
