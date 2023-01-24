<?php

namespace PhpXmlRpc;

use PhpXmlRpc\Helper\Charset;
use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;

/**
 * Manages global configuration for operation of the library.
 */
class PhpXmlRpc
{
    /**
     * @var int[]
     */
    static public $xmlrpcerr = array(
        'unknown_method' => 1,
        /// @deprecated. left in for BC
        'invalid_return' => 2,
        'incorrect_params' => 3,
        'introspect_unknown' => 4,
        'http_error' => 5,
        'no_data' => 6,
        'no_ssl' => 7,
        'curl_fail' => 8,
        'invalid_request' => 15,
        'no_curl' => 16,
        'server_error' => 17,
        'multicall_error' => 18,
        'multicall_notstruct' => 9,
        'multicall_nomethod' => 10,
        'multicall_notstring' => 11,
        'multicall_recursion' => 12,
        'multicall_noparams' => 13,
        'multicall_notarray' => 14,
        'no_http2' => 19,
        // the following 3 are meant to give greater insight than 'invalid_return'. They use the same code for BC,
        // but you can override their value in your own code
        'invalid_xml' => 2,
        'xml_not_compliant' => 2,
        'xml_parsing_error' => 2,

        /// @todo verify: can these conflict with $xmlrpcerrxml?
        'cannot_decompress' => 103,
        'decompress_fail' => 104,
        'dechunk_fail' => 105,
        'server_cannot_decompress' => 106,
        'server_decompress_fail' => 107,
    );

    /**
     * @var string[]
     */
    static public $xmlrpcstr = array(
        'unknown_method' => 'Unknown method',
        /// @deprecated. left in for BC
        'invalid_return' => 'Invalid response payload (you can use the setDebug method to allow analysis of the response)',
        'incorrect_params' => 'Incorrect parameters passed to method',
        'introspect_unknown' => "Can't introspect: method unknown",
        'http_error' => "Didn't receive 200 OK from remote server",
        'no_data' => 'No data received from server',
        'no_ssl' => 'No SSL support compiled in',
        'curl_fail' => 'CURL error',
        'invalid_request' => 'Invalid request payload',
        'no_curl' => 'No CURL support compiled in',
        'server_error' => 'Internal server error',
        'multicall_error' => 'Received from server invalid multicall response',
        'multicall_notstruct' => 'system.multicall expected struct',
        'multicall_nomethod' => 'Missing methodName',
        'multicall_notstring' => 'methodName is not a string',
        'multicall_recursion' => 'Recursive system.multicall forbidden',
        'multicall_noparams' => 'Missing params',
        'multicall_notarray' => 'params is not an array',
        'no_http2' => 'No HTTP/2 support compiled in',
        // the following 3 are meant to give greater insight than 'invalid_return'. They use the same string for BC,
        // but you can override their value in your own code
        'invalid_xml' => 'Invalid response payload (you can use the setDebug method to allow analysis of the response)',
        'xml_not_compliant' => 'Invalid response payload (you can use the setDebug method to allow analysis of the response)',
        'xml_parsing_error' => 'Invalid response payload (you can use the setDebug method to allow analysis of the response)',

        'cannot_decompress' => 'Received from server compressed HTTP and cannot decompress',
        'decompress_fail' => 'Received from server invalid compressed HTTP',
        'dechunk_fail' => 'Received from server invalid chunked HTTP',
        'server_cannot_decompress' => 'Received from client compressed HTTP request and cannot decompress',
        'server_decompress_fail' => 'Received from client invalid compressed HTTP request',
    );

    /**
     * @var string
     * The charset encoding used by the server for received requests and by the client for received responses when
     * received charset cannot be determined and mbstring extension is not enabled.
     */
    public static $xmlrpc_defencoding = "UTF-8";

    /**
     * @var string[]
     * The list of preferred encodings used by the server for requests and by the client for responses to detect the
     * charset of the received payload when
     * - the charset cannot be determined by looking at http headers, xml declaration or BOM
     * - mbstring extension is enabled
     */
    public static $xmlrpc_detectencodings = array();

