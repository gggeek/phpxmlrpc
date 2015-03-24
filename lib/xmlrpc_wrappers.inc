<?php

/******************************************************************************
 *
 *** DEPRECATED ***
 *
 * This file is only used to insure backwards compatibility
 * with the API of the library <= rev. 3
 *****************************************************************************/

include_once(__DIR__.'/../src/Wrapper.php');

/* Expose as global functions the ones which are now class methods */

function php_2_xmlrpc_type($phpType)
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->php_2_xmlrpc_type($phpType);
}

function xmlrpc_2_php_type($xmlrpcType)
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->xmlrpc_2_php_type($xmlrpcType);
}

function wrap_php_function($funcName, $newFuncName='', $extraOptions=array())
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->wrap_php_function($funcName, $newFuncName, $extraOptions);
}

function wrap_php_class($className, $extraOptions=array())
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->wrap_php_class($className, $extraOptions);
}

function wrap_xmlrpc_method($client, $methodName, $extraOptions=0, $timeout=0, $protocol='', $newFuncName='')
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->wrap_xmlrpc_method($client, $methodName, $extraOptions, $timeout, $protocol, $newFuncName);
}

function wrap_xmlrpc_server($client, $extraOptions=array())
{
    $wrapper = new PhpXmlRpc\Wrapper();
    return $wrapper->wrap_xmlrpc_server($client, $extraOptions);
}
