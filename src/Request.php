<?php

namespace PhpXmlRpc;

use PhpXmlRpc\Exception\HttpException;
use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;
use PhpXmlRpc\Traits\CharsetEncoderAware;
use PhpXmlRpc\Traits\LoggerAware;
use PhpXmlRpc\Traits\ParserAware;

/**
 * This class provides the representation of a request to an XML-RPC server.
 * A client sends a PhpXmlrpc\Request to a server, and receives back an PhpXmlrpc\Response.
 *
 * @todo feature creep - add a protected $httpRequest member, in the same way the Response has one
 */
class Request
{
    use CharsetEncoderAware;
    use LoggerAware;
    use ParserAware;

    /// @todo: do these need to be public?
    public $payload;
    /** @internal */
    public $methodname;
    /** @internal */
    public $params = array();
    /** @var int */
    public $debug = 0;
    /** @var string */
    public $content_type = 'text/xml';

    // holds data while parsing the response. NB: Not a full Response object
    /** @deprecated will be removed in a future release */
    protected $httpResponse = array();

    /**
     * @param string $methodName the name of the method to invoke
     * @param Value[] $params array of parameters to be passed to the method (NB: Value objects, not plain php values)
     */
    public function __construct($methodName, $params = array())
    {
        $this->methodname = $methodName;
        foreach ($params as $param) {
            $this->addParam($param);
        }
    }

    /**
     * @internal this function will become protected in the future
     *
     * @param string $charsetEncoding
     * @return string
     */
    public function xml_header($charsetEncoding = '')
    {
        if ($charsetEncoding != '') {
            return "<?xml version=\"1.0\" encoding=\"$charsetEncoding\" ?" . ">\n<methodCall>\n";
        } else {
            return "<?xml version=\"1.0\"?" . ">\n<methodCall>\n";
        }
    }

    /**
     * @internal this function will become protected in the future
     *
     * @return string
     */
    public function xml_footer()
    {
        return '</methodCall>';
    }

    /**
     * @internal this function will become protected in the future
     *
     * @param string $charsetEncoding
     * @return void
     */
    public function createPayload($charsetEncoding = '')
    {
        if ($charsetEncoding != '') {
            $this->content_type = 'text/xml; charset=' . $charsetEncoding;
        } else {
            $this->content_type = 'text/xml';
        }
        $this->payload = $this->xml_header($charsetEncoding);
        $this->payload .= '<methodName>' . $this->getCharsetEncoder()->encodeEntities(
            $this->methodname, PhpXmlRpc::$xmlrpc_internalencoding, $charsetEncoding) . "</methodName>\n";
        $this->payload .= "<params>\n";
        foreach ($this->params as $p) {
            $this->payload .= "<param>\n" . $p->serialize($charsetEncoding) .
                "</param>\n";
        }
        $this->payload .= "</params>\n";
        $this->payload .= $this->xml_footer();
    }

    /**
     * Gets/sets the xml-rpc method to be invoked.
     *
     * @param string $methodName the method to be set (leave empty not to set it)
     * @return string the method that will be invoked
     */
    public function method($methodName = '')
    {
        if ($methodName != '') {
            $this->methodname = $methodName;
        }

        return $this->methodname;
    }

    /**
     * Returns xml representation of the message. XML prologue included.
     *
     * @param string $charsetEncoding
     * @return string the xml representation of the message, xml prologue included
     */
    public function serialize($charsetEncoding = '')
    {
        $this->createPayload($charsetEncoding);

        return $this->payload;
    }

