<?php
/**
 * Common parameter parsing for benchmarks and tests scripts
 *
 * @param integer DEBUG
 * @param string  LOCALSERVER
 * @param string  URI
 * @param string  HTTPSSERVER
 * @param string  HTTPSSURI
 * @param string  PROXY
 *
 * @copyright (C) 2007-20013 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 **/

	require_once('xmlrpc.inc');
	require_once('xmlrpcs.inc');

	// play nice to older PHP versions that miss superglobals
	if(!isset($_SERVER))
	{
		$_SERVER = $HTTP_SERVER_VARS;
		$_GET = isset($HTTP_GET_VARS) ? $HTTP_GET_VARS : array();
		$_POST = isset($HTTP_POST_VARS) ? $HTTP_POST_VARS : array();
	}

	// check for command line vs web page input params
	if(!isset($_SERVER['REQUEST_METHOD']))
	{
		if(isset($argv))
		{
			foreach($argv as $param)
			{
				$param = explode('=', $param);
				if(count($param) > 1)
				{
					$$param[0]=$param[1];
				}
			}
		}
	}
	elseif(!ini_get('register_globals'))
	{
		// play nice to 'safe' PHP installations with register globals OFF
		// NB: we might as well consider using $_GET stuff later on...
		extract($_GET);
		extract($_POST);
	}

	if(!isset($DEBUG))
	{
		$DEBUG = 0;
	}
	else
	{
		$DEBUG = intval($DEBUG);
	}

	if(!isset($LOCALSERVER))
	{
		if(isset($HTTP_HOST))
		{
			$LOCALSERVER = $HTTP_HOST;
		}
		elseif(isset($_SERVER['HTTP_HOST']))
		{
			$LOCALSERVER = $_SERVER['HTTP_HOST'];
		}
		else
		{
			$LOCALSERVER = 'localhost';
		}
	}
	if(!isset($HTTPSSERVER))
	{
		$HTTPSSERVER = 'xmlrpc.usefulinc.com';
	}
	if(!isset($HTTPSURI))
	{
		$HTTPSURI = '/server.php';
	}
	if(!isset($HTTPSIGNOREPEER))
	{
		$HTTPSIGNOREPEER = false;
	}
	if(!isset($PROXY))
	{
		$PROXYSERVER = null;
	}
	else
	{
		$arr = explode(':',$PROXY);
		$PROXYSERVER = $arr[0];
		if(count($arr) > 1)
		{
			$PROXYPORT = $arr[1];
		}
		else
		{
			$PROXYPORT = 8080;
		}
	}
    // used to silence testsuite warnings about proxy code not being tested
    if(!isset($NOPROXY))
    {
        $NOPROXY = false;
    }
	if(!isset($URI))
	{
		// GUESTIMATE the url of local demo server
		// play nice to php 3 and 4-5 in retrieving URL of server.php
		/// @todo filter out query string from REQUEST_URI
		if(isset($REQUEST_URI))
		{
			$URI = str_replace('/test/testsuite.php', '/demo/server/server.php', $REQUEST_URI);
			$URI = str_replace('/testsuite.php', '/server.php', $URI);
			$URI = str_replace('/test/benchmark.php', '/demo/server/server.php', $URI);
			$URI = str_replace('/benchmark.php', '/server.php', $URI);
		}
		elseif(isset($_SERVER['PHP_SELF']) && isset($_SERVER['REQUEST_METHOD']))
		{
			$URI = str_replace('/test/testsuite.php', '/demo/server/server.php', $_SERVER['PHP_SELF']);
			$URI = str_replace('/testsuite.php', '/server.php', $URI);
			$URI = str_replace('/test/benchmark.php', '/demo/server/server.php', $URI);
			$URI = str_replace('/benchmark.php', '/server.php', $URI);
		}
		else
		{
			$URI = '/demo/server/server.php';
		}
	}
	if($URI[0] != '/')
	{
		$URI = '/'.$URI;
	}
	if(!isset($LOCALPATH))
	{
		$LOCALPATH = dirname(__FILE__);
	}
?>