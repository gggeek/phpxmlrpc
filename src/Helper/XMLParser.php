<?php

namespace PhpXmlRpc\Helper;

use PhpXmlRpc\PhpXmlRpc;
use PhpXmlRpc\Value;

/**
 * Deals with parsing the XML.
 */
class XMLParser
{
    // used to store state during parsing
    // quick explanation of components:
    //   ac - used to accumulate values
    //   stack - array with genealogy of xml elements names:
    //           used to validate nesting of xmlrpc elements
    //   valuestack - array used for parsing arrays and structs
    //   lv - used to indicate "looking for a value": implements
    //        the logic to allow values with no types to be strings
    //   isf - used to indicate a parsing fault (2) or xmlrpcresp fault (1)
    //   isf_reason - used for storing xmlrpcresp fault string
    //   method - used to store method name
    //   params - used to store parameters in method calls
    //   pt - used to store the type of each received parameter. Useful if parameters are automatically decoded to php values
    //   rt  - 'methodcall or 'methodresponse'
    public $_xh = array(
        'ac' => '',
        'stack' => array(),
        'valuestack' => array(),
        'isf' => 0,
        'isf_reason' => '',
        'method' => false, // so we can check later if we got a methodname or not
        'params' => array(),
        'pt' => array(),
        'rt' => '',
    );

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
        'EX:NIL' => array('VALUE'), // only used when extension activated
    );

    /**
     * xml parser handler function for opening element tags.
     */
    public function xmlrpc_se($parser, $name, $attrs, $accept_single_vals = false)
    {
        // if invalid xmlrpc already detected, skip all processing
        if ($this->_xh['isf'] < 2) {
            // check for correct element nesting
            // top level element can only be of 2 types
            /// @todo optimization creep: save this check into a bool variable, instead of using count() every time:
            ///       there is only a single top level element in xml anyway
            if (count($this->_xh['stack']) == 0) {
                if ($name != 'METHODRESPONSE' && $name != 'METHODCALL' && (
                        $name != 'VALUE' && !$accept_single_vals)
                ) {
                    $this->_xh['isf'] = 2;
                    $this->_xh['isf_reason'] = 'missing top level xmlrpc element';

                    return;
                } else {
                    $this->_xh['rt'] = strtolower($name);
                }
            } else {
                // not top level element: see if parent is OK
                $parent = end($this->_xh['stack']);
                if (!array_key_exists($name, $this->xmlrpc_valid_parents) || !in_array($parent, $this->xmlrpc_valid_parents[$name])) {
                    $this->_xh['isf'] = 2;
                    $this->_xh['isf_reason'] = "xmlrpc element $name cannot be child of $parent";

                    return;
                }
            }

            switch ($name) {
                // optimize for speed switch cases: most common cases first
                case 'VALUE':
                    /// @todo we could check for 2 VALUE elements inside a MEMBER or PARAM element
                    $this->_xh['vt'] = 'value'; // indicator: no value found yet
                    $this->_xh['ac'] = '';
                    $this->_xh['lv'] = 1;
                    $this->_xh['php_class'] = null;
                    break;
                case 'I4':
                case 'INT':
                case 'STRING':
                case 'BOOLEAN':
                case 'DOUBLE':
                case 'DATETIME.ISO8601':
                case 'BASE64':
                    if ($this->_xh['vt'] != 'value') {
                        //two data elements inside a value: an error occurred!
                        $this->_xh['isf'] = 2;
                        $this->_xh['isf_reason'] = "$name element following a {$this->_xh['vt']} element inside a single value";

                        return;
                    }
                    $this->_xh['ac'] = ''; // reset the accumulator
                    break;
                case 'STRUCT':
                case 'ARRAY':
                    if ($this->_xh['vt'] != 'value') {
                        //two data elements inside a value: an error occurred!
                        $this->_xh['isf'] = 2;
                        $this->_xh['isf_reason'] = "$name element following a {$this->_xh['vt']} element inside a single value";

                        return;
                    }
                    // create an empty array to hold child values, and push it onto appropriate stack
                    $cur_val = array();
                    $cur_val['values'] = array();
                    $cur_val['type'] = $name;
                    // check for out-of-band information to rebuild php objs
                    // and in case it is found, save it
                    if (@isset($attrs['PHP_CLASS'])) {
                        $cur_val['php_class'] = $attrs['PHP_CLASS'];
                    }
                    $this->_xh['valuestack'][] = $cur_val;
                    $this->_xh['vt'] = 'data'; // be prepared for a data element next
                    break;
                case 'DATA':
                    if ($this->_xh['vt'] != 'data') {
                        //two data elements inside a value: an error occurred!
                        $this->_xh['isf'] = 2;
                        $this->_xh['isf_reason'] = "found two data elements inside an array element";

                        return;
                    }
                case 'METHODCALL':
                case 'METHODRESPONSE':
                case 'PARAMS':
                    // valid elements that add little to processing
                    break;
                case 'METHODNAME':
                case 'NAME':
                    /// @todo we could check for 2 NAME elements inside a MEMBER element
                    $this->_xh['ac'] = '';
                    break;
                case 'FAULT':
                    $this->_xh['isf'] = 1;
                    break;
                case 'MEMBER':
                    $this->_xh['valuestack'][count($this->_xh['valuestack']) - 1]['name'] = ''; // set member name to null, in case we do not find in the xml later on
                    //$this->_xh['ac']='';
                // Drop trough intentionally
                case 'PARAM':
                    // clear value type, so we can check later if no value has been passed for this param/member
                    $this->_xh['vt'] = null;
                    break;
                case 'NIL':
                case 'EX:NIL':
                    if (PhpXmlRpc::$xmlrpc_null_extension) {
                        if ($this->_xh['vt'] != 'value') {
                            //two data elements inside a value: an error occurred!
                            $this->_xh['isf'] = 2;
                            $this->_xh['isf_reason'] = "$name element following a {$this->_xh['vt']} element inside a single value";

                            return;
                        }
                        $this->_xh['ac'] = ''; // reset the accumulator
                        break;
                    }
                // we do not support the <NIL/> extension, so
                // drop through intentionally
                default:
                    /// INVALID ELEMENT: RAISE ISF so that it is later recognized!!!
                    $this->_xh['isf'] = 2;
                    $this->_xh['isf_reason'] = "found not-xmlrpc xml element $name";
                    break;
            }

            // Save current element name to stack, to validate nesting
            $this->_xh['stack'][] = $name;

            /// @todo optimization creep: move this inside the big switch() above
            if ($name != 'VALUE') {
                $this->_xh['lv'] = 0;
            }
        }
    }

    /**
     * Used in decoding xml chunks that might represent single xmlrpc values.
     */
    public function xmlrpc_se_any($parser, $name, $attrs)
    {
        $this->xmlrpc_se($parser, $name, $attrs, true);
    }

    /**
     * xml parser handler function for close element tags.
     */
    public function xmlrpc_ee($parser, $name, $rebuild_xmlrpcvals = true)
    {
        if ($this->_xh['isf'] < 2) {
            // push this element name from stack
            // NB: if XML validates, correct opening/closing is guaranteed and
            // we do not have to check for $name == $curr_elem.
            // we also checked for proper nesting at start of elements...
            $curr_elem = array_pop($this->_xh['stack']);

            switch ($name) {
                case 'VALUE':
                    // This if() detects if no scalar was inside <VALUE></VALUE>
                    if ($this->_xh['vt'] == 'value') {
                        $this->_xh['value'] = $this->_xh['ac'];
                        $this->_xh['vt'] = Value::$xmlrpcString;
                    }

                    if ($rebuild_xmlrpcvals) {
                        // build the xmlrpc val out of the data received, and substitute it
                        $temp = new Value($this->_xh['value'], $this->_xh['vt']);
                        // in case we got info about underlying php class, save it
                        // in the object we're rebuilding
                        if (isset($this->_xh['php_class'])) {
                            $temp->_php_class = $this->_xh['php_class'];
                        }
                        // check if we are inside an array or struct:
                        // if value just built is inside an array, let's move it into array on the stack
                        $vscount = count($this->_xh['valuestack']);
                        if ($vscount && $this->_xh['valuestack'][$vscount - 1]['type'] == 'ARRAY') {
                            $this->_xh['valuestack'][$vscount - 1]['values'][] = $temp;
                        } else {
                            $this->_xh['value'] = $temp;
                        }
                    } else {
                        /// @todo this needs to treat correctly php-serialized objects,
                        /// since std deserializing is done by php_xmlrpc_decode,
                        /// which we will not be calling...
                        if (isset($this->_xh['php_class'])) {
                        }

                        // check if we are inside an array or struct:
                        // if value just built is inside an array, let's move it into array on the stack
                        $vscount = count($this->_xh['valuestack']);
                        if ($vscount && $this->_xh['valuestack'][$vscount - 1]['type'] == 'ARRAY') {
                            $this->_xh['valuestack'][$vscount - 1]['values'][] = $this->_xh['value'];
                        }
                    }
                    break;
                case 'BOOLEAN':
                case 'I4':
                case 'INT':
                case 'STRING':
                case 'DOUBLE':
                case 'DATETIME.ISO8601':
                case 'BASE64':
                    $this->_xh['vt'] = strtolower($name);
                    /// @todo: optimization creep - remove the if/elseif cycle below
                    /// since the case() in which we are already did that
                    if ($name == 'STRING') {
                        $this->_xh['value'] = $this->_xh['ac'];
                    } elseif ($name == 'DATETIME.ISO8601') {
                        if (!preg_match('/^[0-9]{8}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $this->_xh['ac'])) {
                            error_log('XML-RPC: invalid value received in DATETIME: ' . $this->_xh['ac']);
                        }
                        $this->_xh['vt'] = Value::$xmlrpcDateTime;
                        $this->_xh['value'] = $this->_xh['ac'];
                    } elseif ($name == 'BASE64') {
                        /// @todo check for failure of base64 decoding / catch warnings
                        $this->_xh['value'] = base64_decode($this->_xh['ac']);
                    } elseif ($name == 'BOOLEAN') {
                        // special case here: we translate boolean 1 or 0 into PHP
                        // constants true or false.
                        // Strings 'true' and 'false' are accepted, even though the
                        // spec never mentions them (see eg. Blogger api docs)
                        // NB: this simple checks helps a lot sanitizing input, ie no
                        // security problems around here
                        if ($this->_xh['ac'] == '1' || strcasecmp($this->_xh['ac'], 'true') == 0) {
                            $this->_xh['value'] = true;
                        } else {
                            // log if receiving something strange, even though we set the value to false anyway
                            if ($this->_xh['ac'] != '0' && strcasecmp($this->_xh['ac'], 'false') != 0) {
                                error_log('XML-RPC: invalid value received in BOOLEAN: ' . $this->_xh['ac']);
                            }
                            $this->_xh['value'] = false;
                        }
                    } elseif ($name == 'DOUBLE') {
                        // we have a DOUBLE
                        // we must check that only 0123456789-.<space> are characters here
                        // NOTE: regexp could be much stricter than this...
                        if (!preg_match('/^[+-eE0123456789 \t.]+$/', $this->_xh['ac'])) {
                            /// @todo: find a better way of throwing an error than this!
                            error_log('XML-RPC: non numeric value received in DOUBLE: ' . $this->_xh['ac']);
                            $this->_xh['value'] = 'ERROR_NON_NUMERIC_FOUND';
                        } else {
                            // it's ok, add it on
                            $this->_xh['value'] = (double)$this->_xh['ac'];
                        }
                    } else {
                        // we have an I4/INT
                        // we must check that only 0123456789-<space> are characters here
                        if (!preg_match('/^[+-]?[0123456789 \t]+$/', $this->_xh['ac'])) {
                            /// @todo find a better way of throwing an error than this!
                            error_log('XML-RPC: non numeric value received in INT: ' . $this->_xh['ac']);
                            $this->_xh['value'] = 'ERROR_NON_NUMERIC_FOUND';
                        } else {
                            // it's ok, add it on
                            $this->_xh['value'] = (int)$this->_xh['ac'];
                        }
                    }
                    //$this->_xh['ac']=''; // is this necessary?
                    $this->_xh['lv'] = 3; // indicate we've found a value
                    break;
                case 'NAME':
                    $this->_xh['valuestack'][count($this->_xh['valuestack']) - 1]['name'] = $this->_xh['ac'];
                    break;
                case 'MEMBER':
                    //$this->_xh['ac']=''; // is this necessary?
                    // add to array in the stack the last element built,
                    // unless no VALUE was found
                    if ($this->_xh['vt']) {
                        $vscount = count($this->_xh['valuestack']);
                        $this->_xh['valuestack'][$vscount - 1]['values'][$this->_xh['valuestack'][$vscount - 1]['name']] = $this->_xh['value'];
                    } else {
                        error_log('XML-RPC: missing VALUE inside STRUCT in received xml');
                    }
                    break;
                case 'DATA':
                    //$this->_xh['ac']=''; // is this necessary?
                    $this->_xh['vt'] = null; // reset this to check for 2 data elements in a row - even if they're empty
                    break;
                case 'STRUCT':
                case 'ARRAY':
                    // fetch out of stack array of values, and promote it to current value
                    $curr_val = array_pop($this->_xh['valuestack']);
                    $this->_xh['value'] = $curr_val['values'];
                    $this->_xh['vt'] = strtolower($name);
                    if (isset($curr_val['php_class'])) {
                        $this->_xh['php_class'] = $curr_val['php_class'];
                    }
                    break;
                case 'PARAM':
                    // add to array of params the current value,
                    // unless no VALUE was found
                    if ($this->_xh['vt']) {
                        $this->_xh['params'][] = $this->_xh['value'];
                        $this->_xh['pt'][] = $this->_xh['vt'];
                    } else {
                        error_log('XML-RPC: missing VALUE inside PARAM in received xml');
                    }
                    break;
                case 'METHODNAME':
                    $this->_xh['method'] = preg_replace('/^[\n\r\t ]+/', '', $this->_xh['ac']);
                    break;
                case 'NIL':
                case 'EX:NIL':
                    if (PhpXmlRpc::$xmlrpc_null_extension) {
                        $this->_xh['vt'] = 'null';
                        $this->_xh['value'] = null;
                        $this->_xh['lv'] = 3;
                        break;
                    }
                // drop through intentionally if nil extension not enabled
                case 'PARAMS':
                case 'FAULT':
                case 'METHODCALL':
                case 'METHORESPONSE':
                    break;
                default:
                    // End of INVALID ELEMENT!
                    // shall we add an assert here for unreachable code???
                    break;
            }
        }
    }

    /**
     * Used in decoding xmlrpc requests/responses without rebuilding xmlrpc Values.
     */
    public function xmlrpc_ee_fast($parser, $name)
    {
        $this->xmlrpc_ee($parser, $name, false);
    }

    /**
     * xml parser handler function for character data.
     */
    public function xmlrpc_cd($parser, $data)
    {
        // skip processing if xml fault already detected
        if ($this->_xh['isf'] < 2) {
            // "lookforvalue==3" means that we've found an entire value
            // and should discard any further character data
            if ($this->_xh['lv'] != 3) {
                // G. Giunta 2006-08-23: useless change of 'lv' from 1 to 2
                //if($this->_xh['lv']==1)
                //{
                // if we've found text and we're just in a <value> then
                // say we've found a value
                //$this->_xh['lv']=2;
                //}
                // we always initialize the accumulator before starting parsing, anyway...
                //if(!@isset($this->_xh['ac']))
                //{
                //    $this->_xh['ac'] = '';
                //}
                $this->_xh['ac'] .= $data;
            }
        }
    }

    /**
     * xml parser handler function for 'other stuff', ie. not char data or
     * element start/end tag. In fact it only gets called on unknown entities...
     */
    public function xmlrpc_dh($parser, $data)
    {
        // skip processing if xml fault already detected
        if ($this->_xh['isf'] < 2) {
            if (substr($data, 0, 1) == '&' && substr($data, -1, 1) == ';') {
                // G. Giunta 2006-08-25: useless change of 'lv' from 1 to 2
                //if($this->_xh['lv']==1)
                //{
                //    $this->_xh['lv']=2;
                //}
                $this->_xh['ac'] .= $data;
            }
        }

        return true;
    }
}
