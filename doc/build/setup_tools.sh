#!/bin/bash

# Has to be run as a sudoer

set -e

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    asciidoctor fop git unzip zip

PHPPKG=$(dpkg --list | grep php | grep cli | grep -v -F '(default)' | awk '{print $2}')
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    asciidoctor \
    curl \
    gpg \
    "${PHPPKG}" \
    "${PHPPKG/cli/xsl}" \
    unzip

cd "$(dirname -- "${BASH_SOURCE[0]}")"

# Install phpdocumentor and the docbook xslt using Composer
# Sadly this method, as of 2023/1, does not allow installing version 3.3.0 and later
## in case we are switching between php versions, always reinstall every tool with the correct version...
#if [ -f composer.lock ]; then
#    rm composer.lock
#fi
#composer install --no-dev
# required as of phpdoc 3.1.2
#sed -r -i -e "s|resource: '%kernel\\.project_dir%/vendor/phpdocumentor/reflection/src/phpDocumentor/Reflection/Php'|resource: '%kernel.project_dir%/../reflection/src/phpDocumentor/Reflection/Php'|g" ./vendor/phpdocumentor/phpdocumentor/config/reflection.yaml
#sudo chown -R "$(id -u):$(id -g)" vendor

# Install the DocBook xslt
if [ ! -d docbook-xsl ]; then
    curl -fsSL -o dbx.zip "https://github.com/docbook/xslt10-stylesheets/releases/download/release/1.79.2/docbook-xsl-1.79.2.zip"
    unzip dbx.zip
    mv docbook-xsl-1.79.2 docbook-xsl
    rm dbx.zip
fi

# Install phpdocumentor via Phive
# @todo wouldn't it be quicker to just scan the github page for the last release and just get the phar?
curl -fsSL -o phive "https://phar.io/releases/phive.phar"
#curl -fsSL -o -O phive.phar.asc "https://phar.io/releases/phive.phar.asc"
#gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x6AF725270AB81E04D79442549D8A98B29B2D5D79
#gpg --verify phive.phar.asc phive.phar
#rm phive.phar.asc
chmod +x phive
./phive install --trust-gpg-keys F33A0AF69AF7A8B15017DB526DA3ACC4991FFAE5 -t "$(pwd)" phpdocumentor

#sudo chown -R "$(id -u):$(id -g)" vendor
