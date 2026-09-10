#!/bin/sh
# ============================================================
# Entry point: jalankan migration & cache sebelum start PHP-FPM
# ============================================================

echo "=== Laravel Startup ==="

# Copy public files (including build/) to shared volume for nginx
echo "Syncing public files to shared volume..."
cp -r /var/www/html/public/. /shared-public/

# Generate APP_KEY kalau belum ada
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Jalankan migration (kalau DB belum ready, retry 3x)
echo "Running migrations..."
for i in 1 2 3; do
    php artisan migrate --force && break
    echo "DB not ready, retrying in 3s... (attempt $i/3)"
    sleep 3
done

# Cache config & routes (optimasi production)
echo "Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Starting PHP-FPM ==="
exec php-fpm
