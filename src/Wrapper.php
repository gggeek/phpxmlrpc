<?php
/**
 * @author Gaetano Giunta
 * @copyright (C) 2006-2015 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 */

namespace PhpXmlRpc;

/**
 * PHP-XMLRPC "wrapper" class.
 * Generate stubs to transparently access xmlrpc methods as php functions and vice-versa.
 * Note: this class implements the PROXY pattern, but it is not named so to avoid confusion with http proxies.
 *
 * @todo separate introspection from code generation for func-2-method wrapping
 * @todo use some better templating system for code generation?
 * @todo implement method wrapping with preservation of php objs in calls
 * @todo when wrapping methods without obj rebuilding, use return_type = 'phpvals' (faster)
 */
class Wrapper
{
    /**
     * Given a string defining a php type or phpxmlrpc type (loosely defined: strings
     * accepted come from javadoc blocks), return corresponding phpxmlrpc type.
     * NB: for php 'resource' types returns empty string, since resources cannot be serialized;
     * for php class names returns 'struct', since php objects can be serialized as xmlrpc structs
     * for php arrays always return array, even though arrays sometimes serialize as json structs.
     *
     * @param string $phpType
     *
     * @return string
     */
    public function php_2_xmlrpc_type($phpType)
    {
        switch (strtolower($phpType)) {
            case 'string':
                return Value::$xmlrpcString;
            case 'integer':
            case Value::$xmlrpcInt: // 'int'
            case Value::$xmlrpcI4:
                return Value::$xmlrpcInt;
            case 'double':
                return Value::$xmlrpcDouble;
            case 'boolean':
                return Value::$xmlrpcBoolean;
            case 'array':
                return Value::$xmlrpcArray;
            case 'object':
                return Value::$xmlrpcStruct;
            case Value::$xmlrpcBase64:
            case Value::$xmlrpcStruct:
                return strtolower($phpType);
            case 'resource':
                return '';
            default:
                if (class_exists($phpType)) {
                    return Value::$xmlrpcStruct;
                } else {
                    // unknown: might be any 'extended' xmlrpc type
                    return Value::$xmlrpcValue;
                }
        }
    }

    /**
     * Given a string defining a phpxmlrpc type return corresponding php type.
     *
     * @param string $xmlrpcType
     *
     * @return string
     */
    public function xmlrpc_2_php_type($xmlrpcType)
    {
        switch (strtolower($xmlrpcType)) {
            case 'base64':
            case 'datetime.iso8601':
            case 'string':
                return Value::$xmlrpcString;
            case 'int':
            case 'i4':
                return 'integer';
            case 'struct':
            case 'array':
                return 'array';
            case 'double':
                return 'float';
            case 'undefined':
                return 'mixed';
            case 'boolean':
            case 'null':
            default:
                // unknown: might be any xmlrpc type
                return strtolower($xmlrpcType);
        }
    }

