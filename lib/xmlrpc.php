<?php
// by Edd Dumbill (C) 1999-2002
// <edd@usefulinc.com>

// Copyright (c) 1999,2000,2002 Edd Dumbill.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "XML-RPC for PHP" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

require_once __DIR__ . "/phpxmlrpc.php";
require_once __DIR__ . "/xmlrpc_client.php";
require_once __DIR__ . "/xmlrpcresp.php";
require_once __DIR__ . "/xmlrpcmsg.php";
require_once __DIR__ . "/xmlrpcval.php";

/**
 * Convert a string to the correct XML representation in a target charset
 * To help correct communication of non-ascii chars inside strings, regardless
 * of the charset used when sending requests, parsing them, sending responses
 * and parsing responses, an option is to convert all non-ascii chars present in the message
 * into their equivalent 'charset entity'. Charset entities enumerated this way
 * are independent of the charset encoding used to transmit them, and all XML
 * parsers are bound to understand them.
 * Note that in the std case we are not sending a charset encoding mime type
 * along with http headers, so we are bound by RFC 3023 to emit strict us-ascii.
 *
 * @todo do a bit of basic benchmarking (strtr vs. str_replace)
 * @todo	make usage of iconv() or recode_string() or mb_string() where available
 */
function xmlrpc_encode_entitites($data, $src_encoding='', $dest_encoding='')
{
    $xmlrpc = Phpxmlrpc::instance();
    if ($src_encoding == '')
    {
        // lame, but we know no better...
        $src_encoding = $xmlrpc->xmlrpc_internalencoding;
    }

    switch(strtoupper($src_encoding.'_'.$dest_encoding))
    {
        case 'ISO-8859-1_':
        case 'ISO-8859-1_US-ASCII':
            $escaped_data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
            $escaped_data = str_replace($xmlrpc->xml_iso88591_Entities['in'], $xmlrpc->xml_iso88591_Entities['out'], $escaped_data);
            break;
        case 'ISO-8859-1_UTF-8':
            $escaped_data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
            $escaped_data = utf8_encode($escaped_data);
            break;
        case 'ISO-8859-1_ISO-8859-1':
        case 'US-ASCII_US-ASCII':
        case 'US-ASCII_UTF-8':
        case 'US-ASCII_':
        case 'US-ASCII_ISO-8859-1':
        case 'UTF-8_UTF-8':
        //case 'CP1252_CP1252':
            $escaped_data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
            break;
        case 'UTF-8_':
        case 'UTF-8_US-ASCII':
        case 'UTF-8_ISO-8859-1':
// NB: this will choke on invalid UTF-8, going most likely beyond EOF
$escaped_data = '';
// be kind to users creating string xmlrpcvals out of different php types
$data = (string) $data;
$ns = strlen ($data);
for ($nn = 0; $nn < $ns; $nn++)
{
    $ch = $data[$nn];
    $ii = ord($ch);
    //1 7 0bbbbbbb (127)
    if ($ii < 128)
    {
        /// @todo shall we replace this with a (supposedly) faster str_replace?
        switch($ii){
            case 34:
                $escaped_data .= '&quot;';
                break;
            case 38:
                $escaped_data .= '&amp;';
                break;
            case 39:
                $escaped_data .= '&apos;';
                break;
            case 60:
                $escaped_data .= '&lt;';
                break;
            case 62:
                $escaped_data .= '&gt;';
                break;
            default:
                $escaped_data .= $ch;
        } // switch
    }
    //2 11 110bbbbb 10bbbbbb (2047)
    else if ($ii>>5 == 6)
    {
        $b1 = ($ii & 31);
        $ii = ord($data[$nn+1]);
        $b2 = ($ii & 63);
        $ii = ($b1 * 64) + $b2;
        $ent = sprintf ('&#%d;', $ii);
        $escaped_data .= $ent;
        $nn += 1;
    }
    //3 16 1110bbbb 10bbbbbb 10bbbbbb
    else if ($ii>>4 == 14)
    {
        $b1 = ($ii & 15);
        $ii = ord($data[$nn+1]);
        $b2 = ($ii & 63);
        $ii = ord($data[$nn+2]);
        $b3 = ($ii & 63);
        $ii = ((($b1 * 64) + $b2) * 64) + $b3;
        $ent = sprintf ('&#%d;', $ii);
        $escaped_data .= $ent;
        $nn += 2;
    }
    //4 21 11110bbb 10bbbbbb 10bbbbbb 10bbbbbb
    else if ($ii>>3 == 30)
    {
        $b1 = ($ii & 7);
        $ii = ord($data[$nn+1]);
        $b2 = ($ii & 63);
        $ii = ord($data[$nn+2]);
        $b3 = ($ii & 63);
        $ii = ord($data[$nn+3]);
        $b4 = ($ii & 63);
        $ii = ((((($b1 * 64) + $b2) * 64) + $b3) * 64) + $b4;
        $ent = sprintf ('&#%d;', $ii);
        $escaped_data .= $ent;
        $nn += 3;
    }
}
            break;
/*
        case 'CP1252_':
        case 'CP1252_US-ASCII':
            $escaped_data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
            $escaped_data = str_replace($GLOBALS['xml_iso88591_Entities']['in'], $GLOBALS['xml_iso88591_Entities']['out'], $escaped_data);
            $escaped_data = str_replace($GLOBALS['xml_cp1252_Entities']['in'], $GLOBALS['xml_cp1252_Entities']['out'], $escaped_data);
            break;
        case 'CP1252_UTF-8':
            $escaped_data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
            /// @todo we could use real UTF8 chars here instead of xml entities... (note that utf_8 encode all allone will NOT convert them)
            $escaped_data = str_replace($GLOBALS['xml_cp1252_Entities']['in'], $GLOBALS['xml_cp1252_Entities']['out'], $escaped_data);
            $escaped_data = utf8_encode($escaped_data);
            break;
        case 'CP1252_ISO-8859-1':
            $escaped_data = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $data);
            // we might as well replave all funky chars with a '?' here, but we are kind and leave it to the receiving application layer to decide what to do with these weird entities...
            $escaped_data = str_replace($GLOBALS['xml_cp1252_Entities']['in'], $GLOBALS['xml_cp1252_Entities']['out'], $escaped_data);
            break;
*/
        default:
            $escaped_data = '';
            error_log("Converting from $src_encoding to $dest_encoding: not supported...");
    }
    return $escaped_data;
}

