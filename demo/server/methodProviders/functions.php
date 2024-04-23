<?php
/**
 * Defines functions and signatures which can be registered as methods exposed by an XML-RPC Server
 *
 * To use this, use something akin to:
 * $signatures = include('functions.php');
 *
 * Demoes a simple possible way to implement webservices without cluttering the global scope: create xml-rpc-aware static
 * methods in a class, and use them for the Server's dispatch map without the need to instantiate an object of that class.
 *
 * Alternative implementation strategies are possible as well:
 * 1. same as above, but use non-static class methods and an object instance
 * 2. define functions in the global scope to be used as xml-rpc method handlers: see interop.php
 * 3. define xml-rpc method handlers as anonymous functions directly within the dispatch map: see validator1.php
 * 4. use php methods or functions which are not aware of xml-rpc and let the Server do all the necessary type conversion:
 *    see discuss.php
 * 5. use the PhpXmlRpc\Wrapper class to achieve the same as in point 4, with no need to manually write the dispatch map
 *    configuration (but taking instead a performance hit)
 * 6. use the PhpXmlRpc\Wrapper class to generate php code in offline mode, achieving the same as in point 5 with no
 *    performance hit at runtime: see codegen.php
 */

use PhpXmlRpc\Encoder;
use PhpXmlRpc\PhpXmlRpc;
use PhpXmlRpc\Response;
use PhpXmlRpc\Server;
use PhpXmlRpc\Value;

class exampleMethods
{
    public static $stateNames = array(
        "Alabama", "Alaska", "Arizona", "Arkansas", "California",
        "Colorado", "Columbia", "Connecticut", "Delaware", "Florida",
        "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas",
        "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan",
        "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada",
        "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina",
        "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island",
        "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont",
        "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming",
    );

    public static $findstate_sig = array(array('string', 'int'));
    public static $findstate_doc = 'When passed an integer between 1 and 51 returns the name of a US state, where the integer is the index of that state name in an alphabetic order.';
    public static function findState($req)
    {
        $err = '';

        // get the first param
        // param must be there and of the correct type: server object does the validation for us
        $sno = $req->getParam(0);

        // extract the value of the state number
        $snv = $sno->scalarVal();

        // look it up in our array (zero-based)
        if (isset(self::$stateNames[$snv - 1])) {
            $stateName = self::$stateNames[$snv - 1];
        } else {
            // not there, so complain
            $err = "I don't have a state for the index '" . $snv . "'";
        }

        if ($err != '') {
            // if we generated an error, create an error return response
            return new Response(0, PhpXmlRpc::$xmlrpcerruser, $err);
        } else {
            // otherwise, we create the right response with the state name
            return new Response(new Value($stateName));
        }
    }

    public static $agesorter_sig = array(array('array', 'array'));
    public static $agesorter_doc = 'Send this method an array of [string, int] structs, eg:
<pre>
 Dave   35
 Edd    45
 Fred   23
 Barney 37
</pre>
And the array will be returned with the entries sorted by their numbers.';
    public static function ageSorter($req)
    {
        Server::xmlrpc_debugmsg("Entering 'agesorter'");

        // error string for [if|when] things go wrong
        $err = '';

        // get the parameter, turn it into an easy-to-manipulate php array
        $enc = new Encoder();
        $v = $enc->decode($req->getParam(0));

        $max = count($v);
        Server::xmlrpc_debugmsg("Found $max array elements");

        // extract name and age from struct. The values nested inside it were not type-checked, so we do it
        $agar = array();
        foreach ($v as $i => $rec) {
            if (!is_array($rec)) {
                $err = "Found non-struct in array at element $i";
                break;
            }
            if (!isset($rec['name']) || !isset($rec['age'])) {
                Server::xmlrpc_debugmsg("Invalid array element $i: miss name or age");
                continue;
            }
            $agar[$rec["name"]] = $rec["age"];
        }

        if ($err != '') {
            Server::xmlrpc_debugmsg("Aborting 'agesorter'");
            return new Response(0, PhpXmlRpc::$xmlrpcerruser, $err);
        }

        asort($agar);

        // create the output value
        $o = array();
        foreach ($agar as $name => $age) {
            $o[] = array("name" => $name, "age" => $age);
        }

        Server::xmlrpc_debugmsg("Leaving 'agesorter'");

        return new Response($enc->encode($o));
    }

