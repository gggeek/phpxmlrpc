<?php

/**
 * A basic comment server. Given an ID it will store a list of names and comment texts against it.
 * It uses a SQLite3 database for storage.
 * NB: this class is totally unaware of the existence of xml-rpc or phpxmlrpc.
 */
class CommentManager
{
    protected $dbFile;

    /**
     * @param string $dbFile
     */
    public function __construct($dbFile)
    {
        $this->dbFile = $dbFile;
    }

    protected function createTable($db)
    {
        return $db->exec('CREATE TABLE IF NOT EXISTS comments (msg_id TEXT NOT NULL, name TEXT NOT NULL, comment TEXT NOT NULL)');
    }

    /**
     * NB: we know for a fact that this will be called with 3 string arguments because of the signature used to register
     * this method in the dispatch map. But nothing prevents the client from sending empty strings, nor sql-injection attempts!
     *
     * @param string $msgID
     * @param string $name username
     * @param string $comment comment text
     * @return int the number of comments for the given message
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
     * NB: we know for a fact that this will be called with 1 string arguments because of the signature used to register
     * this method in the dispatch map. But nothing prevents the client from sending empty strings, nor sql-injection attempts!
     *
     * @param string $msgID
     * @return array[] each element is a struct, with elements 'name', 'comment'
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
