#!/bin/sh

# To be kept in sync with setup_php.sh

# @todo make it optional to disable xdebug ?

set -e

SCRIPT_DIR="$(dirname -- "$(readlink -f "$0")")"

configure_php_ini() {
    echo "cgi.fix_pathinfo = 1" >> "${1}"
    echo "always_populate_raw_post_data = -1" >> "${1}"

    # we disable xdebug for speed for both cli and web mode
    phpenv config-rm xdebug.ini
}

PHPVER=$(phpenv version-name)

configure_php_ini ~/.phpenv/versions/${PHPVER}/etc/php.ini

# configure php-fpm
cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf

# work around travis issue #3385
if [ -d ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d ]; then
    # it seems that www.conf does not exist for php 7.0 .. 7.3
    if [ -f ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf.default -a ! -f ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf ]; then
        cp ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf.default ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf
    fi
fi

# Use a unix socket for communication between apache and php-fpm - same as Ubuntu does by default
if [ -f ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf ]; then
    sed -i -e "s,listen = 127.0.0.1:9000,listen = /run/php/php-fpm.sock,g" ~/.phpenv/versions/${PHPVER}/etc/php-fpm.d/www.conf
else
    # php 5.6 has all fpm conf in a single file
    sed -i -e "s,listen = 127.0.0.1:9000,listen = /run/php/php-fpm.sock,g" ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf
    sed -i -e "s,user = nobody,user = travis,g" ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf
    sed -i -e "s,group = nobody,group = travis,g" ~/.phpenv/versions/${PHPVER}/etc/php-fpm.conf
fi
sudo mkdir /run/php
sudo chown travis:travis /run/php

# @todo run php-fpm as root, and (always) set up 'travis' as user in www.conf, instead ?
~/.phpenv/versions/${PHPVER}/sbin/php-fpm

# configure apache for php-fpm via mod_proxy_fcgi
sudo cp -f "$SCRIPT_DIR/../config/apache_phpfpm_proxyfcgi" "/etc/apache2/conf-available/php${PHPVER}-fpm.conf"
sudo a2enconf php${PHPVER}-fpm
sudo sed -i -e "s,www-data,travis,g" /etc/apache2/envvars
sudo service apache2 restart
