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

echo "==> Fixing storage and cache permissions..."
# Обязательно создаем структуру папок
mkdir -p /var/www/html/storage/app/public \
         /var/www/html/storage/app/livewire-tmp \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Выдаем права пользователю www-data, под которым работает php-fpm
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
exec nginx -g "daemon off;"
