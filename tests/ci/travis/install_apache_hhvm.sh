#!/bin/sh

# enable hhvm-fcgi
# @see https://github.com/travis-ci/travis-ci.github.com/blob/master/docs/user/languages/php.md#apache--php

sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias

# start HHVM
hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true

# configure apache virtual hosts
sudo cp -f tests/ci/travis/apache_vhost_hhvm /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart