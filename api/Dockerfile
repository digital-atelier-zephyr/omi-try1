FROM php:8.4-fpm-alpine

# Зависимости для PostgreSQL и Laravel
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем всё
COPY . .

# Ставим зависимости
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Права на storage и cache
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["docker-entrypoint.sh"]
