#!/bin/sh

# @todo make username flexible

USERNAME=test

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
fi

echo "[$(date)] Fixing apache configuration..."

# @todo set as well APACHE_RUN_USER and/or APACHE_RUN_GROUP ?
sed -e "s?^export TESTS_ROOT_DIR=.*?export TESTS_ROOT_DIR=${TESTS_ROOT_DIR}?g" --in-place /etc/apache2/envvars

echo "[$(date)] Running Composer..."

sudo test -c "cd /home/test && composer install"

trap clean_up TERM

echo "[$(date)] Starting FPM..."
service php-fpm start

echo "[$(date)] Starting the Web server..."
service apache2 start

echo "[$(date)] Starting Privoxy..."
service privoxy start

tail -f /dev/null &
child=$!
wait "$child"
