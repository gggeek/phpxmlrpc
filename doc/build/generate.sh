#!/bin/bash

set -e

cd "$(dirname -- "$(dirname -- "$(realpath "${BASH_SOURCE[0]}")")")"

PHPDOC='./build/phpDocumentor'
DOCBOOKXSLTDIR='./build/docbook-xsl'
#PHPDOC='php ./build/vendor/bin/phpdoc'
#DOCBOOKXSLTDIR='./build/vendor/docbook/docbook-xsl'

### API docs

$PHPDOC run --cache-folder './build/.phpdoc' -d "$(realpath ../src/)" -t './api' --title PHPXMLRPC --defaultpackagename PHPXMLRPC

### User Manual

# HTML (single file) from asciidoc
# Not generated any more - the github rendering is good enough for online viewing, and for local viewing the html+asciidoc.js
# solution is preferred
#asciidoctor -d book -o './manual/phpxmlrpc_manual.html' './manual/phpxmlrpc_manual.adoc'

# PDF file from asciidoc direct conversion
asciidoctor-pdf manual/phpxmlrpc_manual.adoc

# PDF file from asciidoc via docbook
## 1. get docbook
#asciidoctor -d book -b docbook -o './build/phpxmlrpc_manual.xml' './manual/phpxmlrpc_manual.adoc'
## 2a. then PDF via apache fop
##php ./build/convert.php './build/phpxmlrpc_manual.xml' './build/custom.fo.xsl' './manual/phpxmlrpc_manual.fo.xml' "$DOCBOOKXSLTDIR"
##fop ./manual/phpxmlrpc_manual.fo.xml ./manual/phpxmlrpc_manual.pdf
##rm ./manual/phpxmlrpc_manual.fo.xml
## 2b. then PDF via pandoc+xelatex, using a nice template
#cd build; pandoc -s --from=docbook --pdf-engine=xelatex --data-dir=. --template=eisvogel -o ../manual/phpxmlrpc_manual.pdf phpxmlrpc_manual.xml; cd ..
#rm ./build/phpxmlrpc_manual.xml
