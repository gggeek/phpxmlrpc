#!/bin/bash

# Has to be run as a sudoer

set -e

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    asciidoctor fop git unzip zip

PHPPKG=$(dpkg --list | grep php | grep cli | grep -v -F '(default)' | awk '{print $2}')
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y "${PHPPKG/cli/xsl}"

cd "$(dirname -- "${BASH_SOURCE[0]}")"
# in case we are switching between php versions, always reinstall every tool with the correct version...
if [ -f composer.lock ]; then
    rm composer.lock
fi
composer install --no-dev
#sudo chown -R "$(id -u):$(id -g)" vendor

# required as of phpdoc 3.1.2
sed -r -i -e "s|resource: '%kernel\\.project_dir%/vendor/phpdocumentor/reflection/src/phpDocumentor/Reflection/Php'|resource: '%kernel.project_dir%/../reflection/src/phpDocumentor/Reflection/Php'|g" ./vendor/phpdocumentor/phpdocumentor/config/reflection.yaml
