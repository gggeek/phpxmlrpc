#!/bin/sh

# Has to be run as admin

# @todo make it optional to install xdebug. It is fe. missing in sury's ppa for Xenial
# @todo make it optional to install fpm. It is not needed for the cd workflow
# @todo make it optional to disable xdebug ?

set -e

echo "Installing PHP version '${1}'..."

SCRIPT_DIR="$(dirname -- "$(readlink -f "$0")")"

configure_php_ini() {
    # note: these settings are not required for cli config
    echo "cgi.fix_pathinfo = 1" >> "${1}"
    echo "always_populate_raw_post_data = -1" >> "${1}"

    # we disable xdebug for speed for both cli and web mode
    # @todo make this optional
    if which phpdismod >/dev/null 2>/dev/null; then
        phpdismod xdebug
    elif [ -f /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini ]; then
        mv /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini.bak
    fi
}

# install php
PHP_VERSION="$1"
# `lsb-release` is not necessarily onboard. We parse /etc/os-release instead
DEBIAN_VERSION=$(cat /etc/os-release | grep 'VERSION_CODENAME=' | sed 's/VERSION_CODENAME=//')
if [ -z "${DEBIAN_VERSION}" ]; then
    # Example strings:
    # VERSION="14.04.6 LTS, Trusty Tahr"
    # VERSION="8 (jessie)"
    DEBIAN_VERSION=$(cat /etc/os-release | grep 'VERSION=' | grep 'VERSION=' | sed 's/VERSION=//' | sed 's/"[0-9.]\+ *(\?//' | sed 's/)\?"//' | tr '[:upper:]' '[:lower:]' | sed 's/lts, *//' | sed 's/ \+tahr//')
fi

# @todo use native packages if requested for a specific version and that is the same as available in the os repos

if [ "${PHP_VERSION}" = default ]; then
    echo "Using native PHP packages..."

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
        echo "Using PHP from shivammathur/php5-ubuntu..."

        # @todo this set of packages has only been tested on Bionic, Focal and Jammy so far
        if [ "${DEBIAN_VERSION}" = jammy ]; then
            ENCHANTSUFFIX='-2'
        fi
        DEBIAN_FRONTEND=noninteractive apt-get install -y \
            curl \
            enchant${ENCHANTSUFFIX} \
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
        set -e

        # we have to do this as the init script we get for starting/stopping php-fpm seems to be faulty...
        pkill php-fpm
        rm -rf "/usr/local/php/${PHP_VERSION}/var/run"
        ln -s "/var/run/php" "/usr/local/php/${PHP_VERSION}/var/run"
        # set up the minimal php-fpm config we need
        echo 'listen = /run/php/php-fpm.sock' >> "/usr/local/php/${PHP_VERSION}/etc/php-fpm.conf"
        # the user running apache will be different in GHA and local VMS. We just open fully perms on the fpm socket
        echo 'listen.mode = 0666' >> "/usr/local/php/${PHP_VERSION}/etc/php-fpm.conf"
        # as well as the conf to enable php-fpm in apache
        cp "$SCRIPT_DIR/../config/apache_phpfpm_proxyfcgi" "/etc/apache2/conf-available/php${PHP_VERSION}-fpm.conf"
    else
        echo "Using PHP packages from ondrej/php..."

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

service "php${PHPVER}-fpm" stop || true

if [ -d /etc/php/${PHPVER}/fpm ]; then
    configure_php_ini /etc/php/${PHPVER}/fpm/php.ini
elif [ -f /usr/local/php/${PHPVER}/etc/php.ini ]; then
    configure_php_ini /usr/local/php/${PHPVER}/etc/php.ini
fi

# use a nice name for the php-fpm service, so that it does not depend on php version running. Try to make that work
# both for docker and VMs
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
    echo "Reconfiguring Apache..."
    if [ -n "$(ls /etc/apache2/mods-enabled/php* 2>/dev/null)" ]; then
        rm /etc/apache2/mods-enabled/php*
    fi
    a2dismod mpm_prefork
    a2enmod mpm_event
    a2enconf php${PHPVER}-fpm
    service apache2 restart
fi

echo "Done installing PHP"
