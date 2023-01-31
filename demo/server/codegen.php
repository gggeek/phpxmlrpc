<?php
require_once __DIR__ . "/_prepend.php";

/**
 * Demoing the code-generation capabilities of the library: create all that is required to expose as xml-rpc methods
 * a bunch of methods of an instance of a php class which is totally unaware of xml-rpc.
 */

/// @todo add an html header with links to view-source

require_once __DIR__.'/methodProviders/CommentManager.php';

use PhpXmlRpc\Wrapper;

// CommentManager is the "xml-rpc-unaware" class, whose methods we want to make accessible via xml-rpc calls
$cm = new CommentManager();

// analyze the CommentManager instance and generate both code defining stub-methods and a dispatch map for the xml-rpc Server
$w = new Wrapper();
$code = $w->wrapPhpClass(
    $cm,
    array(
        'method_type' => 'nonstatic',
        'return_source' => true,
        // this is used to encode php NULL values into xml-rpc <NIL/> elements. If the partner does not support that, disable it
        'encode_nulls' => true,
    )
);

// save the generated code in 3 files: a new class definition, holding all the stub methods, a file with the dispatch-map,
// and a controller, to be accessed from the internet. This split allows to a) hand-edit the controller code if needed,
// and b) later regenerate the stub-methods-holder and dispatch map without touching the controller.
// NB: good security practices dictate that none of those files should be writeable by the webserver user account
$targetClassFile = '/tmp/MyServerClass.php';
$targetDispatchMapFile = '/tmp/myServerDispatchMap.php';
$targetControllerFile = '/tmp/myServerController.php';

// generate a file with a class definition

// the generated code does not have an autoloader included - we need to add in one
$autoloader = __DIR__ . "/_prepend.php";

file_put_contents($targetClassFile,
    "<?php\n\n" .
    "require_once '$autoloader';\n\n" .
    "class MyServerClass\n{\n\n"
) || die('uh oh');

// we mangle a bit the code we get from wrapPhpClass to turn it into a php class definition instead of a bunch of functions

foreach($code as $methodName => $methodDef) {
    file_put_contents($targetClassFile, '  ' . str_replace(array('function ', "\n"), array('public static function ', "\n  "), $methodDef['source']) . "\n\n", FILE_APPEND) || die('uh oh');
    $code[$methodName]['function'] = 'MyServerClass::' . $methodDef['function'];
    unset($code[$methodName]['source']);
}
file_put_contents($targetClassFile, "}\n", FILE_APPEND) || die('uh oh');

// generate separate files with the xml-rpc server instantiation and its dispatch map

file_put_contents($targetDispatchMapFile, "<?php\n\nreturn " . var_export($code, true) . ";\n");

file_put_contents($targetControllerFile,
    "<?php\n\n" .

    "require_once '$autoloader';\n\n" .

    "require_once '$targetClassFile';\n\n" .

    // NB: since we are running the generated code within the same script, the existing CommentManager instance will be
    // available for usage by the methods of MyServerClass, as we keep a reference to them within the variable Wrapper::$objHolder
    // but if you are generating a php file for later use, it is up to you to initialize that variables with a
    // CommentManager instance:
    //     $cm = new CommentManager();
    //     Wrapper::holdObject('xmlrpc_CommentManager_addComment', $cm);
    //     Wrapper::holdObject('xmlrpc_CommentManager_getComments', $cm);

    "\$dm = require_once '$targetDispatchMapFile';\n" .
    '$s = new \PhpXmlRpc\Server($dm, false);' . "\n\n" .
    '// NB: do not leave these 2 debug lines enabled on publicly accessible servers!' . "\n" .
    '$s->setOption(\PhpXmlRpc\Server::OPT_DEBUG, 2);' . "\n" .
    '$s->setOption(\PhpXmlRpc\Server::OPT_EXCEPTION_HANDLING, 1);' . "\n\n" .
    '$s->service();' . "\n"
) || die('uh oh');

// test that everything worked by running it in realtime (note that this script will return an xml-rpc error message if
// run from the command line, as the server will find no xml-rpc payload to operate on)

// *** NB do not do this in prod! The whole concept of code-generation is to do it offline using console scripts/ci/cd ***

include $targetControllerFile;
