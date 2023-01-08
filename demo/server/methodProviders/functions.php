<?php
/**
 * Defines functions and signatures which can be registered as methods exposed by an XMLRPC Server
 *
 * To use this, use something akin to:
 * $signatures = include('functions.php');
 *
 * Simplest possible way to implement webservices: create xmlrpc-aware php functions in the global namespace
 */

use PhpXmlRpc\Encoder;
use PhpXmlRpc\Response;
use PhpXmlRpc\Server;
use PhpXmlRpc\Value;

// a PHP version of the state-number server
// send me an integer and I'll sell you a state

$GLOBALS['stateNames'] = array(
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

$findstate_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcInt));
$findstate_doc = 'When passed an integer between 1 and 51 returns the name of a US state, where the integer is the ' .
    'index of that state name in an alphabetic order.';
function findState($req)
{
    $err = "";
    // get the first param
    $sno = $req->getParam(0);

    // param must be there and of the correct type: server object does the validation for us

    // extract the value of the state number
    $snv = $sno->scalarval();
    // look it up in our array (zero-based)
    if (isset($GLOBALS['stateNames'][$snv - 1])) {
        $stateName = $GLOBALS['stateNames'][$snv - 1];
    } else {
        // not there, so complain
        $err = "I don't have a state for the index '" . $snv . "'";
    }

    // if we generated an error, create an error return response
    if ($err) {
        return new Response(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser, $err);
    } else {
        // otherwise, we create the right response with the state name
        return new Response(new Value($stateName));
    }
}

// Sorting demo
//
// send me an array of structs thus:
//
// Dave 35
// Edd  45
// Fred 23
// Barney 37
//
// and I'll return it to you in sorted order

function agesorter_compare($a, $b)
{
    /// @todo move away from usage of globals for such a simple case
    global $agesorter_arr;

    // don't even ask me _why_ these come padded with hyphens, I couldn't tell you :p
    $a = str_replace("-", "", $a);
    $b = str_replace("-", "", $b);

    if ($agesorter_arr[$a] == $agesorter_arr[$b]) {
        return 0;
    }

    return ($agesorter_arr[$a] > $agesorter_arr[$b]) ? -1 : 1;
}

$agesorter_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$agesorter_doc = 'Send this method an array of [string, int] structs, eg:
<pre>
 Dave   35
 Edd    45
 Fred   23
 Barney 37
</pre>
And the array will be returned with the entries sorted by their numbers.
';
function ageSorter($req)
{
    global $agesorter_arr;

    Server::xmlrpc_debugmsg("Entering 'agesorter'");
    // get the parameter
    $sno = $req->getParam(0);
    // error string for [if|when] things go wrong
    $err = "";
    $agar = array();

    $max = $sno->count();
    Server::xmlrpc_debugmsg("Found $max array elements");
    foreach ($sno as $i => $rec) {
        if ($rec->kindOf() != "struct") {
            $err = "Found non-struct in array at element $i";
            break;
        }
        // extract name and age from struct
        $n = $rec["name"];
        $a = $rec["age"];
        // $n and $a are Values,
        // so get the scalarval from them
        $agar[$n->scalarval()] = $a->scalarval();
    }

    // create the output value
    $v = new Value(array(), Value::$xmlrpcArray);

    $agesorter_arr = $agar;
    // hack, must make global as uksort() won't
    // allow us to pass any other auxiliary information
    uksort($agesorter_arr, 'agesorter_compare');
    foreach($agesorter_arr as $key => $val) {
        // recreate each struct element
        $v[] = new Value(
            array(
                "name" => new Value($key),
                "age" => new Value($val, "int")
            ),
            Value::$xmlrpcStruct
        );
    }

    if ($err) {
        return new Response(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser, $err);
    } else {
        return new Response($v);
    }
}

$addtwo_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcInt, Value::$xmlrpcInt));
$addtwo_doc = 'Add two integers together and return the result';
function addTwo($req)
{
    $s = $req->getParam(0);
    $t = $req->getParam(1);

    return new Response(new Value($s->scalarval() + $t->scalarval(), Value::$xmlrpcInt));
}

$addtwodouble_sig = array(array(Value::$xmlrpcDouble, Value::$xmlrpcDouble, Value::$xmlrpcDouble));
$addtwodouble_doc = 'Add two doubles together and return the result';
function addTwoDouble($req)
{
    $s = $req->getParam(0);
    $t = $req->getParam(1);

    return new Response(new Value($s->scalarval() + $t->scalarval(), Value::$xmlrpcDouble));
}

$stringecho_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcString));
$stringecho_doc = 'Accepts a string parameter, returns the string.';
function stringEcho($req)
{
    // just sends back a string
    return new Response(new Value($req->getParam(0)->scalarval()));
}

$echoback_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcString));
$echoback_doc = 'Accepts a string parameter, returns the entire incoming payload';
function echoBack($req)
{
    // just sends back a string with what i got sent to me, just escaped, that's all
    $s = "I got the following message:\n" . $req->serialize();

    return new Response(new Value($s));
}

