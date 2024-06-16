FROM php:8.1-apache

# Enable Apache modules
RUN a2enmod rewrite
# Install PostgreSQL client and its PHP extensions
RUN apt-get update \
    && apt-get install -y libpq-dev make \
    && docker-php-ext-install pdo pdo_pgsql pgsql


ENV APACHE_DOCUMENT_ROOT /var/www/boca

# Changes document root to be /var/www/boca
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set the working directory to /var/www/html
WORKDIR /boca

COPY . .

# Runs boca make install
RUN make install
