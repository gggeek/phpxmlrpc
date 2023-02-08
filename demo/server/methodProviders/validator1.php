<?php
/**
 * Defines functions and signatures which can be registered as methods exposed by an XML-RPC Server
 *
 * To use this, use something akin to:
 * $signatures = include('validator1.php');
 *
 * Validator1 tests
 */

use PhpXmlRpc\Response;
use PhpXmlRpc\Value;

return array(
    "validator1.arrayOfStructsTest" => array(
        "signature" => array(
            array(Value::$xmlrpcInt, Value::$xmlrpcArray)
        ),
        "docstring" => 'This handler takes a single parameter, an array of structs, each of which contains at least three elements named moe, larry and curly, all <i4>s. Your handler must add all the struct elements named curly and return the result.',
        "function" => function ($req)
        {
            $sno = $req->getParam(0);
            $numCurly = 0;
            foreach ($sno as $str) {
                foreach ($str as $key => $val) {
                    if ($key == "curly") {
                        $numCurly += $val->scalarVal();
                    }
                }
            }

            return new Response(new Value($numCurly, Value::$xmlrpcInt));
        }
    ),

    "validator1.easyStructTest" => array(
        "signature" => array(
            array(Value::$xmlrpcInt, Value::$xmlrpcStruct)
        ),
        "docstring" => 'This handler takes a single parameter, a struct, containing at least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.',
        "function" => function ($req)
        {
            $sno = $req->getParam(0);
            $moe = $sno["moe"];
            $larry = $sno["larry"];
            $curly = $sno["curly"];
            $num = $moe->scalarVal() + $larry->scalarVal() + $curly->scalarVal();

            return new Response(new Value($num, Value::$xmlrpcInt));
        }
    ),

    "validator1.echoStructTest" => array(
        "signature" => array(
            array(Value::$xmlrpcStruct, Value::$xmlrpcStruct)
        ),
        "docstring" => 'This handler takes a single parameter, a struct. Your handler must return the struct.',
        "function" => function ($req)
        {
            $sno = $req->getParam(0);

            return new Response($sno);
        }
    ),

    "validator1.manyTypesTest" => array(
        "signature" => array(
            array(Value::$xmlrpcArray, Value::$xmlrpcInt, Value::$xmlrpcBoolean,
                Value::$xmlrpcString, Value::$xmlrpcDouble, Value::$xmlrpcDateTime,
                Value::$xmlrpcBase64,
            )
        ),
        "docstring" => 'This handler takes six parameters, and returns an array containing all the parameters.',
        "function" => function ($req)
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
    ),

    "validator1.moderateSizeArrayCheck" => array(
        "signature" => array(
            array(Value::$xmlrpcString, Value::$xmlrpcArray)
        ),
        "docstring" => 'This handler takes a single parameter, which is an array containing between 100 and 200 elements. Each of the items is a string, your handler must return a string containing the concatenated text of the first and last elements.',
        "function" => function ($req)
        {
            $ar = $req->getParam(0);
            $sz = $ar->count();
            $first = $ar[0];
            $last = $ar[$sz - 1];

            return new Response(new Value($first->scalarVal() . $last->scalarVal(), Value::$xmlrpcString));
        }
    ),

    "validator1.simpleStructReturnTest" => array(
        "signature" => array(
            array(Value::$xmlrpcStruct, Value::$xmlrpcInt)
        ),
        "docstring" => 'This handler takes one parameter, and returns a struct containing three elements, times10, times100 and times1000, the result of multiplying the number by 10, 100 and 1000.',
        "function" => function ($req)
        {
            $sno = $req->getParam(0);
            $v = $sno->scalarVal();

            return new Response(new Value(
                array(
                    "times10" => new Value($v * 10, Value::$xmlrpcInt),
                    "times100" => new Value($v * 100, Value::$xmlrpcInt),
                    "times1000" => new Value($v * 1000, Value::$xmlrpcInt)
                ),
                Value::$xmlrpcStruct
            ));
        }
    ),

    "validator1.nestedStructTest" => array(
        "signature" => array(
            array(Value::$xmlrpcInt, Value::$xmlrpcStruct)
        ),
        "docstring" => 'This handler takes a single parameter, a struct, that models a daily calendar. At the top level, there is one struct for each year. Each year is broken down into months, and months into days. Most of the days are empty in the struct you receive, but the entry for April 1, 2000 contains a least three elements named moe, larry and curly, all &lt;i4&gt;s. Your handler must add the three numbers and return the result.',
        "function" => function ($req)
        {
            $sno = $req->getParam(0);

            $twoK = $sno["2000"];
            $april = $twoK["04"];
            $fools = $april["01"];
            $curly = $fools["curly"];
            $larry = $fools["larry"];
            $moe = $fools["moe"];

            return new Response(new Value($curly->scalarVal() + $larry->scalarVal() + $moe->scalarVal(), Value::$xmlrpcInt));
        }
    ),

    "validator1.countTheEntities" => array(
        "signature" => array(
            array(Value::$xmlrpcStruct, Value::$xmlrpcString)
        ),
        "docstring" => 'This handler takes a single parameter, a string, that contains any number of predefined entities, namely &lt;, &gt;, &amp; \' and ".<BR>Your handler must return a struct that contains five fields, all numbers: ctLeftAngleBrackets, ctRightAngleBrackets, ctAmpersands, ctApostrophes, ctQuotes.',
        "function" => function ($req)
        {
            $sno = $req->getParam(0);
            $str = $sno->scalarVal();
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
    ),
);
