#!/bin/sh
set -e

cd /var/www/html

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting services..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
