<?php

	include("xmlrpc.inc");
	include("xmlrpcs.inc");

	$addcomment_sig=array(array($xmlrpcInt, $xmlrpcString, $xmlrpcString, $xmlrpcString));

	$addcomment_doc='Adds a comment to an item. The first parameter
is the item ID, the second the name of the commenter, and the third
is the comment itself. Returns the number of comments against that
ID.';

	function addcomment($m)
	{
		global $xmlrpcerruser;
		$err="";
		// since validation has already been carried out for us,
		// we know we got exactly 3 string values
		$n = php_xmlrpc_decode($m);
		$msgID = $n[0];
		$name  = $n[1];
		$comment = $n[2];

		$dbh=dba_open("/tmp/comments.db", "c", "db2");
		if($dbh)
		{
			$countID="${msgID}_count";
			if(dba_exists($countID, $dbh))
			{
				$count=dba_fetch($countID, $dbh);
			}
			else
			{
				$count=0;
			}
			// add the new comment in
			dba_insert($msgID . "_comment_${count}", $comment, $dbh);
			dba_insert($msgID . "_name_${count}", $name, $dbh);
			$count++;
			dba_replace($countID, $count, $dbh);
			dba_close($dbh);
		}
		else
		{
			$err="Unable to open comments database.";
		}
		// if we generated an error, create an error return response
		if($err)
		{
			return new xmlrpcresp(0, $xmlrpcerruser, $err);
		}
		else
		{
			// otherwise, we create the right response
			// with the state name
			return new xmlrpcresp(new xmlrpcval($count, "int"));
		}
	}

	$getcomments_sig=array(array($xmlrpcArray, $xmlrpcString));

	$getcomments_doc='Returns an array of comments for a given ID, which
is the sole argument. Each array item is a struct containing name
and comment text.';

	function getcomments($m)
	{
		global $xmlrpcerruser;
		$err="";
		$ra=array();
		// get the first param
		if(XMLRPC_EPI_ENABLED == '1')
		{
			$msgID=xmlrpc_decode($m->getParam(0));
		}
		else
		{
			$msgID=php_xmlrpc_decode($m->getParam(0));
		}
		$dbh=dba_open("/tmp/comments.db", "r", "db2");
		if($dbh)
		{
			$countID="${msgID}_count";
			if(dba_exists($countID, $dbh))
			{
				$count=dba_fetch($countID, $dbh);
				for($i=0; $i<$count; $i++)
				{
					$name=dba_fetch("${msgID}_name_${i}", $dbh);
					$comment=dba_fetch("${msgID}_comment_${i}", $dbh);
					// push a new struct onto the return array
					$ra[] = array(
						"name" => $name,
						"comment" => $comment
						);
				}
			}
		}
		// if we generated an error, create an error return response
		if($err)
		{
			return new xmlrpcresp(0, $xmlrpcerruser, $err);
		}
		else
		{
			// otherwise, we create the right response
			// with the state name
			return new xmlrpcresp(php_xmlrpc_encode($ra));
		}
	}

	$s = new xmlrpc_server(array(
		"discuss.addComment" => array(
			"function" => "addcomment",
			"signature" => $addcomment_sig,
			"docstring" => $addcomment_doc
		),
		"discuss.getComments" => array(
			"function" => "getcomments",
			"signature" => $getcomments_sig,
			"docstring" => $getcomments_doc
		)
	));
?>
