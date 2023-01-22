<?php
require_once __DIR__ . "/_prepend.php";

require_once __DIR__.'/methodProviders/CommentManager.php';

use PhpXmlRpc\Wrapper;

$cm = new CommentManager();
$w = new Wrapper();

$code = $w->wrapPhpClass(
    $cm,
    array(
        'method_type' => 'nonstatic',
        'return_source' => true,
        'encode_nulls' => true,
    )
);

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

// we mangle a bit the code we get from wrapPhpClass to generate a php class instead of a bunch of functions

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
    '$s = new \PhpXmlRpc\Server($dm, false);' . "\n" .
    '// NB: do not leave these 2 debug lines enabled on publicly accessible servers!' . "\n" .
    '$s->setDebug(2);' . "\n" .
    '$s->exception_handling = 1;' . "\n" .
    '$s->service();' . "\n"
) || die('uh oh');

// test that everything worked by running it in realtime (note that this will return an xml-rpc error message if run
// from the command line, as the server will find no xml-rpc payload to operate on)

// *** NB do not do this in prod! The whole concept of code-generation is to do it offline using console scripts/ci/cd ***

include $targetControllerFile;
