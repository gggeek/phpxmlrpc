#!/bin/sh

# Has to be run as admin

# To be kept in sync with setup_php_travis.sh

# @todo make it optional to disable xdebug ?

set -e

configure_php_ini() {
    # note: these settings are not required for cli config
    echo "cgi.fix_pathinfo = 1" >> "${1}"
    echo "always_populate_raw_post_data = -1" >> "${1}"

    # we disable xdebug for speed for both cli and web mode
    phpdismod xdebug
}

PHPVER=$(php -r 'echo implode(".",array_slice(explode(".",PHP_VERSION),0,2));' 2>/dev/null)

# this is done via shivammathur/setup-php
#configure_php_ini /etc/php/${PHPVER}/fpm/php.ini

# install php-fpm
apt-get install php$PHPVER-fpm
cp /usr/sbin/php-fpm$PHPVER /usr/bin/php-fpm # copy to /usr/bin

# use a nice name for the php-fpm service, so that it does not depend on php version running
#service "php${PHPVER}-fpm" stop
#ln -s "/etc/init.d/php${PHPVER}-fpm" /etc/init.d/php-fpm

# @todo shall we configure php-fpm?

service php$PHPVER-fpm start
service php$PHPVER-fpm status

# configure apache
a2enconf php${PHPVER}-fpm
service apache2 restart
