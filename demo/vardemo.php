<html>
<head><title>xmlrpc</title></head>
<body>
<?php
	include("xmlrpc.inc");

	$f = new xmlrpcmsg('examples.getStateName');

	print "<h3>Testing value serialization</h3>\n";

	$v = new xmlrpcval(23, "int");
	print "<PRE>" . htmlentities($v->serialize()) . "</PRE>";
	$v = new xmlrpcval("What are you saying? >> << &&");
	print "<PRE>" . htmlentities($v->serialize()) . "</PRE>";

	$v = new xmlrpcval(array(
		new xmlrpcval("ABCDEFHIJ"),
		new xmlrpcval(1234, 'int'),
		new xmlrpcval(1, 'boolean')),
		"array"
	);

	print "<PRE>" . htmlentities($v->serialize()) . "</PRE>";

	$v = new xmlrpcval(
		array(
			"thearray" => new xmlrpcval(
				array(
					new xmlrpcval("ABCDEFHIJ"),
					new xmlrpcval(1234, 'int'),
					new xmlrpcval(1, 'boolean'),
					new xmlrpcval(0, 'boolean'),
					new xmlrpcval(true, 'boolean'),
					new xmlrpcval(false, 'boolean')
				),
				"array"
			),
			"theint" => new xmlrpcval(23, 'int'),
			"thestring" => new xmlrpcval("foobarwhizz"),
			"thestruct" => new xmlrpcval(
				array(
					"one" => new xmlrpcval(1, 'int'),
					"two" => new xmlrpcval(2, 'int')
				),
				"struct"
			)
		),
		"struct"
	);

	print "<PRE>" . htmlentities($v->serialize()) . "</PRE>";

	$w = new xmlrpcval(array($v, new xmlrpcval("That was the struct!")), "array");

	print "<PRE>" . htmlentities($w->serialize()) . "</PRE>";

	$w = new xmlrpcval("Mary had a little lamb,
Whose fleece was white as snow,
And everywhere that Mary went
the lamb was sure to go.

Mary had a little lamb
She tied it to a pylon
Ten thousand volts went down its back
And turned it into nylon", "base64"
	);
	print "<PRE>" . htmlentities($w->serialize()) . "</PRE>";
	print "<PRE>Value of base64 string is: '" . $w->scalarval() . "'</PRE>";

	$f->method('');
	$f->addParam(new xmlrpcval("41", "int"));

	print "<h3>Testing request serialization</h3>\n";
	$op = $f->serialize();
	print "<PRE>" . htmlentities($op) . "</PRE>";

	print "<h3>Testing ISO date format</h3><pre>\n";

	$t = time();
	$date = iso8601_encode($t);
	print "Now is $t --> $date\n";
	print "Or in UTC, that is " . iso8601_encode($t, 1) . "\n";
	$tb = iso8601_decode($date);
	print "That is to say $date --> $tb\n";
	print "Which comes out at " . iso8601_encode($tb) . "\n";
	print "Which was the time in UTC at " . iso8601_decode($date, 1) . "\n";

	print "</pre>\n";
?>
</body>
</html>
