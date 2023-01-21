#!/bin/sh

# Installs Composer (latest version, to avoid relying on old ones bundled with the OS)
# @todo allow users to lock down to Composer v1 if needed

echo "Installing Composer..."

if dpkg -l composer 2>/dev/null; then
    apt-get remove -y composer
fi

### Code below taken from https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md

# @todo replace wget with curl, so that we only need one of the two tools. Since we use php-curl, libcurl will be installed anyway
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet --install-dir=/usr/local/bin
RESULT=$?
rm composer-setup.php

###

if [ -f /usr/local/bin/composer.phar -a "$RESULT" = 0 ]; then
    mv /usr/local/bin/composer.phar /usr/local/bin/composer && chmod 755 /usr/local/bin/composer
fi

echo "Done installing Composer"

exit $RESULT
