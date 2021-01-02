<?php
/**
 * Defines functions and signatures which can be registered as methods exposed by an XMLRPC Server
 *
 * To use this, use something akin to:
 * $signatures = include('validator1.php');
 *
 * Validator1 tests
 */

use PhpXmlRpc\Response;
use PhpXmlRpc\Value;

$v1_arrayOfStructs_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcArray));
$v1_arrayOfStructs_doc = 'This handler takes a single parameter, an array of structs, each of which contains at least three elements named moe, larry and curly, all <i4>s. Your handler must add all the struct elements named curly and return the result.';
function v1_arrayOfStructs($req)
{
    $sno = $req->getParam(0);
    $numCurly = 0;
    foreach ($sno as $str) {
        foreach ($str as $key => $val) {
            if ($key == "curly") {
                $numCurly += $val->scalarval();
            }
        }
    }

    return new Response(new Value($numCurly, Value::$xmlrpcInt));
}

$v1_easyStruct_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$v1_easyStruct_doc = 'This handler takes a single parameter, a struct, containing at least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.';
function v1_easyStruct($req)
{
    $sno = $req->getParam(0);
    $moe = $sno["moe"];
    $larry = $sno["larry"];
    $curly = $sno["curly"];
    $num = $moe->scalarval() + $larry->scalarval() + $curly->scalarval();

    return new Response(new Value($num, Value::$xmlrpcInt));
}

$v1_echoStruct_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcStruct));
$v1_echoStruct_doc = 'This handler takes a single parameter, a struct. Your handler must return the struct.';
function v1_echoStruct($req)
{
    $sno = $req->getParam(0);

    return new Response($sno);
}

$v1_manyTypes_sig = array(array(
    Value::$xmlrpcArray, Value::$xmlrpcInt, Value::$xmlrpcBoolean,
    Value::$xmlrpcString, Value::$xmlrpcDouble, Value::$xmlrpcDateTime,
    Value::$xmlrpcBase64,
));
$v1_manyTypes_doc = 'This handler takes six parameters, and returns an array containing all the parameters.';
function v1_manyTypes($req)
{
    return new Response(new Value(
        array(
            $req->getParam(0),
            $req->getParam(1),
            $req->getParam(2),
            $req->getParam(3),
            $req->getParam(4),
            $req->getParam(5)
        ),
        Value::$xmlrpcArray
    ));
}

$v1_moderateSizeArrayCheck_sig = array(array(Value::$xmlrpcString, Value::$xmlrpcArray));
$v1_moderateSizeArrayCheck_doc = 'This handler takes a single parameter, which is an array containing between 100 and 200 elements. Each of the items is a string, your handler must return a string containing the concatenated text of the first and last elements.';
function v1_moderateSizeArrayCheck($req)
{
    $ar = $req->getParam(0);
    $sz = $ar->count();
    $first = $ar[0];
    $last = $ar[$sz - 1];

    return new Response(new Value($first->scalarval() .
        $last->scalarval(), Value::$xmlrpcString));
}

$v1_simpleStructReturn_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcInt));
$v1_simpleStructReturn_doc = 'This handler takes one parameter, and returns a struct containing three elements, times10, times100 and times1000, the result of multiplying the number by 10, 100 and 1000.';
function v1_simpleStructReturn($req)
{
    $sno = $req->getParam(0);
    $v = $sno->scalarval();

    return new Response(new Value(
        array(
            "times10" => new Value($v * 10, Value::$xmlrpcInt),
            "times100" => new Value($v * 100, Value::$xmlrpcInt),
            "times1000" => new Value($v * 1000, Value::$xmlrpcInt)
        ),
        Value::$xmlrpcStruct
    ));
}

