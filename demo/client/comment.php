<?php
include("xmlrpc.inc");

$mydir="/demo";

// define some utility functions
function bomb() { print "</body></html>"; exit(); }
function dispatch($client, $method, $args) {
	$msg=new xmlrpcmsg($method, $args);
	$resp=$client->send($msg);
	if (!$resp) { print "<p>IO error: ".$client->errstr."</p>"; bomb(); }
	if ($resp->faultCode()) {
		print "<p>There was an error: " . $resp->faultCode() . " " .
			$resp->faultString() . "</p>";
		bomb();
	}
	return php_xmlrpc_decode($resp->value());
}

// create client for discussion server
$dclient=new xmlrpc_client("${mydir}/discuss.php",
													 "xmlrpc.usefulinc.com", 80);

// check if we're posting a comment, and send it if so
@$storyid=$_POST["storyid"];
if ($storyid) {


	//	print "Returning to " . $HTTP_POST_VARS["returnto"];

	$res=dispatch($dclient, "discuss.addComment",
								array(new xmlrpcval($storyid),
											new xmlrpcval(stripslashes
																		(@$_POST["name"])),
											new xmlrpcval(stripslashes
																		(@$_POST["commenttext"]))));

	// send the browser back to the originating page
	Header("Location: ${mydir}/comment.php?catid=" .
				 $_POST["catid"] . "&chanid=" .
				 $_POST["chanid"] . "&oc=" .
				 $_POST["catid"]);
	exit(0);
}

// now we've got here, we're exploring the story store

?>
<html><head><title>meerkat browser</title></head>
<body bgcolor="#ffffff">
<h2>Meerkat integration</h2>
<?php
@$catid=$_GET["catid"];
if (@$_GET["oc"]==$catid)
	@$chanid=$_GET["chanid"];
else
	$chanid=0;

$client=new xmlrpc_client("/meerkat/xml-rpc/server.php",
													"www.oreillynet.com", 80);

if (@$_GET["comment"] &&
		(!@$_GET["cdone"])) {
	// we're making a comment on a story,
	// so display a comment form
?>
<h3>Make a comment on the story</h3>
<form method="post">
<p>Your name:<br /><input type="text" size="30" name="name" /></p>
<p>Your comment:<br /><textarea rows="5" cols="60"
   name="commenttext"></textarea></p>
<input type="submit" value="Send comment" />
<input type="hidden" name="storyid"
   value="<?php echo @$_GET["comment"];?>" />
<input type="hidden" name="chanid"
   value="<?php echo $chanid; ?>" />
<input type="hidden" name="catid"
   value="<?php echo $catid; ?>" />

</form>
<?php
} else {
	$categories=dispatch($client, "meerkat.getCategories", array());
	if ($catid)
		$sources = dispatch($client, "meerkat.getChannelsByCategory",
												array(new xmlrpcval($catid, "int")));
	if ($chanid) {
		$stories = dispatch($client, "meerkat.getItems",
					array(new xmlrpcval(
						array(
							"channel" => new xmlrpcval($chanid, "int"),
							"ids" => new xmlrpcval(1, "int"),
							"descriptions" => new xmlrpcval(200, "int"),
							"num_items" => new xmlrpcval(5, "int"),
							"dates" => new xmlrpcval(0, "int")
						), "struct")));
	}
?>
<form>
<p>Subject area:<br />
<select name="catid">
<?php
	if (!$catid)
		print "<option value=\"0\">Choose a category</option>\n";
	while(list($k,$v) = each($categories)) {
		print "<option value=\"" . $v['id'] ."\"";
		if ($v['id']==$catid) print " selected=\"selected\"";
			print ">". $v['title'] . "</option>\n";
	}
?>
</select></p>
<?php
	if ($catid) {
?>
<p>News source:<br />
<select name="chanid">
<?php
		if (!$chanid)
			print "<option value=\"0\">Choose a source</option>\n";
		while(list($k,$v) = each($sources)) {
			print "<option value=\"" . $v['id'] ."\"";
			if ($v['id']==$chanid) print "\" selected=\"selected\"";
			print ">". $v['title'] . "</option>\n";
		}
?>
</select>
</p>

<?php
		} // end if ($catid)
?>

<p><input type="submit" value="Update" /></p>
<input type="hidden" name="oc" value="<?php echo $catid; ?>" />
</form>

<?php
	 if ($chanid) {
?>

<h2>Stories available</h2>
<table>
<?php
	 while(list($k,$v) = each($stories)) {
		 print "<tr>";
		 print "<td><b>" . $v['title'] . "</b><br />";
		 print $v['description'] . "<br />";
		 print "<em><a target=\"_blank\" href=\"" .
			 $v['link'] . "\">Read full story</a> ";
		 print "<a href=\"comment.php?catid=${catid}&chanid=${chanid}&" .
			 "oc=${oc}&comment=" . $v['id'] . "\">Comment on this story</a>";
		 print "</em>";
		 print "</td>";
		 print "</tr>\n";
		 // now look for existing comments
		 $res=dispatch($dclient, "discuss.getComments",
							array(new xmlrpcval($v['id'])));
		 if (sizeof($res)>0) {
			 print "<tr><td bgcolor=\"#dddddd\"><p><b><i>" .
				 "Comments on this story:</i></b></p>";
			 for($i=0; $i<sizeof($res); $i++) {
				 $s=$res[$i];
				 print "<p><b>From:</b> " . htmlentities($s['name']) . "<br />";
				 print "<b>Comment:</b> " . htmlentities($s['comment']) . "</p>";
			 }
			 print "</td></tr>\n";
		 }
		 print "<tr><td><hr /></td></tr>\n";
	 }
?>
</table>

<?php
		} // end if ($chanid)
} // end if comment
?>
<hr />
<p>
<a href="http://meerkat.oreillynet.com"><img align="right"
	src="http://meerkat.oreillynet.com/icons/meerkat-powered.jpg"
	height="31" width="88" alt="Meerkat powered, yeah!"
	border="0" hspace="8" /></a>
</p>
</body>
</html>
