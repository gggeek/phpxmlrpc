<?php

class Phpxmlrpc {

    public $xmlrpcI4 = "i4";
    public $xmlrpcInt = "int";
    public $xmlrpcBoolean = "boolean";
    public $xmlrpcDouble = "double";
    public $xmlrpcString = "string";
    public $xmlrpcDateTime = "dateTime.iso8601";
    public $xmlrpcBase64 = "base64";
    public $xmlrpcArray = "array";
    public $xmlrpcStruct = "struct";
    public $xmlrpcValue = "undefined";
    public $xmlrpcNull = "null";

    public $xmlrpcTypes;

    public $xmlrpc_valid_parents = array(
        'VALUE' => array('MEMBER', 'DATA', 'PARAM', 'FAULT'),
        'BOOLEAN' => array('VALUE'),
        'I4' => array('VALUE'),
        'INT' => array('VALUE'),
        'STRING' => array('VALUE'),
        'DOUBLE' => array('VALUE'),
        'DATETIME.ISO8601' => array('VALUE'),
        'BASE64' => array('VALUE'),
        'MEMBER' => array('STRUCT'),
        'NAME' => array('MEMBER'),
        'DATA' => array('ARRAY'),
        'ARRAY' => array('VALUE'),
        'STRUCT' => array('VALUE'),
        'PARAM' => array('PARAMS'),
        'METHODNAME' => array('METHODCALL'),
        'PARAMS' => array('METHODCALL', 'METHODRESPONSE'),
        'FAULT' => array('METHODRESPONSE'),
        'NIL' => array('VALUE'), // only used when extension activated
        'EX:NIL' => array('VALUE') // only used when extension activated
    );

    // tables used for transcoding different charsets into us-ascii xml
    public $xml_iso88591_Entities = array("in" => array(), "out" => array());

    /// @todo add to iso table the characters from cp_1252 range, i.e. 128 to 159?
    /// These will NOT be present in true ISO-8859-1, but will save the unwary
    /// windows user from sending junk (though no luck when reciving them...)
    /*
    $GLOBALS['xml_cp1252_Entities']=array();
    for ($i = 128; $i < 160; $i++)
    {
        $GLOBALS['xml_cp1252_Entities']['in'][] = chr($i);
    }
    $GLOBALS['xml_cp1252_Entities']['out'] = array(
        '&#x20AC;', '?',        '&#x201A;', '&#x0192;',
        '&#x201E;', '&#x2026;', '&#x2020;', '&#x2021;',
        '&#x02C6;', '&#x2030;', '&#x0160;', '&#x2039;',
        '&#x0152;', '?',        '&#x017D;', '?',
        '?',        '&#x2018;', '&#x2019;', '&#x201C;',
        '&#x201D;', '&#x2022;', '&#x2013;', '&#x2014;',
        '&#x02DC;', '&#x2122;', '&#x0161;', '&#x203A;',
        '&#x0153;', '?',        '&#x017E;', '&#x0178;'
    );
    */

    public $xmlrpcerr = array(
        'unknown_method'=>1,
        'invalid_return'=>2,
        'incorrect_params'=>3,
        'introspect_unknown'=>4,
        'http_error'=>5,
        'no_data'=>6,
        'no_ssl'=>7,
        'curl_fail'=>8,
        'invalid_request'=>15,
        'no_curl'=>16,
        'server_error'=>17,
        'multicall_error'=>18,
        'multicall_notstruct'=>9,
        'multicall_nomethod'=>10,
        'multicall_notstring'=>11,
        'multicall_recursion'=>12,
        'multicall_noparams'=>13,
        'multicall_notarray'=>14,

        'cannot_decompress'=>103,
        'decompress_fail'=>104,
        'dechunk_fail'=>105,
        'server_cannot_decompress'=>106,
        'server_decompress_fail'=>107
    );

