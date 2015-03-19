#!/bin/sh

# enable php-fpm
# @see https://github.com/travis-ci/travis-ci.github.com/blob/master/docs/user/languages/php.md#apache--php

sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias

# enable php-fpm
sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

# configure apache virtual hosts
sudo cp -f tests/ci/apache_vhost /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart
