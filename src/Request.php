<?php

namespace PhpXmlRpc;

use PhpXmlRpc\Helper\Http;
use PhpXmlRpc\Helper\XMLParser;

class Request
{

    /// @todo: do these need to be public?
    public $payload;
    public $methodname;
    public $params=array();
    public $debug=0;
    public $content_type = 'text/xml';

    // holds data while parsing the response. NB: Not a full Response object
    protected $httpResponse = array();

    /**
     * @param string $methodName the name of the method to invoke
     * @param array $params array of parameters to be passed to the method (xmlrpcval objects)
     */
    function __construct($methodName, $params=array())
    {
        $this->methodname = $methodName;
        foreach($params as $param)
        {
            $this->addParam($param);
        }
    }

    public function xml_header($charset_encoding='')
    {
        if ($charset_encoding != '')
        {
            return "<?xml version=\"1.0\" encoding=\"$charset_encoding\" ?" . ">\n<methodCall>\n";
        }
        else
        {
            return "<?xml version=\"1.0\"?" . ">\n<methodCall>\n";
        }
    }

    public function xml_footer()
    {
        return '</methodCall>';
    }

    /**
     * Kept the old name even if class was renamed, for compatibility
     * @return string
     */
    private function kindOf()
    {
        return 'msg';
    }

    public function createPayload($charset_encoding='')
    {
        if ($charset_encoding != '')
            $this->content_type = 'text/xml; charset=' . $charset_encoding;
        else
            $this->content_type = 'text/xml';
        $this->payload=$this->xml_header($charset_encoding);
        $this->payload.='<methodName>' . $this->methodname . "</methodName>\n";
        $this->payload.="<params>\n";
        foreach($this->params as $p)
        {
            $this->payload.="<param>\n" . $p->serialize($charset_encoding) .
            "</param>\n";
        }
        $this->payload.="</params>\n";
        $this->payload.=$this->xml_footer();
    }

    /**
     * Gets/sets the xmlrpc method to be invoked
     * @param string $meth the method to be set (leave empty not to set it)
     * @return string the method that will be invoked
     */
    public function method($methodName='')
    {
        if($methodName!='')
        {
            $this->methodname=$methodName;
        }
        return $this->methodname;
    }

    /**
     * Returns xml representation of the message. XML prologue included
     * @param string $charset_encoding
     * @return string the xml representation of the message, xml prologue included
     */
    public function serialize($charset_encoding='')
    {
        $this->createPayload($charset_encoding);
        return $this->payload;
    }

