#========================================================================
# Copyright Universidade Federal do Espirito Santo (Ufes)
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
# 
# This program is released under license GNU GPL v3+ license.
#
#========================================================================

# Build on base image
ARG BASE_IMAGE=boca-base
# hadolint ignore=DL3006
FROM --platform=${BUILDPLATFORM:-linux/amd64} ${BASE_IMAGE}

ARG TARGETPLATFORM
ARG BUILDPLATFORM
ARG TARGETOS
ARG TARGETARCH

LABEL authors="Joao Vitor Alves Fazolo, Rodrigo Laiola Guimaraes"
ENV CREATED_AT 2020-06-26
ENV UPDATED_AT 2023-06-01

# Apache settings
ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_PID_FILE  /var/run/apache2/apache2.pid
ENV APACHE_DIR       /etc/apache2
ENV APACHE_RUN_DIR   /var/run/apache2
ENV APACHE_LOCK_DIR  /var/lock/apache2
ENV APACHE_LOG_DIR   /var/log/apache2

# Redundant but to ensure we are not going to break anything
# https://github.com/cassiopc/boca/tree/master/doc
USER root

# Install dependencies
# hadolint ignore=DL3008,DL3015,DL4006
RUN apt-get -y update \
    && echo N | apt-get -y install \
        # Package: boca-web
        # https://github.com/cassiopc/boca/blob/master/debian/control
        # Depends:
        apache2 \
        libapache2-mod-php \
        php \
        php-fpm \
        python3-matplotlib \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p $APACHE_LOCK_DIR \
    && mkdir -p $APACHE_LOG_DIR \
    && mkdir -p $APACHE_RUN_DIR \
    && echo "ServerName localhost" >> $APACHE_DIR/apache2.conf \
    && ln -sf /proc/self/fd/1 $APACHE_LOG_DIR/access.log \
    && ln -sf /proc/self/fd/1 $APACHE_LOG_DIR/error.log \
    && chown -R "$APACHE_RUN_USER:$APACHE_RUN_GROUP" "$APACHE_LOCK_DIR" \
    && chown -R "$APACHE_RUN_USER:$APACHE_RUN_GROUP" "$APACHE_LOG_DIR" \
    && chown -R "$APACHE_RUN_USER:$APACHE_RUN_GROUP" "$APACHE_RUN_DIR"

WORKDIR /var/www/boca
RUN \
    # install-bocaapache
    # https://github.com/cassiopc/boca/blob/master/Makefile
    mkdir -p $APACHE_DIR/sites-available/ \
    && cp tools/000-boca.conf $APACHE_DIR/sites-available/000-boca.conf \
    # install-scripts
    # https://github.com/cassiopc/boca/blob/master/Makefile
    && mkdir -p /usr/sbin/ \
	&& install tools/dump.sh /usr/sbin/boca-dump \
	&& chmod 700 /usr/sbin/boca-dump \
    # boca-web.postinst
    # https://github.com/cassiopc/boca/blob/master/debian/boca-web.postinst
    && chown -R "$APACHE_RUN_USER:$APACHE_RUN_GROUP" /var/www/boca \
    && chmod -R go-rwx /var/www/boca/src/private \
    && a2enmod ssl \
    # Necessary SSLCertificateKeyFile
    # && a2ensite default-ssl \
    && mkdir -p $APACHE_DIR/sites-enabled \
    && cp tools/000-boca.conf $APACHE_DIR/sites-enabled/000-boca.conf \
    && a2enmod socache_shmcb \
    && a2enmod proxy_fcgi setenvif \
    # && a2enconf php8.1-fpm \
    && apache2ctl configtest

COPY --chmod=755 --chown="$APACHE_RUN_USER:$APACHE_RUN_GROUP" docker/dev/web/init.sh /

# Create a non-root user for the container
USER $APACHE_RUN_USER

# Add HEALTHCHECK instruction to the container image
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=5 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:80/boca/ || exit 1

EXPOSE 80

# Use exec format to run program directly as pid 1
# https://www.padok.fr/en/blog/docker-processes-container
ENTRYPOINT ["/init.sh"]
