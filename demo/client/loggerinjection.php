<?php
require_once __DIR__ . "/_prepend.php";

/**
 * Demoing how to inject a custom logger for use by the library
 */

use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;
use PhpXmlRpc\Helper\Charset;
use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;
use PhpXmlRpc\Request;

// Definition of a custom logger implementing the same API as the default one

class MyLogger
{
    protected $debugBuffer = '';
    protected $errorBuffer = '';

    // logger API
    public function debug($message, $context = array())
    {
        $this->debugBuffer .= $message . "\n";
    }

    // logger API
    public function error($message, $context = array())
    {
        $this->errorBuffer .= $message . "\n";
    }

    public function getDebug()
    {
        return $this->debugBuffer;
    }

    public function getError()
    {
        return $this->errorBuffer;
    }
}

// create the custom logger instance

$logger = new MyLogger();

// inject it into all the classes (possibly) involved

Charset::setLogger($logger);
Client::setLogger($logger);
Encoder::setLogger($logger);
Http::setLogger($logger);
Request::setLogger($logger);
XMLParser::setLogger($logger);

// then send a request

$input = array(
    array('name' => 'Dave', 'age' => 24),
    array('name' => 'Edd',  'age' => 45),
    array('name' => 'Joe',  'age' => 37),
    array('name' => 'Fred', 'age' => 27),
);

$encoder = new Encoder();
$client = new Client(XMLRPCSERVER);

// set maximum debug level, to have all the communication details logged
$client->setDebug(2);

// avid compressed responses, as they mess up the output if echoed on the command-line
$client->setAcceptedCompression('');

// send request
output("Sending the request. No output debug should appear below...<br>");
$request = new Request('examples.sortByAge', array($encoder->encode($input)));
$response = $client->send($request);
output("Response received.<br>");

output("The client error info is:<pre>\n" . $logger->getError() . "\n</pre>");
output("The client debug info is:<pre>\n" . $logger->getDebug() . "\n</pre>");
