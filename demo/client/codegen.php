<?php
require_once __DIR__ . "/_prepend.php";

/**
 * Demoing the code-generation capabilities of the library: create a client class which exposes a bunch of methods
 * advertised by a remote xml-rpc server.
 */

/// @todo add an html header with links to view-source

use PhpXmlRpc\Client;
use PhpXmlRpc\Wrapper;

$w = new Wrapper();
$code = $w->wrapXmlrpcServer(
    new Client(XMLRPCSERVER),
    array(
        'return_source' => true,
        'new_class_name' => 'MyClient',
        'method_filter' => '/^examples\./',
        'simple_client_copy' => true,
        // this is used to encode php NULL values into xml-rpc <NIL/> elements. If the partner does not support that, disable it
        'encode_nulls' => true,
        'throw_on_fault' => true,
    )
);

// the generated code does not have an autoloader included - we need to add in one
$autoloader = __DIR__ . "/_prepend.php";

$targetFile = '/tmp/MyClient.php';
$generated = file_put_contents($targetFile,
    "<?php\n\n" .
    "require_once '$autoloader';\n\n" .
    $code['code']
);

if (!$generated) {
    die("uh oh");
}

// *** NB take care when doing  this in prod! ***
// There's a race condition here - what if someone replaces the file we just created, before we include it?
// You should at the very least make sure that filesystem permissions are tight, so that only the authorized user
// accounts can write into the folder where $targetFile is created.
// You might even want to disallow php code executed from the webserver from generating new php code for direct inclusion,
// and only allow a cli process to do that

include($targetFile);

$client = new MyClient();
$sorted = $client->examples_sortByAge(array(
    array('name' => 'Dave', 'age' => 24),
    array('name' => 'Edd',  'age' => 45),
    array('name' => 'Joe',  'age' => 37),
    array('name' => 'Fred', 'age' => 27),
));

echo "Sorted array:\n";
print_r($sorted);
