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
    )
);

$targetClassFile = '/tmp/MyServerClass.php';
$targetServerFile = '/tmp/myServer.php';

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
    file_put_contents($targetClassFile, '  public static ' . str_replace("\n", "  \n  ", $methodDef['source']) . "\n\n", FILE_APPEND) || die('uh oh');
    $code[$methodName]['function'] = 'MyServerClass::' . $methodDef['function'];
    unset($code[$methodName]['source']);
}
file_put_contents($targetClassFile, "}\n", FILE_APPEND) || die('uh oh');

// generate the separate file with the xml-rpc server and dispatch map

file_put_contents($targetServerFile,
    "<?php\n\n" .

    "require_once '$autoloader';\n\n" .

    "require_once '$targetClassFile';\n\n" .

    // NB: since we are running the generated code within the same script, the existing CommentManager instance will be
    // available for usage by the methods of MyServerClass, as we keep a reference to them within the variable Wrapper::$objHolder
    // but if you are generating a php file for later use, it is up to you to initialize that variables with a
    // CommentManager instance:
    //     $cm = new CommentManager();
    //     Wrapper::$objHolder['xmlrpc_CommentManager_addComment'] = $cm;
    //     Wrapper::$objHolder['xmlrpc_CommentManager_getComments'] = $cm;

    '$dm = ' . var_export($code, true) . ";\n" .
    '$s = new \PhpXmlRpc\Server($dm, false);' . "\n" .
    '$s->setDebug(2);' . "\n" .
    '$s->exception_handling = 1;' . "\n" .
    '$s->service();' . "\n"
) || die('uh oh');

// test that everything worked by running it in realtime
// *** NB do not do this in prod! The whole concept of code-generation is to do it offline using console scripts/ci/cd ***

include $targetServerFile;
