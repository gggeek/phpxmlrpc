<?php
/**
 * Helper function for the terminally lazy
 *
 * @copyright (c) 2006-2014 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 */

	/**
	 * Takes a client object, a remote method name, and a variable numbers of
	 * php values, and calls the method with the supplied parameters. The
	 * parameters are native php values and the result is an xmlrpcresp object.
	 *
	 * Notes:
	 * The function encodes the received parameters using php_xmlrpc_encode:
	 * the limitations of automatic encoding apply to this function too);
	 *
	 * the type of the value returned by the function can be changed setting
	 * beforehand the 'return_type' member of the client object to 'phpvals' -
	 * see the manual for more details about this capability).
	 *
	 *
	 * @author Toth Istvan
	 *
	 * @param xmlrpc_client client object, properly set up to connect to server
	 * @param string remote function name
	 * @param mixed $parameter1
	 * @param mixed $parameter2
	 * @param mixed $parameter3 ...
	 * @return xmlrpcresp or false on error
	 */
	function xmlrpccall_simple()
	{
		if(func_num_args() < 2)
		{
			// Incorrect
			return false;
		}
		else
		{
			$varargs = func_get_args();
			$client = array_shift($varargs);
			$remote_function_name = array_shift($varargs);
			if (!is_a($client, 'xmlrpc_client') || !is_string($remote_function_name))
			{
				return false;
			}

			$xmlrpcval_array = array();
			foreach($varargs as $parameter)
			{
				$xmlrpcval_array[] = php_xmlrpc_encode($parameter);
			}

			return $client->send(new xmlrpcmsg($remote_function_name, $xmlrpcval_array));
		}
	}
?>
