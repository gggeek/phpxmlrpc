<?php
/**
 * Hackish code used to make the demos both viewable as source, runnable, and viewable as html
 */

if (isset($_GET['showSource']) && $_GET['showSource']) {
    $file = debug_backtrace()[0]['file'];
    highlight_file($file);
    die();
}

// Make errors visible
ini_set('display_errors', true);
error_reporting(E_ALL);

// Use the custom class autoloader. These two lines not needed when the phpxmlrpc library is installed using Composer
include_once __DIR__ . '/../../src/Autoloader.php';
PhpXmlRpc\Autoloader::register();

// Let unit tests run against localhost, 'plain' demos against a known public server
if (isset($_SERVER['HTTPSERVER'])) {
    define('XMLRPCSERVER', 'http://'.$_SERVER['HTTPSERVER'].'/demo/server/server.php');
} else {
    define('XMLRPCSERVER', 'http://phpxmlrpc.sourceforge.net/server.php');
}
