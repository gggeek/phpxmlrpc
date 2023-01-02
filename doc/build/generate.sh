#!/bin/bash

set -e

cd "$(dirname -- "$(dirname -- "${BASH_SOURCE[0]}")")"

### API docs

php ./build/vendor/bin/phpdoc run --cache-folder './build/.phpdoc' -d "$(realpath ../src/)" -t './api' --title PHPXMLRPC --defaultpackagename PHPXMLRPC

### User Manual

# HTML (single file) from asciidoc
# Not generated any more - the github rendering is good enough for online viewing, and for local viewing the html+asciidoc.js
# solution is preferred
#asciidoctor -d book -o './manual/phpxmlrpc_manual.html' './manual/phpxmlrpc_manual.adoc'

# PDF file from asciidoc via docbook and apache fop
# @todo test: is it faster to use pandoc+texlive (including tools download time)? Does it render better?
asciidoctor -d book -b docbook -o './build/phpxmlrpc_manual.xml' './manual/phpxmlrpc_manual.adoc'
php ./build/convert.php './build/phpxmlrpc_manual.xml' './build/custom.fo.xsl' './manual/phpxmlrpc_manual.fo.xml'
fop ./manual/phpxmlrpc_manual.fo.xml ./manual/phpxmlrpc_manual.pdf
rm ./build/phpxmlrpc_manual.xml ./manual/phpxmlrpc_manual.fo.xml