$echosixtyfour_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcBase64));
$echosixtyfour_doc = 'Accepts a base64 parameter and returns it decoded as a string';
function echoSixtyFour($req)
{
    // Accepts an encoded value, but sends it back as a normal string.
    // This is to test that base64 encoding is working as expected
    $incoming = $req->getParam(0);

    return new Response(new Value($incoming->scalarval(), Value::$xmlrpcString));
}

$bitflipper_sig = array(array(Value::$xmlrpcArray, Value::$xmlrpcArray));
$bitflipper_doc = 'Accepts an array of booleans, and returns them inverted';
function bitFlipper($req)
{
    $v = $req->getParam(0);
    $rv = new Value(array(), Value::$xmlrpcArray);

    foreach ($v as $b) {
        if ($b->scalarval()) {
            $rv[] = new Value(false, Value::$xmlrpcBoolean);
        } else {
            $rv[] = new Value(true, Value::$xmlrpcBoolean);
        }
    }

    return new Response($rv);
}


$mailsend_sig = array(array(
    Value::$xmlrpcBoolean, Value::$xmlrpcString, Value::$xmlrpcString,
    Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString,
    Value::$xmlrpcString, Value::$xmlrpcString,
));
$mailsend_doc = 'mail.send(recipient, subject, text, sender, cc, bcc, mimetype)<br/>
recipient, cc, and bcc are strings, comma-separated lists of email addresses, as described above.<br/>
subject is a string, the subject of the message.<br/>
sender is a string, it\'s the email address of the person sending the message. This string can not be
a comma-separated list, it must contain a single email address only.<br/>
text is a string, it contains the body of the message.<br/>
mimetype, a string, is a standard MIME type, for example, text/plain.
';
// WARNING; this functionality depends on the sendmail -t option
// it may not work with Windows machines properly; particularly
// the Bcc option. Sneak on your friends at your own risk!
function mailSend($req)
{
    $err = "";

    $mTo = $req->getParam(0);
    $mSub = $req->getParam(1);
    $mBody = $req->getParam(2);
    $mFrom = $req->getParam(3);
    $mCc = $req->getParam(4);
    $mBcc = $req->getParam(5);
    $mMime = $req->getParam(6);

    if ($mTo->scalarval() == "") {
        $err = "Error, no 'To' field specified";
    }

    if ($mFrom->scalarval() == "") {
        $err = "Error, no 'From' field specified";
    }

    $msgHdr = "From: " . $mFrom->scalarval() . "\n";
    $msgHdr .= "To: " . $mTo->scalarval() . "\n";

    if ($mCc->scalarval() != "") {
        $msgHdr .= "Cc: " . $mCc->scalarval() . "\n";
    }
    if ($mBcc->scalarval() != "") {
        $msgHdr .= "Bcc: " . $mBcc->scalarval() . "\n";
    }
    if ($mMime->scalarval() != "") {
        $msgHdr .= "Content-type: " . $mMime->scalarval() . "\n";
    }
    $msgHdr .= "X-Mailer: XML-RPC for PHP mailer 1.0";

    if ($err == "") {
        if (!mail("", $mSub->scalarval(), $mBody->scalarval(), $msgHdr)
        ) {
            $err = "Error, could not send the mail.";
        }
    }

    if ($err) {
        return new Response(0, PhpXmlRpc\PhpXmlRpc::$xmlrpcerruser, $err);
    } else {
        return new Response(new Value(true, Value::$xmlrpcBoolean));
    }
}

return array(
    "examples.getStateName" => array(
        "function" => "findState",
        "signature" => $findstate_sig,
        "docstring" => $findstate_doc,
    ),
    "examples.sortByAge" => array(
        "function" => "ageSorter",
        "signature" => $agesorter_sig,
        "docstring" => $agesorter_doc,
    ),
    "examples.addtwo" => array(
        "function" => "addTwo",
        "signature" => $addtwo_sig,
        "docstring" => $addtwo_doc,
    ),
    "examples.addtwodouble" => array(
        "function" => "addTwoDouble",
        "signature" => $addtwodouble_sig,
        "docstring" => $addtwodouble_doc,
    ),
    "examples.stringecho" => array(
        "function" => "stringEcho",
        "signature" => $stringecho_sig,
        "docstring" => $stringecho_doc,
    ),
    "examples.echo" => array(
        "function" => "echoBack",
        "signature" => $echoback_sig,
        "docstring" => $echoback_doc,
    ),
    "examples.decode64" => array(
        "function" => "echoSixtyFour",
        "signature" => $echosixtyfour_sig,
        "docstring" => $echosixtyfour_doc,
    ),
    "examples.invertBooleans" => array(
        "function" => "bitFlipper",
        "signature" => $bitflipper_sig,
        "docstring" => $bitflipper_doc,
    ),

    // left in as an example, but disabled by default, to avoid this being abused if left on an open server
    /*"mail.send" => array(
        "function" => "mailSend",
        "signature" => $mailsend_sig,
        "docstring" => $mailsend_doc,
    ),*/
);
