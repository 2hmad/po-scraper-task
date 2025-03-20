#!/bin/bash
set -e

# Fix storage directory permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Clear cache and optimize
php artisan optimize:clear

# Execute the main command
exec "$@"
