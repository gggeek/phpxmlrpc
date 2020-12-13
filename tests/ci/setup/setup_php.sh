#!/bin/sh

# To be kept in sync with setup_php_travis.sh

set -e

configure_php_ini() {
    echo "cgi.fix_pathinfo = 1" >> "${1}"
    echo "always_populate_raw_post_data = -1" >> "${1}"

    # @todo this only disables xdebug for CLI. To do the same for the FPM config as well, should we use instead `phpdismod` ?
    XDEBUG_INI=$(php -i | grep xdebug.ini | grep -v '=>' | head -1)
    if [ "$XDEBUG_INI" != "" ]; then
        XDEBUG_INI="$(echo "$XDEBUG_INI" | tr -d ',')"
        mv "$XDEBUG_INI" "$XDEBUG_INI.bak";
    fi
}

# install php
PHP_VERSION="$1"
DEBIAN_VERSION="$(lsb_release -s -c)"

if [ "${PHP_VERSION}" = default ]; then
    if [ "${DEBIAN_VERSION}" = jessie -o "${DEBIAN_VERSION}" = precise -o "${DEBIAN_VERSION}" = trusty ]; then
        PHPSUFFIX=5
    else
        PHPSUFFIX=
    fi
    # @todo check for mbstring presence in php5 (jessie) packages
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        php${PHPSUFFIX} \
        php${PHPSUFFIX}-cli \
        php${PHPSUFFIX}-dom \
        php${PHPSUFFIX}-curl \
        php${PHPSUFFIX}-fpm \
        php${PHPSUFFIX}-mbstring \
        php${PHPSUFFIX}-xdebug
else
    DEBIAN_FRONTEND=noninteractive apt-get install -y language-pack-en-base software-properties-common
    LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php
    apt-get update

    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-dom \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xdebug

    update-alternatives --set php /usr/bin/php${PHP_VERSION}
fi

PHPVER=$(php -r 'echo implode(".",array_slice(explode(".",PHP_VERSION),0,2));' 2>/dev/null)

configure_php_ini /etc/php/${PHPVER}/fpm/php.ini

# use a nice name for the php-fpm service, so that it does not depend on php version running
service "php${PHPVER}-fpm" stop
ln -s "/etc/init.d/php${PHPVER}-fpm" /etc/init.d/php-fpm

# @todo shall we configure php-fpm?

service php-fpm start

# configure apache
a2enconf php${PHPVER}-fpm
service apache2 restart
