#!/bin/sh

# Install and configure apache2
# Has to be run as root
# @todo make sure this works across all ubuntu versions (precise to noble)

echo "Installing and configuring Apache2..."

set -e

SCRIPT_DIR="$(dirname -- "$(readlink -f "$0")")"

export DEBIAN_FRONTEND=noninteractive

apt-get install -y apache2

# set up Apache for php-fpm

a2enmod rewrite proxy_fcgi setenvif ssl
if [ -f /etc/apache2/mods-available/http2.load ]; then
    a2enmod http2
fi

# in case mod-php was enabled (this is the case at least on GHA's ubuntu with php 5.x and shivammathur/setup-php)
if [ -n "$(ls /etc/apache2/mods-enabled/php* 2>/dev/null)" ]; then
    rm /etc/apache2/mods-enabled/php*
fi

# configure apache virtual hosts

cp -f "$SCRIPT_DIR/../config/apache_vhost" /etc/apache2/sites-available/000-default.conf

# default apache siteaccess found in GHA Ubuntu. We remove it just in case
if [ -f /etc/apache2/sites-available/default-ssl.conf ]; then
    rm /etc/apache2/sites-available/default-ssl.conf
fi

# @todo avoid adding these lines if they already exist - rewrite them instead
if [ -n "${GITHUB_ACTIONS}" ]; then
    echo "export TESTS_ROOT_DIR=$(pwd)" >> /etc/apache2/envvars
else
    # NB: TESTS_ROOT_DIR in /etc/apache2/envvars is reset by entrypoint.sh when running in a local container
    echo "export TESTS_ROOT_DIR=/var/www/html" >> /etc/apache2/envvars
fi
echo "export HTTPSERVER=localhost" >> /etc/apache2/envvars

service apache2 restart

echo "Done Installing and configuring Apache2"