$v1_nestedStruct_sig = array(array(Value::$xmlrpcInt, Value::$xmlrpcStruct));
$v1_nestedStruct_doc = 'This handler takes a single parameter, a struct, that models a daily calendar. At the top level, there is one struct for each year. Each year is broken down into months, and months into days. Most of the days are empty in the struct you receive, but the entry for April 1, 2000 contains a least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.';
function v1_nestedStruct($req)
{
    $sno = $req->getParam(0);

    $twoK = $sno["2000"];
    $april = $twoK["04"];
    $fools = $april["01"];
    $curly = $fools["curly"];
    $larry = $fools["larry"];
    $moe = $fools["moe"];

    return new Response(new Value($curly->scalarval() + $larry->scalarval() + $moe->scalarval(), Value::$xmlrpcInt));
}

$v1_countTheEntities_sig = array(array(Value::$xmlrpcStruct, Value::$xmlrpcString));
$v1_countTheEntities_doc = 'This handler takes a single parameter, a string, that contains any number of predefined entities, namely &lt;, &gt;, &amp; \' and ".<BR>Your handler must return a struct that contains five fields, all numbers: ctLeftAngleBrackets, ctRightAngleBrackets, ctAmpersands, ctApostrophes, ctQuotes.';
function v1_countTheEntities($req)
{
    $sno = $req->getParam(0);
    $str = $sno->scalarval();
    $gt = 0;
    $lt = 0;
    $ap = 0;
    $qu = 0;
    $amp = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $c = substr($str, $i, 1);
        switch ($c) {
            case ">":
                $gt++;
                break;
            case "<":
                $lt++;
                break;
            case "\"":
                $qu++;
                break;
            case "'":
                $ap++;
                break;
            case "&":
                $amp++;
                break;
            default:
                break;
        }
    }

    return new Response(new Value(
        array(
            "ctLeftAngleBrackets" => new Value($lt, Value::$xmlrpcInt),
            "ctRightAngleBrackets" => new Value($gt, Value::$xmlrpcInt),
            "ctAmpersands" => new Value($amp, Value::$xmlrpcInt),
            "ctApostrophes" => new Value($ap, Value::$xmlrpcInt),
            "ctQuotes" => new Value($qu, Value::$xmlrpcInt)
        ),
        Value::$xmlrpcStruct
    ));
}

return array(
    "validator1.arrayOfStructsTest" => array(
        "function" => "v1_arrayOfStructs",
        "signature" => $v1_arrayOfStructs_sig,
        "docstring" => $v1_arrayOfStructs_doc,
    ),
    "validator1.easyStructTest" => array(
        "function" => "v1_easyStruct",
        "signature" => $v1_easyStruct_sig,
        "docstring" => $v1_easyStruct_doc,
    ),
    "validator1.echoStructTest" => array(
        "function" => "v1_echoStruct",
        "signature" => $v1_echoStruct_sig,
        "docstring" => $v1_echoStruct_doc,
    ),
    "validator1.manyTypesTest" => array(
        "function" => "v1_manyTypes",
        "signature" => $v1_manyTypes_sig,
        "docstring" => $v1_manyTypes_doc,
    ),
    "validator1.moderateSizeArrayCheck" => array(
        "function" => "v1_moderateSizeArrayCheck",
        "signature" => $v1_moderateSizeArrayCheck_sig,
        "docstring" => $v1_moderateSizeArrayCheck_doc,
    ),
    "validator1.simpleStructReturnTest" => array(
        "function" => "v1_simpleStructReturn",
        "signature" => $v1_simpleStructReturn_sig,
        "docstring" => $v1_simpleStructReturn_doc,
    ),
    "validator1.nestedStructTest" => array(
        "function" => "v1_nestedStruct",
        "signature" => $v1_nestedStruct_sig,
        "docstring" => $v1_nestedStruct_doc,
    ),
    "validator1.countTheEntities" => array(
        "function" => "v1_countTheEntities",
        "signature" => $v1_countTheEntities_sig,
        "docstring" => $v1_countTheEntities_doc,
    ),
);
