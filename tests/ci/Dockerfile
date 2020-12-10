ARG UBUNTU_VERSION=xenial

FROM ubuntu:${UBUNTU_VERSION}

ARG PHP_VERSION=default

RUN mkdir -p /usr/share/man/man1 && \
      apt-get update

COPY setup/*.sh /root/setup/
COPY config/* /root/config/

RUN chmod 755 /root/setup/*.sh && \
  cd /root/setup && \
  ./install_packages.sh && \
  ./create_user.sh && \
  ./setup_apache.sh && \
  ./setup_privoxy.sh && \
  ./setup_php.sh "${PHP_VERSION}" && \
  apt-get remove -y composer || echo An error on the line above is ok; ./get_composer.sh && mv /usr/local/bin/composer.phar /usr/local/bin/composer && chmod 755 /usr/local/bin/composer

COPY docker/entrypoint.sh /root/entrypoint.sh
RUN chmod 755 /root/entrypoint.sh

EXPOSE 80 443 8080

WORKDIR /home/test

ENTRYPOINT ["/root/entrypoint.sh"]
