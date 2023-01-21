#!/bin/sh

# @todo add 'query' action
# @todo avoid reloading php-fpm if config did not change

# Note: we have php set up either via Ubuntu packages (PHP_VERSION=default) or Sury packages.
#       xdebug comes either at version 2 or 3

set -e

PHPCONFDIR_CLI=$(php -i | grep 'Scan this dir for additional .ini files' | sed 's|Scan this dir for additional .ini files => ||')
PHPCONFDIR_FPM=$(echo "$PHPCONFDIR_CLI" | sed 's|/cli/|/fpm/|')

enable_cc() {
    if [ -L "${PHPCONFDIR_CLI}/99-codecoverage_xdebug.ini" ]; then sudo rm "${PHPCONFDIR_CLI}/99-codecoverage_xdebug.ini"; fi
    sudo ln -s $(realpath tests/ci/config/codecoverage_xdebug.ini) "${PHPCONFDIR_CLI}/99-codecoverage_xdebug.ini"
    if [ -L "${PHPCONFDIR_FPM}/99-codecoverage_xdebug.ini" ]; then sudo rm "${PHPCONFDIR_FPM}/99-codecoverage_xdebug.ini"; fi
    sudo ln -s $(realpath tests/ci/config/codecoverage_xdebug.ini) "${PHPCONFDIR_FPM}/99-codecoverage_xdebug.ini"

    sudo service php-fpm restart
}

disable_cc() {
    if [ -L "${PHPCONFDIR_CLI}/99-codecoverage_xdebug.ini" ]; then sudo rm "${PHPCONFDIR_CLI}/99-codecoverage_xdebug.ini"; fi
    if [ -L "${PHPCONFDIR_FPM}/99-codecoverage_xdebug.ini" ]; then sudo rm "${PHPCONFDIR_FPM}/99-codecoverage_xdebug.ini"; fi

    sudo service php-fpm restart
}

case "$1" in
   enable | on)
       enable_cc
       ;;
   disable | off)
       disable_cc
       ;;
   *)
       echo "ERROR: unknown action '${1}', please use 'enable' or 'disable'" >&2
       exit 1
       ;;
esac
