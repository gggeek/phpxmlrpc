<?php
/**
 * XMLRPC server acting as proxy for requests to other servers
 * (useful e.g. for ajax-originated calls that can only connect back to
 * the originating server)
 *
 * @author Gaetano Giunta
 * @copyright (C) 2006-2014 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 */

	include("xmlrpc.inc");
	include("xmlrpcs.inc");

	/**
	* Forward an xmlrpc request to another server, and return to client the response received.
	* @param xmlrpcmsg $m (see method docs below for a description of the expected parameters)
	* @return xmlrpcresp
	*/
	function forward_request($m)
	{
		// create client
		$timeout = 0;
		$url = php_xmlrpc_decode($m->getParam(0));
		$c = new xmlrpc_client($url);
		if ($m->getNumParams() > 3)
		{
			// we have to set some options onto the client.
			// Note that if we do not untaint the received values, warnings might be generated...
			$options = php_xmlrpc_decode($m->getParam(3));
			foreach($options as $key => $val)
			{
				switch($key)
				{
					case 'Cookie':
						break;
					case 'Credentials':
						break;
					case 'RequestCompression':
						$c->setRequestCompression($val);
						break;
					case 'SSLVerifyHost':
						$c->setSSLVerifyHost($val);
						break;
					case 'SSLVerifyPeer':
						$c->setSSLVerifyPeer($val);
						break;
					case 'Timeout':
						$timeout = (integer) $val;
						break;
				} // switch
			}
		}

		// build call for remote server
		/// @todo find a weay to forward client info (such as IP) to server, either
		/// - as xml comments in the payload, or
		/// - using std http header conventions, such as X-forwarded-for...
		$method = php_xmlrpc_decode($m->getParam(1));
		$pars = $m->getParam(2);
		$m = new xmlrpcmsg($method);
		for ($i = 0; $i < $pars->arraySize(); $i++)
		{
			$m->addParam($pars->arraymem($i));
		}

		// add debug info into response we give back to caller
		xmlrpc_debugmsg("Sending to server $url the payload: ".$m->serialize());
		return $c->send($m, $timeout);
	}

	// run the server
	$server = new xmlrpc_server(
		array(
			'xmlrpcproxy.call' => array(
				'function' => 'forward_request',
				'signature' => array(
					array('mixed', 'string', 'string', 'array'),
					array('mixed', 'string', 'string', 'array', 'stuct'),
				),
				'docstring' => 'forwards xmlrpc calls to remote servers. Returns remote method\'s response. Accepts params: remote server url (might include basic auth credentials), method name, array of params, and (optionally) a struct containing call options'
			)
		)
	);
?>
