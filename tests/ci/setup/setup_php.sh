#!/bin/sh

# Has to be run as admin

# @todo make it optional to install xdebug. It is fe. missing in sury's ppa for Xenial
# @todo make it optional to install fpm. It is not needed for the cd workflow
# @todo make it optional to disable xdebug ?

set -e

SCRIPT_DIR="$(dirname -- "$(readlink -f "$0")")"

configure_php_ini() {
    # note: these settings are not required for cli config
    echo "cgi.fix_pathinfo = 1" >> "${1}"
    echo "always_populate_raw_post_data = -1" >> "${1}"

    # we disable xdebug for speed for both cli and web mode
    if which phpdesmod >/dev/null 2>/dev/null; then
        phpdismod xdebug
    elif [ -f /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini ]; then
        mv /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini.bak
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
    # on GHA runners ubuntu version, php 7.4 and 8.0 seem to be preinstalled. Remove them if found
    for PHP_CURRENT in $(dpkg -l | grep -E 'php.+-common' | awk '{print $2}'); do
        if [ "${PHP_CURRENT}" != "php${PHP_VERSION}-common" ]; then
            apt-get purge -y "${PHP_CURRENT}"
        fi
    done

    if [ "${PHP_VERSION}" = 5.3 -o "${PHP_VERSION}" = 5.4 -o "${PHP_VERSION}" = 5.5 ]; then
        # NB: this set of packages has only been tested on Focal so far
        DEBIAN_FRONTEND=noninteractive apt-get install -y \
            curl \
            enchant \
            imagemagick \
            libc-client2007e \
            libcurl3-gnutls \
            libmcrypt4 \
            libodbc1 \
            libpq5 \
            libqdbm14 \
            libtinfo5 \
            libxpm4 \
            libxslt1.1 \
            mysql-common \
            zstd
        if [ ! -d /usr/include/php ]; then mkdir -p /usr/include/php; fi

        set +e
        curl -sSL https://github.com/shivammathur/php5-ubuntu/releases/latest/download/install.sh | bash -s "${PHP_VERSION}"
        # we have to do this as the init script we get for starting/stopping php-fpm seems to be faulty...
        pkill php-fpm
        echo 'listen = /run/php/php-fpm.sock' >> "/usr/local/php/${PHP_VERSION}/etc/php-fpm.conf"
        # the user running apache will be different in GHA and local VMS. We just open fully perms on the fpm socket
        echo 'listen.mode = 0666' >> "/usr/local/php/${PHP_VERSION}/etc/php-fpm.conf"
        cp "$SCRIPT_DIR/../config/apache_phpfpm_proxyfcgi" "/etc/apache2/conf-available/php${PHP_VERSION}-fpm.conf"
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
fi

PHPVER=$(php -r 'echo implode(".",array_slice(explode(".",PHP_VERSION),0,2));' 2>/dev/null)

if [ -d /etc/php/${PHPVER}/fpm ]; then
    configure_php_ini /etc/php/${PHPVER}/fpm/php.ini
elif [ -f /usr/local/php/${PHPVER}/etc/php.ini ]; then
    configure_php_ini /usr/local/php/${PHPVER}/etc/php.ini
fi

# use a nice name for the php-fpm service, so that it does not depend on php version running. Try to make that work
# both for docker and VMs
service "php${PHPVER}-fpm" stop
if [ -f "/etc/init.d/php${PHPVER}-fpm" ]; then
    ln -s "/etc/init.d/php${PHPVER}-fpm" /etc/init.d/php-fpm
fi
if [ -f "/lib/systemd/system/php${PHPVER}-fpm.service" ]; then
    ln -s "/lib/systemd/system/php${PHPVER}-fpm.service" /lib/systemd/system/php-fpm.service
    if [ ! -f /.dockerenv ]; then
        systemctl daemon-reload
    fi
fi

# @todo shall we configure php-fpm?

service php-fpm start

# reconfigure apache (if installed). Sadly, php will switch on mod-php and mpm_prefork at install time...
if [ -n "$(dpkg --list | grep apache)" ]; then
    # @todo silence errors in a smarter way
    rm /etc/apache2/mods-enabled/php* || true
    a2dismod mpm_prefork
    a2enmod mpm_event
    a2enconf php${PHPVER}-fpm
    service apache2 restart
fi