    /**
     * Add a parameter to the list of parameters to be used upon method invocation.
     * Checks that $params is actually a Value object and not a plain php value.
     *
     * @param Value $param
     * @return boolean false on failure
     */
    public function addParam($param)
    {
        // check: do not add to self params which are not xml-rpc values
        if (is_object($param) && is_a($param, 'PhpXmlRpc\Value')) {
            $this->params[] = $param;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the nth parameter in the request. The index zero-based.
     *
     * @param integer $i the index of the parameter to fetch (zero based)
     * @return Value the i-th parameter
     */
    public function getParam($i)
    {
        return $this->params[$i];
    }

    /**
     * Returns the number of parameters in the message.
     *
     * @return integer the number of parameters currently set
     */
    public function getNumParams()
    {
        return count($this->params);
    }

    /**
     * Given an open file handle, read all data available and parse it as an xml-rpc response.
     *
     * NB: the file handle is not closed by this function.
     * NNB: might have trouble in rare cases to work on network streams, as we check for a read of 0 bytes instead of
     *      feof($fp). But since checking for feof(null) returns false, we would risk an infinite loop in that case,
     *      because we cannot trust the caller to give us a valid pointer to an open file...
     *
     * @param resource $fp stream pointer
     * @param bool $headersProcessed
     * @param string $returnType
     * @return Response
     */
    public function parseResponseFile($fp, $headersProcessed = false, $returnType = 'xmlrpcvals')
    {
        $ipd = '';
        while ($data = fread($fp, 32768)) {
            $ipd .= $data;
        }
        return $this->parseResponse($ipd, $headersProcessed, $returnType);
    }

    /**
     * Parse the xml-rpc response contained in the string $data and return a Response object.
     *
     * When $this->debug has been set to a value greater than 0, will echo debug messages to screen while decoding.
     *
     * @param string $data the xml-rpc response, possibly including http headers
     * @param bool $headersProcessed when true prevents parsing HTTP headers for interpretation of content-encoding and
     *                               consequent decoding
     * @param string $returnType decides return type, i.e. content of response->value(). Either 'xmlrpcvals', 'xml' or
     *                           'phpvals'
     * @return Response
     *
     * @todo parsing Responses is not really the responsibility of the Request class. Maybe of the Client...
     * @todo what about only populating 'raw_data' and 'headers' in httpResponse when debug mode is on? Even better, have
     *       3 debug levels: data only, echo messages, echo more messages
     */
    public function parseResponse($data = '', $headersProcessed = false, $returnType = XMLParser::RETURN_XMLRPCVALS)
    {
        if ($this->debug > 0) {
            $this->getLogger()->debugMessage("---GOT---\n$data\n---END---");
        }

        $httpResponse = array('raw_data' => $data, 'headers' => array(), 'cookies' => array());
        $this->httpResponse = $httpResponse;

        if ($data == '') {
            $this->getLogger()->errorLog('XML-RPC: ' . __METHOD__ . ': no response received from server.');
            return new Response(0, PhpXmlRpc::$xmlrpcerr['no_data'], PhpXmlRpc::$xmlrpcstr['no_data']);
        }

        // parse the HTTP headers of the response, if present, and separate them from data
        if (substr($data, 0, 4) == 'HTTP') {
            $httpParser = new Http();
            try {
                $httpResponse = $httpParser->parseResponseHeaders($data, $headersProcessed, $this->debug > 0);
            } catch (HttpException $e) {
                // failed processing of HTTP response headers
                // save into response obj the full payload received, for debugging
                return new Response(0, $e->getCode(), $e->getMessage(), '', array('raw_data' => $data, 'status_code', $e->statusCode()));
            } catch(\Exception $e) {
                return new Response(0, $e->getCode(), $e->getMessage(), '', array('raw_data' => $data));
            }
        }

        // be tolerant of extra whitespace in response body
        $data = trim($data);

        /// @todo optimization creep - return an error msg if $data == ''

        // be tolerant of junk after methodResponse (e.g. javascript ads automatically inserted by free hosts)
        // idea from Luca Mariano <luca.mariano@email.it> originally in PEARified version of the lib
        $pos = strrpos($data, '</methodResponse>');
        if ($pos !== false) {
            $data = substr($data, 0, $pos + 17);
        }

        // try to 'guestimate' the character encoding of the received response
        $respEncoding = XMLParser::guessEncoding(
            isset($httpResponse['headers']['content-type']) ? $httpResponse['headers']['content-type'] : '',
            $data
        );

        if ($this->debug >= 0) {
            $this->httpResponse = $httpResponse;
        } else {
            $httpResponse = null;
        }

        if ($this->debug > 0) {
            $start = strpos($data, '<!-- SERVER DEBUG INFO (BASE64 ENCODED):');
            if ($start) {
                $start += strlen('<!-- SERVER DEBUG INFO (BASE64 ENCODED):');
                $end = strpos($data, '-->', $start);
                $comments = substr($data, $start, $end - $start);
                $this->getLogger()->debugMessage("---SERVER DEBUG INFO (DECODED) ---\n\t" .
                    str_replace("\n", "\n\t", base64_decode($comments)) . "\n---END---", $respEncoding);
            }
        }

        // if the user wants back raw xml, give it to her
        if ($returnType == 'xml') {
            return new Response($data, 0, '', 'xml', $httpResponse);
        }

        /// @todo move this block of code into the XMLParser
        if ($respEncoding != '') {
            // Since parsing will fail if charset is not specified in the xml declaration,
            // the encoding is not UTF8 and there are non-ascii chars in the text, we try to work round that...
            // The following code might be better for mb_string enabled installs, but makes the lib about 200% slower...
            //if (!is_valid_charset($respEncoding, array('UTF-8')))
            if (!in_array($respEncoding, array('UTF-8', 'US-ASCII')) && !XMLParser::hasEncoding($data)) {
                if (function_exists('mb_convert_encoding')) {
                    $data = mb_convert_encoding($data, 'UTF-8', $respEncoding);
                } else {
                    if ($respEncoding == 'ISO-8859-1') {
                        $data = utf8_encode($data);
                    } else {
                        $this->getLogger()->errorLog('XML-RPC: ' . __METHOD__ . ': unsupported charset encoding of received response: ' . $respEncoding);
                    }
                }
            }
        }
        // PHP internally might use ISO-8859-1, so we have to tell the xml parser to give us back data in the expected charset.
        // What if internal encoding is not in one of the 3 allowed? We use the broadest one, i.e. utf8
        if (in_array(PhpXmlRpc::$xmlrpc_internalencoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII'))) {
            $options = array(XML_OPTION_TARGET_ENCODING => PhpXmlRpc::$xmlrpc_internalencoding);
        } else {
            $options = array(XML_OPTION_TARGET_ENCODING => 'UTF-8', 'target_charset' => PhpXmlRpc::$xmlrpc_internalencoding);
        }

        $xmlRpcParser = $this->getParser();
        $xmlRpcParser->parse($data, $returnType, XMLParser::ACCEPT_RESPONSE, $options);

        // first error check: xml not well-formed
        if ($xmlRpcParser->_xh['isf'] == 3) {

            // BC break: in the past for some cases we used the error message: 'XML error at line 1, check URL'

            // Q: should we give back an error with variable error number, as we do server-side? But if we do, will
            //    we be able to tell apart the two cases? In theory, we never emit invalid xml on our end, but
            //    there could be proxies meddling with the request, or network data corruption...

            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['invalid_xml'],
                PhpXmlRpc::$xmlrpcstr['invalid_xml'] . ' ' . $xmlRpcParser->_xh['isf_reason'], '',
                $httpResponse
            );

            if ($this->debug > 0) {
                $this->getLogger()->debugMessage($xmlRpcParser->_xh['isf_reason']);
            }
        }
        // second error check: xml well-formed but not xml-rpc compliant
        elseif ($xmlRpcParser->_xh['isf'] == 2) {
            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['xml_not_compliant'],
                PhpXmlRpc::$xmlrpcstr['xml_not_compliant'] . ' ' . $xmlRpcParser->_xh['isf_reason'], '',
                $httpResponse
            );

            /// @todo echo something for the user? check if this was already done by the parser...
            //if ($this->debug > 0) {
            //    $this->getLogger()->debugMessage($xmlRpcParser->_xh['isf_reason']);
            //}
        }
        // third error check: parsing of the response has somehow gone boink.
        /// @todo shall we omit this check, since we trust the parsing code?
        elseif ($xmlRpcParser->_xh['isf'] > 3 || $returnType == XMLParser::RETURN_XMLRPCVALS && !is_object($xmlRpcParser->_xh['value'])) {
            // something odd has happened and it's time to generate a client side error indicating something odd went on
            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['xml_parsing_error'], PhpXmlRpc::$xmlrpcstr['xml_parsing_error'],
                '', $httpResponse
            );

            /// @todo echo something for the user?
        } else {
            if ($this->debug > 1) {
                $this->getLogger()->debugMessage(
                    "---PARSED---\n".var_export($xmlRpcParser->_xh['value'], true)."\n---END---"
                );
            }

            $v = $xmlRpcParser->_xh['value'];

            if ($xmlRpcParser->_xh['isf']) {
                /// @todo we should test here if server sent an int and a string, and/or coerce them into such...
                if ($returnType == XMLParser::RETURN_XMLRPCVALS) {
                    $errNo_v = $v['faultCode'];
                    $errStr_v = $v['faultString'];
                    $errNo = $errNo_v->scalarval();
                    $errStr = $errStr_v->scalarval();
                } else {
                    $errNo = $v['faultCode'];
                    $errStr = $v['faultString'];
                }

                if ($errNo == 0) {
                    // FAULT returned, errno needs to reflect that
                    /// @todo we should signal somehow that the server returned a fault with code 0?
                    $errNo = -1;
                }

                $r = new Response(0, $errNo, $errStr, '', $httpResponse);
            } else {
                $r = new Response($v, 0, '', $returnType, $httpResponse);
            }
        }

        return $r;
    }

    /**
     * Kept the old name even if Request class was renamed, for BC.
     *
     * @return string
     */
    public function kindOf()
    {
        return 'msg';
    }

    /**
     * Enables/disables the echoing to screen of the xml-rpc responses received.
     *
     * @param integer $level values <0, 0, 1, >1 are supported
     * @return $this
     */
    public function setDebug($level)
    {
        $this->debug = $level;
        return $this;
    }
}
