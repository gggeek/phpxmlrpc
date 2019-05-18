#!/bin/sh

# make sure all files and folders are accessible by Apache
sudo find /home -type d -exec chmod 755 {} \;
sudo find . -type f -name "*.php" -exec chmod 644 {} \;

# set up Apache for php-fpm
# @see https://github.com/travis-ci/travis-ci.github.com/blob/master/docs/user/languages/php.md#apache--php

sudo a2enmod rewrite actions fastcgi alias ssl

# configure apache virtual hosts
sudo cp -f tests/ci/travis/apache_vhost /etc/apache2/sites-available/000-default.conf
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/000-default.conf
sudo service apache2 restart
