#!/bin/sh
set -eu
# Refresh executable PHP files after an image upgrade, preserving local data.
find /opt/seminfo/private-seed -maxdepth 1 -type f -name '*.php' -exec cp '{}' /var/www/boca/src/private/ \;
chown -R www-data:www-data /var/www/boca/src/private
chmod 700 /var/www/boca/src/private
php /opt/seminfo/bootstrap.php
exec apache2 -DFOREGROUND