    /**
     * @var string
     * The encoding used internally by PHP.
     * String values received as xml will be converted to this, and php strings will be converted to xml as if
     * having been coded with this.
     * Valid also when defining names of xml-rpc methods
     */
    public static $xmlrpc_internalencoding = "UTF-8";

    /**
     * @var string
     */
    public static $xmlrpcName = "XML-RPC for PHP";
    /**
     * @var string
     */
    public static $xmlrpcVersion = "4.9.5";

    /**
     * @var int
     * Let user errors start at 800
     */
    public static $xmlrpcerruser = 800;
    /**
     * @var int
     * Let XML parse errors start at 100
     */
    public static $xmlrpcerrxml = 100;

    /**
     * @var bool
     * Set to TRUE to enable correct decoding of <NIL/> and <EX:NIL/> values
     */
    public static $xmlrpc_null_extension = false;

    /**
     * @var bool
     * Set to TRUE to make the library use DateTime objects instead of strings for all values parsed from incoming XML.
     * NB: if the received strings are not parseable as dates, NULL will be returned. To prevent that, enable as
     * well `xmlrpc_reject_invalid_values`, so that invalid dates will be rejected by the library
     */
    public static $xmlrpc_return_datetimes = false;

    /**
     * @var bool
     * Set to TRUE to make the library reject incoming xml which uses invalid data for xml-rpc elements, such
     * as base64 strings which can not be decoded, dateTime strings which do not represent a valid date, invalid bools,
     * floats and integers, method names with forbidden characters, or struct members missing the value or name
     */
    public static $xmlrpc_reject_invalid_values = false;

    /**
     * @var bool
     * Set to TRUE to enable encoding of php NULL values to <EX:NIL/> instead of <NIL/>
     */
    public static $xmlrpc_null_apache_encoding = false;

    public static $xmlrpc_null_apache_encoding_ns = "http://ws.apache.org/xmlrpc/namespaces/extensions";

    /**
     * @var int
     * Number of decimal digits used to serialize Double values.
     * @todo rename :'-(
     */
    public static $xmlpc_double_precision = 128;

    /**
     * @var string
     * Used to validate received date values. Alter this if the server/client you are communicating with uses date
     * formats non-conformant with the spec
     * NB: atm, the Date helper uses this regexp and expects to find matches in a specific order
     */
    public static $xmlrpc_datetime_format = '/^([0-9]{4})(0[1-9]|1[012])(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-4]):([0-5][0-9]):([0-5][0-9]|60)$/';

    /**
     * @var string
     * Used to validate received integer values. Alter this if the server/client you are communicating with uses
     * formats non-conformant with the spec.
     * We keep in spaces for BC, even though they are forbidden by the spec.
     * NB: the string should not match any data which php can not successfully cast to an integer
     */
    public static $xmlrpc_int_format = '/^[ \t]*[+-]?[0-9]+[ \t]*$/';

    /**
     * @var string
     * Used to validate received double values. Alter this if the server/client you are communicating with uses
     * formats non-conformant with the spec, e.g. with leading/trailing spaces/tabs/newlines.
     * We keep in spaces for BC, even though they are forbidden by the spec.
     * NB: the string should not match any data which php can not successfully cast to a float
     */
    public static $xmlrpc_double_format = '/^[ \t]*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?[ \t]*$/';

    /**
     * @var string
     * Used to validate received methodname values.
     * According to the spec: "The string may only contain identifier characters, upper and lower-case A-Z, the numeric
     * characters, 0-9, underscore, dot, colon and slash".
     * We keep in spaces for BC, even though they are forbidden by the spec.
     */
    public static $xmlrpc_methodname_format = '|^[ \t]*[a-zA-Z0-9_.:/]+[ \t]*$|';

