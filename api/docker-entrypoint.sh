#!/bin/sh
set -e

echo "🔧 Running startup..."

# Создаём .env из env vars если не существует
if [ ! -f .env ]; then
    echo "APP_KEY=${APP_KEY}" > .env
    echo "DB_CONNECTION=${DB_CONNECTION}" >> .env
    echo "DB_HOST=${DB_HOST}" >> .env
    echo "DB_PORT=${DB_PORT}" >> .env
    echo "DB_DATABASE=${DB_DATABASE}" >> .env
    echo "DB_USERNAME=${DB_USERNAME}" >> .env
    echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
fi

# Генерим APP_KEY если не задан
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --force
fi

# Ждём БД
echo "⏳ Waiting for database..."
until php artisan db:monitor --databases=pgsql 2>/dev/null; do
    sleep 2
done

# Миграции
php artisan migrate --force

# Кэш
php artisan config:cache
php artisan route:cache

echo "✅ Startup complete. Launching PHP-FPM..."
exec php-fpm