    /**
     * Given a user-defined PHP function, create a PHP 'wrapper' function that can
     * be exposed as xmlrpc method from an xmlrpc server object and called from remote
     * clients (as well as its corresponding signature info).
     *
     * Since php is a typeless language, to infer types of input and output parameters,
     * it relies on parsing the javadoc-style comment block associated with the given
     * function. Usage of xmlrpc native types (such as datetime.dateTime.iso8601 and base64)
     * in the @param tag is also allowed, if you need the php function to receive/send
     * data in that particular format (note that base64 encoding/decoding is transparently
     * carried out by the lib, while datetime vals are passed around as strings)
     *
     * Known limitations:
     * - only works for user-defined functions, not for PHP internal functions
     *   (reflection does not support retrieving number/type of params for those)
     * - functions returning php objects will generate special xmlrpc responses:
     *   when the xmlrpc decoding of those responses is carried out by this same lib, using
     *   the appropriate param in php_xmlrpc_decode, the php objects will be rebuilt.
     *   In short: php objects can be serialized, too (except for their resource members),
     *   using this function.
     *   Other libs might choke on the very same xml that will be generated in this case
     *   (i.e. it has a nonstandard attribute on struct element tags)
     * - usage of javadoc @param tags using param names in a different order from the
     *   function prototype is not considered valid (to be fixed?)
     *
     * Note that since rel. 2.0RC3 the preferred method to have the server call 'standard'
     * php functions (ie. functions not expecting a single Request obj as parameter)
     * is by making use of the functions_parameters_type class member.
     *
     * @param string $funcName the name of the PHP user function to be exposed as xmlrpc method; array($obj, 'methodname') and array('class', 'methodname') are ok too
     * @param string $newFuncName (optional) name for function to be created
     * @param array $extraOptions (optional) array of options for conversion. valid values include:
     *                              bool  return_source when true, php code w. function definition will be returned, not evaluated
     *                              bool  encode_php_objs let php objects be sent to server using the 'improved' xmlrpc notation, so server can deserialize them as php objects
     *                              bool  decode_php_objs --- WARNING !!! possible security hazard. only use it with trusted servers ---
     *                              bool  suppress_warnings  remove from produced xml any runtime warnings due to the php function being invoked
     *
     * @return false on error, or an array containing the name of the new php function,
     *               its signature and docs, to be used in the server dispatch map
     *
     * @todo decide how to deal with params passed by ref: bomb out or allow?
     * @todo finish using javadoc info to build method sig if all params are named but out of order
     * @todo add a check for params of 'resource' type
     * @todo add some trigger_errors / error_log when returning false?
     * @todo what to do when the PHP function returns NULL? we are currently returning an empty string value...
     * @todo add an option to suppress php warnings in invocation of user function, similar to server debug level 3?
     * @todo if $newFuncName is empty, we could use create_user_func instead of eval, as it is possibly faster
     * @todo add a verbatim_object_copy parameter to allow avoiding the same obj instance?
     */
    public function wrap_php_function($funcName, $newFuncName = '', $extraOptions = array())
    {
        $buildIt = isset($extraOptions['return_source']) ? !($extraOptions['return_source']) : true;
        $prefix = isset($extraOptions['prefix']) ? $extraOptions['prefix'] : 'xmlrpc';
        $namespace = '\\PhpXmlRpc\\';
        $encodePhpObjects = isset($extraOptions['encode_php_objs']) ? (bool)$extraOptions['encode_php_objs'] : false;
        $decodePhpObjects = isset($extraOptions['decode_php_objs']) ? (bool)$extraOptions['decode_php_objs'] : false;
        $catchWarnings = isset($extraOptions['suppress_warnings']) && $extraOptions['suppress_warnings'] ? '@' : '';

        if (is_string($funcName) && strpos($funcName, '::') !== false) {
            $funcName = explode('::', $funcName);
        }
        if (is_array($funcName)) {
            if (count($funcName) < 2 || (!is_string($funcName[0]) && !is_object($funcName[0]))) {
                error_log('XML-RPC: syntax for function to be wrapped is wrong');

                return false;
            }
            if (is_string($funcName[0])) {
                $plainFuncName = implode('::', $funcName);
            } elseif (is_object($funcName[0])) {
                $plainFuncName = get_class($funcName[0]) . '->' . $funcName[1];
            }
            $exists = method_exists($funcName[0], $funcName[1]);
        } else {
            $plainFuncName = $funcName;
            $exists = function_exists($funcName);
        }

        if (!$exists) {
            error_log('XML-RPC: function to be wrapped is not defined: ' . $plainFuncName);

            return false;
        } else {
            // determine name of new php function
            if ($newFuncName == '') {
                if (is_array($funcName)) {
                    if (is_string($funcName[0])) {
                        $xmlrpcFuncName = "{$prefix}_" . implode('_', $funcName);
                    } else {
                        $xmlrpcFuncName = "{$prefix}_" . get_class($funcName[0]) . '_' . $funcName[1];
                    }
                } else {
                    $xmlrpcFuncName = "{$prefix}_$funcName";
                }
            } else {
                $xmlrpcFuncName = $newFuncName;
            }
            while ($buildIt && function_exists($xmlrpcFuncName)) {
                $xmlrpcFuncName .= 'x';
            }

            // start to introspect PHP code
            if (is_array($funcName)) {
                $func = new \ReflectionMethod($funcName[0], $funcName[1]);
                if ($func->isPrivate()) {
                    error_log('XML-RPC: method to be wrapped is private: ' . $plainFuncName);

                    return false;
                }
                if ($func->isProtected()) {
                    error_log('XML-RPC: method to be wrapped is protected: ' . $plainFuncName);

                    return false;
                }
                if ($func->isConstructor()) {
                    error_log('XML-RPC: method to be wrapped is the constructor: ' . $plainFuncName);

                    return false;
                }
                if ($func->isDestructor()) {
                    error_log('XML-RPC: method to be wrapped is the destructor: ' . $plainFuncName);

                    return false;
                }
                if ($func->isAbstract()) {
                    error_log('XML-RPC: method to be wrapped is abstract: ' . $plainFuncName);

                    return false;
                }
                /// @todo add more checks for static vs. nonstatic?
            } else {
                $func = new \ReflectionFunction($funcName);
            }
            if ($func->isInternal()) {
                // Note: from PHP 5.1.0 onward, we will possibly be able to use invokeargs
                // instead of getparameters to fully reflect internal php functions ?
                error_log('XML-RPC: function to be wrapped is internal: ' . $plainFuncName);

                return false;
            }

            // retrieve parameter names, types and description from javadoc comments

            // function description
            $desc = '';
            // type of return val: by default 'any'
            $returns = Value::$xmlrpcValue;
            // desc of return val
            $returnsDocs = '';
            // type + name of function parameters
            $paramDocs = array();

            $docs = $func->getDocComment();
            if ($docs != '') {
                $docs = explode("\n", $docs);
                $i = 0;
                foreach ($docs as $doc) {
                    $doc = trim($doc, " \r\t/*");
                    if (strlen($doc) && strpos($doc, '@') !== 0 && !$i) {
                        if ($desc) {
                            $desc .= "\n";
                        }
                        $desc .= $doc;
                    } elseif (strpos($doc, '@param') === 0) {
                        // syntax: @param type [$name] desc
                        if (preg_match('/@param\s+(\S+)(\s+\$\S+)?\s+(.+)/', $doc, $matches)) {
                            if (strpos($matches[1], '|')) {
                                //$paramDocs[$i]['type'] = explode('|', $matches[1]);
                                $paramDocs[$i]['type'] = 'mixed';
                            } else {
                                $paramDocs[$i]['type'] = $matches[1];
                            }
                            $paramDocs[$i]['name'] = trim($matches[2]);
                            $paramDocs[$i]['doc'] = $matches[3];
                        }
                        $i++;
                    } elseif (strpos($doc, '@return') === 0) {
                        // syntax: @return type desc
                        //$returns = preg_split('/\s+/', $doc);
                        if (preg_match('/@return\s+(\S+)\s+(.+)/', $doc, $matches)) {
                            $returns = $this->php_2_xmlrpc_type($matches[1]);
                            if (isset($matches[2])) {
                                $returnsDocs = $matches[2];
                            }
                        }
                    }
                }
            }

            // execute introspection of actual function prototype
            $params = array();
            $i = 0;
            foreach ($func->getParameters() as $paramObj) {
                $params[$i] = array();
                $params[$i]['name'] = '$' . $paramObj->getName();
                $params[$i]['isoptional'] = $paramObj->isOptional();
                $i++;
            }

            // start  building of PHP code to be eval'd

            $innerCode = "\$encoder = new {$namespace}Encoder();\n";
            $i = 0;
            $parsVariations = array();
            $pars = array();
            $pNum = count($params);
            foreach ($params as $param) {
                if (isset($paramDocs[$i]['name']) && $paramDocs[$i]['name'] && strtolower($paramDocs[$i]['name']) != strtolower($param['name'])) {
                    // param name from phpdoc info does not match param definition!
                    $paramDocs[$i]['type'] = 'mixed';
                }

                if ($param['isoptional']) {
                    // this particular parameter is optional. save as valid previous list of parameters
                    $innerCode .= "if (\$paramcount > $i) {\n";
                    $parsVariations[] = $pars;
                }
                $innerCode .= "\$p$i = \$msg->getParam($i);\n";
                if ($decodePhpObjects) {
                    $innerCode .= "if (\$p{$i}->kindOf() == 'scalar') \$p$i = \$p{$i}->scalarval(); else \$p$i = \$encoder->decode(\$p$i, array('decode_php_objs'));\n";
                } else {
                    $innerCode .= "if (\$p{$i}->kindOf() == 'scalar') \$p$i = \$p{$i}->scalarval(); else \$p$i = \$encoder->decode(\$p$i);\n";
                }

                $pars[] = "\$p$i";
                $i++;
                if ($param['isoptional']) {
                    $innerCode .= "}\n";
                }
                if ($i == $pNum) {
                    // last allowed parameters combination
                    $parsVariations[] = $pars;
                }
            }

            $sigs = array();
            $pSigs = array();
            if (count($parsVariations) == 0) {
                // only known good synopsis = no parameters
                $parsVariations[] = array();
                $minPars = 0;
            } else {
                $minPars = count($parsVariations[0]);
            }

            if ($minPars) {
                // add to code the check for min params number
                // NB: this check needs to be done BEFORE decoding param values
                $innerCode = "\$paramcount = \$msg->getNumParams();\n" .
                    "if (\$paramcount < $minPars) return new {$namespace}Response(0, " . PhpXmlRpc::$xmlrpcerr['incorrect_params'] . ", '" . PhpXmlRpc::$xmlrpcerr['incorrect_params'] . "');\n" . $innerCode;
            } else {
                $innerCode = "\$paramcount = \$msg->getNumParams();\n" . $innerCode;
            }

            $innerCode .= "\$np = false;\n";
            // since there are no closures in php, if we are given an object instance,
            // we store a pointer to it in a global var...
            if (is_array($funcName) && is_object($funcName[0])) {
                $GLOBALS['xmlrpcWPFObjHolder'][$xmlrpcFuncName] = &$funcName[0];
                $innerCode .= "\$obj =& \$GLOBALS['xmlrpcWPFObjHolder']['$xmlrpcFuncName'];\n";
                $realFuncName = '$obj->' . $funcName[1];
            } else {
                $realFuncName = $plainFuncName;
            }
            foreach ($parsVariations as $pars) {
                $innerCode .= "if (\$paramcount == " . count($pars) . ") \$retval = {$catchWarnings}$realFuncName(" . implode(',', $pars) . "); else\n";
                // build a 'generic' signature (only use an appropriate return type)
                $sig = array($returns);
                $pSig = array($returnsDocs);
                for ($i = 0; $i < count($pars); $i++) {
                    if (isset($paramDocs[$i]['type'])) {
                        $sig[] = $this->php_2_xmlrpc_type($paramDocs[$i]['type']);
                    } else {
                        $sig[] = Value::$xmlrpcValue;
                    }
                    $pSig[] = isset($paramDocs[$i]['doc']) ? $paramDocs[$i]['doc'] : '';
                }
                $sigs[] = $sig;
                $pSigs[] = $pSig;
            }
            $innerCode .= "\$np = true;\n";
            $innerCode .= "if (\$np) return new {$namespace}Response(0, " . PhpXmlRpc::$xmlrpcerr['incorrect_params'] . ", '" . PhpXmlRpc::$xmlrpcerr['incorrect_params'] . "'); else {\n";
            //$innerCode .= "if (\$_xmlrpcs_error_occurred) return new Response(0, $GLOBALS['xmlrpcerr']user, \$_xmlrpcs_error_occurred); else\n";
            $innerCode .= "if (is_a(\$retval, '{$namespace}Response')) return \$retval; else\n";
            if ($returns == Value::$xmlrpcDateTime || $returns == Value::$xmlrpcBase64) {
                $innerCode .= "return new {$namespace}Response(new {$namespace}Value(\$retval, '$returns'));";
            } else {
                if ($encodePhpObjects) {
                    $innerCode .= "return new {$namespace}Response(\$encoder->encode(\$retval, array('encode_php_objs')));\n";
                } else {
                    $innerCode .= "return new {$namespace}Response(\$encoder->encode(\$retval));\n";
                }
            }
            // shall we exclude functions returning by ref?
            // if($func->returnsReference())
            //     return false;
            $code = "function $xmlrpcFuncName(\$msg) {\n" . $innerCode . "}\n}";
            //print_r($code);
            if ($buildIt) {
                $allOK = 0;
                eval($code . '$allOK=1;');
                // alternative
                //$xmlrpcFuncName = create_function('$m', $innerCode);

                if (!$allOK) {
                    error_log('XML-RPC: could not create function ' . $xmlrpcFuncName . ' to wrap php function ' . $plainFuncName);

                    return false;
                }
            }

            /// @todo examine if $paramDocs matches $parsVariations and build array for
            /// usage as method signature, plus put together a nice string for docs

            $ret = array('function' => $xmlrpcFuncName, 'signature' => $sigs, 'docstring' => $desc, 'signature_docs' => $pSigs, 'source' => $code);

            return $ret;
        }
    }

