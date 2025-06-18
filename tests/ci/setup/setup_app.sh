#!/bin/sh

# Install and configure the 'app'
# Has to be run as admin

set -e

echo "Installing the app dependencies..."

TESTS_ROOT_DIR="${1}"
USERNAME="${2:-docker}"

if [ -f "${TESTS_ROOT_DIR}/composer.json" ]; then
    echo "[$(date)] Running Composer..."

    # @todo if there is a composer.lock file present, there are chances it might be a leftover from when running the
    #       container using a different os/php version. We could then back it up / do some symlink magic to make sure
    #       that it matches the current php version and a hash of composer.json... (also symlink the vendor folder).

    su "${USERNAME}" -c "cd ${TESTS_ROOT_DIR} && composer install --no-interaction --audit"
else
    echo "Missing file '${TESTS_ROOT_DIR}/composer.json' - was the container started without the correct mount?" >&2
    exit 1
fi
