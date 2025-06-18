#!/bin/sh

USERNAME="${1:-docker}"

echo "[$(date)] Bootstrapping the Test container..."

# load values for UBUNTU_VERSION, PHP_VERSION
. /etc/build-info
# NB: the following line does not account for 'default'
#PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
#UBUNTU_VERSION="$(fgrep DISTRIB_CODENAME /etc/lsb-release | sed 's/DISTRIB_CODENAME=//')"
BOOTSTRAP_OK_FILE="${TESTS_ROOT_DIR}/tests/ci/var/bootstrap_ok_${UBUNTU_VERSION}_${PHP_VERSION}"

if [ -f "${BOOTSTRAP_OK_FILE}" ]; then
    rm "${BOOTSTRAP_OK_FILE}"
fi

clean_up() {
    # Perform program exit housekeeping

    echo "[$(date)] Stopping the Web server"
    service apache2 stop

    echo "[$(date)] Stopping Privoxy"
    service privoxy stop

    echo "[$(date)] Stopping FPM"
    service php-fpm stop

    if [ -f "${BOOTSTRAP_OK_FILE}" ]; then
        rm "${BOOTSTRAP_OK_FILE}"
    fi

    echo "[$(date)] Exiting"
    exit
}

# Fix UID & GID for user

echo "[$(date)] Fixing filesystem permissions..."

ORIGPASSWD="$(grep "^${USERNAME}:" /etc/passwd)"
ORIG_UID="$(echo "$ORIGPASSWD" | cut -f3 -d:)"
ORIG_GID="$(echo "$ORIGPASSWD" | cut -f4 -d:)"
CONTAINER_USER_HOME="$(echo "$ORIGPASSWD" | cut -f6 -d:)"
CONTAINER_USER_UID="${CONTAINER_USER_UID:=$ORIG_UID}"
CONTAINER_USER_GID="${CONTAINER_USER_GID:=$ORIG_GID}"

if [ "$CONTAINER_USER_UID" != "$ORIG_UID" ] || [ "$CONTAINER_USER_GID" != "$ORIG_GID" ]; then
    groupmod -g "$CONTAINER_USER_GID" "${USERNAME}"
    usermod -u "$CONTAINER_USER_UID" -g "$CONTAINER_USER_GID" "${USERNAME}"
fi
if [ "$(stat -c '%u' "${CONTAINER_USER_HOME}")" != "${CONTAINER_USER_UID}" ] || [ "$(stat -c '%g' "${CONTAINER_USER_HOME}")" != "${CONTAINER_USER_GID}" ]; then
    chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${CONTAINER_USER_HOME}"
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${CONTAINER_USER_HOME}"/.*
    if [ -d /usr/local/php ]; then
        chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" /usr/local/php
    fi
fi
# @todo do the same chmod for ${TESTS_ROOT_DIR}, if it's not within CONTAINER_USER_HOME
#       Also, the composer cache dir, while within the user home dir, is mounted via docker and might have faulty ownership  or perms

# @todo the following snippet does not seem to be required on any vm - but we might want to run a chown/chmod on $TESTS_ROOT_DIR
#DIR="$(dirname "$TESTS_ROOT_DIR")"
#while "$DIR" != /; do
#    chmod o+rx "$DIR"
#    DIR="$(dirname "$DIR")"
#done

echo "[$(date)] Fixing Apache configuration..."

sed -e "s?^export TESTS_ROOT_DIR=.*?export TESTS_ROOT_DIR=${TESTS_ROOT_DIR}?g" --in-place /etc/apache2/envvars
sed -e "s?^export APACHE_RUN_USER=.*?export APACHE_RUN_USER=${USERNAME}?g" --in-place /etc/apache2/envvars
sed -e "s?^export APACHE_RUN_GROUP=.*?export APACHE_RUN_GROUP=${USERNAME}?g" --in-place /etc/apache2/envvars

echo "[$(date)] Fixing FPM configuration..."

PHPVER="$(php -r 'echo implode(".",array_slice(explode(".",PHP_VERSION),0,2));' 2>/dev/null)"
if [ -f "/usr/local/php/${PHPVER}/etc/php-fpm.conf" ]; then
    # presumably a php installation from shivammathur/php5-ubuntu, which does not have separate files in a pool.d dir
    FPMCONF="/usr/local/php/${PHPVER}/etc/php-fpm.conf"
else
    FPMCONF="/etc/php/${PHPVER}/fpm/pool.d/www.conf"
fi
sed -e "s?^user =.*?user = ${USERNAME}?g" --in-place "${FPMCONF}"
sed -e "s?^group =.*?group = ${USERNAME}?g" --in-place "${FPMCONF}"
sed -e "s?^listen.owner =.*?listen.owner = ${USERNAME}?g" --in-place "${FPMCONF}"
sed -e "s?^listen.group =.*?listen.group = ${USERNAME}?g" --in-place "${FPMCONF}"

#  We make it optional to run composer at container start
if [ "${INSTALL_ON_START}" = true ]; then
    /root/setup/setup_app.sh "${TESTS_ROOT_DIR}"
fi

trap clean_up TERM

echo "[$(date)] Starting FPM..."
service php-fpm start

echo "[$(date)] Starting the Web server..."
service apache2 start

echo "[$(date)] Starting Privoxy..."
service privoxy start

echo "[$(date)] Bootstrap finished"

# Create the file which can be used by the vm.sh script to check for end of bootstrap
if [ ! -d "${TESTS_ROOT_DIR}/tests/ci/var" ]; then
    mkdir -p "${TESTS_ROOT_DIR}/tests/ci/var"
    chown -R "${USERNAME}" "${TESTS_ROOT_DIR}/tests/ci/var"
fi
# @todo save to bootstrap_ok an actual error code if any of the commands above failed
touch "${BOOTSTRAP_OK_FILE}" && chown "${USERNAME}" "${BOOTSTRAP_OK_FILE}"

tail -f /dev/null &
child=$!
wait "$child"
