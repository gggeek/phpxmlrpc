<?php

/**
 * Common parameter parsing for benchmark and tests scripts.
 *
 * @param integer DEBUG
 * @param string  HTTPSERVER
 * @param string  HTTPURI
 * @param string  HTTPSSERVER
 * @param string  HTTPSURI
 * @param bool    HTTPSIGNOREPEER
 * @param int     HTTPSVERIFYHOST
 * @param int     SSLVERSION
 * @param string  PROXYSERVER
 *
 * @copyright (C) 2007-2024 G. Giunta
 * @license code licensed under the BSD License: see file license.txt
 *
 * @todo rename both the class and the file. PhpXmlRpc_TestConfigParser ?
 **/
class argParser
{
    /**
     * @return array
     */
    public static function getArgs()
    {
        /// @todo should we prefix all test parameters with TESTS_ ?
        $args = array(
            'DEBUG' => 0,
            'HTTPSERVER' => 'localhost',
            'HTTPURI' => null,
            // now that we run tests in Docker by default, with a webserver set up for https, let's default to it
            'HTTPSSERVER' => 'localhost',
            'HTTPSURI' => null,
            // example alternative:
            //'HTTPSSERVER' => 'gggeek.altervista.org',
            //'HTTPSURI' => '/sw/xmlrpc/demo/server/server.php',
            'HTTPSIGNOREPEER' => false,
            'HTTPSVERIFYHOST' => 2,
            'SSLVERSION' => 0,
            'PROXYSERVER' => null,
            //'LOCALPATH' => __DIR__,
        );

        // check for command line params (passed as env vars) vs. web page input params (passed as GET/POST)
        // Note that the only use-case for web-page mode is when this is used by benchmark.php
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            foreach($_SERVER as $key => $val) {
                if (array_key_exists($key, $args)) {
                    $$key = $val;
                }
            }
        } else {
            // NB: we might as well consider using $_GET stuff later on...
            extract($_GET);
            extract($_POST);
        }

        if (isset($DEBUG)) {
            $args['DEBUG'] = intval($DEBUG);
        }

        if (isset($HTTPSERVER)) {
            $args['HTTPSERVER'] = $HTTPSERVER;
        } else {
            if (isset($HTTP_HOST)) {
                $args['HTTPSERVER'] = $HTTP_HOST;
            } elseif (isset($_SERVER['HTTP_HOST'])) {
                $args['HTTPSERVER'] = $_SERVER['HTTP_HOST'];
            }
        }

        if (!isset($HTTPURI) || $HTTPURI == '') {
            // GUESTIMATE the url of local test controller
            // play nice to php 5 and 7 in retrieving URL of index.php
            /// @todo filter out query string from REQUEST_URI
            /// @todo review this code...
            /*if (isset($REQUEST_URI)) {
                $HTTPURI = str_replace('/tests/testsuite.php', '/demo/server/server.php', $REQUEST_URI);
                $HTTPURI = str_replace('/testsuite.php', '/server.php', $HTTPURI);
                $HTTPURI = str_replace('/extras/benchmark.php', '/demo/server/server.php', $HTTPURI);
                $HTTPURI = str_replace('/benchmark.php', '/server.php', $HTTPURI);
            } elseif (isset($_SERVER['PHP_SELF']) && isset($_SERVER['REQUEST_METHOD'])) {
                $HTTPURI = str_replace('/tests/testsuite.php', '/demo/server/server.php', $_SERVER['PHP_SELF']);
                $HTTPURI = str_replace('/testsuite.php', '/server.php', $HTTPURI);
                $HTTPURI = str_replace('/extras/benchmark.php', '/demo/server/server.php', $HTTPURI);
                $HTTPURI = str_replace('/benchmark.php', '/server.php', $HTTPURI);
            } else {*/
                $HTTPURI = '/tests/index.php?demo=server/server.php';
            //}
        }
        if ($HTTPURI[0] != '/') {
            $HTTPURI = '/' . $HTTPURI;
        }
        $args['HTTPURI'] = $HTTPURI;

        if (isset($HTTPSSERVER)) {
            $args['HTTPSSERVER'] = $HTTPSSERVER;
        }

        /// @todo if $HTTPSURI is unset, and HTTPSSERVER == localhost, use HTTPURI
        if (isset($HTTPSURI)) {
            $args['HTTPSURI'] = $HTTPSURI;
        }

        if (isset($HTTPSIGNOREPEER)) {
            $args['HTTPSIGNOREPEER'] = (bool)$HTTPSIGNOREPEER;
        }

        if (isset($HTTPSVERIFYHOST)) {
            $args['HTTPSVERIFYHOST'] = (int)$HTTPSVERIFYHOST;
        }

        if (isset($SSLVERSION)) {
            $args['SSLVERSION'] = (int)$SSLVERSION;
        }

        if (isset($PROXYSERVER)) {
            $arr = explode(':', $PROXYSERVER);
            $args['PROXYSERVER'] = $arr[0];
            if (count($arr) > 1) {
                $args['PROXYPORT'] = $arr[1];
            } else {
                $args['PROXYPORT'] = 8080;
            }
        }

        //if (isset($LOCALPATH)) {
        //    $args['LOCALPATH'] = $LOCALPATH;
        //}

        return $args;
    }
}
