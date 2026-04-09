#!/bin/sh
set -e

echo "🔧 Running startup..."

# Создаём .env если не существует
if [ ! -f .env ]; then
    echo "APP_KEY=" > .env
    echo "DB_CONNECTION=${DB_CONNECTION}" >> .env
    echo "DB_HOST=${DB_HOST}" >> .env
    echo "DB_PORT=${DB_PORT}" >> .env
    echo "DB_DATABASE=${DB_DATABASE}" >> .env
    echo "DB_USERNAME=${DB_USERNAME}" >> .env
    echo "DB_PASSWORD=${DB_PASSWORD}" >> .env
    echo "LOG_CHANNEL=stderr" >> .env
fi

# Генерим APP_KEY если не задан
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
    # Экспортируем в env чтобы config:cache подхватил
    export APP_KEY=$(grep APP_KEY .env | head -1 | cut -d= -f2-)
fi

# Ждём БД
echo "⏳ Waiting for database..."
until php -r "try { new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'ok'; } catch(Exception \$e) { exit(1); }" 2>/dev/null; do
    echo "  DB not ready, retrying..."
    sleep 2
done
echo "✅ Database connected"

# Миграции
php artisan migrate --force

# Кэш
php artisan config:cache

echo "✅ Startup complete. Launching PHP-FPM..."
exec php-fpm