    /**
     * Add a parameter to the list of parameters to be used upon method invocation
     * @param Value $par
     * @return boolean false on failure
     */
    public function addParam($param)
    {
        // add check: do not add to self params which are not xmlrpcvals
        if(is_object($param) && is_a($param, 'PhpXmlRpc\Value'))
        {
            $this->params[]=$param;
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Returns the nth parameter in the request. The index zero-based.
     * @param integer $i the index of the parameter to fetch (zero based)
     * @return Value the i-th parameter
     */
    public function getParam($i) { return $this->params[$i]; }

    /**
     * Returns the number of parameters in the messge.
     * @return integer the number of parameters currently set
     */
    public function getNumParams() { return count($this->params); }

    /**
     * Given an open file handle, read all data available and parse it as axmlrpc response.
     * NB: the file handle is not closed by this function.
     * NNB: might have trouble in rare cases to work on network streams, as we
     *      check for a read of 0 bytes instead of feof($fp).
     *      But since checking for feof(null) returns false, we would risk an
     *      infinite loop in that case, because we cannot trust the caller
     *      to give us a valid pointer to an open file...
     * @param resource $fp stream pointer
     * @return Response
     * @todo add 2nd & 3rd param to be passed to ParseResponse() ???
     */
    public function parseResponseFile($fp)
    {
        $ipd='';
        while($data=fread($fp, 32768))
        {
            $ipd.=$data;
        }
        //fclose($fp);
        return $this->parseResponse($ipd);
    }

    /**
     * Parses HTTP headers and separates them from data.
     * @return null|Response null on success, or a Response on error
     */
    private function parseResponseHeaders(&$data, $headers_processed=false)
    {
        $this->httpResponse['headers'] = array();
        $this->httpResponse['cookies'] = array();

        // Support "web-proxy-tunelling" connections for https through proxies
        if(preg_match('/^HTTP\/1\.[0-1] 200 Connection established/', $data))
        {
            // Look for CR/LF or simple LF as line separator,
            // (even though it is not valid http)
            $pos = strpos($data,"\r\n\r\n");
            if($pos || is_int($pos))
            {
                $bd = $pos+4;
            }
            else
            {
                $pos = strpos($data,"\n\n");
                if($pos || is_int($pos))
                {
                    $bd = $pos+2;
                }
                else
                {
                    // No separation between response headers and body: fault?
                    $bd = 0;
                }
            }
            if ($bd)
            {
                // this filters out all http headers from proxy.
                // maybe we could take them into account, too?
                $data = substr($data, $bd);
            }
            else
            {
                error_log('XML-RPC: '.__METHOD__.': HTTPS via proxy error, tunnel connection possibly failed');
                $r=new Response(0, PhpXmlRpc::$xmlrpcerr['http_error'], PhpXmlRpc::$xmlrpcstr['http_error']. ' (HTTPS via proxy error, tunnel connection possibly failed)');
                return $r;
            }
        }

        // Strip HTTP 1.1 100 Continue header if present
        while(preg_match('/^HTTP\/1\.1 1[0-9]{2} /', $data))
        {
            $pos = strpos($data, 'HTTP', 12);
            // server sent a Continue header without any (valid) content following...
            // give the client a chance to know it
            if(!$pos && !is_int($pos)) // works fine in php 3, 4 and 5
            {
                break;
            }
            $data = substr($data, $pos);
        }
        if(!preg_match('/^HTTP\/[0-9.]+ 200 /', $data))
        {
            $errstr= substr($data, 0, strpos($data, "\n")-1);
            error_log('XML-RPC: '.__METHOD__.': HTTP error, got response: ' .$errstr);
            $r=new Response(0, PhpXmlRpc::$xmlrpcerr['http_error'], PhpXmlRpc::$xmlrpcstr['http_error']. ' (' . $errstr . ')');
            return $r;
        }

        // be tolerant to usage of \n instead of \r\n to separate headers and data
        // (even though it is not valid http)
        $pos = strpos($data,"\r\n\r\n");
        if($pos || is_int($pos))
        {
            $bd = $pos+4;
        }
        else
        {
            $pos = strpos($data,"\n\n");
            if($pos || is_int($pos))
            {
                $bd = $pos+2;
            }
            else
            {
                // No separation between response headers and body: fault?
                // we could take some action here instead of going on...
                $bd = 0;
            }
        }
        // be tolerant to line endings, and extra empty lines
        $ar = preg_split("/\r?\n/", trim(substr($data, 0, $pos)));
        while(list(,$line) = @each($ar))
        {
            // take care of multi-line headers and cookies
            $arr = explode(':',$line,2);
            if(count($arr) > 1)
            {
                $header_name = strtolower(trim($arr[0]));
                /// @todo some other headers (the ones that allow a CSV list of values)
                /// do allow many values to be passed using multiple header lines.
                /// We should add content to $xmlrpc->_xh['headers'][$header_name]
                /// instead of replacing it for those...
                if ($header_name == 'set-cookie' || $header_name == 'set-cookie2')
                {
                    if ($header_name == 'set-cookie2')
                    {
                        // version 2 cookies:
                        // there could be many cookies on one line, comma separated
                        $cookies = explode(',', $arr[1]);
                    }
                    else
                    {
                        $cookies = array($arr[1]);
                    }
                    foreach ($cookies as $cookie)
                    {
                        // glue together all received cookies, using a comma to separate them
                        // (same as php does with getallheaders())
                        if (isset($this->httpResponse['headers'][$header_name]))
                            $this->httpResponse['headers'][$header_name] .= ', ' . trim($cookie);
                        else
                            $this->httpResponse['headers'][$header_name] = trim($cookie);
                        // parse cookie attributes, in case user wants to correctly honour them
                        // feature creep: only allow rfc-compliant cookie attributes?
                        // @todo support for server sending multiple time cookie with same name, but using different PATHs
                        $cookie = explode(';', $cookie);
                        foreach ($cookie as $pos => $val)
                        {
                            $val = explode('=', $val, 2);
                            $tag = trim($val[0]);
                            $val = trim(@$val[1]);
                            /// @todo with version 1 cookies, we should strip leading and trailing " chars
                            if ($pos == 0)
                            {
                                $cookiename = $tag;
                                $this->httpResponse['cookies'][$tag] = array();
                                $this->httpResponse['cookies'][$cookiename]['value'] = urldecode($val);
                            }
                            else
                            {
                                if ($tag != 'value')
                                {
                                    $this->httpResponse['cookies'][$cookiename][$tag] = $val;
                                }
                            }
                        }
                    }
                }
                else
                {
                    $this->httpResponse['headers'][$header_name] = trim($arr[1]);
                }
            }
            elseif(isset($header_name))
            {
                /// @todo version1 cookies might span multiple lines, thus breaking the parsing above
                $this->httpResponse['headers'][$header_name] .= ' ' . trim($line);
            }
        }

        $data = substr($data, $bd);

        /// @todo when in CLI mode, do not html-encode the output
        if($this->debug && count($this->httpResponse['headers']))
        {
            print "</PRE>\n";
            foreach($this->httpResponse['headers'] as $header => $value)
            {
                print htmlentities("HEADER: $header: $value\n");
            }
            foreach($this->httpResponse['cookies'] as $header => $value)
            {
                print htmlentities("COOKIE: $header={$value['value']}\n");
            }
            print "</PRE>\n";
        }

        // if CURL was used for the call, http headers have been processed,
        // and dechunking + reinflating have been carried out
        if(!$headers_processed)
        {
            // Decode chunked encoding sent by http 1.1 servers
            if(isset($this->httpResponse['headers']['transfer-encoding']) && $this->httpResponse['headers']['transfer-encoding'] == 'chunked')
            {
                if(!$data = Http::decode_chunked($data))
                {
                    error_log('XML-RPC: '.__METHOD__.': errors occurred when trying to rebuild the chunked data received from server');
                    $r = new Response(0, PhpXmlRpc::$xmlrpcerr['dechunk_fail'], PhpXmlRpc::$xmlrpcstr['dechunk_fail']);
                    return $r;
                }
            }

            // Decode gzip-compressed stuff
            // code shamelessly inspired from nusoap library by Dietrich Ayala
            if(isset($this->httpResponse['headers']['content-encoding']))
            {
                $this->httpResponse['headers']['content-encoding'] = str_replace('x-', '', $this->httpResponse['headers']['content-encoding']);
                if($this->httpResponse['headers']['content-encoding'] == 'deflate' || $this->httpResponse['headers']['content-encoding'] == 'gzip')
                {
                    // if decoding works, use it. else assume data wasn't gzencoded
                    if(function_exists('gzinflate'))
                    {
                        if($this->httpResponse['headers']['content-encoding'] == 'deflate' && $degzdata = @gzuncompress($data))
                        {
                            $data = $degzdata;
                            if($this->debug)
                                print "<PRE>---INFLATED RESPONSE---[".strlen($data)." chars]---\n" . htmlentities($data) . "\n---END---</PRE>";
                        }
                        elseif($this->httpResponse['headers']['content-encoding'] == 'gzip' && $degzdata = @gzinflate(substr($data, 10)))
                        {
                            $data = $degzdata;
                            if($this->debug)
                                print "<PRE>---INFLATED RESPONSE---[".strlen($data)." chars]---\n" . htmlentities($data) . "\n---END---</PRE>";
                        }
                        else
                        {
                            error_log('XML-RPC: '.__METHOD__.': errors occurred when trying to decode the deflated data received from server');
                            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['decompress_fail'], PhpXmlRpc::$xmlrpcstr['decompress_fail']);
                            return $r;
                        }
                    }
                    else
                    {
                        error_log('XML-RPC: '.__METHOD__.': the server sent deflated data. Your php install must have the Zlib extension compiled in to support this.');
                        $r = new Response(0, PhpXmlRpc::$xmlrpcerr['cannot_decompress'], PhpXmlRpc::$xmlrpcstr['cannot_decompress']);
                        return $r;
                    }
                }
            }
        } // end of 'if needed, de-chunk, re-inflate response'

        return null;
    }

    /**
     * Parse the xmlrpc response contained in the string $data and return a Response object.
     * @param string $data the xmlrpc response, eventually including http headers
     * @param bool $headers_processed when true prevents parsing HTTP headers for interpretation of content-encoding and consequent decoding
     * @param string $return_type decides return type, i.e. content of response->value(). Either 'xmlrpcvals', 'xml' or 'phpvals'
     * @return Response
     */
    public function parseResponse($data='', $headers_processed=false, $return_type='xmlrpcvals')
    {
        if($this->debug)
        {
            // by maHo, replaced htmlspecialchars with htmlentities
            print "<PRE>---GOT---\n" . htmlentities($data) . "\n---END---\n</PRE>";
        }

        $this->httpResponse = array();
        $this->httpResponse['raw_data'] = $data;
        $this->httpResponse['headers'] = array();
        $this->httpResponse['cookies'] = array();

        if($data == '')
        {
            error_log('XML-RPC: '.__METHOD__.': no response received from server.');
            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['no_data'], PhpXmlRpc::$xmlrpcstr['no_data']);
            return $r;
        }

        // parse the HTTP headers of the response, if present, and separate them from data
        if(substr($data, 0, 4) == 'HTTP')
        {
            $r = $this->parseResponseHeaders($data, $headers_processed);
            if ($r)
            {
                // failed processing of HTTP response headers
                // save into response obj the full payload received, for debugging
                $r->raw_data = $data;
                return $r;
            }
        }

        if($this->debug)
        {
            $start = strpos($data, '<!-- SERVER DEBUG INFO (BASE64 ENCODED):');
            if ($start)
            {
                $start += strlen('<!-- SERVER DEBUG INFO (BASE64 ENCODED):');
                $end = strpos($data, '-->', $start);
                $comments = substr($data, $start, $end-$start);
                print "<PRE>---SERVER DEBUG INFO (DECODED) ---\n\t".htmlentities(str_replace("\n", "\n\t", base64_decode($comments)))."\n---END---\n</PRE>";
            }
        }

        // be tolerant of extra whitespace in response body
        $data = trim($data);

        /// @todo return an error msg if $data=='' ?

        // be tolerant of junk after methodResponse (e.g. javascript ads automatically inserted by free hosts)
        // idea from Luca Mariano <luca.mariano@email.it> originally in PEARified version of the lib
        $pos = strrpos($data, '</methodResponse>');
        if($pos !== false)
        {
            $data = substr($data, 0, $pos+17);
        }

        // if user wants back raw xml, give it to him
        if ($return_type == 'xml')
        {
            $r = new Response($data, 0, '', 'xml');
            $r->hdrs = $this->httpResponse['headers'];
            $r->_cookies = $this->httpResponse['cookies'];
            $r->raw_data = $this->httpResponse['raw_data'];
            return $r;
        }

        // try to 'guestimate' the character encoding of the received response
        $resp_encoding = Encoder::guess_encoding(@$this->httpResponse['headers']['content-type'], $data);

        // if response charset encoding is not known / supported, try to use
        // the default encoding and parse the xml anyway, but log a warning...
        if (!in_array($resp_encoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII')))
        // the following code might be better for mb_string enabled installs, but
        // makes the lib about 200% slower...
        //if (!is_valid_charset($resp_encoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII')))
        {
            error_log('XML-RPC: '.__METHOD__.': invalid charset encoding of received response: '.$resp_encoding);
            $resp_encoding = PhpXmlRpc::$xmlrpc_defencoding;
        }
        $parser = xml_parser_create($resp_encoding);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
        // G. Giunta 2005/02/13: PHP internally uses ISO-8859-1, so we have to tell
        // the xml parser to give us back data in the expected charset.
        // What if internal encoding is not in one of the 3 allowed?
        // we use the broadest one, ie. utf8
        // This allows to send data which is native in various charset,
        // by extending xmlrpc_encode_entities() and setting xmlrpc_internalencoding
        if (!in_array(PhpXmlRpc::$xmlrpc_internalencoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII')))
        {
            xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        }
        else
        {
            xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, PhpXmlRpc::$xmlrpc_internalencoding);
        }

        $xmlRpcParser = new XMLParser();
        xml_set_object($parser, $xmlRpcParser);

        if ($return_type == 'phpvals')
        {
            xml_set_element_handler($parser, 'xmlrpc_se', 'xmlrpc_ee_fast');
        }
        else
        {
            xml_set_element_handler($parser, 'xmlrpc_se', 'xmlrpc_ee');
        }

        xml_set_character_data_handler($parser, 'xmlrpc_cd');
        xml_set_default_handler($parser, 'xmlrpc_dh');

        // first error check: xml not well formed
        if(!xml_parse($parser, $data, count($data)))
        {
            // thanks to Peter Kocks <peter.kocks@baygate.com>
            if((xml_get_current_line_number($parser)) == 1)
            {
                $errstr = 'XML error at line 1, check URL';
            }
            else
            {
                $errstr = sprintf('XML error: %s at line %d, column %d',
                    xml_error_string(xml_get_error_code($parser)),
                    xml_get_current_line_number($parser), xml_get_current_column_number($parser));
            }
            error_log($errstr);
            $r=new Response(0, PhpXmlRpc::$xmlrpcerr['invalid_return'], PhpXmlRpc::$xmlrpcstr['invalid_return'].' ('.$errstr.')');
            xml_parser_free($parser);
            if($this->debug)
            {
                print $errstr;
            }
            $r->hdrs = $this->httpResponse['headers'];
            $r->_cookies = $this->httpResponse['cookies'];
            $r->raw_data = $this->httpResponse['raw_data'];
            return $r;
        }
        xml_parser_free($parser);
        // second error check: xml well formed but not xml-rpc compliant
        if ($xmlRpcParser->_xh['isf'] > 1)
        {
            if ($this->debug)
            {
                /// @todo echo something for user?
            }

            $r = new Response(0, PhpXmlRpc::$xmlrpcerr['invalid_return'],
            PhpXmlRpc::$xmlrpcstr['invalid_return'] . ' ' . $xmlRpcParser->_xh['isf_reason']);
        }
        // third error check: parsing of the response has somehow gone boink.
        // NB: shall we omit this check, since we trust the parsing code?
        elseif ($return_type == 'xmlrpcvals' && !is_object($xmlRpcParser->_xh['value']))
        {
            // something odd has happened
            // and it's time to generate a client side error
            // indicating something odd went on
            $r=new Response(0, PhpXmlRpc::$xmlrpcerr['invalid_return'],
                PhpXmlRpc::$xmlrpcstr['invalid_return']);
        }
        else
        {
            if ($this->debug)
            {
                print "<PRE>---PARSED---\n";
                // somehow htmlentities chokes on var_export, and some full html string...
                //print htmlentitites(var_export($xmlRpcParser->_xh['value'], true));
                print htmlspecialchars(var_export($xmlRpcParser->_xh['value'], true));
                print "\n---END---</PRE>";
            }

            // note that using =& will raise an error if $xmlRpcParser->_xh['st'] does not generate an object.
            $v =& $xmlRpcParser->_xh['value'];

            if($xmlRpcParser->_xh['isf'])
            {
                /// @todo we should test here if server sent an int and a string,
                /// and/or coerce them into such...
                if ($return_type == 'xmlrpcvals')
                {
                    $errno_v = $v->structmem('faultCode');
                    $errstr_v = $v->structmem('faultString');
                    $errno = $errno_v->scalarval();
                    $errstr = $errstr_v->scalarval();
                }
                else
                {
                    $errno = $v['faultCode'];
                    $errstr = $v['faultString'];
                }

                if($errno == 0)
                {
                    // FAULT returned, errno needs to reflect that
                    $errno = -1;
                }

                $r = new Response(0, $errno, $errstr);
            }
            else
            {
                $r=new Response($v, 0, '', $return_type);
            }
        }

        $r->hdrs = $this->httpResponse['headers'];
        $r->_cookies = $this->httpResponse['cookies'];
        $r->raw_data = $this->httpResponse['raw_data'];;
        return $r;
    }
}
