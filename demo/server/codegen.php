<?php
require_once __DIR__ . "/_prepend.php";

require_once __DIR__.'/methodProviders/CommentManager.php';

use PhpXmlRpc\Server;
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

// the generated code does not have an autoloader included - we need to add in one
$autoloader = __DIR__ . "/_prepend.php";

$targetClassFile = '/tmp/MyServerClass.php';
$targetDispatchMapFile = '/tmp/myServerDM.php';

// generate a file with a class definition

file_put_contents($targetClassFile,
    "<?php\n\n" .
    "require_once '$autoloader';\n\n" .
    "class MyServerClass {\n\n"
) || die('uh oh');

// we mangle a bit the code we get from wrapPhpClass to generate a php class instead of a bunch of functions

foreach($code as $methodName => $methodDef) {
    file_put_contents($targetClassFile, 'public static ' . $methodDef['source'] . "\n\n", FILE_APPEND) || die('uh oh');
    $code[$methodName]['function'] = 'MyServerClass::' . $methodDef['function'];
    unset($code[$methodName]['source']);
}
file_put_contents($targetClassFile, "}\n", FILE_APPEND) || die('uh oh');

// and a separate file with the dispatch map

file_put_contents($targetDispatchMapFile,
    "<?php\n\n" .
    "require_once '$autoloader';\n\n" .
    "return " . var_export($code, true) . ";\n"
) || die('uh oh');

// test that everything worked by running it in realtime
// *** NB do not do this in prod! The whole concept of code-generation is to do it offline using console scripts/ci/cd ***

include_once $targetClassFile;

// NB: since we are running the generated code within the same script, the existing CommentManager instance will be
// available for usage by the methods of MyServerClass, as we keep a reference to them within the variable Wrapper::$objHolder
// but if you are generating a php file for later use, it is up to you to initialize that variables with a
// CommentManager instance:
//     $cm = new CommentManager();
//     Wrapper::$objHolder['xmlrpc_CommentManager_addComment'] = $cm;
//     Wrapper::$objHolder['xmlrpc_CommentManager_getComments'] = $cm;

$dm = include_once $targetDispatchMapFile;
$s = new Server($dm, false);
$s->setDebug(2);
$s->exception_handling = 1;
$s->service();
