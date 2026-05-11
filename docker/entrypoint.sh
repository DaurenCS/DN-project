#!/bin/sh
set -e

echo "==> Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    sleep 2
done
echo "==> PostgreSQL ready"

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Running seeders..."
php artisan db:seed --force

echo "==> Publishing Livewire assets..."
php artisan livewire:publish --assets

echo "==> Publishing Filament assets..."
php artisan filament:assets

echo "==> Caching..."
php artisan config:cache
php artisan view:cache

echo "==> Creating storage link..."
php artisan storage:link || true

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
exec nginx -g "daemon off;"
