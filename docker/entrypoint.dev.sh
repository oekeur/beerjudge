#!/bin/sh
set -e

cd /var/www

php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan storage:link --quiet 2>/dev/null || true

exec supervisord -c /etc/supervisord.dev.conf
