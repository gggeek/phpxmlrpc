<?php

namespace PhpXmlRpc;

use PhpXmlRpc\Helper\XMLParser;

class Encoder
{
    /**
     * Takes an xmlrpc value in PHP xmlrpcval object format and translates it into native PHP types.
     *
     * Works with xmlrpc requests objects as input, too.
     *
     * Given proper options parameter, can rebuild generic php object instances
     * (provided those have been encoded to xmlrpc format using a corresponding
     * option in php_xmlrpc_encode())
     * PLEASE NOTE that rebuilding php objects involves calling their constructor function.
     * This means that the remote communication end can decide which php code will
     * get executed on your server, leaving the door possibly open to 'php-injection'
     * style of attacks (provided you have some classes defined on your server that
     * might wreak havoc if instances are built outside an appropriate context).
     * Make sure you trust the remote server/client before eanbling this!
     *
     * @author Dan Libby (dan@libby.com)
     *
     * @param Value|Request $xmlrpc_val
     * @param array $options if 'decode_php_objs' is set in the options array, xmlrpc structs can be decoded into php objects; if 'dates_as_objects' is set xmlrpc datetimes are decoded as php DateTime objects (standard is
     *
     * @return mixed
     */
    public function decode($xmlrpc_val, $options = array())
    {
        switch ($xmlrpc_val->kindOf()) {
            case 'scalar':
                if (in_array('extension_api', $options)) {
                    reset($xmlrpc_val->me);
                    list($typ, $val) = each($xmlrpc_val->me);
                    switch ($typ) {
                        case 'dateTime.iso8601':
                            $xmlrpc_val->scalar = $val;
                            $xmlrpc_val->type = 'datetime';
                            $xmlrpc_val->timestamp = \PhpXmlRpc\Helper\Date::iso8601_decode($val);

                            return $xmlrpc_val;
                        case 'base64':
                            $xmlrpc_val->scalar = $val;
                            $xmlrpc_val->type = $typ;

                            return $xmlrpc_val;
                        default:
                            return $xmlrpc_val->scalarval();
                    }
                }
                if (in_array('dates_as_objects', $options) && $xmlrpc_val->scalartyp() == 'dateTime.iso8601') {
                    // we return a Datetime object instead of a string
                    // since now the constructor of xmlrpcval accepts safely strings, ints and datetimes,
                    // we cater to all 3 cases here
                    $out = $xmlrpc_val->scalarval();
                    if (is_string($out)) {
                        $out = strtotime($out);
                    }
                    if (is_int($out)) {
                        $result = new \Datetime();
                        $result->setTimestamp($out);

                        return $result;
                    } elseif (is_a($out, 'Datetime')) {
                        return $out;
                    }
                }

                return $xmlrpc_val->scalarval();
            case 'array':
                $size = $xmlrpc_val->arraysize();
                $arr = array();
                for ($i = 0; $i < $size; $i++) {
                    $arr[] = $this->decode($xmlrpc_val->arraymem($i), $options);
                }

                return $arr;
            case 'struct':
                $xmlrpc_val->structreset();
                // If user said so, try to rebuild php objects for specific struct vals.
                /// @todo should we raise a warning for class not found?
                // shall we check for proper subclass of xmlrpcval instead of
                // presence of _php_class to detect what we can do?
                if (in_array('decode_php_objs', $options) && $xmlrpc_val->_php_class != ''
                    && class_exists($xmlrpc_val->_php_class)
                ) {
                    $obj = @new $xmlrpc_val->_php_class();
                    while (list($key, $value) = $xmlrpc_val->structeach()) {
                        $obj->$key = $this->decode($value, $options);
                    }

                    return $obj;
                } else {
                    $arr = array();
                    while (list($key, $value) = $xmlrpc_val->structeach()) {
                        $arr[$key] = $this->decode($value, $options);
                    }

                    return $arr;
                }
            case 'msg':
                $paramcount = $xmlrpc_val->getNumParams();
                $arr = array();
                for ($i = 0; $i < $paramcount; $i++) {
                    $arr[] = $this->decode($xmlrpc_val->getParam($i));
                }

                return $arr;
        }
    }

