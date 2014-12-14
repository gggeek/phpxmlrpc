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

/******************************************************************************
 *
 *** DEPRECATED ***
 *
 * This file is only used to insure backwards compatibility
 * with the API of the library <= rev. 3
 *****************************************************************************/

include_once(__DIR__.'/../src/PhpXmlRpc.php');
include_once(__DIR__.'/../src/Value.php');
include_once(__DIR__.'/../src/Request.php');
include_once(__DIR__.'/../src/Response.php');
include_once(__DIR__.'/../src/Client.php');
include_once(__DIR__.'/../src/Encoder.php');

/* Expose with the old names the classes which have been namespaced */

class xmlrpcval extends PhpXmlRpc\Value
{
}

class xmlrpcmsg extends PhpXmlRpc\Request
{
}

class xmlrpcresp extends PhpXmlRpc\Response
{
}

class xmlrpc_client extends PhpXmlRpc\Client
{
}

/* Expose as global functions the ones which are now class methods */

function xmlrpc_encode_entitites($data, $srcEncoding='', $destEncoding='')
{
    return PhpXmlRpc\Helper\Charset::instance()->encode_entitites($data, $srcEncoding, $destEncoding);
}

function iso8601_encode($timeT, $utc=0)
{
    return PhpXmlRpc\Helper\Date::iso8601_encode($timeT, $utc);
}

function iso8601_decode($iDate, $utc=0)
{
    return PhpXmlRpc\Helper\Date::iso8601_decode($iDate, $utc);
}

function decode_chunked($buffer)
{
    return PhpXmlRpc\Helper\Http::decode_chunked($buffer);
}

function php_xmlrpc_decode($xmlrpcVal, $options=array())
{
    $encoder = new PhpXmlRpc\Encoder();
    return $encoder->decode($xmlrpcVal, $options);
}

function php_xmlrpc_encode($phpVal, $options=array())
{
    $encoder = new PhpXmlRpc\Encoder();
    return $encoder->encode($phpVal, $options);
}

function php_xmlrpc_decode_xml($xmlVal, $options=array())
{
    $encoder = new PhpXmlRpc\Encoder();
    return $encoder->decode_xml($xmlVal, $options);
}

function guess_encoding($httpHeader='', $xmlChunk='', $encodingPrefs=null)
{
}

function is_valid_charset($encoding, $validList)
{
    return PhpXmlRpc\Helper\Charset::instance()->is_valid_charset($encoding, $validList);
}