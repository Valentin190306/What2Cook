#!/bin/sh
set -e

chmod 733 /var/lib/php/sessions
chown www-data:www-data /var/lib/php/sessions

mkdir -p /var/www/app/log/cache/translations
chown -R www-data:www-data /var/www/app/log
chmod -R 755 /var/www/app/log

exec "$@"
