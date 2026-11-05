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

# Build on base image (default: ubuntu:jammy)
# Snap did not build boca-jail properly on ubuntu:bionic 
# error: cannot list snaps: cannot communicate with server: Get http://localhost/v2/snaps: dial unix /run/snapd.socket: connect: no such file or directory
# https://groups.google.com/g/boca-users/c/QrgnJl-KAKw/m/uSuRO64_CQAJ
ARG BASE_IMAGE=ubuntu:jammy

# This stage clones BOCA's repository
# hadolint ignore=DL3006
FROM --platform=${BUILDPLATFORM:-linux/amd64} ${BASE_IMAGE} AS bocarepo
# Git URL of the BOCA repository to clone from (default: cassiopc/boca)
ARG REPOSITORY=https://github.com/cassiopc/boca.git
# The branch, tag or SHA to point to in the cloned BOCA repository
ARG REF=master
# Using ARG to set ENV
ENV ENV_REPOSITORY=$REPOSITORY
ENV ENV_REF=$REF

# hadolint ignore=DL3008,DL3015
RUN apt-get -y update \
    && apt-get -y install \
        git \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /
RUN git clone "${ENV_REPOSITORY}"
WORKDIR /boca
RUN git checkout "${ENV_REF}"

# The efficient way to publish multi-arch containers from GitHub Actions
# https://actuated.dev/blog/multi-arch-docker-github-actions
# hadolint ignore=DL3006
FROM --platform=${BUILDPLATFORM:-linux/amd64} ${BASE_IMAGE}

ARG TARGETPLATFORM
ARG BUILDPLATFORM
ARG TARGETOS
ARG TARGETARCH

LABEL authors="Joao Vitor Alves Fazolo, Rodrigo Laiola Guimaraes"
ENV CREATED_AT 2020-06-26
ENV UPDATED_AT 2023-06-01

# No interactive frontend during docker build
ENV DEBIAN_FRONTEND noninteractive
ENV DEBCONF_NONINTERACTIVE_SEEN true

# Locale and encoding settings
ENV LANG_WHICH en
ENV LANG_WHERE US
ENV ENCODING UTF-8
ENV LC_ALL C.${ENCODING}
ENV LANGUAGE ${LANG_WHICH}_${LANG_WHERE}.${ENCODING}
ENV LANG ${LANGUAGE}

# Timezone settings
# Full list at https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
# e.g. "US/Pacific" for Los Angeles, California, USA
# e.g. ENV TZ "US/Pacific"
ENV TZ America/Sao_Paulo

# Apache settings
ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data

# Redundant but to ensure we are not going to break anything
# https://github.com/cassiopc/boca/tree/master/doc
# https://github.com/cassiopc/boca/tree/master/tools
# hadolint ignore=DL3002
USER root

# Install dependencies
# hadolint ignore=DL3005,DL3008,DL3015
RUN apt-get -y update \
    && apt-get -y dist-upgrade \
    && apt-get -y install \
        # Miscellaneous
        locales \
        tzdata \
        # Package: boca-common
        # https://github.com/cassiopc/boca/blob/master/debian/control
        # Pre-Depends:
        debconf \
        makepasswd \
        sharutils \
        # Depends:
        libany-uri-escape-perl \
        openssl \
        php-cli \
        php-gd \
        php-pgsql \
        php-xml \
        php-zip \
        postgresql-client \
        wget \
    && rm -rf /var/lib/apt/lists/*

RUN echo "Setting time zone to '${TZ}'" ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone \
    && echo "Etc/UTC" > /etc/timezone; dpkg-reconfigure -f noninteractive tzdata

# Copy BOCA repository from base image
COPY --from=bocarepo --chown="$APACHE_RUN_USER:$APACHE_RUN_GROUP" /boca/doc /var/www/boca/doc
COPY --from=bocarepo --chown="$APACHE_RUN_USER:$APACHE_RUN_GROUP" /boca/src /var/www/boca/src
COPY --from=bocarepo --chown="$APACHE_RUN_USER:$APACHE_RUN_GROUP" /boca/tools /var/www/boca/tools
# Copy modified local source code file(s)
# Use getenv() function to dynamically set up BOCA
COPY --chown="$APACHE_RUN_USER:$APACHE_RUN_GROUP" boca/src/private/conf.php /var/www/boca/src/private
# The script to create the database can run as a non-root user
COPY --chown="$APACHE_RUN_USER:$APACHE_RUN_GROUP" boca/src/private/createdb.php /var/www/boca/src/private

WORKDIR /var/www/boca
RUN \
    # install-bocawww
    # https://github.com/cassiopc/boca/blob/master/Makefile
    mkdir -p /usr/sbin /etc/cron.d /var/www/boca/ \
    # Done above
    # && cp -r src /var/www/boca/ \
    # Docs not necessary
	# && cp -r doc /var/www/boca/ \
    && install tools/boca-fixssh /usr/sbin/ \
    && install tools/cron-boca-fixssh /etc/cron.d/ \
    && chmod 700 /usr/sbin/boca-fixssh \
    # install-bocacommon
    # https://github.com/cassiopc/boca/blob/master/Makefile
    && mkdir -p /usr/sbin/ \
    && mkdir -p /etc/ \
    && cp tools/boca.conf /etc/ \
    && install tools/boca-config-dbhost.sh /usr/sbin/boca-config-dbhost \
    && chmod 700 /usr/sbin/boca-config-dbhost \
    # boca-common.postinst
    # https://github.com/cassiopc/boca/blob/master/debian/boca-common.postinst
    && chmod 600 /var/www/boca/src/private/conf.php
    # Done above
    # && chown "$APACHE_RUN_USER:$APACHE_RUN_GROUP" /var/www/boca/src/private/conf.php
