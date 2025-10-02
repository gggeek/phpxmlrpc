#!/bin/sh

# Has to be run as admin

# @todo make it optional to install xdebug. It is fe. missing in sury's ppa for Xenial
# @todo make it optional to install fpm. It is not needed for the cd workflow
# @todo make it optional to disable xdebug ?
# @todo set the list of required php extensions in a variable, allow it to be overridden
# @todo allow to force usage of shivammatur/ondrej repos regardless of php version in use

set -e

echo "Installing PHP version '${1}'..."

SCRIPT_DIR="$(dirname -- "$(readlink -f "$0")")"

export DEBIAN_FRONTEND=noninteractive

configure_php_ini() {
    # note: these settings are not required for cli config
    echo "cgi.fix_pathinfo = 1" >> "${1}"
    echo "always_populate_raw_post_data = -1" >> "${1}"

    # we disable xdebug for speed for both cli and web mode
    # @todo make this optional
    if which phpdismod >/dev/null 2>/dev/null; then
        phpdismod xdebug
    elif [ -f /etc/php/$PHP_VERSION/mods-available/xdebug.ini ]; then
        mv /etc/php/$PHP_VERSION/mods-available/xdebug.ini /etc/php/$PHP_VERSION/mods-available/xdebug.ini.bak
    elif [ -f /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini ]; then
        mv /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini /usr/local/php/$PHP_VERSION/etc/conf.d/20-xdebug.ini.bak
    else
        echo "Could not disable loading of xdebug - xdebug.ini file not found" >&2
    fi
}

install_native() {
    echo "Using native PHP packages..."

    if [ "${DEBIAN_VERSION}" = jessie -o "${DEBIAN_VERSION}" = precise -o "${DEBIAN_VERSION}" = trusty ]; then
        PHPSUFFIX=5
    else
        PHPSUFFIX=
    fi
    # @todo check for mbstring presence in php5 (jessie) packages
    apt-get install -y \
        php${PHPSUFFIX} \
        php${PHPSUFFIX}-cli \
        php${PHPSUFFIX}-dom \
        php${PHPSUFFIX}-curl \
        php${PHPSUFFIX}-fpm \
        php${PHPSUFFIX}-mbstring \
        php${PHPSUFFIX}-xdebug
}

