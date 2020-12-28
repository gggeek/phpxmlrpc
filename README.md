XMLRPC for PHP
==============
A php library for building xml-rpc clients and servers.

Installation
------------
The recommended way to install this library is using Composer.

Detailed installation instructions are in the [INSTALL.md](INSTALL.md) file, along with system requirements listing.

Documentation
-------------

See the documentation page at [gggeek.github.io/phpxmlrpc](https://gggeek.github.io/phpxmlrpc) for a list of the
library main features and all project related information.

The user manual can be found in the doc/manual directory, in Asciidoc format: [phpxmlrpc_manual.adoc](doc/manual/phpxmlrpc_manual.adoc)

Release tarballs also contain HTML and PDF versions of the manual, as well as an automatically generated API documentation.

*NB: the user manual has not been updated yet with all the changes made in version 4. Please consider it unreliable!*

*You are encouraged to look instead the code examples found in the demo/ directory*

Upgrading
---------
If you are upgrading from version 3 or earlier you have two options:

1. adapt your code to the new API (all changes needed are described in [doc/api_changes_v4.md](doc/api_changes_v4.md))

2. use instead the *compatibility layer* which is provided. Instructions and pitfalls described in [doc/api_changes_v4.md](doc/api_changes_v4.md##enabling-compatibility-with-legacy-code)

In any case, read carefully the docs in [doc/api_changes_v4.md](doc/api_changes_v4.md) and report back any undocumented
issue using GitHub.

Running tests
-------------

The recommended way to run the library test suite is via the provided Docker containers.
A handy shell script is available that simplifies usage of Docker.

The full sequence of operations is:

    ./tests/ci/vm.sh build
    ./tests/ci/vm.sh start
    ./tests/ci/vm.sh runtests
    ./tests/ci/vm.sh stop

    # and, once you have finished all testing related work:
    ./tests/ci/vm.sh cleanup

By default tests are run using php 7.0 in a Container based on Ubuntu 16 Xenial.
You can change the version of PHP and Ubuntu in use by setting the environment variables PHP_VERSION and UBUNTU_VERSION
before building the Container.

To generate the code-coverage report, run `./tests/ci/vm.sh runcoverage`

License
-------
Use of this software is subject to the terms in the [license.txt](license.txt) file


[![License](https://poser.pugx.org/phpxmlrpc/phpxmlrpc/license)](https://packagist.org/packages/phpxmlrpc/phpxmlrpc)
[![Latest Stable Version](https://poser.pugx.org/phpxmlrpc/phpxmlrpc/v/stable)](https://packagist.org/packages/phpxmlrpc/phpxmlrpc)
[![Total Downloads](https://poser.pugx.org/phpxmlrpc/phpxmlrpc/downloads)](https://packagist.org/packages/phpxmlrpc/phpxmlrpc)

[![Build Status](https://travis-ci.com/gggeek/phpxmlrpc.svg)](https://travis-ci.com/gggeek/phpxmlrpc)
[![Code Coverage](https://scrutinizer-ci.com/g/gggeek/phpxmlrpc/badges/coverage.png)](https://scrutinizer-ci.com/g/gggeek/phpxmlrpc)
