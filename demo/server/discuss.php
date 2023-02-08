<?php
/**
 * A basic comment server. Given an ID it will store a list of names and comment texts against it.
 *
 * The source code demonstrates:
 * - registration of php class methods as xml-rpc method handlers
 * - usage as method handlers of php code which is completely unaware of xml-rpc, via the Server's properties
 *   `$functions_parameters_type` and `$exception_handling`
 */

require_once __DIR__ . "/_prepend.php";

require_once __DIR__.'/methodProviders/CommentManager.php';

use PhpXmlRpc\Server;
use PhpXmlRpc\Value;

$manager = new CommentManager();

$addComment_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString));

$addComment_doc = 'Adds a comment to an item. The first parameter is the item ID, the second the name of the commenter, ' .
    'and the third is the comment itself. Returns the number of comments against that ID.';

$getComments_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcString));

$getComments_doc = 'Returns an array of comments for a given ID, which is the sole argument. Each array item is a struct ' .
    'containing name and comment text.';

$srv = new Server();

$srv->setDispatchMap(array(
    "discuss.addComment" => array(
        "function" => array($manager, "addComment"),
        "signature" => $addComment_sig,
        "docstring" => $addComment_doc,
    ),
    "discuss.getComments" => array(
        "function" => array($manager, "getComments"),
        "signature" => $getComments_sig,
        "docstring" => $getComments_doc,
    ),
));

// let the xml-rpc server know that the method-handler functions expect plain php values
$srv->setOption(Server::OPT_FUNCTIONS_PARAMETERS_TYPE, 'phpvals');

// let code exceptions float all the way to the remote caller as xml-rpc faults - it helps debugging.
// At the same time, it opens a wide security hole, and should never be enabled on public or production servers...
//$srv->->setOption(Server::OPT_EXCEPTION_HANDLING, 1);

// NB: take care not to output anything else after this call, as it will mess up the responses and it will be hard to
// debug. In case you have to do so, at least re-emit a correct Content-Length http header (requires output buffering)
$srv->service();
