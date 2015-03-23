<?php

namespace PhpXmlRpc;

use PhpXmlRpc\Helper\Charset;

class Value
{
    public static $xmlrpcI4 = "i4";
    public static $xmlrpcInt = "int";
    public static $xmlrpcBoolean = "boolean";
    public static $xmlrpcDouble = "double";
    public static $xmlrpcString = "string";
    public static $xmlrpcDateTime = "dateTime.iso8601";
    public static $xmlrpcBase64 = "base64";
    public static $xmlrpcArray = "array";
    public static $xmlrpcStruct = "struct";
    public static $xmlrpcValue = "undefined";
    public static $xmlrpcNull = "null";

    public static $xmlrpcTypes = array(
        "i4" => 1,
        "int" => 1,
        "boolean" => 1,
        "double" => 1,
        "string" => 1,
        "dateTime.iso8601" => 1,
        "base64" => 1,
        "array" => 2,
        "struct" => 3,
        "null" => 1,
    );

    /// @todo: does these need to be public?
    public $me = array();
    public $mytype = 0;
    public $_php_class = null;

    /**
     * @param mixed $val
     * @param string $type any valid xmlrpc type name (lowercase). If null, 'string' is assumed
     */
    public function __construct($val = -1, $type = '')
    {
        /// @todo: optimization creep - do not call addXX, do it all inline.
        /// downside: booleans will not be coerced anymore
        if ($val !== -1 || $type != '') {
            // optimization creep: inlined all work done by constructor
            switch ($type) {
                case '':
                    $this->mytype = 1;
                    $this->me['string'] = $val;
                    break;
                case 'i4':
                case 'int':
                case 'double':
                case 'string':
                case 'boolean':
                case 'dateTime.iso8601':
                case 'base64':
                case 'null':
                    $this->mytype = 1;
                    $this->me[$type] = $val;
                    break;
                case 'array':
                    $this->mytype = 2;
                    $this->me['array'] = $val;
                    break;
                case 'struct':
                    $this->mytype = 3;
                    $this->me['struct'] = $val;
                    break;
                default:
                    error_log("XML-RPC: " . __METHOD__ . ": not a known type ($type)");
            }
            /*if($type=='')
            {
                $type='string';
            }
            if(static::$xmlrpcTypes[$type]==1)
            {
                $this->addScalar($val,$type);
            }
            elseif(static::$xmlrpcTypes[$type]==2)
            {
                $this->addArray($val);
            }
            elseif(static::$xmlrpcTypes[$type]==3)
            {
                $this->addStruct($val);
            }*/
        }
    }

    /**
     * Add a single php value to an (unitialized) xmlrpcval.
     *
     * @param mixed $val
     * @param string $type
     *
     * @return int 1 or 0 on failure
     */
    public function addScalar($val, $type = 'string')
    {
        $typeof = null;
        if (isset(static::$xmlrpcTypes[$type])) {
            $typeof = static::$xmlrpcTypes[$type];
        }

        if ($typeof != 1) {
            error_log("XML-RPC: " . __METHOD__ . ": not a scalar type ($type)");

            return 0;
        }

        // coerce booleans into correct values
        // NB: we should either do it for datetimes, integers and doubles, too,
        // or just plain remove this check, implemented on booleans only...
        if ($type == static::$xmlrpcBoolean) {
            if (strcasecmp($val, 'true') == 0 || $val == 1 || ($val == true && strcasecmp($val, 'false'))) {
                $val = true;
            } else {
                $val = false;
            }
        }

        switch ($this->mytype) {
            case 1:
                error_log('XML-RPC: ' . __METHOD__ . ': scalar xmlrpc value can have only one value');

                return 0;
            case 3:
                error_log('XML-RPC: ' . __METHOD__ . ': cannot add anonymous scalar to struct xmlrpc value');

                return 0;
            case 2:
                // we're adding a scalar value to an array here
                //$ar=$this->me['array'];
                //$ar[]=new Value($val, $type);
                //$this->me['array']=$ar;
                // Faster (?) avoid all the costly array-copy-by-val done here...
                $this->me['array'][] = new Value($val, $type);

                return 1;
            default:
                // a scalar, so set the value and remember we're scalar
                $this->me[$type] = $val;
                $this->mytype = $typeof;

                return 1;
        }
    }

