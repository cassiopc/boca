FROM php:5.6-apache-stretch

RUN apt-get update

# Install Postgre PDO
RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install and check lsb-release
RUN apt-get install -y lsb-release quota debootstrap schroot 

COPY docker/boca.conf /etc/apache2/conf-enabled/boca.conf

ADD docker/lsb-release /etc/lsb-release

#ADD docker/conf.php /var/www/html/private/conf.php

#RUN ./icpc/createbocajail.sh

COPY ./src /var/www/html
WORKDIR /var/www/html

EXPOSE 80