    /**
     * Given a user-defined PHP class or php object, map its methods onto a list of
     * PHP 'wrapper' functions that can be exposed as xmlrpc methods from an xmlrpc server
     * object and called from remote clients (as well as their corresponding signature info).
     *
     * @param mixed $classname the name of the class whose methods are to be exposed as xmlrpc methods, or an object instance of that class
     * @param array $extraOptions see the docs for wrap_php_method for more options
     *                             string method_type 'static', 'nonstatic', 'all' and 'auto' (default); the latter will switch between static and non-static depending on wheter $classname is a class name or object instance
     *
     * @return array or false on failure
     *
     * @todo get_class_methods will return both static and non-static methods.
     *       we have to differentiate the action, depending on wheter we recived a class name or object
     */
    public function wrap_php_class($classname, $extraOptions = array())
    {
        $methodfilter = isset($extraOptions['method_filter']) ? $extraOptions['method_filter'] : '';
        $methodtype = isset($extraOptions['method_type']) ? $extraOptions['method_type'] : 'auto';

        $result = array();
        $mlist = get_class_methods($classname);
        foreach ($mlist as $mname) {
            if ($methodfilter == '' || preg_match($methodfilter, $mname)) {
                // echo $mlist."\n";
                $func = new \ReflectionMethod($classname, $mname);
                if (!$func->isPrivate() && !$func->isProtected() && !$func->isConstructor() && !$func->isDestructor() && !$func->isAbstract()) {
                    if (($func->isStatic && ($methodtype == 'all' || $methodtype == 'static' || ($methodtype == 'auto' && is_string($classname)))) ||
                        (!$func->isStatic && ($methodtype == 'all' || $methodtype == 'nonstatic' || ($methodtype == 'auto' && is_object($classname))))
                    ) {
                        $methodwrap = $this->wrap_php_function(array($classname, $mname), '', $extraOptions);
                        if ($methodwrap) {
                            $result[$methodwrap['function']] = $methodwrap['function'];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Given an xmlrpc client and a method name, register a php wrapper function
     * that will call it and return results using native php types for both
     * params and results. The generated php function will return a Response
     * object for failed xmlrpc calls.
     *
     * Known limitations:
     * - server must support system.methodsignature for the wanted xmlrpc method
     * - for methods that expose many signatures, only one can be picked (we
     *   could in principle check if signatures differ only by number of params
     *   and not by type, but it would be more complication than we can spare time)
     * - nested xmlrpc params: the caller of the generated php function has to
     *   encode on its own the params passed to the php function if these are structs
     *   or arrays whose (sub)members include values of type datetime or base64
     *
     * Notes: the connection properties of the given client will be copied
     * and reused for the connection used during the call to the generated
     * php function.
     * Calling the generated php function 'might' be slow: a new xmlrpc client
     * is created on every invocation and an xmlrpc-connection opened+closed.
     * An extra 'debug' param is appended to param list of xmlrpc method, useful
     * for debugging purposes.
     *
     * @param Client $client an xmlrpc client set up correctly to communicate with target server
     * @param string $methodName the xmlrpc method to be mapped to a php function
     * @param array $extraOptions array of options that specify conversion details. valid options include
     *                              integer       signum      the index of the method signature to use in mapping (if method exposes many sigs)
     *                              integer       timeout     timeout (in secs) to be used when executing function/calling remote method
     *                              string        protocol    'http' (default), 'http11' or 'https'
     *                              string        new_function_name the name of php function to create. If unspecified, lib will pick an appropriate name
     *                              string        return_source if true return php code w. function definition instead fo function name
     *                              bool          encode_php_objs let php objects be sent to server using the 'improved' xmlrpc notation, so server can deserialize them as php objects
     *                              bool          decode_php_objs --- WARNING !!! possible security hazard. only use it with trusted servers ---
     *                              mixed         return_on_fault a php value to be returned when the xmlrpc call fails/returns a fault response (by default the Response object is returned in this case). If a string is used, '%faultCode%' and '%faultString%' tokens will be substituted with actual error values
     *                              bool          debug        set it to 1 or 2 to see debug results of querying server for method synopsis
     *
     * @return string the name of the generated php function (or false) - OR AN ARRAY...
     */
    public function wrap_xmlrpc_method($client, $methodName, $extraOptions = 0, $timeout = 0, $protocol = '', $newFuncName = '')
    {
        // mind numbing: let caller use sane calling convention (as per javadoc, 3 params),
        // OR the 2.0 calling convention (no options) - we really love backward compat, don't we?
        if (!is_array($extraOptions)) {
            $signum = $extraOptions;
            $extraOptions = array();
        } else {
            $signum = isset($extraOptions['signum']) ? (int)$extraOptions['signum'] : 0;
            $timeout = isset($extraOptions['timeout']) ? (int)$extraOptions['timeout'] : 0;
            $protocol = isset($extraOptions['protocol']) ? $extraOptions['protocol'] : '';
            $newFuncName = isset($extraOptions['new_function_name']) ? $extraOptions['new_function_name'] : '';
        }
        //$encodePhpObjects = in_array('encode_php_objects', $extraOptions);
        //$verbatimClientCopy = in_array('simple_client_copy', $extraOptions) ? 1 :
        //	in_array('build_class_code', $extraOptions) ? 2 : 0;

        $encodePhpObjects = isset($extraOptions['encode_php_objs']) ? (bool)$extraOptions['encode_php_objs'] : false;
        $decodePhpObjects = isset($extraOptions['decode_php_objs']) ? (bool)$extraOptions['decode_php_objs'] : false;
        // it seems like the meaning of 'simple_client_copy' here is swapped wrt client_copy_mode later on...
        $simpleClientCopy = isset($extraOptions['simple_client_copy']) ? (int)($extraOptions['simple_client_copy']) : 0;
        $buildIt = isset($extraOptions['return_source']) ? !($extraOptions['return_source']) : true;
        $prefix = isset($extraOptions['prefix']) ? $extraOptions['prefix'] : 'xmlrpc';
        $namespace = '\\PhpXmlRpc\\';
        if (isset($extraOptions['return_on_fault'])) {
            $decodeFault = true;
            $faultResponse = $extraOptions['return_on_fault'];
        } else {
            $decodeFault = false;
            $faultResponse = '';
        }
        $debug = isset($extraOptions['debug']) ? ($extraOptions['debug']) : 0;

        $msgclass = $namespace . 'Request';
        $valclass = $namespace . 'Value';
        $decoderClass = $namespace . 'Encoder';

        $msg = new $msgclass('system.methodSignature');
        $msg->addparam(new $valclass($methodName));
        $client->setDebug($debug);
        $response = $client->send($msg, $timeout, $protocol);
        if ($response->faultCode()) {
            error_log('XML-RPC: could not retrieve method signature from remote server for method ' . $methodName);

            return false;
        } else {
            $msig = $response->value();
            if ($client->return_type != 'phpvals') {
                $decoder = new $decoderClass();
                $msig = $decoder->decode($msig);
            }
            if (!is_array($msig) || count($msig) <= $signum) {
                error_log('XML-RPC: could not retrieve method signature nr.' . $signum . ' from remote server for method ' . $methodName);

                return false;
            } else {
                // pick a suitable name for the new function, avoiding collisions
                if ($newFuncName != '') {
                    $xmlrpcFuncName = $newFuncName;
                } else {
                    // take care to insure that methodname is translated to valid
                    // php function name
                    $xmlrpcFuncName = $prefix . '_' . preg_replace(array('/\./', '/[^a-zA-Z0-9_\x7f-\xff]/'),
                            array('_', ''), $methodName);
                }
                while ($buildIt && function_exists($xmlrpcFuncName)) {
                    $xmlrpcFuncName .= 'x';
                }

                $msig = $msig[$signum];
                $mdesc = '';
                // if in 'offline' mode, get method description too.
                // in online mode, favour speed of operation
                if (!$buildIt) {
                    $msg = new $msgclass('system.methodHelp');
                    $msg->addparam(new $valclass($methodName));
                    $response = $client->send($msg, $timeout, $protocol);
                    if (!$response->faultCode()) {
                        $mdesc = $response->value();
                        if ($client->return_type != 'phpvals') {
                            $mdesc = $mdesc->scalarval();
                        }
                    }
                }

                $results = $this->build_remote_method_wrapper_code($client, $methodName,
                    $xmlrpcFuncName, $msig, $mdesc, $timeout, $protocol, $simpleClientCopy,
                    $prefix, $decodePhpObjects, $encodePhpObjects, $decodeFault,
                    $faultResponse, $namespace);
                //print_r($code);
                if ($buildIt) {
                    $allOK = 0;
                    eval($results['source'] . '$allOK=1;');
                    // alternative
                    //$xmlrpcFuncName = create_function('$m', $innerCode);
                    if ($allOK) {
                        return $xmlrpcFuncName;
                    } else {
                        error_log('XML-RPC: could not create function ' . $xmlrpcFuncName . ' to wrap remote method ' . $methodName);

                        return false;
                    }
                } else {
                    $results['function'] = $xmlrpcFuncName;

                    return $results;
                }
            }
        }
    }

    /**
     * Similar to wrap_xmlrpc_method, but will generate a php class that wraps
     * all xmlrpc methods exposed by the remote server as own methods.
     * For more details see wrap_xmlrpc_method.
     *
     * @param Client $client the client obj all set to query the desired server
     * @param array $extraOptions list of options for wrapped code
     *
     * @return mixed false on error, the name of the created class if all ok or an array with code, class name and comments (if the appropriatevoption is set in extra_options)
     */
    public function wrap_xmlrpc_server($client, $extraOptions = array())
    {
        $methodfilter = isset($extraOptions['method_filter']) ? $extraOptions['method_filter'] : '';
        //$signum = isset($extraOptions['signum']) ? (int)$extraOptions['signum'] : 0;
        $timeout = isset($extraOptions['timeout']) ? (int)$extraOptions['timeout'] : 0;
        $protocol = isset($extraOptions['protocol']) ? $extraOptions['protocol'] : '';
        $newclassname = isset($extraOptions['new_class_name']) ? $extraOptions['new_class_name'] : '';
        $encodePhpObjects = isset($extraOptions['encode_php_objs']) ? (bool)$extraOptions['encode_php_objs'] : false;
        $decodePhpObjects = isset($extraOptions['decode_php_objs']) ? (bool)$extraOptions['decode_php_objs'] : false;
        $verbatimClientCopy = isset($extraOptions['simple_client_copy']) ? !($extraOptions['simple_client_copy']) : true;
        $buildIt = isset($extraOptions['return_source']) ? !($extraOptions['return_source']) : true;
        $prefix = isset($extraOptions['prefix']) ? $extraOptions['prefix'] : 'xmlrpc';
        $namespace = '\\PhpXmlRpc\\';

        $msgclass = $namespace . 'Request';
        //$valclass = $prefix.'val';
        $decoderClass = $namespace . 'Encoder';

        $msg = new $msgclass('system.listMethods');
        $response = $client->send($msg, $timeout, $protocol);
        if ($response->faultCode()) {
            error_log('XML-RPC: could not retrieve method list from remote server');

            return false;
        } else {
            $mlist = $response->value();
            if ($client->return_type != 'phpvals') {
                $decoder = new $decoderClass();
                $mlist = $decoder->decode($mlist);
            }
            if (!is_array($mlist) || !count($mlist)) {
                error_log('XML-RPC: could not retrieve meaningful method list from remote server');

                return false;
            } else {
                // pick a suitable name for the new function, avoiding collisions
                if ($newclassname != '') {
                    $xmlrpcClassName = $newclassname;
                } else {
                    $xmlrpcClassName = $prefix . '_' . preg_replace(array('/\./', '/[^a-zA-Z0-9_\x7f-\xff]/'),
                            array('_', ''), $client->server) . '_client';
                }
                while ($buildIt && class_exists($xmlrpcClassName)) {
                    $xmlrpcClassName .= 'x';
                }

                /// @todo add function setdebug() to new class, to enable/disable debugging
                $source = "class $xmlrpcClassName\n{\nvar \$client;\n\n";
                $source .= "function __construct()\n{\n";
                $source .= $this->build_client_wrapper_code($client, $verbatimClientCopy, $prefix, $namespace);
                $source .= "\$this->client = \$client;\n}\n\n";
                $opts = array('simple_client_copy' => 2, 'return_source' => true,
                    'timeout' => $timeout, 'protocol' => $protocol,
                    'encode_php_objs' => $encodePhpObjects, 'prefix' => $prefix,
                    'decode_php_objs' => $decodePhpObjects,
                );
                /// @todo build javadoc for class definition, too
                foreach ($mlist as $mname) {
                    if ($methodfilter == '' || preg_match($methodfilter, $mname)) {
                        $opts['new_function_name'] = preg_replace(array('/\./', '/[^a-zA-Z0-9_\x7f-\xff]/'),
                            array('_', ''), $mname);
                        $methodwrap = $this->wrap_xmlrpc_method($client, $mname, $opts);
                        if ($methodwrap) {
                            if (!$buildIt) {
                                $source .= $methodwrap['docstring'];
                            }
                            $source .= $methodwrap['source'] . "\n";
                        } else {
                            error_log('XML-RPC: will not create class method to wrap remote method ' . $mname);
                        }
                    }
                }
                $source .= "}\n";
                if ($buildIt) {
                    $allOK = 0;
                    eval($source . '$allOK=1;');
                    // alternative
                    //$xmlrpcFuncName = create_function('$m', $innerCode);
                    if ($allOK) {
                        return $xmlrpcClassName;
                    } else {
                        error_log('XML-RPC: could not create class ' . $xmlrpcClassName . ' to wrap remote server ' . $client->server);

                        return false;
                    }
                } else {
                    return array('class' => $xmlrpcClassName, 'code' => $source, 'docstring' => '');
                }
            }
        }
    }

    /**
     * Given the necessary info, build php code that creates a new function to
     * invoke a remote xmlrpc method.
     * Take care that no full checking of input parameters is done to ensure that
     * valid php code is emitted.
     * Note: real spaghetti code follows...
     */
    public function build_remote_method_wrapper_code($client, $methodName, $xmlrpcFuncName,
                                                        $msig, $mdesc = '', $timeout = 0, $protocol = '', $clientCopyMode = 0, $prefix = 'xmlrpc',
                                                        $decodePhpObjects = false, $encodePhpObjects = false, $decdoeFault = false,
                                                        $faultResponse = '', $namespace = '\\PhpXmlRpc\\')
    {
        $code = "function $xmlrpcFuncName (";
        if ($clientCopyMode < 2) {
            // client copy mode 0 or 1 == partial / full client copy in emitted code
            $innerCode = $this->build_client_wrapper_code($client, $clientCopyMode, $prefix, $namespace);
            $innerCode .= "\$client->setDebug(\$debug);\n";
            $this_ = '';
        } else {
            // client copy mode 2 == no client copy in emitted code
            $innerCode = '';
            $this_ = 'this->';
        }
        $innerCode .= "\$msg = new {$namespace}Request('$methodName');\n";

        if ($mdesc != '') {
            // take care that PHP comment is not terminated unwillingly by method description
            $mdesc = "/**\n* " . str_replace('*/', '* /', $mdesc) . "\n";
        } else {
            $mdesc = "/**\nFunction $xmlrpcFuncName\n";
        }

        // param parsing
        $innerCode .= "\$encoder = new {$namespace}Encoder();\n";
        $plist = array();
        $pcount = count($msig);
        for ($i = 1; $i < $pcount; $i++) {
            $plist[] = "\$p$i";
            $ptype = $msig[$i];
            if ($ptype == 'i4' || $ptype == 'int' || $ptype == 'boolean' || $ptype == 'double' ||
                $ptype == 'string' || $ptype == 'dateTime.iso8601' || $ptype == 'base64' || $ptype == 'null'
            ) {
                // only build directly xmlrpc values when type is known and scalar
                $innerCode .= "\$p$i = new {$namespace}Value(\$p$i, '$ptype');\n";
            } else {
                if ($encodePhpObjects) {
                    $innerCode .= "\$p$i = \$encoder->encode(\$p$i, array('encode_php_objs'));\n";
                } else {
                    $innerCode .= "\$p$i = \$encoder->encode(\$p$i);\n";
                }
            }
            $innerCode .= "\$msg->addparam(\$p$i);\n";
            $mdesc .= '* @param ' . $this->xmlrpc_2_php_type($ptype) . " \$p$i\n";
        }
        if ($clientCopyMode < 2) {
            $plist[] = '$debug=0';
            $mdesc .= "* @param int \$debug when 1 (or 2) will enable debugging of the underlying {$prefix} call (defaults to 0)\n";
        }
        $plist = implode(', ', $plist);
        $mdesc .= '* @return ' . $this->xmlrpc_2_php_type($msig[0]) . " (or an {$namespace}Response obj instance if call fails)\n*/\n";

        $innerCode .= "\$res = \${$this_}client->send(\$msg, $timeout, '$protocol');\n";
        if ($decdoeFault) {
            if (is_string($faultResponse) && ((strpos($faultResponse, '%faultCode%') !== false) || (strpos($faultResponse, '%faultString%') !== false))) {
                $respCode = "str_replace(array('%faultCode%', '%faultString%'), array(\$res->faultCode(), \$res->faultString()), '" . str_replace("'", "''", $faultResponse) . "')";
            } else {
                $respCode = var_export($faultResponse, true);
            }
        } else {
            $respCode = '$res';
        }
        if ($decodePhpObjects) {
            $innerCode .= "if (\$res->faultcode()) return $respCode; else return \$encoder->decode(\$res->value(), array('decode_php_objs'));";
        } else {
            $innerCode .= "if (\$res->faultcode()) return $respCode; else return \$encoder->decode(\$res->value());";
        }

        $code = $code . $plist . ") {\n" . $innerCode . "\n}\n";

        return array('source' => $code, 'docstring' => $mdesc);
    }

    /**
     * Given necessary info, generate php code that will rebuild a client object
     * Take care that no full checking of input parameters is done to ensure that
     * valid php code is emitted.
     * @param Client $client
     * @param bool $verbatimClientCopy
     * @param string $prefix
     * @param string $namespace
     * @return string
     */
    protected function build_client_wrapper_code($client, $verbatimClientCopy, $prefix = 'xmlrpc', $namespace = '\\PhpXmlRpc\\' )
    {
        $code = "\$client = new {$namespace}Client('" . str_replace("'", "\'", $client->path) .
            "', '" . str_replace("'", "\'", $client->server) . "', $client->port);\n";

        // copy all client fields to the client that will be generated runtime
        // (this provides for future expansion or subclassing of client obj)
        if ($verbatimClientCopy) {
            foreach ($client as $fld => $val) {
                if ($fld != 'debug' && $fld != 'return_type') {
                    $val = var_export($val, true);
                    $code .= "\$client->$fld = $val;\n";
                }
            }
        }
        // only make sure that client always returns the correct data type
        $code .= "\$client->return_type = '{$prefix}vals';\n";
        //$code .= "\$client->setDebug(\$debug);\n";
        return $code;
    }
}