    public static $addtwo_sig = array(array('int', 'int', 'int'));
    public static $addtwo_doc = 'Add two integers together and return the result';
    public static function addTwo($req)
    {
        $s = $req->getParam(0);
        $t = $req->getParam(1);

        return new Response(new Value($s->scalarVal() + $t->scalarVal(), Value::$xmlrpcInt));
    }

    public static $addtwodouble_sig = array(array('double', 'double', 'double'));
    public static $addtwodouble_doc = 'Add two doubles together and return the result';
    public static function addTwoDouble($req)
    {
        $s = $req->getParam(0);
        $t = $req->getParam(1);

        return new Response(new Value($s->scalarVal() + $t->scalarVal(), Value::$xmlrpcDouble));
    }

    public static $stringecho_sig = array(array('string', 'string'));
    public static $stringecho_doc = 'Accepts a string parameter, returns the string.';
    public static function stringEcho($req)
    {
        // just sends back a string
        return new Response(new Value($req->getParam(0)->scalarVal()));
    }

    public static $echoback_sig = array(array('string', 'string'));
    public static $echoback_doc = 'Accepts a string parameter, returns the entire incoming payload';
    public static function echoBack($req)
    {
        // just sends back a string with what I got sent to me, that's all

        /// @todo file_get_contents does not take into account either receiving compressed requests, or requests with
        ///       data which is not in UTF-8. Otoh using req->serialize means that what we are sending back is not
        ///       byte-for-byte identical to what we received, and that <, >, ', " and & will be double-encoded.
        ///       In fact, we miss some API (or extra data) in the Request...
        //$payload = file_get_contents('php://input');
        $payload = $req->serialize(PhpXmlRpc::$xmlrpc_internalencoding);
        $s = "I got the following message:\n" . $payload;

        return new Response(new Value($s));
    }

    public static $echosixtyfour_sig = array(array('string', 'base64'));
    public static $echosixtyfour_doc = 'Accepts a base64 parameter and returns it decoded as a string';
    public static function echoSixtyFour($req)
    {
        // Accepts an encoded value, but sends it back as a normal string.
        // This is to test that base64 encoding is working as expected
        $incoming = $req->getParam(0);

        return new Response(new Value($incoming->scalarVal(), Value::$xmlrpcString));
    }

    public static $bitflipper_sig = array(array('array', 'array'));
    public static $bitflipper_doc = 'Accepts an array of booleans, and returns them inverted';
    public static function bitFlipper($req)
    {
        $v = $req->getParam(0);
        $rv = new Value(array(), Value::$xmlrpcArray);

        foreach ($v as $b) {
            if ($b->scalarVal()) {
                $rv[] = new Value(false, Value::$xmlrpcBoolean);
            } else {
                $rv[] = new Value(true, Value::$xmlrpcBoolean);
            }
        }

        return new Response($rv);
    }

