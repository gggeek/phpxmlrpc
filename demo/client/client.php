<html>
<head><title>xmlrpc</title></head>
<body>
<h1>Getstatename demo</h1>
<h2>Send a U.S. state number to the server and get back the state name</h2>
<h3>The code demonstrates usage of the php_xmlrpc_encode function</h3>
<?php
	include("xmlrpc.inc");

	// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
	if(!isset($HTTP_POST_VARS) && isset($_POST))
	{
		$HTTP_POST_VARS = $_POST;
	}

	if(isset($HTTP_POST_VARS["stateno"]) && $HTTP_POST_VARS["stateno"]!="")
	{
		$stateno=(integer)$HTTP_POST_VARS["stateno"];
		$f=new xmlrpcmsg('examples.getStateName',
			array(php_xmlrpc_encode($stateno))
		);
		print "<pre>Sending the following request:\n\n" . htmlentities($f->serialize()) . "\n\nDebug info of server data follows...\n\n";
		$c=new xmlrpc_client("/server.php", "phpxmlrpc.sourceforge.net", 80);
		$c->setDebug(1);
		$r=&$c->send($f);
		if(!$r->faultCode())
		{
			$v=$r->value();
			print "</pre><br/>State number " . $stateno . " is "
				. htmlspecialchars($v->scalarval()) . "<br/>";
			// print "<HR>I got this value back<BR><PRE>" .
			//  htmlentities($r->serialize()). "</PRE><HR>\n";
		}
		else
		{
			print "An error occurred: ";
			print "Code: " . htmlspecialchars($r->faultCode())
				. " Reason: '" . htmlspecialchars($r->faultString()) . "'</pre><br/>";
		}
	}
	else
	{
		$stateno = "";
	}

	print "<form action=\"client.php\" method=\"POST\">
<input name=\"stateno\" value=\"" . $stateno . "\"><input type=\"submit\" value=\"go\" name=\"submit\"></form>
<p>Enter a state number to query its name</p>";

?>
</body>
</html>
