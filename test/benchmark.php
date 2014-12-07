<?php
/**
 * Benchamrking suite for the PHP-XMLRPC lib
 * @author Gaetano Giunta
 * @copyright (c) 2005-2014 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 *
 * @todo add a test for response ok in call testing?
 **/

	include(dirname(__FILE__).'/parse_args.php');

	require_once('xmlrpc.inc');

	// Set up PHP structures to be used in many tests
	$data1 = array(1, 1.0, 'hello world', true, '20051021T23:43:00', -1, 11.0, '~!@#$%^&*()_+|', false, '20051021T23:43:00');
	$data2 = array('zero' => $data1, 'one' => $data1, 'two' => $data1, 'three' => $data1, 'four' => $data1, 'five' => $data1, 'six' => $data1, 'seven' => $data1, 'eight' => $data1, 'nine' => $data1);
	$data = array($data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2, $data2);
	$keys = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine');

	$test_results=array();
	$xd = extension_loaded('xdebug') && ini_get('xdebug.profiler_enable');
	if ($xd)
		$num_tests = 1;
	else
		$num_tests = 10;

	$title = 'XML-RPC Benchmark Tests';

	if(isset($_SERVER['REQUEST_METHOD']))
	{
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\" xml:lang=\"en\">\n<head>\n<title>$title</title>\n</head>\n<body>\n<h1>$title</h1>\n<pre>\n";
	}
	else
	{
		echo "$title\n\n";
	}

	if(isset($_SERVER['REQUEST_METHOD']))
	{
		echo "<h3>Using lib version: $xmlrpcVersion on PHP version: ".phpversion()."</h3>\n";
		if ($xd) echo "<h4>XDEBUG profiling enabled: skipping remote tests. Trace file is: ".htmlspecialchars(xdebug_get_profiler_filename())."</h4>\n";
		flush();
		ob_flush();
	}
	else
	{
		echo "Using lib version: $xmlrpcVersion on PHP version: ".phpversion()."\n";
		if ($xd) echo "XDEBUG profiling enabled: skipping remote tests\nTrace file is: ".xdebug_get_profiler_filename()."\n";
	}

	// test 'old style' data encoding vs. 'automatic style' encoding
	begin_test('Data encoding (large array)', 'manual encoding');
	for ($i = 0; $i < $num_tests; $i++)
	{
		$vals = array();
		for ($j = 0; $j < 10; $j++)
		{
			$valarray = array();
			foreach ($data[$j] as $key => $val)
			{
				$values = array();
				$values[] = new xmlrpcval($val[0], 'int');
				$values[] = new xmlrpcval($val[1], 'double');
				$values[] = new xmlrpcval($val[2], 'string');
				$values[] = new xmlrpcval($val[3], 'boolean');
				$values[] = new xmlrpcval($val[4], 'dateTime.iso8601');
				$values[] = new xmlrpcval($val[5], 'int');
				$values[] = new xmlrpcval($val[6], 'double');
				$values[] = new xmlrpcval($val[7], 'string');
				$values[] = new xmlrpcval($val[8], 'boolean');
				$values[] = new xmlrpcval($val[9], 'dateTime.iso8601');
				$valarray[$key] = new xmlrpcval($values, 'array');
			}
			$vals[] = new xmlrpcval($valarray, 'struct');
		}
		$value = new xmlrpcval($vals, 'array');
		$out = $value->serialize();
	}
	end_test('Data encoding (large array)', 'manual encoding', $out);

	begin_test('Data encoding (large array)', 'automatic encoding');
	for ($i = 0; $i < $num_tests; $i++)
	{
		$value = php_xmlrpc_encode($data, array('auto_dates'));
		$out = $value->serialize();
	}
	end_test('Data encoding (large array)', 'automatic encoding', $out);

	if (function_exists('xmlrpc_set_type'))
	{
	begin_test('Data encoding (large array)', 'xmlrpc-epi encoding');
	for ($i = 0; $i < $num_tests; $i++)
	{
		for ($j = 0; $j < 10; $j++)
			foreach ($keys as $k)
			{
				xmlrpc_set_type($data[$j][$k][4], 'datetime');
				xmlrpc_set_type($data[$j][$k][8], 'datetime');
			}
		$out = xmlrpc_encode($data);
	}
	end_test('Data encoding (large array)', 'xmlrpc-epi encoding', $out);
	}

	// test 'old style' data decoding vs. 'automatic style' decoding
	$dummy = new xmlrpcmsg('');
	$out = new xmlrpcresp($value);
	$in = '<?xml version="1.0" ?>'."\n".$out->serialize();

	begin_test('Data decoding (large array)', 'manual decoding');
	for ($i = 0; $i < $num_tests; $i++)
	{
		$response =& $dummy->ParseResponse($in, true);
		$value = $response->value();
		$result = array();
		for ($k = 0; $k < $value->arraysize(); $k++)
		{
			$val1 = $value->arraymem($k);
			$out = array();
			while (list($name, $val) = $val1->structeach())
			{
				$out[$name] = array();
				for ($j = 0; $j < $val->arraysize(); $j++)
				{
					$data = $val->arraymem($j);
					$out[$name][] = $data->scalarval();
				}
			} // while
			$result[] = $out;
		}
	}
	end_test('Data decoding (large array)', 'manual decoding', $result);

	begin_test('Data decoding (large array)', 'automatic decoding');
	for ($i = 0; $i < $num_tests; $i++)
	{
		$response =& $dummy->ParseResponse($in, true, 'phpvals');
		$value = $response->value();
	}
	end_test('Data decoding (large array)', 'automatic decoding', $value);

	if (function_exists('xmlrpc_decode'))
	{
	begin_test('Data decoding (large array)', 'xmlrpc-epi decoding');
	for ($i = 0; $i < $num_tests; $i++)
	{
		$response =& $dummy->ParseResponse($in, true, 'xml');
		$value = xmlrpc_decode($response->value());
	}
	end_test('Data decoding (large array)', 'xmlrpc-epi decoding', $value);
	}

	if (!$xd) {

	/// test multicall vs. many calls vs. keep-alives
	$value = php_xmlrpc_encode($data1, array('auto_dates'));
	$msg = new xmlrpcmsg('interopEchoTests.echoValue', array($value));
	$msgs=array();
	for ($i = 0; $i < 25; $i++)
		$msgs[] = $msg;
	$server = explode(':', $LOCALSERVER);
	if(count($server) > 1)
	{
		$c = new xmlrpc_client($URI, $server[0], $server[1]);
	}
	else
	{
		$c = new xmlrpc_client($URI, $LOCALSERVER);
	}
	// do not interfere with http compression
	$c->accepted_compression = array();
	//$c->debug=true;

	if (function_exists('gzinflate')) {
		$c->accepted_compression = null;
	}
	begin_test('Repeated send (small array)', 'http 10');
	$response = array();
	for ($i = 0; $i < 25; $i++)
	{
		$resp =& $c->send($msg);
		$response[] = $resp->value();
	}
	end_test('Repeated send (small array)', 'http 10', $response);

	if (function_exists('curl_init'))
	{
		begin_test('Repeated send (small array)', 'http 11 w. keep-alive');
		$response = array();
		for ($i = 0; $i < 25; $i++)
		{
			$resp =& $c->send($msg, 10, 'http11');
			$response[] = $resp->value();
		}
		end_test('Repeated send (small array)', 'http 11 w. keep-alive', $response);

		$c->keepalive = false;
		begin_test('Repeated send (small array)', 'http 11');
		$response = array();
		for ($i = 0; $i < 25; $i++)
		{
			$resp =& $c->send($msg, 10, 'http11');
			$response[] = $resp->value();
		}
		end_test('Repeated send (small array)', 'http 11', $response);
	}

	begin_test('Repeated send (small array)', 'multicall');
	$response =& $c->send($msgs);
	foreach ($response as $key =>& $val)
	{
	    $val = $val->value();
	}
	end_test('Repeated send (small array)', 'multicall', $response);

	if (function_exists('gzinflate'))
	{
		$c->accepted_compression = array('gzip');
		$c->request_compression = 'gzip';

		begin_test('Repeated send (small array)', 'http 10 w. compression');
		$response = array();
		for ($i = 0; $i < 25; $i++)
		{
			$resp =& $c->send($msg);
			$response[] = $resp->value();
		}
		end_test('Repeated send (small array)', 'http 10 w. compression', $response);

        if (function_exists('curl_init'))
        {
            begin_test('Repeated send (small array)', 'http 11 w. keep-alive and compression');
            $response = array();
            for ($i = 0; $i < 25; $i++)
            {
                $resp =& $c->send($msg, 10, 'http11');
                $response[] = $resp->value();
            }
            end_test('Repeated send (small array)', 'http 11 w. keep-alive and compression', $response);

            $c->keepalive = false;
            begin_test('Repeated send (small array)', 'http 11 w. compression');
            $response = array();
            for ($i = 0; $i < 25; $i++)
            {
                $resp =& $c->send($msg, 10, 'http11');
                $response[] = $resp->value();
            }
            end_test('Repeated send (small array)', 'http 11 w. compression', $response);
        }

        begin_test('Repeated send (small array)', 'multicall w. compression');
        $response =& $c->send($msgs);
        foreach ($response as $key =>& $val)
        {
            $val = $val->value();
        }
        end_test('Repeated send (small array)', 'multicall w. compression', $response);
	}

	} // end of 'if no xdebug profiling'

	function begin_test($test_name, $test_case)
	{
		global $test_results;
		if (!isset($test_results[$test_name]))
			$test_results[$test_name]=array();
		$test_results[$test_name][$test_case] = array();
		$test_results[$test_name][$test_case]['time'] = microtime(true);
	}

	function end_test($test_name, $test_case, $test_result)
	{
		global $test_results;
		$end = microtime(true);
		if (!isset($test_results[$test_name][$test_case]))
			trigger_error('ending test that was not sterted');
		$test_results[$test_name][$test_case]['time'] = $end - $test_results[$test_name][$test_case]['time'];
		$test_results[$test_name][$test_case]['result'] = $test_result;
		echo '.';
		flush();
		ob_flush();
	}


	echo "\n";
	foreach($test_results as $test => $results)
	{
		echo "\nTEST: $test\n";
		foreach ($results as $case => $data)
			echo "  $case: {$data['time']} secs - Output data CRC: ".crc32(serialize($data['result']))."\n";
	}


	if(isset($_SERVER['REQUEST_METHOD']))
	{
		echo "\n</pre>\n</body>\n</html>\n";
	}
?>