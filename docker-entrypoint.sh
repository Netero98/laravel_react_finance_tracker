#!/bin/bash
set -e

# Copy storage directory contents if storage volume is empty
if [ ! "$(ls -A /var/www/storage)" ]; then
    cp -a /var/www/storage.dist/. /var/www/storage/
fi

# Copy bootstrap/cache directory contents if cache volume is empty
if [ ! "$(ls -A /var/www/bootstrap/cache)" ]; then
    cp -a /var/www/bootstrap/cache.dist/. /var/www/bootstrap/cache/
fi

# Set proper permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Clear and cache routes and config
php /var/www/artisan route:clear
php /var/www/artisan route:cache
php /var/www/artisan config:clear
php /var/www/artisan config:cache

# Run original docker-php-entrypoint
exec docker-php-entrypoint "$@"
