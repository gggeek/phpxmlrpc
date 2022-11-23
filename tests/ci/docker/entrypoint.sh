#!/bin/sh

USERNAME="${1:-docker}"

echo "[$(date)] Bootstrapping the Test container..."

clean_up() {
    # Perform program exit housekeeping

    echo "[$(date)] Stopping the Web server"
    service apache2 stop

    echo "[$(date)] Stopping Privoxy"
    service privoxy stop

    echo "[$(date)] Stopping FPM"
    service php-fpm stop

    echo "[$(date)] Exiting"
    exit
}

# Fix UID & GID for user

echo "[$(date)] Fixing filesystem permissions..."

ORIGPASSWD=$(cat /etc/passwd | grep "^${USERNAME}:")
ORIG_UID=$(echo "$ORIGPASSWD" | cut -f3 -d:)
ORIG_GID=$(echo "$ORIGPASSWD" | cut -f4 -d:)
CONTAINER_USER_HOME=$(echo "$ORIGPASSWD" | cut -f6 -d:)
CONTAINER_USER_UID=${CONTAINER_USER_UID:=$ORIG_UID}
CONTAINER_USER_GID=${CONTAINER_USER_GID:=$ORIG_GID}

if [ "$CONTAINER_USER_UID" != "$ORIG_UID" -o "$CONTAINER_USER_GID" != "$ORIG_GID" ]; then
    groupmod -g "$CONTAINER_USER_GID" "${USERNAME}"
    usermod -u "$CONTAINER_USER_UID" -g "$CONTAINER_USER_GID" "${USERNAME}"
fi
if [ $(stat -c '%u' "${CONTAINER_USER_HOME}") != "${CONTAINER_USER_UID}" -o $(stat -c '%g' "${CONTAINER_USER_HOME}") != "${CONTAINER_USER_GID}" ]; then
    chown "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${CONTAINER_USER_HOME}"
    chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" "${CONTAINER_USER_HOME}"/.*
    if [ -d /usr/local/php ]; then
        chown -R "${CONTAINER_USER_UID}":"${CONTAINER_USER_GID}" /usr/local/php
    fi
fi
# @todo do the same chmod for ${TESTS_ROOT_DIR}, if it's not within CONTAINER_USER_HOME

echo "[$(date)] Fixing Apache configuration..."

sed -e "s?^export TESTS_ROOT_DIR=.*?export TESTS_ROOT_DIR=${TESTS_ROOT_DIR}?g" --in-place /etc/apache2/envvars
sed -e "s?^export APACHE_RUN_USER=.*?export APACHE_RUN_USER=${USERNAME}?g" --in-place /etc/apache2/envvars
sed -e "s?^export APACHE_RUN_GROUP=.*?export APACHE_RUN_GROUP=${USERNAME}?g" --in-place /etc/apache2/envvars

echo "[$(date)] Fixing FPM configuration..."

FPMCONF="/etc/php/$(php -r 'echo implode(".",array_slice(explode(".",PHP_VERSION),0,2));' 2>/dev/null)/fpm/pool.d/www.conf"
sed -e "s?^user =.*?user = ${USERNAME}?g" --in-place "${FPMCONF}"
sed -e "s?^group =.*?group = ${USERNAME}?g" --in-place "${FPMCONF}"
sed -e "s?^listen.owner =.*?listen.owner = ${USERNAME}?g" --in-place "${FPMCONF}"
sed -e "s?^listen.group =.*?listen.group = ${USERNAME}?g" --in-place "${FPMCONF}"

echo "[$(date)] Running Composer..."

# @todo if there is a composer.lock file present, there are chances it might be a leftover from when running the
#       container using a different php version. We should then back it up / do some symlink magic to make sure that
#       it matches the current php version and a hash of composer.json...
sudo "${USERNAME}" -c "cd ${TESTS_ROOT_DIR} && composer install"

trap clean_up TERM

echo "[$(date)] Starting FPM..."
service php-fpm start

echo "[$(date)] Starting the Web server..."
service apache2 start

echo "[$(date)] Starting Privoxy..."
service privoxy start

echo "[$(date)] Bootstrap finished"

tail -f /dev/null &
child=$!
wait "$child"