    /// @todo review - should we use the range -32099 .. -32000 for some server erors?
    public static $xmlrpcerr_interop = array(
        'unknown_method' => -32601,
        'invalid_return' => 2,
        'incorrect_params' => -32602,
        'introspect_unknown' => -32601, // this shares the same code but has a separate meaning from 'unknown_method'...
        'http_error' => 32300,
        'no_data' => -32700,
        'no_ssl' => -32400,
        'curl_fail' => -32400,
        'invalid_request' => -32600,
        'no_curl' => -32400,
        'server_error' => -32500,
        'multicall_error' => -32700,
        'multicall_notstruct' => -32600,
        'multicall_nomethod' => -32601,
        'multicall_notstring' => -32600,
        'multicall_recursion' => -32603,
        'multicall_noparams' => -32602,
        'multicall_notarray' => -32600,
        'no_http2' => -32400,
        'invalid_xml' => -32700,
        'xml_not_compliant' => -32700,
        'xml_parsing_error' => -32700,
        'cannot_decompress' => -32400,
        'decompress_fail' => -32300,
        'dechunk_fail' => -32300,
        'server_cannot_decompress' => -32300,
        'server_decompress_fail' => -32300,
    );

    /**
     * A function to be used for compatibility with legacy code: it creates all global variables which used to be declared,
     * such as library version etc...
     * @return void
     */
    public static function exportGlobals()
    {
        $reflection = new \ReflectionClass('PhpXmlRpc\PhpXmlRpc');
        foreach ($reflection->getStaticProperties() as $name => $value) {
            $GLOBALS[$name] = $value;
        }

        // NB: all the variables exported into the global namespace below here do NOT guarantee 100% compatibility,
        // as they are NOT reimported back during calls to importGlobals()

        $reflection = new \ReflectionClass('PhpXmlRpc\Value');
        foreach ($reflection->getStaticProperties() as $name => $value) {
            if (!in_array($name, array('logger', 'charsetEncoder'))) {
                $GLOBALS[$name] = $value;
            }
        }

        $parser = new Helper\XMLParser();
        $reflection = new \ReflectionClass('PhpXmlRpc\Helper\XMLParser');
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $name => $value) {
            if (in_array($value->getName(), array('xmlrpc_valid_parents')))
            {
                $GLOBALS[$value->getName()] = $value->getValue($parser);
            }
        }

        $charset = Charset::instance();
        $GLOBALS['xml_iso88591_Entities'] = $charset->getEntities('iso88591');
    }

    /**
     * A function to be used for compatibility with legacy code: it gets the values of all global variables which used
     * to be declared, such as library version etc... and sets them to php classes.
     * It should be used by code which changed the values of those global variables to alter the working of the library.
     * Example code:
     * 1. include xmlrpc.inc
     * 2. set the values, e.g. $GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
     * 3. import them: PhpXmlRpc\PhpXmlRpc::importGlobals();
     * 4. run your own code.
     *
     * @return void
     */
    public static function importGlobals()
    {
        $reflection = new \ReflectionClass('PhpXmlRpc\PhpXmlRpc');
        $staticProperties = $reflection->getStaticProperties();
        foreach ($staticProperties as $name => $value) {
            if (isset($GLOBALS[$name])) {
                self::$$name = $GLOBALS[$name];
            }
        }
    }

    /**
     * Inject a logger into all classes of the PhpXmlRpc library which use one
     *
     * @param $logger
     * @return void
     */
    public static function setLogger($logger)
    {
        Charset::setLogger($logger);
        Client::setLogger($logger);
        Encoder::setLogger($logger);
        Http::setLogger($logger);
        Request::setLogger($logger);
        Server::setLogger($logger);
        Value::setLogger($logger);
        Wrapper::setLogger($logger);
        XMLParser::setLogger($logger);
    }

    /**
     * Makes the library use the error codes detailed at https://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php
     *
     * @return void
     *
     * @tofo feature creep - allow switching back to the original set of codes; querying the current mode
     */
    public static function useInteropFaults()
    {
        self::$xmlrpcerr = self::$xmlrpcerr_interop;

        self::$xmlrpcerruser = -32000;
    }
}