install_shivammatur() {
    if [ ! -d /usr/include/php ]; then mkdir -p /usr/include/php; fi

    # In case we will be using php5.x from shivammatur, it has a dependency on a lib missing from bionic
    # `lsb-release` is not necessarily onboard. We parse /etc/os-release instead
    if [ "$DEBIAN_VERSION" = bionic ]; then
        wget https://launchpad.net/~ubuntu-security/+archive/ubuntu/ppa/+build/15108504/+files/libpng12-0_1.2.54-1ubuntu1.1_amd64.deb
        dpkg --install libpng12-0_1.2.54-1ubuntu1.1_amd64.deb
        rm libpng12-0_1.2.54-1ubuntu1.1_amd64.deb
    fi

    set +e
    if [ "${PHP_VERSION}" = 5.3 -o "${PHP_VERSION}" = 5.4 -o "${PHP_VERSION}" = 5.5 ]; then
        echo "Using PHP from shivammathur/php5-ubuntu..."
        if [ "${DEBIAN_VERSION}" = jammy -o "${DEBIAN_VERSION}" = noble ]; then
            ENCHANTSUFFIX='-2'
        fi
        # note: on ubuntu 24, libtinfo5 is missing, and libodbc1 is replaced by libodbc2
        if [ "${DEBIAN_VERSION}" = noble ]; then
            PACKAGES="libodbc2"
            # @todo is libtinfo required?
            #wget https://security.ubuntu.com/ubuntu/pool/universe/n/ncurses/libtinfo5_6.3-2ubuntu0.1_amd64.deb
            #apt install ./libtinfo5_6.3-2ubuntu0.1_amd64.deb
            #rm ./libtinfo5_6.3-2ubuntu0.1_amd64.deb
        else
            PACKAGES="libodbc1 libtinfo5"
        fi
        # @todo this set of packages has only been tested on Bionic, Focal, Jammy and Noble so far
        apt-get install -y \
            curl \
            enchant${ENCHANTSUFFIX} \
            imagemagick \
            libc-client2007e \
            libcurl3-gnutls \
            libmcrypt4 \
            libodbc2 \
            libpq5 \
            libqdbm14 \
            libxpm4 \
            libxslt1.1 \
            mysql-common \
            zstd $PACKAGES

        curl -sSL https://github.com/shivammathur/php5-ubuntu/releases/latest/download/install.sh | bash -s "${PHP_VERSION}"

        # @todo check which php extensions are enabled, and disable all except the desired ones
    else
        # @todo check if this script works with all php versions from 5.6 onwards
        echo "Using PHP from shivammathur/php-ubuntu..."
        if [ "${DEBIAN_VERSION}" = noble ]; then
            PACKAGES="gir1.2-girepository-2.0 libelf1t64 libglib2.0-0t64"
        else
            # @todo add also gir1.2-girepository-2.0 - check if name/availability is the same as on noble.
            PACKAGES="libelf1 libglib2.0-0"
        fi

        # Most of these tools are used by the `sudo update-alternatives` part in the install.sh script, and
        # will be downloaded at that time, along with some ominous warnings.
        # We are just as good preinstalling them anyway.
        apt-get install -y \
            autoconf \
            automake \
            autotools-dev \
            build-essential \
              curl \
            icu-devtools \
              libargon2-1 \
            libgirepository-1.0-1 \
            libicu-dev \
            libltdl7 \
              libsodium23 \
            libxml2-dev \
            pkg-config \
            python3 \
            python3-apt \
              systemd-standalone-tmpfiles \
            zlib1g-dev \
              zstd $PACKAGES

        curl -sSL https://github.com/shivammathur/php-ubuntu/releases/latest/download/install.sh | bash -s "${PHP_VERSION}"

        # Disable all extensions, as there are too many enabled. Many of these require .so libs which we did not install
        for DIR in apache2 cgi cli embed fpm phpdbg; do
            if [ -d "/etc/php/${PHP_VERSION}/${DIR}/conf.d" ]; then
                rm -rf /etc/php/${PHP_VERSION}/${DIR}/conf.d/*.ini
                for EXT in dom curl mbstring phar xml; do
                    ln -s /etc/php/${PHP_VERSION}/mods-available/${EXT}.ini /etc/php/${PHP_VERSION}/${DIR}/conf.d/20-${EXT}.ini
                done
            fi
        done
    fi
    set -e

    # we have to do this as the init script we get for starting/stopping php-fpm seems to be faulty...
    if [ -n "$(ps auxwww | grep php-fpm | grep -v ' grep ')" ]; then pkill php-fpm; fi

    # @todo at least for jammy and noble, /var/run/ is a symlink to /run. Check on older os version, and use /run/php?
    if [ -d "/var/run/php" ] && [ -d "/usr/local/php/" ]; then
        if [ -d "/usr/local/php/${PHP_VERSION}/var/run" ]; then rm -rf "/usr/local/php/${PHP_VERSION}/var/run"; fi
        if [ ! -d "/usr/local/php/${PHP_VERSION}/var/" ]; then mkdir -p "/usr/local/php/${PHP_VERSION}/var/"; fi
        ln -s "/var/run/php" "/usr/local/php/${PHP_VERSION}/var/run"
    fi

    CONFIG_FILE=
    for FILE in "/etc/php/${PHP_VERSION}/fpm/php-fpm.conf" "/usr/local/php/${PHP_VERSION}/etc/php-fpm.conf"; do
        if [ -f "$FILE" ]; then
            CONFIG_FILE="$FILE"
            break
        fi
    done
    if [ -n "$CONFIG_FILE" ]; then
        # set up the minimal php-fpm config we need
        echo 'listen = /run/php/php-fpm.sock' >> "$CONFIG_FILE"
        # the user running apache will be different in GHA and local VMS. We just open fully perms on the fpm socket
        echo 'listen.mode = 0666' >> "$CONFIG_FILE"
        # as well as the conf to enable php-fpm in apache
        cp "$SCRIPT_DIR/../config/apache_phpfpm_proxyfcgi" "/etc/apache2/conf-available/php${PHP_VERSION}-fpm.conf"
    else
        echo "Not enabling fcgi in apache as php-fpm config file not found" >&2
    fi
}

install_ondrej() {
    echo "Using PHP packages from ondrej/php..."

    apt-get install -y language-pack-en-base software-properties-common
    LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php
    apt-get update

    PHP_PACKAGES="php${PHP_VERSION} \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-dom \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xdebug"
    apt-get install -y ${PHP_PACKAGES}

    update-alternatives --set php /usr/bin/php${PHP_VERSION}
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
    install_native
else
    # on GHA runners ubuntu version, php 7.4 and 8.0 seem to be preinstalled. Remove them if found
    for PHP_CURRENT in $(dpkg -l | grep -E 'php.+-common' | awk '{print $2}'); do
        if [ "${PHP_CURRENT}" != "php${PHP_VERSION}-common" ]; then
            apt-get purge -y "${PHP_CURRENT}"
        fi
    done

    # @todo use ondrej packages for php 8.5 when they are available
    # @todo move this to looping over an array
    if [ "${PHP_VERSION}" = 5.3 -o "${PHP_VERSION}" = 5.4 -o "${PHP_VERSION}" = 5.5 -o "${PHP_VERSION}" = 8.5 ]; then
        install_shivammatur
    else
        install_ondrej
    fi
fi


PHPVER=$(php -r 'echo implode(".",array_slice(explode(".",PHP_VERSION),0,2));' 2>/dev/null)

service "php${PHPVER}-fpm" stop || true

if [ -d /etc/php/${PHPVER}/fpm ]; then
    configure_php_ini /etc/php/${PHPVER}/fpm/php.ini
elif [ -f /usr/local/php/${PHPVER}/etc/php.ini ]; then
    configure_php_ini /usr/local/php/${PHPVER}/etc/php.ini
fi

# @todo shall we configure php-fpm?

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