    /**
     * Add an array of xmlrpcval objects to an xmlrpcval.
     *
     * @param Value[] $vals
     *
     * @return int 1 or 0 on failure
     *
     * @todo add some checking for $vals to be an array of xmlrpcvals?
     */
    public function addArray($vals)
    {
        if ($this->mytype == 0) {
            $this->mytype = static::$xmlrpcTypes['array'];
            $this->me['array'] = $vals;

            return 1;
        } elseif ($this->mytype == 2) {
            // we're adding to an array here
            $this->me['array'] = array_merge($this->me['array'], $vals);

            return 1;
        } else {
            error_log('XML-RPC: ' . __METHOD__ . ': already initialized as a [' . $this->kindOf() . ']');

            return 0;
        }
    }

    /**
     * Add an array of named xmlrpcval objects to an xmlrpcval.
     *
     * @param Value[] $vals
     *
     * @return int 1 or 0 on failure
     *
     * @todo add some checking for $vals to be an array?
     */
    public function addStruct($vals)
    {
        if ($this->mytype == 0) {
            $this->mytype = static::$xmlrpcTypes['struct'];
            $this->me['struct'] = $vals;

            return 1;
        } elseif ($this->mytype == 3) {
            // we're adding to a struct here
            $this->me['struct'] = array_merge($this->me['struct'], $vals);

            return 1;
        } else {
            error_log('XML-RPC: ' . __METHOD__ . ': already initialized as a [' . $this->kindOf() . ']');

            return 0;
        }
    }

    /**
     * Returns a string containing "struct", "array" or "scalar" describing the base type of the value.
     *
     * @return string
     */
    public function kindOf()
    {
        switch ($this->mytype) {
            case 3:
                return 'struct';
                break;
            case 2:
                return 'array';
                break;
            case 1:
                return 'scalar';
                break;
            default:
                return 'undef';
        }
    }

    protected function serializedata($typ, $val, $charset_encoding = '')
    {
        $rs = '';

        if (!isset(static::$xmlrpcTypes[$typ])) {
            return $rs;
        }

        switch (static::$xmlrpcTypes[$typ]) {
            case 1:
                switch ($typ) {
                    case static::$xmlrpcBase64:
                        $rs .= "<${typ}>" . base64_encode($val) . "</${typ}>";
                        break;
                    case static::$xmlrpcBoolean:
                        $rs .= "<${typ}>" . ($val ? '1' : '0') . "</${typ}>";
                        break;
                    case static::$xmlrpcString:
                        // G. Giunta 2005/2/13: do NOT use htmlentities, since
                        // it will produce named html entities, which are invalid xml
                        $rs .= "<${typ}>" . Charset::instance()->encode_entities($val, PhpXmlRpc::$xmlrpc_internalencoding, $charset_encoding) . "</${typ}>";
                        break;
                    case static::$xmlrpcInt:
                    case static::$xmlrpcI4:
                        $rs .= "<${typ}>" . (int)$val . "</${typ}>";
                        break;
                    case static::$xmlrpcDouble:
                        // avoid using standard conversion of float to string because it is locale-dependent,
                        // and also because the xmlrpc spec forbids exponential notation.
                        // sprintf('%F') could be most likely ok but it fails eg. on 2e-14.
                        // The code below tries its best at keeping max precision while avoiding exp notation,
                        // but there is of course no limit in the number of decimal places to be used...
                        $rs .= "<${typ}>" . preg_replace('/\\.?0+$/', '', number_format((double)$val, 128, '.', '')) . "</${typ}>";
                        break;
                    case static::$xmlrpcDateTime:
                        if (is_string($val)) {
                            $rs .= "<${typ}>${val}</${typ}>";
                        } elseif (is_a($val, 'DateTime')) {
                            $rs .= "<${typ}>" . $val->format('Ymd\TH:i:s') . "</${typ}>";
                        } elseif (is_int($val)) {
                            $rs .= "<${typ}>" . strftime("%Y%m%dT%H:%M:%S", $val) . "</${typ}>";
                        } else {
                            // not really a good idea here: but what shall we output anyway? left for backward compat...
                            $rs .= "<${typ}>${val}</${typ}>";
                        }
                        break;
                    case static::$xmlrpcNull:
                        if (PhpXmlRpc::$xmlrpc_null_apache_encoding) {
                            $rs .= "<ex:nil/>";
                        } else {
                            $rs .= "<nil/>";
                        }
                        break;
                    default:
                        // no standard type value should arrive here, but provide a possibility
                        // for xmlrpcvals of unknown type...
                        $rs .= "<${typ}>${val}</${typ}>";
                }
                break;
            case 3:
                // struct
                if ($this->_php_class) {
                    $rs .= '<struct php_class="' . $this->_php_class . "\">\n";
                } else {
                    $rs .= "<struct>\n";
                }
                $charsetEncoder = Charset::instance();
                foreach ($val as $key2 => $val2) {
                    $rs .= '<member><name>' . $charsetEncoder->encode_entities($key2, PhpXmlRpc::$xmlrpc_internalencoding, $charset_encoding) . "</name>\n";
                    //$rs.=$this->serializeval($val2);
                    $rs .= $val2->serialize($charset_encoding);
                    $rs .= "</member>\n";
                }
                $rs .= '</struct>';
                break;
            case 2:
                // array
                $rs .= "<array>\n<data>\n";
                foreach ($val as $element) {
                    //$rs.=$this->serializeval($val[$i]);
                    $rs .= $element->serialize($charset_encoding);
                }
                $rs .= "</data>\n</array>";
                break;
            default:
                break;
        }

        return $rs;
    }

