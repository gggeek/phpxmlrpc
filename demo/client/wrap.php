<html>
<head><title>xmlrpc</title></head>
<body>
<h1>Webservice wrappper demo</h1>
<h2>Wrap methods exposed by server into php functions</h2>
<h3>The code demonstrates usage of the most automagic client usage possible:<br/>
1) client that returns php values instead of xmlrpcval objects<br/>
2) wrapping of remote methods into php functions
</h3>
<?php
	include("xmlrpc.inc");
	include("xmlrpc_wrappers.inc");

	$c = new xmlrpc_client("/server.php", "phpxmlrpc.sourceforge.net", 80);
	$c->return_type = 'phpvals'; // let client give us back php values instead of xmlrpcvals
	$r =& $c->send(new xmlrpcmsg('system.listMethods'));
	if($r->faultCode())
	{
		echo "<p>Server methods list could not be retrieved: error '".htmlspecialchars($r->faultString())."'</p>\n";
	}
	else
	{
		$testcase = '';
		echo "<p>Server methods list retrieved, now wrapping it up...</p>\n<ul>\n";
		foreach($r->value() as $methodname) // $r->value is an array of strings
		{
			// do not wrap remote server system methods
			if (strpos($methodname, 'system.') !== 0)
			{
				$funcname = wrap_xmlrpc_method($c, $methodname);
				if($funcname)
				{
					echo "<li>Remote server method ".htmlspecialchars($methodname)." wrapped into php function ".$funcname."</li>\n";
				}
				else
				{
					echo "<li>Remote server method ".htmlspecialchars($methodname)." could not be wrapped!</li>\n";
				}
				if($methodname == 'examples.getStateName')
				{
					$testcase = $funcname;
				}
			}
		}
		echo "</ul>\n";
		if($testcase)
		{
			echo "Now testing function $testcase: remote method to convert U.S. state number into state name";
			$statenum = 25;
			$statename = $testcase($statenum, 2);
			echo "State number $statenum is ".htmlspecialchars($statename);
		}
	}
?>
</body>
</html>
