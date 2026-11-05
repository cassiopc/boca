ARG BASE=ghcr.io/joaofazolo/boca-docker/boca-web:latest
FROM ${BASE}
USER root
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends texlive-latex-base texlive-latex-recommended texlive-lang-portuguese && rm -rf /var/lib/apt/lists/*
COPY src/ /var/www/boca/src/
COPY tools/ /var/www/boca/tools/
COPY doc/problemexamples/problemtemplate/ /var/www/boca/doc/problemexamples/problemtemplate/
COPY boca-docker/boca/src/private/conf.php /var/www/boca/src/private/conf.php
COPY seminfo/server/ /opt/seminfo/
RUN sed -i 's/\r$//' /opt/seminfo/*.sh \
    && find /var/www/boca/doc/problemexamples/problemtemplate/compare \
            /var/www/boca/doc/problemexamples/problemtemplate/compile \
            /var/www/boca/doc/problemexamples/problemtemplate/limits \
            /var/www/boca/doc/problemexamples/problemtemplate/run \
       -type f -exec sed -i 's/\r$//' {} + \
    && mkdir -p /opt/seminfo/private-seed \
    && cp -a /var/www/boca/src/private/. /opt/seminfo/private-seed/ \
    && chown -R www-data:www-data /var/www/boca /opt/seminfo/private-seed \
    && chmod 700 /var/www/boca/src/private \
    && chmod 755 /opt/seminfo/server-init.sh \
    && cp /opt/seminfo/webcast-local.conf /etc/apache2/conf-enabled/seminfo-webcast.conf
ENTRYPOINT ["/opt/seminfo/server-init.sh"]