    /**
     * Takes native php types and encodes them into xmlrpc PHP object format.
     * It will not re-encode xmlrpcval objects.
     *
     * Feature creep -- could support more types via optional type argument
     * (string => datetime support has been added, ??? => base64 not yet)
     *
     * If given a proper options parameter, php object instances will be encoded
     * into 'special' xmlrpc values, that can later be decoded into php objects
     * by calling php_xmlrpc_decode() with a corresponding option
     *
     * @author Dan Libby (dan@libby.com)
     *
     * @param mixed $php_val the value to be converted into an xmlrpcval object
     * @param array $options can include 'encode_php_objs', 'auto_dates', 'null_extension' or 'extension_api'
     *
     * @return \PhpXmlrpc\Value
     */
    public function encode($php_val, $options = array())
    {
        $type = gettype($php_val);
        switch ($type) {
            case 'string':
                if (in_array('auto_dates', $options) && preg_match('/^[0-9]{8}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $php_val)) {
                    $xmlrpc_val = new Value($php_val, Value::$xmlrpcDateTime);
                } else {
                    $xmlrpc_val = new Value($php_val, Value::$xmlrpcString);
                }
                break;
            case 'integer':
                $xmlrpc_val = new Value($php_val, Value::$xmlrpcInt);
                break;
            case 'double':
                $xmlrpc_val = new Value($php_val, Value::$xmlrpcDouble);
                break;
            // <G_Giunta_2001-02-29>
            // Add support for encoding/decoding of booleans, since they are supported in PHP
            case 'boolean':
                $xmlrpc_val = new Value($php_val, Value::$xmlrpcBoolean);
                break;
            // </G_Giunta_2001-02-29>
            case 'array':
                // PHP arrays can be encoded to either xmlrpc structs or arrays,
                // depending on wheter they are hashes or plain 0..n integer indexed
                // A shorter one-liner would be
                // $tmp = array_diff(array_keys($php_val), range(0, count($php_val)-1));
                // but execution time skyrockets!
                $j = 0;
                $arr = array();
                $ko = false;
                foreach ($php_val as $key => $val) {
                    $arr[$key] = $this->encode($val, $options);
                    if (!$ko && $key !== $j) {
                        $ko = true;
                    }
                    $j++;
                }
                if ($ko) {
                    $xmlrpc_val = new Value($arr, Value::$xmlrpcStruct);
                } else {
                    $xmlrpc_val = new Value($arr, Value::$xmlrpcArray);
                }
                break;
            case 'object':
                if (is_a($php_val, 'PhpXmlRpc\Value')) {
                    $xmlrpc_val = $php_val;
                } elseif (is_a($php_val, 'DateTime')) {
                    $xmlrpc_val = new Value($php_val->format('Ymd\TH:i:s'), Value::$xmlrpcStruct);
                } else {
                    $arr = array();
                    reset($php_val);
                    while (list($k, $v) = each($php_val)) {
                        $arr[$k] = $this->encode($v, $options);
                    }
                    $xmlrpc_val = new Value($arr, Value::$xmlrpcStruct);
                    if (in_array('encode_php_objs', $options)) {
                        // let's save original class name into xmlrpcval:
                        // might be useful later on...
                        $xmlrpc_val->_php_class = get_class($php_val);
                    }
                }
                break;
            case 'NULL':
                if (in_array('extension_api', $options)) {
                    $xmlrpc_val = new Value('', Value::$xmlrpcString);
                } elseif (in_array('null_extension', $options)) {
                    $xmlrpc_val = new Value('', Value::$xmlrpcNull);
                } else {
                    $xmlrpc_val = new Value();
                }
                break;
            case 'resource':
                if (in_array('extension_api', $options)) {
                    $xmlrpc_val = new Value((int)$php_val, Value::$xmlrpcInt);
                } else {
                    $xmlrpc_val = new Value();
                }
            // catch "user function", "unknown type"
            default:
                // giancarlo pinerolo <ping@alt.it>
                // it has to return
                // an empty object in case, not a boolean.
                $xmlrpc_val = new Value();
                break;
        }

        return $xmlrpc_val;
    }

    /**
     * Convert the xml representation of a method response, method request or single
     * xmlrpc value into the appropriate object (a.k.a. deserialize).
     *
     * @param string $xml_val
     * @param array $options
     *
     * @return mixed false on error, or an instance of either Value, Request or Response
     */
    public function decode_xml($xml_val, $options = array())
    {

        /// @todo 'guestimate' encoding
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        // What if internal encoding is not in one of the 3 allowed?
        // we use the broadest one, ie. utf8!
        if (!in_array(PhpXmlRpc::$xmlrpc_internalencoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII'))) {
            xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        } else {
            xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, PhpXmlRpc::$xmlrpc_internalencoding);
        }

        $xmlRpcParser = new XMLParser();
        xml_set_object($parser, $xmlRpcParser);

        xml_set_element_handler($parser, 'xmlrpc_se_any', 'xmlrpc_ee');
        xml_set_character_data_handler($parser, 'xmlrpc_cd');
        xml_set_default_handler($parser, 'xmlrpc_dh');
        if (!xml_parse($parser, $xml_val, 1)) {
            $errstr = sprintf('XML error: %s at line %d, column %d',
                xml_error_string(xml_get_error_code($parser)),
                xml_get_current_line_number($parser), xml_get_current_column_number($parser));
            error_log($errstr);
            xml_parser_free($parser);

            return false;
        }
        xml_parser_free($parser);
        if ($xmlRpcParser->_xh['isf'] > 1) {
            // test that $xmlrpc->_xh['value'] is an obj, too???

            error_log($xmlRpcParser->_xh['isf_reason']);

            return false;
        }
        switch ($xmlRpcParser->_xh['rt']) {
            case 'methodresponse':
                $v = &$xmlRpcParser->_xh['value'];
                if ($xmlRpcParser->_xh['isf'] == 1) {
                    $vc = $v->structmem('faultCode');
                    $vs = $v->structmem('faultString');
                    $r = new Response(0, $vc->scalarval(), $vs->scalarval());
                } else {
                    $r = new Response($v);
                }

                return $r;
            case 'methodcall':
                $m = new Request($xmlRpcParser->_xh['method']);
                for ($i = 0; $i < count($xmlRpcParser->_xh['params']); $i++) {
                    $m->addParam($xmlRpcParser->_xh['params'][$i]);
                }

                return $m;
            case 'value':
                return $xmlRpcParser->_xh['value'];
            default:
                return false;
        }
    }

    /**
     * xml charset encoding guessing helper function.
     * Tries to determine the charset encoding of an XML chunk received over HTTP.
     * NB: according to the spec (RFC 3023), if text/xml content-type is received over HTTP without a content-type,
     * we SHOULD assume it is strictly US-ASCII. But we try to be more tolerant of unconforming (legacy?) clients/servers,
     * which will be most probably using UTF-8 anyway...
     *
     * @param string $httpheader the http Content-type header
     * @param string $xmlchunk xml content buffer
     * @param string $encoding_prefs comma separated list of character encodings to be used as default (when mb extension is enabled)
     * @return string
     *
     * @todo explore usage of mb_http_input(): does it detect http headers + post data? if so, use it instead of hand-detection!!!
     */
    public static function guess_encoding($httpheader = '', $xmlchunk = '', $encoding_prefs = null)
    {
        // discussion: see http://www.yale.edu/pclt/encoding/
        // 1 - test if encoding is specified in HTTP HEADERS

        //Details:
        // LWS:           (\13\10)?( |\t)+
        // token:         (any char but excluded stuff)+
        // quoted string: " (any char but double quotes and cointrol chars)* "
        // header:        Content-type = ...; charset=value(; ...)*
        //   where value is of type token, no LWS allowed between 'charset' and value
        // Note: we do not check for invalid chars in VALUE:
        //   this had better be done using pure ereg as below
        // Note 2: we might be removing whitespace/tabs that ought to be left in if
        //   the received charset is a quoted string. But nobody uses such charset names...

        /// @todo this test will pass if ANY header has charset specification, not only Content-Type. Fix it?
        $matches = array();
        if (preg_match('/;\s*charset\s*=([^;]+)/i', $httpheader, $matches)) {
            return strtoupper(trim($matches[1], " \t\""));
        }

        // 2 - scan the first bytes of the data for a UTF-16 (or other) BOM pattern
        //     (source: http://www.w3.org/TR/2000/REC-xml-20001006)
        //     NOTE: actually, according to the spec, even if we find the BOM and determine
        //     an encoding, we should check if there is an encoding specified
        //     in the xml declaration, and verify if they match.
        /// @todo implement check as described above?
        /// @todo implement check for first bytes of string even without a BOM? (It sure looks harder than for cases WITH a BOM)
        if (preg_match('/^(\x00\x00\xFE\xFF|\xFF\xFE\x00\x00|\x00\x00\xFF\xFE|\xFE\xFF\x00\x00)/', $xmlchunk)) {
            return 'UCS-4';
        } elseif (preg_match('/^(\xFE\xFF|\xFF\xFE)/', $xmlchunk)) {
            return 'UTF-16';
        } elseif (preg_match('/^(\xEF\xBB\xBF)/', $xmlchunk)) {
            return 'UTF-8';
        }

        // 3 - test if encoding is specified in the xml declaration
        // Details:
        // SPACE:         (#x20 | #x9 | #xD | #xA)+ === [ \x9\xD\xA]+
        // EQ:            SPACE?=SPACE? === [ \x9\xD\xA]*=[ \x9\xD\xA]*
        if (preg_match('/^<\?xml\s+version\s*=\s*' . "((?:\"[a-zA-Z0-9_.:-]+\")|(?:'[a-zA-Z0-9_.:-]+'))" .
            '\s+encoding\s*=\s*' . "((?:\"[A-Za-z][A-Za-z0-9._-]*\")|(?:'[A-Za-z][A-Za-z0-9._-]*'))/",
            $xmlchunk, $matches)) {
            return strtoupper(substr($matches[2], 1, -1));
        }

        // 4 - if mbstring is available, let it do the guesswork
        // NB: we favour finding an encoding that is compatible with what we can process
        if (extension_loaded('mbstring')) {
            if ($encoding_prefs) {
                $enc = mb_detect_encoding($xmlchunk, $encoding_prefs);
            } else {
                $enc = mb_detect_encoding($xmlchunk);
            }
            // NB: mb_detect likes to call it ascii, xml parser likes to call it US_ASCII...
            // IANA also likes better US-ASCII, so go with it
            if ($enc == 'ASCII') {
                $enc = 'US-' . $enc;
            }

            return $enc;
        } else {
            // no encoding specified: as per HTTP1.1 assume it is iso-8859-1?
            // Both RFC 2616 (HTTP 1.1) and 1945 (HTTP 1.0) clearly state that for text/xxx content types
            // this should be the standard. And we should be getting text/xml as request and response.
            // BUT we have to be backward compatible with the lib, which always used UTF-8 as default...
            return PhpXmlRpc::$xmlrpc_defencoding;
        }
    }
}
