XMLRPC for PHP (a.k.a. PHPXMLRPC)
=================================

A php library for building xml-rpc clients and servers.

Requirements and Installation
-----------------------------

The recommended way to install this library is using Composer.

Detailed installation instructions are in the [INSTALL.md](INSTALL.md) file, along with system requirements listing.

Documentation
-------------

See the documentation page at [gggeek.github.io/phpxmlrpc](https://gggeek.github.io/phpxmlrpc) for a list of the
library main features and all project related information, including information about online resources such as
debuggers and demo servers.

The user manual can be found in the doc/manual directory: [phpxmlrpc_manual.adoc](doc/manual/phpxmlrpc_manual.adoc).
It includes sections about upgrading from previous versions as well as running the library's testing suite and bundled
debugger.

The manual is formatted as an asciidoc file - if viewing it locally, it is recommended to either use an IDE which can
natively render asciidoc, or view it as html with a browser by serving it via a webserver and accessing
/doc/manual/index.html

Automatically-generated documentation for the API is available online at [http://gggeek.github.io/phpxmlrpc/doc-4/api/index.html](http://gggeek.github.io/phpxmlrpc/doc-4/api/index.html)

You are encouraged to look also at the code examples found in the demo/ directory.

Note: to reduce the size of the download, the demo files are not part of the default package installed with Composer.
You can either check them out online at https://github.com/gggeek/phpxmlrpc/tree/master/demo, download them as a separate
tarball from https://github.com/gggeek/phpxmlrpc/releases or make sure they are available locally by installing the
library using Composer option `--prefer-install=source`. Whatever the method chosen, make sure that the demo folder is
not directly accessible from the internet, i.e. it is not within the webserver root directory).

License
-------
Use of this software is subject to the terms in the [license.txt](license.txt) file


[![License](https://poser.pugx.org/phpxmlrpc/phpxmlrpc/license)](https://packagist.org/packages/phpxmlrpc/phpxmlrpc)
[![Latest Stable Version](https://poser.pugx.org/phpxmlrpc/phpxmlrpc/v/stable)](https://packagist.org/packages/phpxmlrpc/phpxmlrpc)
[![Total Downloads](https://poser.pugx.org/phpxmlrpc/phpxmlrpc/downloads)](https://packagist.org/packages/phpxmlrpc/phpxmlrpc)

[![Build Status](https://github.com/gggeek/phpxmlrpc/actions/workflows/ci.yaml/badge.svg)](https://github.com/gggeek/phpxmlrpc/actions/workflows/ci.yml)
[![Code Coverage](https://codecov.io/gh/gggeek/phpxmlrpc/branch/master/graph/badge.svg)](https://app.codecov.io/gh/gggeek/phpxmlrpc)
