<?php

// Hackish code used to make the demos both viewable as source and runnable
if (isset($_GET['showSource']) && $_GET['showSource']) {
    $file = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file'];
    highlight_file($file);
    die();
}

// Use the custom class autoloader. These two lines are not needed when the phpxmlrpc library is installed using Composer
include_once __DIR__ . '/../../src/Autoloader.php';
PhpXmlRpc\Autoloader::register();

// Let unit tests run against localhost, 'plain' demos against a known public server
if (isset($_SERVER['HTTPSERVER'])) {
    define('XMLRPCSERVER', 'http://'.$_SERVER['HTTPSERVER'].'/demo/server/server.php');
} else {
    define('XMLRPCSERVER', 'http://gggeek.altervista.org/sw/xmlrpc/demo/server/server.php');
}

// A helper for cli vs web output:
function output($text)
{
    /// @todo we should only strip html tags, and let through all xml tags
    echo PHP_SAPI == 'cli' ? strip_tags(str_replace(array('<br/>','<br>'), "\n", $text)) : $text;
}
