#!/bin/sh
set -e

cd /var/www/html

# Ensure the SQLite database file exists
if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    chown www-data:www-data "$DB_DATABASE"
fi

# Run migrations (idempotent)
php artisan migrate --force

# Ensure the administrator account exists (idempotent)
php artisan db:seed --class=AdminUserSeeder --force

# Cache config/routes/views for production
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Ensure writable dirs
chown -R www-data:www-data storage bootstrap/cache database

exec "$@"
