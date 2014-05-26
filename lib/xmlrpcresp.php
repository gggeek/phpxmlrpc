<?php

class xmlrpcresp
{

    /// @todo: do these need to be public?
    public $val = 0;
    public $valtyp;
    public $errno = 0;
    public $errstr = '';
    public $payload;
    public $hdrs = array();
    public $_cookies = array();
    public $content_type = 'text/xml';
    public $raw_data = '';

    /**
     * @param mixed $val either an xmlrpcval obj, a php value or the xml serialization of an xmlrpcval (a string)
     * @param integer $fcode set it to anything but 0 to create an error response
     * @param string $fstr the error string, in case of an error response
     * @param string $valtyp either 'xmlrpcvals', 'phpvals' or 'xml'
     *
     * @todo add check that $val / $fcode / $fstr is of correct type???
     * NB: as of now we do not do it, since it might be either an xmlrpcval or a plain
     * php val, or a complete xml chunk, depending on usage of xmlrpc_client::send() inside which creator is called...
     */
    function __construct($val, $fcode = 0, $fstr = '', $valtyp='')
    {
        if($fcode != 0)
        {
            // error response
            $this->errno = $fcode;
            $this->errstr = $fstr;
            //$this->errstr = htmlspecialchars($fstr); // XXX: encoding probably shouldn't be done here; fix later.
        }
        else
        {
            // successful response
            $this->val = $val;
            if ($valtyp == '')
            {
                // user did not declare type of response value: try to guess it
                if (is_object($this->val) && is_a($this->val, 'xmlrpcval'))
                {
                    $this->valtyp = 'xmlrpcvals';
                }
                else if (is_string($this->val))
                {
                    $this->valtyp = 'xml';

                }
                else
                {
                    $this->valtyp = 'phpvals';
                }
            }
            else
            {
                // user declares type of resp value: believe him
                $this->valtyp = $valtyp;
            }
        }
    }

    /**
     * Returns the error code of the response.
     * @return integer the error code of this response (0 for not-error responses)
     */
    public function faultCode()
    {
        return $this->errno;
    }

    /**
     * Returns the error code of the response.
     * @return string the error string of this response ('' for not-error responses)
     */
    public function faultString()
    {
        return $this->errstr;
    }

    /**
     * Returns the value received by the server.
     * @return mixed the xmlrpcval object returned by the server. Might be an xml string or php value if the response has been created by specially configured xmlrpc_client objects
     */
    public function value()
    {
        return $this->val;
    }

    /**
     * Returns an array with the cookies received from the server.
     * Array has the form: $cookiename => array ('value' => $val, $attr1 => $val1, $attr2 = $val2, ...)
     * with attributes being e.g. 'expires', 'path', domain'.
     * NB: cookies sent as 'expired' by the server (i.e. with an expiry date in the past)
     * are still present in the array. It is up to the user-defined code to decide
     * how to use the received cookies, and whether they have to be sent back with the next
     * request to the server (using xmlrpc_client::setCookie) or not
     * @return array array of cookies received from the server
     */
    public function cookies()
    {
        return $this->_cookies;
    }

    /**
     * Returns xml representation of the response. XML prologue not included
     * @param string $charset_encoding the charset to be used for serialization. if null, US-ASCII is assumed
     * @return string the xml representation of the response
     */
    public function serialize($charset_encoding='')
    {
        $xmlrpc = Phpxmlrpc::instance();

        if ($charset_encoding != '')
            $this->content_type = 'text/xml; charset=' . $charset_encoding;
        else
            $this->content_type = 'text/xml';
        if ($xmlrpc->xmlrpc_null_apache_encoding)
        {
            $result = "<methodResponse xmlns:ex=\"".$xmlrpc->xmlrpc_null_apache_encoding_ns."\">\n";
        }
        else
        {
        $result = "<methodResponse>\n";
        }
        if($this->errno)
        {
            // G. Giunta 2005/2/13: let non-ASCII response messages be tolerated by clients
            // by xml-encoding non ascii chars
            $result .= "<fault>\n" .
"<value>\n<struct><member><name>faultCode</name>\n<value><int>" . $this->errno .
"</int></value>\n</member>\n<member>\n<name>faultString</name>\n<value><string>" .
xmlrpc_encode_entitites($this->errstr, $xmlrpc->xmlrpc_internalencoding, $charset_encoding) . "</string></value>\n</member>\n" .
"</struct>\n</value>\n</fault>";
        }
        else
        {
            if(!is_object($this->val) || !is_a($this->val, 'xmlrpcval'))
            {
                if (is_string($this->val) && $this->valtyp == 'xml')
                {
                    $result .= "<params>\n<param>\n" .
                        $this->val .
                        "</param>\n</params>";
                }
                else
                {
                    /// @todo try to build something serializable?
                    die('cannot serialize xmlrpcresp objects whose content is native php values');
                }
            }
            else
            {
                $result .= "<params>\n<param>\n" .
                    $this->val->serialize($charset_encoding) .
                    "</param>\n</params>";
            }
        }
        $result .= "\n</methodResponse>";
        $this->payload = $result;
        return $result;
    }
}