    /**
     * Returns xml representation of the value. XML prologue not included.
     *
     * @param string $charset_encoding the charset to be used for serialization. if null, US-ASCII is assumed
     *
     * @return string
     */
    public function serialize($charset_encoding = '')
    {
        // add check? slower, but helps to avoid recursion in serializing broken xmlrpcvals...
        //if (is_object($o) && (get_class($o) == 'xmlrpcval' || is_subclass_of($o, 'xmlrpcval')))
        //{
        reset($this->me);
        list($typ, $val) = each($this->me);

        return '<value>' . $this->serializedata($typ, $val, $charset_encoding) . "</value>\n";
        //}
    }

    /**
     * Checks whether a struct member with a given name is present.
     * Works only on xmlrpcvals of type struct.
     *
     * @param string $m the name of the struct member to be looked up
     *
     * @return boolean
     */
    public function structmemexists($m)
    {
        return array_key_exists($m, $this->me['struct']);
    }

    /**
     * Returns the value of a given struct member (an xmlrpcval object in itself).
     * Will raise a php warning if struct member of given name does not exist.
     *
     * @param string $m the name of the struct member to be looked up
     *
     * @return Value
     */
    public function structmem($m)
    {
        return $this->me['struct'][$m];
    }

    /**
     * Reset internal pointer for xmlrpcvals of type struct.
     */
    public function structreset()
    {
        reset($this->me['struct']);
    }

    /**
     * Return next member element for xmlrpcvals of type struct.
     *
     * @return xmlrpcval
     */
    public function structeach()
    {
        return each($this->me['struct']);
    }

    /**
     * Returns the value of a scalar xmlrpcval.
     *
     * @return mixed
     */
    public function scalarval()
    {
        reset($this->me);
        list(, $b) = each($this->me);

        return $b;
    }

    /**
     * Returns the type of the xmlrpcval.
     * For integers, 'int' is always returned in place of 'i4'.
     *
     * @return string
     */
    public function scalartyp()
    {
        reset($this->me);
        list($a,) = each($this->me);
        if ($a == static::$xmlrpcI4) {
            $a = static::$xmlrpcInt;
        }

        return $a;
    }

    /**
     * Returns the m-th member of an xmlrpcval of struct type.
     *
     * @param integer $m the index of the value to be retrieved (zero based)
     *
     * @return Value
     */
    public function arraymem($m)
    {
        return $this->me['array'][$m];
    }

    /**
     * Returns the number of members in an xmlrpcval of array type.
     *
     * @return integer
     */
    public function arraysize()
    {
        return count($this->me['array']);
    }

    /**
     * Returns the number of members in an xmlrpcval of struct type.
     *
     * @return integer
     */
    public function structsize()
    {
        return count($this->me['struct']);
    }
}
