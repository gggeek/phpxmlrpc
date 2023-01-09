<?php
/**
 * A basic comment server. Given an ID it will store a list of names and comment texts against it.
 * It uses a SQLite3 database for storage.
 *
 * The source code demonstrates:
 * - registration of php class methods as xml-rpc method handlers
 * - usage as method handlers of php code which is completely unaware of xml-rpc, via the Server's properties
 *   `$functions_parameters_type` and `$exception_handling`
 */

require_once __DIR__ . "/_prepend.php";

use PhpXmlRpc\Response;
use PhpXmlRpc\Server;
use PhpXmlRpc\Value;

class CommentManager
{
    protected $dbFile = "/tmp/comments.db";

    protected function createTable($db)
    {
        return $db->exec('CREATE TABLE IF NOT EXISTS comments (msg_id TEXT NOT NULL, name TEXT NOT NULL, comment TEXT NOT NULL)');
    }

    /**
     * NB: we know for a fact that this will be called with 3 string arguments because of the signature used to register
     * this method in the dispatch map. But nothing prevents the client from sending empty strings, nor sql-injection attempts!
     *
     * @param string $msgID
     * @param string $name
     * @param string $comment
     * @return int
     * @throws \Exception
     */
    public function addComment($msgID, $name, $comment)
    {
        $db = new SQLite3($this->dbFile);
        $this->createTable($db);

        $statement = $db->prepare("INSERT INTO comments VALUES(:msg_id, :name, :comment)");
        $statement->bindValue(':msg_id', $msgID);
        $statement->bindValue(':name', $name);
        $statement->bindValue(':comment', $comment);
        $statement->execute();

        /// @todo this insert-then-count is not really atomic - we should use a transaction

        $statement = $db->prepare("SELECT count(*) AS tot FROM comments WHERE msg_id = :id");
        $statement->bindValue(':id', $msgID);
        $results = $statement->execute();
        $row = $results->fetchArray(SQLITE3_ASSOC);
        $results->finalize();
        $count = $row['tot'];

        $db->close();

        return $count;
    }

    /**
     * NB: we know for a fact that this will be called with 1 strin arguments because of the signature used to register
     * this method in the dispatch map. But nothing prevents the client from sending empty strings, nor sql-injection attempts!
     *
     * @param string $msgID
     * @return Response|array[]
     * @throws \Exception
     */
    public function getComments($msgID)
    {
        $db = new SQLite3($this->dbFile);
        $this->createTable($db);

        $ra = array();
        $statement = $db->prepare("SELECT name, comment FROM comments WHERE msg_id = :id ORDER BY rowid");
        $statement->bindValue(':id', $msgID);
        $results = $statement->execute();
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
            $ra[] = $row;
        }
        $results->finalize();

        $db->close();

        return $ra;
    }
}

$manager = new CommentManager();

$addComment_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString));

$addComment_doc = 'Adds a comment to an item. The first parameter is the item ID, the second the name of the commenter, ' .
    'and the third is the comment itself. Returns the number of comments against that ID.';

$getComments_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcString));

$getComments_doc = 'Returns an array of comments for a given ID, which is the sole argument. Each array item is a struct ' .
    'containing name and comment text.';

// NB: take care not to output anything else after this call, as it will mess up the responses and it will be hard to
// debug. In case you have to do so, at least re-emit a correct Content-Length http header (requires output buffering)
$srv = new Server(array(
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
), false);

// let the xml=rpc server know that the method-handler functions expect plain php values
$srv->functions_parameters_type = 'phpvals';

// let code exceptions float all the way to the remote caller as xml-rpc faults - it helps debugging
$srv->exception_handling = 1;

$srv->service();