    public static $mailsend_sig = array(array(
        'boolean', 'string', 'string',
        'string', 'string', 'string',
        'string', 'string',
    ));
    public static $mailsend_doc = 'mail.send(recipient, subject, text, sender, cc, bcc, mimetype)<br/>
recipient, cc, and bcc are strings, comma-separated lists of email addresses, as described above.<br/>
subject is a string, the subject of the message.<br/>
sender is a string, it\'s the email address of the person sending the message. This string can not be
a comma-separated list, it must contain a single email address only.<br/>
text is a string, it contains the body of the message.<br/>
mimetype, a string, is a standard MIME type, for example, text/plain.';
    /**
     * WARNING: this functionality depends on the sendmail -t option, it may not work with Windows machines properly;
     * particularly the Bcc option.
     * Sneak on your friends at your own risk!
     */
    public static function mailSend($req)
    {
        $err = "";

        $mTo = $req->getParam(0);
        $mSub = $req->getParam(1);
        $mBody = $req->getParam(2);
        $mFrom = $req->getParam(3);
        $mCc = $req->getParam(4);
        $mBcc = $req->getParam(5);
        $mMime = $req->getParam(6);

        if ($mTo->scalarVal() == "") {
            $err = "Error, no 'To' field specified";
        }

        if ($mFrom->scalarVal() == "") {
            $err = "Error, no 'From' field specified";
        }

        /// @todo in real life, we should check for presence of return characters to avoid header injection!

        $msgHdr = "From: " . $mFrom->scalarVal() . "\n";
        $msgHdr .= "To: " . $mTo->scalarVal() . "\n";

        if ($mCc->scalarVal() != "") {
            $msgHdr .= "Cc: " . $mCc->scalarVal() . "\n";
        }
        if ($mBcc->scalarVal() != "") {
            $msgHdr .= "Bcc: " . $mBcc->scalarVal() . "\n";
        }
        if ($mMime->scalarVal() != "") {
            $msgHdr .= "Content-type: " . $mMime->scalarVal() . "\n";
        }
        $msgHdr .= "X-Mailer: XML-RPC for PHP mailer 1.0";

        if ($err == "") {
            if (!mail("", $mSub->scalarVal(), $mBody->scalarVal(), $msgHdr)) {
                $err = "Error, could not send the mail.";
            }
        }

        if ($err) {
            return new Response(0, PhpXmlRpc::$xmlrpcerruser, $err);
        } else {
            return new Response(new Value(true, Value::$xmlrpcBoolean));
        }
    }

}

return array(
    "examples.getStateName" => array(
        "function" => array("exampleMethods", "findState"),
        "signature" => exampleMethods::$findstate_sig,
        "docstring" => exampleMethods::$findstate_doc,
    ),
    "examples.sortByAge" => array(
        "function" => array("exampleMethods", "ageSorter"),
        "signature" => exampleMethods::$agesorter_sig,
        "docstring" => exampleMethods::$agesorter_doc,
    ),
    "examples.addtwo" => array(
        "function" => array("exampleMethods", "addTwo"),
        "signature" => exampleMethods::$addtwo_sig,
        "docstring" => exampleMethods::$addtwo_doc,
    ),
    "examples.addtwodouble" => array(
        "function" => array("exampleMethods", "addTwoDouble"),
        "signature" => exampleMethods::$addtwodouble_sig,
        "docstring" => exampleMethods::$addtwodouble_doc,
    ),
    "examples.stringecho" => array(
        "function" => array("exampleMethods", "stringEcho"),
        "signature" => exampleMethods::$stringecho_sig,
        "docstring" => exampleMethods::$stringecho_doc,
    ),
    "examples.echo" => array(
        "function" => array("exampleMethods", "echoBack"),
        "signature" => exampleMethods::$echoback_sig,
        "docstring" => exampleMethods::$echoback_doc,
    ),
    "examples.decode64" => array(
        "function" => array("exampleMethods", "echoSixtyFour"),
        "signature" => exampleMethods::$echosixtyfour_sig,
        "docstring" => exampleMethods::$echosixtyfour_doc,
    ),
    "examples.invertBooleans" => array(
        "function" => array("exampleMethods", "bitFlipper"),
        "signature" => exampleMethods::$bitflipper_sig,
        "docstring" => exampleMethods::$bitflipper_doc,
    ),

    // same as examples_getStateName, but with no dot - so that it is easier to map this into a method call
    // by clients which map f.e. xmlrpc method names into php object method names
    "examples_getStateName" => array(
        "function" => array("exampleMethods", "findState"),
        "signature" => exampleMethods::$findstate_sig,
        "docstring" => exampleMethods::$findstate_doc,
    ),

    // left in as an example, but disabled by default, to avoid this being abused if left on an open server
    /*"mail.send" => array(
        "function" => array("exampleMethods", "mailSend"),
        "signature" => exampleMethods::$mailsend_sig,
        "docstring" => exampleMethods::$mailsend_doc,
    ),*/
);
