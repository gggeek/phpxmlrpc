#!/bin/bash

# Has to be run as a sudoer

set -e

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    asciidoctor fop git unzip zip

PHPPKG=$(dpkg --list | grep php | grep cli | awk '{print $2}')
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y "${PHPPKG/cli/xsl}"

cd "$(dirname -- $(dirname -- $(dirname -- ${BASH_SOURCE[0]})))"
if [ ! -d build/tools ]; then
    mkdir build/tools
fi
if [ -L "$(pwd)/build/tools/composer.json" ]; then
    rm "$(pwd)/build/tools/composer.json"
fi
ln -s $(pwd)/doc/build/composer.json $(pwd)/build/tools/composer.json
cd build/tools
# in case we are switching between php versions, aleways reinstall every tool with the corect version...
if [ -f composer.lock ]; then
    rm composer.lock
fi
composer install --no-dev
# required as of phpdoc 3.1.2
sed -r -i -e "s|resource: '%kernel\\.project_dir%/vendor/phpdocumentor/reflection/src/phpDocumentor/Reflection/Php'|resource: '%kernel.project_dir%/../reflection/src/phpDocumentor/Reflection/Php'|g" ./vendor/phpdocumentor/phpdocumentor/config/reflection.yaml