    public $xmlrpcstr = array(
        'unknown_method'=>'Unknown method',
        'invalid_return'=>'Invalid return payload: enable debugging to examine incoming payload',
        'incorrect_params'=>'Incorrect parameters passed to method',
        'introspect_unknown'=>"Can't introspect: method unknown",
        'http_error'=>"Didn't receive 200 OK from remote server.",
        'no_data'=>'No data received from server.',
        'no_ssl'=>'No SSL support compiled in.',
        'curl_fail'=>'CURL error',
        'invalid_request'=>'Invalid request payload',
        'no_curl'=>'No CURL support compiled in.',
        'server_error'=>'Internal server error',
        'multicall_error'=>'Received from server invalid multicall response',
        'multicall_notstruct'=>'system.multicall expected struct',
        'multicall_nomethod'=>'missing methodName',
        'multicall_notstring'=>'methodName is not a string',
        'multicall_recursion'=>'recursive system.multicall forbidden',
        'multicall_noparams'=>'missing params',
        'multicall_notarray'=>'params is not an array',

        'cannot_decompress'=>'Received from server compressed HTTP and cannot decompress',
        'decompress_fail'=>'Received from server invalid compressed HTTP',
        'dechunk_fail'=>'Received from server invalid chunked HTTP',
        'server_cannot_decompress'=>'Received from client compressed HTTP request and cannot decompress',
        'server_decompress_fail'=>'Received from client invalid compressed HTTP request'
    );

    // The charset encoding used by the server for received messages and
    // by the client for received responses when received charset cannot be determined
    // or is not supported
    public $xmlrpc_defencoding = "UTF-8";

    // The encoding used internally by PHP.
    // String values received as xml will be converted to this, and php strings will be converted to xml
    // as if having been coded with this
    public $xmlrpc_internalencoding = "ISO-8859-1"; // TODO: maybe this would be better as UTF-8, or atleast configurable?

    public $xmlrpcName = "XML-RPC for PHP";
    public $xmlrpcVersion = "3.0.0.beta";

    // let user errors start at 800
    public $xmlrpcerruser = 800;
    // let XML parse errors start at 100
    public $xmlrpcerrxml = 100;

    // set to TRUE to enable correct decoding of <NIL/> and <EX:NIL/> values
    public $xmlrpc_null_extension = false;

    // set to TRUE to enable encoding of php NULL values to <EX:NIL/> instead of <NIL/>
    public $xmlrpc_null_apache_encoding = false;

    public $xmlrpc_null_apache_encoding_ns = "http://ws.apache.org/xmlrpc/namespaces/extensions";

    // used to store state during parsing
    // quick explanation of components:
    //   ac - used to accumulate values
    //   isf - used to indicate a parsing fault (2) or xmlrpcresp fault (1)
    //   isf_reason - used for storing xmlrpcresp fault string
    //   lv - used to indicate "looking for a value": implements
    //        the logic to allow values with no types to be strings
    //   params - used to store parameters in method calls
    //   method - used to store method name
    //   stack - array with genealogy of xml elements names:
    //           used to validate nesting of xmlrpc elements
    public $_xh = null;

    private static $instance = null;

    private function __construct() {
        $this->xmlrpcTypes = array(
            $this->xmlrpcI4 => 1,
            $this->xmlrpcInt => 1,
            $this->xmlrpcBoolean => 1,
            $this->xmlrpcDouble => 1,
            $this->xmlrpcString => 1,
            $this->xmlrpcDateTime => 1,
            $this->xmlrpcBase64 => 1,
            $this->xmlrpcArray => 2,
            $this->xmlrpcStruct => 3,
            $this->xmlrpcNull => 1
        );

        for($i = 0; $i < 32; $i++) {
            $this->xml_iso88591_Entities["in"][] = chr($i);
            $this->xml_iso88591_Entities["out"][] = "&#{$i};";
        }

        for($i = 160; $i < 256; $i++) {
            $this->xml_iso88591_Entities["in"][] = chr($i);
            $this->xml_iso88591_Entities["out"][] = "&#{$i};";
        }
    }

    /**
     * This class is singleton for performance reasons: this way the ASCII array needs to be done only once.
     */
    public static function instance() {
        if(Phpxmlrpc::$instance === null) {
            Phpxmlrpc::$instance = new Phpxmlrpc();
        }

        return Phpxmlrpc::$instance;
    }
}

?>