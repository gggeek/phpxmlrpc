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
