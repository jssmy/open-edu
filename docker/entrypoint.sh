#!/bin/sh
set -e

echo "Starting Open-Edu application setup..."

# Ensure log directories exist
mkdir -p /var/log/supervisor /var/log/nginx

# Ensure storage directories and permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure storage link exists
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php /var/www/html/artisan storage:link || true
fi

# Run database migrations safely if requested (default on production)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    php /var/www/html/artisan migrate --force || echo "Migrations failed or already applied."
fi

# Cache configuration, routes, and views in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Optimizing Laravel configuration & routes cache..."
    php /var/www/html/artisan config:cache || true
    php /var/www/html/artisan route:cache || true
    php /var/www/html/artisan view:cache || true
fi

echo "Setup complete. Launching supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