/// xml parser handler function for opening element tags
function xmlrpc_se($parser, $name, $attrs, $accept_single_vals=false)
{
    $xmlrpc = Phpxmlrpc::instance();
    // if invalid xmlrpc already detected, skip all processing
    if ($xmlrpc->_xh['isf'] < 2)
    {
        // check for correct element nesting
        // top level element can only be of 2 types
        /// @todo optimization creep: save this check into a bool variable, instead of using count() every time:
        ///       there is only a single top level element in xml anyway
        if (count($xmlrpc->_xh['stack']) == 0)
        {
            if ($name != 'METHODRESPONSE' && $name != 'METHODCALL' && (
                $name != 'VALUE' && !$accept_single_vals))
            {
                $xmlrpc->_xh['isf'] = 2;
                $xmlrpc->_xh['isf_reason'] = 'missing top level xmlrpc element';
                return;
            }
            else
            {
                $xmlrpc->_xh['rt'] = strtolower($name);
                $xmlrpc->_xh['rt'] = strtolower($name);
            }
        }
        else
        {
            // not top level element: see if parent is OK
            $parent = end($xmlrpc->_xh['stack']);
            if (!array_key_exists($name, $xmlrpc->xmlrpc_valid_parents) || !in_array($parent, $xmlrpc->xmlrpc_valid_parents[$name]))
            {
                $xmlrpc->_xh['isf'] = 2;
                $xmlrpc->_xh['isf_reason'] = "xmlrpc element $name cannot be child of $parent";
                return;
            }
        }

        switch($name)
        {
            // optimize for speed switch cases: most common cases first
            case 'VALUE':
                /// @todo we could check for 2 VALUE elements inside a MEMBER or PARAM element
                $xmlrpc->_xh['vt']='value'; // indicator: no value found yet
                $xmlrpc->_xh['ac']='';
                $xmlrpc->_xh['lv']=1;
                $xmlrpc->_xh['php_class']=null;
                break;
            case 'I4':
            case 'INT':
            case 'STRING':
            case 'BOOLEAN':
            case 'DOUBLE':
            case 'DATETIME.ISO8601':
            case 'BASE64':
                if ($xmlrpc->_xh['vt']!='value')
                {
                    //two data elements inside a value: an error occurred!
                    $xmlrpc->_xh['isf'] = 2;
                    $xmlrpc->_xh['isf_reason'] = "$name element following a {$xmlrpc->_xh['vt']} element inside a single value";
                    return;
                }
                $xmlrpc->_xh['ac']=''; // reset the accumulator
                break;
            case 'STRUCT':
            case 'ARRAY':
                if ($xmlrpc->_xh['vt']!='value')
                {
                    //two data elements inside a value: an error occurred!
                    $xmlrpc->_xh['isf'] = 2;
                    $xmlrpc->_xh['isf_reason'] = "$name element following a {$xmlrpc->_xh['vt']} element inside a single value";
                    return;
                }
                // create an empty array to hold child values, and push it onto appropriate stack
                $cur_val = array();
                $cur_val['values'] = array();
                $cur_val['type'] = $name;
                // check for out-of-band information to rebuild php objs
                // and in case it is found, save it
                if (@isset($attrs['PHP_CLASS']))
                {
                    $cur_val['php_class'] = $attrs['PHP_CLASS'];
                }
                $xmlrpc->_xh['valuestack'][] = $cur_val;
                $xmlrpc->_xh['vt']='data'; // be prepared for a data element next
                break;
            case 'DATA':
                if ($xmlrpc->_xh['vt']!='data')
                {
                    //two data elements inside a value: an error occurred!
                    $xmlrpc->_xh['isf'] = 2;
                    $xmlrpc->_xh['isf_reason'] = "found two data elements inside an array element";
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
                $xmlrpc->_xh['ac']='';
                break;
            case 'FAULT':
                $xmlrpc->_xh['isf']=1;
                break;
            case 'MEMBER':
                $xmlrpc->_xh['valuestack'][count($xmlrpc->_xh['valuestack'])-1]['name']=''; // set member name to null, in case we do not find in the xml later on
                //$xmlrpc->_xh['ac']='';
                // Drop trough intentionally
            case 'PARAM':
                // clear value type, so we can check later if no value has been passed for this param/member
                $xmlrpc->_xh['vt']=null;
                break;
            case 'NIL':
            case 'EX:NIL':
                if ($xmlrpc->xmlrpc_null_extension)
                {
                    if ($xmlrpc->_xh['vt']!='value')
                    {
                        //two data elements inside a value: an error occurred!
                        $xmlrpc->_xh['isf'] = 2;
                        $xmlrpc->_xh['isf_reason'] = "$name element following a {$xmlrpc->_xh['vt']} element inside a single value";
                        return;
                    }
                    $xmlrpc->_xh['ac']=''; // reset the accumulator
                    break;
                }
                // we do not support the <NIL/> extension, so
                // drop through intentionally
            default:
                /// INVALID ELEMENT: RAISE ISF so that it is later recognized!!!
                $xmlrpc->_xh['isf'] = 2;
                $xmlrpc->_xh['isf_reason'] = "found not-xmlrpc xml element $name";
                break;
        }

        // Save current element name to stack, to validate nesting
        $xmlrpc->_xh['stack'][] = $name;

        /// @todo optimization creep: move this inside the big switch() above
        if($name!='VALUE')
        {
            $xmlrpc->_xh['lv']=0;
        }
    }
}

/// Used in decoding xml chunks that might represent single xmlrpc values
function xmlrpc_se_any($parser, $name, $attrs)
{
    xmlrpc_se($parser, $name, $attrs, true);
}

/// xml parser handler function for close element tags
function xmlrpc_ee($parser, $name, $rebuild_xmlrpcvals = true)
{
    $xmlrpc = Phpxmlrpc::instance();

    if ($xmlrpc->_xh['isf'] < 2)
    {
        // push this element name from stack
        // NB: if XML validates, correct opening/closing is guaranteed and
        // we do not have to check for $name == $curr_elem.
        // we also checked for proper nesting at start of elements...
        $curr_elem = array_pop($xmlrpc->_xh['stack']);

        switch($name)
        {
            case 'VALUE':
                // This if() detects if no scalar was inside <VALUE></VALUE>
                if ($xmlrpc->_xh['vt']=='value')
                {
                    $xmlrpc->_xh['value']=$xmlrpc->_xh['ac'];
                    $xmlrpc->_xh['vt']=$xmlrpc->xmlrpcString;
                }

                if ($rebuild_xmlrpcvals)
                {
                    // build the xmlrpc val out of the data received, and substitute it
                    $temp = new xmlrpcval($xmlrpc->_xh['value'], $xmlrpc->_xh['vt']);
                    // in case we got info about underlying php class, save it
                    // in the object we're rebuilding
                    if (isset($xmlrpc->_xh['php_class']))
                        $temp->_php_class = $xmlrpc->_xh['php_class'];
                    // check if we are inside an array or struct:
                    // if value just built is inside an array, let's move it into array on the stack
                    $vscount = count($xmlrpc->_xh['valuestack']);
                    if ($vscount && $xmlrpc->_xh['valuestack'][$vscount-1]['type']=='ARRAY')
                    {
                        $xmlrpc->_xh['valuestack'][$vscount-1]['values'][] = $temp;
                    }
                    else
                    {
                        $xmlrpc->_xh['value'] = $temp;
                    }
                }
                else
                {
                    /// @todo this needs to treat correctly php-serialized objects,
                    /// since std deserializing is done by php_xmlrpc_decode,
                    /// which we will not be calling...
                    if (isset($xmlrpc->_xh['php_class']))
                    {
                    }

                    // check if we are inside an array or struct:
                    // if value just built is inside an array, let's move it into array on the stack
                    $vscount = count($xmlrpc->_xh['valuestack']);
                    if ($vscount && $xmlrpc->_xh['valuestack'][$vscount-1]['type']=='ARRAY')
                    {
                        $xmlrpc->_xh['valuestack'][$vscount-1]['values'][] = $xmlrpc->_xh['value'];
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
                $xmlrpc->_xh['vt']=strtolower($name);
                /// @todo: optimization creep - remove the if/elseif cycle below
                /// since the case() in which we are already did that
                if ($name=='STRING')
                {
                    $xmlrpc->_xh['value']=$xmlrpc->_xh['ac'];
                }
                elseif ($name=='DATETIME.ISO8601')
                {
                    if (!preg_match('/^[0-9]{8}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $xmlrpc->_xh['ac']))
                    {
                        error_log('XML-RPC: invalid value received in DATETIME: '.$xmlrpc->_xh['ac']);
                    }
                    $xmlrpc->_xh['vt']=$xmlrpc->xmlrpcDateTime;
                    $xmlrpc->_xh['value']=$xmlrpc->_xh['ac'];
                }
                elseif ($name=='BASE64')
                {
                    /// @todo check for failure of base64 decoding / catch warnings
                    $xmlrpc->_xh['value']=base64_decode($xmlrpc->_xh['ac']);
                }
                elseif ($name=='BOOLEAN')
                {
                    // special case here: we translate boolean 1 or 0 into PHP
                    // constants true or false.
                    // Strings 'true' and 'false' are accepted, even though the
                    // spec never mentions them (see eg. Blogger api docs)
                    // NB: this simple checks helps a lot sanitizing input, ie no
                    // security problems around here
                    if ($xmlrpc->_xh['ac']=='1' || strcasecmp($xmlrpc->_xh['ac'], 'true') == 0)
                    {
                        $xmlrpc->_xh['value']=true;
                    }
                    else
                    {
                        // log if receiveing something strange, even though we set the value to false anyway
                        if ($xmlrpc->_xh['ac']!='0' && strcasecmp($xmlrpc->_xh['ac'], 'false') != 0)
                            error_log('XML-RPC: invalid value received in BOOLEAN: '.$xmlrpc->_xh['ac']);
                        $xmlrpc->_xh['value']=false;
                    }
                }
                elseif ($name=='DOUBLE')
                {
                    // we have a DOUBLE
                    // we must check that only 0123456789-.<space> are characters here
                    // NOTE: regexp could be much stricter than this...
                    if (!preg_match('/^[+-eE0123456789 \t.]+$/', $xmlrpc->_xh['ac']))
                    {
                        /// @todo: find a better way of throwing an error than this!
                        error_log('XML-RPC: non numeric value received in DOUBLE: '.$xmlrpc->_xh['ac']);
                        $xmlrpc->_xh['value']='ERROR_NON_NUMERIC_FOUND';
                    }
                    else
                    {
                        // it's ok, add it on
                        $xmlrpc->_xh['value']=(double)$xmlrpc->_xh['ac'];
                    }
                }
                else
                {
                    // we have an I4/INT
                    // we must check that only 0123456789-<space> are characters here
                    if (!preg_match('/^[+-]?[0123456789 \t]+$/', $xmlrpc->_xh['ac']))
                    {
                        /// @todo find a better way of throwing an error than this!
                        error_log('XML-RPC: non numeric value received in INT: '.$xmlrpc->_xh['ac']);
                        $xmlrpc->_xh['value']='ERROR_NON_NUMERIC_FOUND';
                    }
                    else
                    {
                        // it's ok, add it on
                        $xmlrpc->_xh['value']=(int)$xmlrpc->_xh['ac'];
                    }
                }
                //$xmlrpc->_xh['ac']=''; // is this necessary?
                $xmlrpc->_xh['lv']=3; // indicate we've found a value
                break;
            case 'NAME':
                $xmlrpc->_xh['valuestack'][count($xmlrpc->_xh['valuestack'])-1]['name'] = $xmlrpc->_xh['ac'];
                break;
            case 'MEMBER':
                //$xmlrpc->_xh['ac']=''; // is this necessary?
                // add to array in the stack the last element built,
                // unless no VALUE was found
                if ($xmlrpc->_xh['vt'])
                {
                    $vscount = count($xmlrpc->_xh['valuestack']);
                    $xmlrpc->_xh['valuestack'][$vscount-1]['values'][$xmlrpc->_xh['valuestack'][$vscount-1]['name']] = $xmlrpc->_xh['value'];
                } else
                    error_log('XML-RPC: missing VALUE inside STRUCT in received xml');
                break;
            case 'DATA':
                //$xmlrpc->_xh['ac']=''; // is this necessary?
                $xmlrpc->_xh['vt']=null; // reset this to check for 2 data elements in a row - even if they're empty
                break;
            case 'STRUCT':
            case 'ARRAY':
                // fetch out of stack array of values, and promote it to current value
                $curr_val = array_pop($xmlrpc->_xh['valuestack']);
                $xmlrpc->_xh['value'] = $curr_val['values'];
                $xmlrpc->_xh['vt']=strtolower($name);
                if (isset($curr_val['php_class']))
                {
                    $xmlrpc->_xh['php_class'] = $curr_val['php_class'];
                }
                break;
            case 'PARAM':
                // add to array of params the current value,
                // unless no VALUE was found
                if ($xmlrpc->_xh['vt'])
                {
                    $xmlrpc->_xh['params'][]=$xmlrpc->_xh['value'];
                    $xmlrpc->_xh['pt'][]=$xmlrpc->_xh['vt'];
                }
                else
                    error_log('XML-RPC: missing VALUE inside PARAM in received xml');
                break;
            case 'METHODNAME':
                $xmlrpc->_xh['method']=preg_replace('/^[\n\r\t ]+/', '', $xmlrpc->_xh['ac']);
                break;
            case 'NIL':
            case 'EX:NIL':
                if ($xmlrpc->xmlrpc_null_extension)
                {
                    $xmlrpc->_xh['vt']='null';
                    $xmlrpc->_xh['value']=null;
                    $xmlrpc->_xh['lv']=3;
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

/// Used in decoding xmlrpc requests/responses without rebuilding xmlrpc values
function xmlrpc_ee_fast($parser, $name)
{
    xmlrpc_ee($parser, $name, false);
}

/// xml parser handler function for character data
function xmlrpc_cd($parser, $data)
{
    $xmlrpc = Phpxmlrpc::instance();
    // skip processing if xml fault already detected
    if ($xmlrpc->_xh['isf'] < 2)
    {
        // "lookforvalue==3" means that we've found an entire value
        // and should discard any further character data
        if($xmlrpc->_xh['lv']!=3)
        {
            // G. Giunta 2006-08-23: useless change of 'lv' from 1 to 2
            //if($xmlrpc->_xh['lv']==1)
            //{
                // if we've found text and we're just in a <value> then
                // say we've found a value
                //$xmlrpc->_xh['lv']=2;
            //}
            // we always initialize the accumulator before starting parsing, anyway...
            //if(!@isset($xmlrpc->_xh['ac']))
            //{
            //	$xmlrpc->_xh['ac'] = '';
            //}
            $xmlrpc->_xh['ac'].=$data;
        }
    }
}

/// xml parser handler function for 'other stuff', ie. not char data or
/// element start/end tag. In fact it only gets called on unknown entities...
function xmlrpc_dh($parser, $data)
{
    $xmlrpc = Phpxmlrpc::instance();
    // skip processing if xml fault already detected
    if ($xmlrpc->_xh['isf'] < 2)
    {
        if(substr($data, 0, 1) == '&' && substr($data, -1, 1) == ';')
        {
            // G. Giunta 2006-08-25: useless change of 'lv' from 1 to 2
            //if($xmlrpc->_xh['lv']==1)
            //{
            //	$xmlrpc->_xh['lv']=2;
            //}
            $xmlrpc->_xh['ac'].=$data;
        }
    }
    return true;
}

// date helpers

/**
 * Given a timestamp, return the corresponding ISO8601 encoded string.
 *
 * Really, timezones ought to be supported
 * but the XML-RPC spec says:
 *
 * "Don't assume a timezone. It should be specified by the server in its
 * documentation what assumptions it makes about timezones."
 *
 * These routines always assume localtime unless
 * $utc is set to 1, in which case UTC is assumed
 * and an adjustment for locale is made when encoding
 *
 * @param int $timet (timestamp)
 * @param int $utc (0 or 1)
 * @return string
 */
function iso8601_encode($timet, $utc=0)
{
    if(!$utc)
    {
        $t=strftime("%Y%m%dT%H:%M:%S", $timet);
    }
    else
    {
        if(function_exists('gmstrftime'))
        {
            // gmstrftime doesn't exist in some versions
            // of PHP
            $t=gmstrftime("%Y%m%dT%H:%M:%S", $timet);
        }
        else
        {
            $t=strftime("%Y%m%dT%H:%M:%S", $timet-date('Z'));
        }
    }
    return $t;
}

/**
 * Given an ISO8601 date string, return a timet in the localtime, or UTC
 * @param string $idate
 * @param int $utc either 0 or 1
 * @return int (datetime)
 */
function iso8601_decode($idate, $utc=0)
{
    $t=0;
    if(preg_match('/([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $idate, $regs))
    {
        if($utc)
        {
            $t=gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
        }
        else
        {
            $t=mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
        }
    }
    return $t;
}

/**
 * Takes an xmlrpc value in PHP xmlrpcval object format and translates it into native PHP types.
 *
 * Works with xmlrpc message objects as input, too.
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
 * @param xmlrpcval $xmlrpc_val
 * @param array $options if 'decode_php_objs' is set in the options array, xmlrpc structs can be decoded into php objects; if 'dates_as_objects' is set xmlrpc datetimes are decoded as php DateTime objects (standard is
 * @return mixed
 */
function php_xmlrpc_decode($xmlrpc_val, $options=array())
{
    switch($xmlrpc_val->kindOf())
    {
        case 'scalar':
            if (in_array('extension_api', $options))
            {
                reset($xmlrpc_val->me);
                list($typ,$val) = each($xmlrpc_val->me);
                switch ($typ)
                {
                    case 'dateTime.iso8601':
                        $xmlrpc_val->scalar = $val;
                        $xmlrpc_val->xmlrpc_type = 'datetime';
                        $xmlrpc_val->timestamp = iso8601_decode($val);
                        return $xmlrpc_val;
                    case 'base64':
                        $xmlrpc_val->scalar = $val;
                        $xmlrpc_val->type = $typ;
                        return $xmlrpc_val;
                    default:
                        return $xmlrpc_val->scalarval();
                }
            }
            if (in_array('dates_as_objects', $options) && $xmlrpc_val->scalartyp() == 'dateTime.iso8601')
            {
                // we return a Datetime object instead of a string
                // since now the constructor of xmlrpcval accepts safely strings, ints and datetimes,
                // we cater to all 3 cases here
                $out = $xmlrpc_val->scalarval();
                if (is_string($out))
                {
                    $out = strtotime($out);
                }
                if (is_int($out))
                {
                    $result = new Datetime();
                    $result->setTimestamp($out);
                    return $result;
                }
                elseif (is_a($out, 'Datetime'))
                {
                    return $out;
                }
            }
            return $xmlrpc_val->scalarval();
        case 'array':
            $size = $xmlrpc_val->arraysize();
            $arr = array();
            for($i = 0; $i < $size; $i++)
            {
                $arr[] = php_xmlrpc_decode($xmlrpc_val->arraymem($i), $options);
            }
            return $arr;
        case 'struct':
            $xmlrpc_val->structreset();
            // If user said so, try to rebuild php objects for specific struct vals.
            /// @todo should we raise a warning for class not found?
            // shall we check for proper subclass of xmlrpcval instead of
            // presence of _php_class to detect what we can do?
            if (in_array('decode_php_objs', $options) && $xmlrpc_val->_php_class != ''
                && class_exists($xmlrpc_val->_php_class))
            {
                $obj = @new $xmlrpc_val->_php_class;
                while(list($key,$value)=$xmlrpc_val->structeach())
                {
                    $obj->$key = php_xmlrpc_decode($value, $options);
                }
                return $obj;
            }
            else
            {
                $arr = array();
                while(list($key,$value)=$xmlrpc_val->structeach())
                {
                    $arr[$key] = php_xmlrpc_decode($value, $options);
                }
                return $arr;
            }
        case 'msg':
            $paramcount = $xmlrpc_val->getNumParams();
            $arr = array();
            for($i = 0; $i < $paramcount; $i++)
            {
                $arr[] = php_xmlrpc_decode($xmlrpc_val->getParam($i));
            }
            return $arr;
        }
}

// This constant left here only for historical reasons...
// it was used to decide if we have to define xmlrpc_encode on our own, but
// we do not do it anymore
if(function_exists('xmlrpc_decode'))
{
    define('XMLRPC_EPI_ENABLED','1');
}
else
{
    define('XMLRPC_EPI_ENABLED','0');
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
 * @param array $options	can include 'encode_php_objs', 'auto_dates', 'null_extension' or 'extension_api'
 * @return xmlrpcval
 */
function php_xmlrpc_encode($php_val, $options=array())
{
    $xmlrpc = Phpxmlrpc::instance();
    $type = gettype($php_val);
    switch($type)
    {
        case 'string':
            if (in_array('auto_dates', $options) && preg_match('/^[0-9]{8}T[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $php_val))
                $xmlrpc_val = new xmlrpcval($php_val, $xmlrpc->xmlrpcDateTime);
            else
                $xmlrpc_val = new xmlrpcval($php_val, $xmlrpc->xmlrpcString);
            break;
        case 'integer':
            $xmlrpc_val = new xmlrpcval($php_val, $xmlrpc->xmlrpcInt);
            break;
        case 'double':
            $xmlrpc_val = new xmlrpcval($php_val, $xmlrpc->xmlrpcDouble);
            break;
            // <G_Giunta_2001-02-29>
            // Add support for encoding/decoding of booleans, since they are supported in PHP
        case 'boolean':
            $xmlrpc_val = new xmlrpcval($php_val, $xmlrpc->xmlrpcBoolean);
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
            foreach($php_val as $key => $val)
            {
                $arr[$key] = php_xmlrpc_encode($val, $options);
                if(!$ko && $key !== $j)
                {
                    $ko = true;
                }
                $j++;
            }
            if($ko)
            {
                $xmlrpc_val = new xmlrpcval($arr, $xmlrpc->xmlrpcStruct);
            }
            else
            {
                $xmlrpc_val = new xmlrpcval($arr, $xmlrpc->xmlrpcArray);
            }
            break;
        case 'object':
            if(is_a($php_val, 'xmlrpcval'))
            {
                $xmlrpc_val = $php_val;
            }
            else if(is_a($php_val, 'DateTime'))
            {
                $xmlrpc_val = new xmlrpcval($php_val->format('Ymd\TH:i:s'), $xmlrpc->xmlrpcStruct);
            }
            else
            {
                $arr = array();
                reset($php_val);
                while(list($k,$v) = each($php_val))
                {
                    $arr[$k] = php_xmlrpc_encode($v, $options);
                }
                $xmlrpc_val = new xmlrpcval($arr, $xmlrpc->xmlrpcStruct);
                if (in_array('encode_php_objs', $options))
                {
                    // let's save original class name into xmlrpcval:
                    // might be useful later on...
                    $xmlrpc_val->_php_class = get_class($php_val);
                }
            }
            break;
        case 'NULL':
            if (in_array('extension_api', $options))
            {
                $xmlrpc_val = new xmlrpcval('', $xmlrpc->xmlrpcString);
            }
            else if (in_array('null_extension', $options))
            {
                $xmlrpc_val = new xmlrpcval('', $xmlrpc->xmlrpcNull);
            }
            else
            {
                $xmlrpc_val = new xmlrpcval();
            }
            break;
        case 'resource':
            if (in_array('extension_api', $options))
            {
                $xmlrpc_val = new xmlrpcval((int)$php_val, $xmlrpc->xmlrpcInt);
            }
            else
            {
                $xmlrpc_val = new xmlrpcval();
            }
        // catch "user function", "unknown type"
        default:
            // giancarlo pinerolo <ping@alt.it>
            // it has to return
            // an empty object in case, not a boolean.
            $xmlrpc_val = new xmlrpcval();
            break;
        }
        return $xmlrpc_val;
}

/**
 * Convert the xml representation of a method response, method request or single
 * xmlrpc value into the appropriate object (a.k.a. deserialize)
 * @param string $xml_val
 * @param array $options
 * @return mixed false on error, or an instance of either xmlrpcval, xmlrpcmsg or xmlrpcresp
 */
function php_xmlrpc_decode_xml($xml_val, $options=array())
{
    $xmlrpc = Phpxmlrpc::instance();

    $xmlrpc->_xh = array();
    $xmlrpc->_xh['ac'] = '';
    $xmlrpc->_xh['stack'] = array();
    $xmlrpc->_xh['valuestack'] = array();
    $xmlrpc->_xh['params'] = array();
    $xmlrpc->_xh['pt'] = array();
    $xmlrpc->_xh['isf'] = 0;
    $xmlrpc->_xh['isf_reason'] = '';
    $xmlrpc->_xh['method'] = false;
    $xmlrpc->_xh['rt'] = '';
    /// @todo 'guestimate' encoding
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
    // What if internal encoding is not in one of the 3 allowed?
    // we use the broadest one, ie. utf8!
    if (!in_array($xmlrpc->xmlrpc_internalencoding, array('UTF-8', 'ISO-8859-1', 'US-ASCII')))
    {
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
    }
    else
    {
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $xmlrpc->xmlrpc_internalencoding);
    }
    xml_set_element_handler($parser, 'xmlrpc_se_any', 'xmlrpc_ee');
    xml_set_character_data_handler($parser, 'xmlrpc_cd');
    xml_set_default_handler($parser, 'xmlrpc_dh');
    if(!xml_parse($parser, $xml_val, 1))
    {
        $errstr = sprintf('XML error: %s at line %d, column %d',
                    xml_error_string(xml_get_error_code($parser)),
                    xml_get_current_line_number($parser), xml_get_current_column_number($parser));
        error_log($errstr);
        xml_parser_free($parser);
        return false;
    }
    xml_parser_free($parser);
    if ($xmlrpc->_xh['isf'] > 1) // test that $xmlrpc->_xh['value'] is an obj, too???
    {
        error_log($xmlrpc->_xh['isf_reason']);
        return false;
    }
    switch ($xmlrpc->_xh['rt'])
    {
        case 'methodresponse':
            $v =& $xmlrpc->_xh['value'];
            if ($xmlrpc->_xh['isf'] == 1)
            {
                $vc = $v->structmem('faultCode');
                $vs = $v->structmem('faultString');
                $r = new xmlrpcresp(0, $vc->scalarval(), $vs->scalarval());
            }
            else
            {
                $r = new xmlrpcresp($v);
            }
            return $r;
        case 'methodcall':
            $m = new xmlrpcmsg($xmlrpc->_xh['method']);
            for($i=0; $i < count($xmlrpc->_xh['params']); $i++)
            {
                $m->addParam($xmlrpc->_xh['params'][$i]);
            }
            return $m;
        case 'value':
            return $xmlrpc->_xh['value'];
        default:
            return false;
    }
}

/**
 * decode a string that is encoded w/ "chunked" transfer encoding
 * as defined in rfc2068 par. 19.4.6
 * code shamelessly stolen from nusoap library by Dietrich Ayala
 *
 * @param string $buffer the string to be decoded
 * @return string
 */
function decode_chunked($buffer)
{
    // length := 0
    $length = 0;
    $new = '';

    // read chunk-size, chunk-extension (if any) and crlf
    // get the position of the linebreak
    $chunkend = strpos($buffer,"\r\n") + 2;
    $temp = substr($buffer,0,$chunkend);
    $chunk_size = hexdec( trim($temp) );
    $chunkstart = $chunkend;
    while($chunk_size > 0)
    {
        $chunkend = strpos($buffer, "\r\n", $chunkstart + $chunk_size);

        // just in case we got a broken connection
        if($chunkend == false)
        {
            $chunk = substr($buffer,$chunkstart);
            // append chunk-data to entity-body
            $new .= $chunk;
            $length += strlen($chunk);
            break;
        }

        // read chunk-data and crlf
        $chunk = substr($buffer,$chunkstart,$chunkend-$chunkstart);
        // append chunk-data to entity-body
        $new .= $chunk;
        // length := length + chunk-size
        $length += strlen($chunk);
        // read chunk-size and crlf
        $chunkstart = $chunkend + 2;

        $chunkend = strpos($buffer,"\r\n",$chunkstart)+2;
        if($chunkend == false)
        {
            break; //just in case we got a broken connection
        }
        $temp = substr($buffer,$chunkstart,$chunkend-$chunkstart);
        $chunk_size = hexdec( trim($temp) );
        $chunkstart = $chunkend;
    }
    return $new;
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
function guess_encoding($httpheader='', $xmlchunk='', $encoding_prefs=null)
{
    $xmlrpc = Phpxmlrpc::instance();

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
    if(preg_match('/;\s*charset\s*=([^;]+)/i', $httpheader, $matches))
    {
        return strtoupper(trim($matches[1], " \t\""));
    }

    // 2 - scan the first bytes of the data for a UTF-16 (or other) BOM pattern
    //     (source: http://www.w3.org/TR/2000/REC-xml-20001006)
    //     NOTE: actually, according to the spec, even if we find the BOM and determine
    //     an encoding, we should check if there is an encoding specified
    //     in the xml declaration, and verify if they match.
    /// @todo implement check as described above?
    /// @todo implement check for first bytes of string even without a BOM? (It sure looks harder than for cases WITH a BOM)
    if(preg_match('/^(\x00\x00\xFE\xFF|\xFF\xFE\x00\x00|\x00\x00\xFF\xFE|\xFE\xFF\x00\x00)/', $xmlchunk))
    {
        return 'UCS-4';
    }
    elseif(preg_match('/^(\xFE\xFF|\xFF\xFE)/', $xmlchunk))
    {
        return 'UTF-16';
    }
    elseif(preg_match('/^(\xEF\xBB\xBF)/', $xmlchunk))
    {
        return 'UTF-8';
    }

    // 3 - test if encoding is specified in the xml declaration
    // Details:
    // SPACE:         (#x20 | #x9 | #xD | #xA)+ === [ \x9\xD\xA]+
    // EQ:            SPACE?=SPACE? === [ \x9\xD\xA]*=[ \x9\xD\xA]*
    if (preg_match('/^<\?xml\s+version\s*=\s*'. "((?:\"[a-zA-Z0-9_.:-]+\")|(?:'[a-zA-Z0-9_.:-]+'))".
        '\s+encoding\s*=\s*' . "((?:\"[A-Za-z][A-Za-z0-9._-]*\")|(?:'[A-Za-z][A-Za-z0-9._-]*'))/",
        $xmlchunk, $matches))
    {
        return strtoupper(substr($matches[2], 1, -1));
    }

    // 4 - if mbstring is available, let it do the guesswork
    // NB: we favour finding an encoding that is compatible with what we can process
    if(extension_loaded('mbstring'))
    {
        if($encoding_prefs)
        {
            $enc = mb_detect_encoding($xmlchunk, $encoding_prefs);
        }
        else
        {
            $enc = mb_detect_encoding($xmlchunk);
        }
        // NB: mb_detect likes to call it ascii, xml parser likes to call it US_ASCII...
        // IANA also likes better US-ASCII, so go with it
        if($enc == 'ASCII')
        {
            $enc = 'US-'.$enc;
        }
        return $enc;
    }
    else
    {
        // no encoding specified: as per HTTP1.1 assume it is iso-8859-1?
        // Both RFC 2616 (HTTP 1.1) and 1945 (HTTP 1.0) clearly state that for text/xxx content types
        // this should be the standard. And we should be getting text/xml as request and response.
        // BUT we have to be backward compatible with the lib, which always used UTF-8 as default...
        return $xmlrpc->xmlrpc_defencoding;
    }
}

/**
 * Checks if a given charset encoding is present in a list of encodings or
 * if it is a valid subset of any encoding in the list
 * @param string $encoding charset to be tested
 * @param mixed $validlist comma separated list of valid charsets (or array of charsets)
 * @return bool
 */
function is_valid_charset($encoding, $validlist)
{
    $charset_supersets = array(
        'US-ASCII' => array ('ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4',
            'ISO-8859-5', 'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8',
            'ISO-8859-9', 'ISO-8859-10', 'ISO-8859-11', 'ISO-8859-12',
            'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'UTF-8',
            'EUC-JP', 'EUC-', 'EUC-KR', 'EUC-CN')
    );
    if (is_string($validlist))
        $validlist = explode(',', $validlist);
    if (@in_array(strtoupper($encoding), $validlist))
        return true;
    else
    {
        if (array_key_exists($encoding, $charset_supersets))
            foreach ($validlist as $allowed)
                if (in_array($allowed, $charset_supersets[$encoding]))
                    return true;
        return false;
    }
}

?>