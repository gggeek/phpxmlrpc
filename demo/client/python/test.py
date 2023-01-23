#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import xmlrpc.client
import base64
import sys

server = xmlrpc.client.ServerProxy("http://localhost/demo/server/server.php")

try:
    print ("Got '" + server.examples.getStateName(32) + "'")

    r = server.examples.echo('Three "blind" mice - ' + "See 'how' they run")
    print (r)

    # name/age example. this exercises structs and arrays
    a = [
            {'name': 'Dave', 'age': 35}, {'name': 'Edd', 'age': 45 },
            {'name': 'Fred', 'age': 23}, {'name': 'Barney', 'age': 36 }
        ]
    r = server.examples.sortByAge(a)
    print (r)

    # test base 64
    r = server.examples.decode64(b'Mary had a little lamb She tied it to a pylon')
    print (r)

except xmlrpc.client.Fault as err:
    print("A fault occurred")
    print("Fault code: %d" % err.faultCode)
    print("Fault string: %s" % err.faultString)

    # let the test suite know that here was a fault
    sys.exit(err.faultCode)
