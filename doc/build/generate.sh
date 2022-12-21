#!/bin/bash

set -e

cd "$(dirname -- "$(dirname -- "${BASH_SOURCE[0]}")")"

# API docs

php ./build/vendor/bin/phpdoc run --cache-folder './build/.phpdoc' -d "$(realpath ../src/)" -t './api' --title PHP-XMLRPC --defaultpackagename PHPXMLRPC

# User Manual

# HTML (single file) from asciidoc
asciidoctor -d book -o './manual/phpxmlrpc_manual.html' './manual/phpxmlrpc_manual.adoc'

# PDF file from asciidoc via docbook and apache fop
asciidoctor -d book -b docbook -o './build/phpxmlrpc_manual.xml' './manual/phpxmlrpc_manual.adoc'
php ./build/convert.php './build/phpxmlrpc_manual.xml' './build/custom.fo.xsl' './manual/phpxmlrpc_manual.fo.xml'
fop ./manual/phpxmlrpc_manual.fo.xml ./manual/phpxmlrpc_manual.pdf
rm ./build/phpxmlrpc_manual.xml ./manual/phpxmlrpc_manual.fo.xml
