FROM php:8.1-apache

# Enable Apache modules
RUN a2enmod rewrite
# Install PostgreSQL client and its PHP extensions
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Set the working directory to /var/www/html
WORKDIR /var/www/html

COPY src .
