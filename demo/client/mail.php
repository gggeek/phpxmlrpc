<?php
// Allow users to see the source of this file even if PHP is not configured for it
if ((isset($HTTP_GET_VARS['showSource']) && $HTTP_GET_VARS['showSource']) ||
	(isset($_GET['showSource']) && $_GET['showSource']))
	{ highlight_file(__FILE__); die(); }
?>
<html>
<head><title>xmlrpc</title></head>
<body>
<h1>Mail demo</h1>
<p>This form enables you to send mail via an XML-RPC server. For public use
only the "Userland" server will work (see <a href="http://www.xmlrpc.com/discuss/msgReader$598">Dave Winer's message</a>).
When you press <kbd>Send</kbd> this page will reload, showing you the XML-RPC request sent to the host server, the XML-RPC response received and the internal evaluation done by the PHP implementation.</p>
<p>You can find the source to this page here: <a href="mail.php?showSource=1">mail.php</a><br/>
And the source to a functionally identical mail-by-XML-RPC server in the file <a href="../server/server.php?showSource=1">server.php</a> included with the library (look for the 'mail_send' method)</p>
<?php
include("xmlrpc.inc");

// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
if (!isset($HTTP_POST_VARS) && isset($_POST))
	$HTTP_POST_VARS = $_POST;

if (isset($HTTP_POST_VARS["server"]) && $HTTP_POST_VARS["server"]) {
	if ($HTTP_POST_VARS["server"]=="Userland") {
		$XP="/RPC2"; $XS="206.204.24.2";
	} else {
		$XP="/xmlrpc/server.php"; $XS="pingu.heddley.com";
	}
	$f=new xmlrpcmsg('mail.send');
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailto"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailsub"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailmsg"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailfrom"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailcc"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailbcc"]));
	$f->addParam(new xmlrpcval("text/plain"));

	$c=new xmlrpc_client($XP, $XS, 80);
	$c->setDebug(2);
	$r=&$c->send($f);
	if (!$r->faultCode()) {
		print "Mail sent OK<br/>\n";
	} else {
		print "<fonr color=\"red\">";
		print "Mail send failed<br/>\n";
		print "Fault: ";
		print "Code: " . htmlspecialchars($r->faultCode()) .
	  " Reason: '" . htmlspecialchars($r->faultString()) . "'<br/>";
		print "</font><br/>";
	}
}
?>
<form method="POST">
Server <select name="server"><option value="Userland">Userland</option>
<option value="UsefulInc">UsefulInc private server</option></select>
<hr/>
From <input size="60" name="mailfrom" value=""/><br/>
<hr/>
To <input size="60" name="mailto" value=""/><br/>
Cc <input size="60" name="mailcc" value=""/><br/>
Bcc <input size="60" name="mailbcc" value=""/><br/>
<hr/>
Subject <input size="60" name="mailsub" value="A message from xmlrpc"/>
<hr/>
Body <textarea rows="7" cols="60" name="mailmsg">Your message here</textarea><br/>
<input type="Submit" value="Send"/>
</form>
</body>
</html>
