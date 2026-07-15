#!/bin/bash

# Ensure storage directories exist
mkdir -p storage/framework/{views,sessions,cache} \
         storage/logs \
         storage/app/public \
         bootstrap/cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Always generate .env from environment variables
printenv | grep -E '^(APP_|DB_|CACHE_|SESSION_|MAIL_|LOG_|QUEUE_|BROADCAST_|FILESYSTEM_|REDIS_)' | while IFS='=' read -r key value; do
    echo "${key}=\"${value}\""
done | sort > .env
echo "Generated .env from environment variables"

# Discover packages (skipped during build)
php artisan package:discover --ansi

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations
php artisan migrate --force --no-interaction

# Cache config, routes, views for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting nginx + php-fpm..."

# Start services
exec /usr/bin/supervisord -c /etc/supervisord.conf
