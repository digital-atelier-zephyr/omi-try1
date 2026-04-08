#!/bin/sh
set -e

echo "🔧 Running startup..."

# Генерим APP_KEY если не задан
if [ "$APP_KEY" = "" ] || [ "$APP_KEY" = "base64:placeholder_will_be_set" ]; then
    php artisan key:generate --force
fi

# Миграции
php artisan migrate --force

# Кэш
php artisan config:cache
php artisan route:cache

echo "✅ Startup complete. Launching PHP-FPM..."

# Запуск PHP-FPM
exec php-fpm
