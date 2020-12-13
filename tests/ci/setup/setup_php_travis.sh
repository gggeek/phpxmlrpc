#!/bin/sh

# To be kept in sync with setup_php.sh

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

PHPVER=$(phpenv version-name)

configure_php_ini ~/.phpenv/versions/${PHPVER}/etc/php.ini

# configure php-fpm
cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf

# work around travis issue #3385
if [ -d ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d ]; then
    if [ "$TRAVIS_PHP_VERSION" = "7.0" -a -n "$(ls -A ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d)" ]; then
      cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf
    fi
    if [ "$TRAVIS_PHP_VERSION" = "7.1" -a -n "$(ls -A ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d)" ]; then
      cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf
    fi
    if [ "$TRAVIS_PHP_VERSION" = "7.2" -a -n "$(ls -A ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d)" ]; then
      cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf
    fi
    if [ "$TRAVIS_PHP_VERSION" = "7.3" -a -n "$(ls -A ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d)" ]; then
      cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf
    fi
fi

# @todo run php-fpm as root, and set up 'travis' as user in www.conf, instead ?
~/.phpenv/versions/${PHPVER}/sbin/php-fpm

# @todo configure apache for php-fpm via mod_proxy_fcgi...
#sudo a2enconf php${PHPVER}-fpm
#sudo service apache2 restart
