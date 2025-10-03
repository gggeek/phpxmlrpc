#!/bin/sh

# Install and configure the 'app'
# Has to be run as root

set -e

echo "Setting up the app..."

TESTS_ROOT_DIR="${1}"
USERNAME="${2:-docker}"

if [ -f "${TESTS_ROOT_DIR}/composer.json" ]; then
    if [ ! -f "${TESTS_ROOT_DIR}/composer.lock" ] || [ ! -d "${TESTS_ROOT_DIR}/vendor" ]; then
        echo "Running Composer..."

        su "${USERNAME}" -c "cd ${TESTS_ROOT_DIR} && composer install --no-interaction"
    else
        # @todo calculate an md5 of composer.lock, and compare it to an md5 (previously stored in ./var at the time that
        #       composer was run), adding in as key the os+php versions. If not matching, delete composer.lock
        #       and reinstall

        echo "Not running Composer: it was done previously"
    fi
else
    echo "Missing file '${TESTS_ROOT_DIR}/composer.json' - was the container started without the correct mount?" >&2
    exit 1
fi
