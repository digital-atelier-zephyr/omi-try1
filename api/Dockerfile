FROM php:8.3-fpm-alpine

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

# Если есть composer.json — ставим зависимости
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader; fi

COPY . .

EXPOSE 9000
CMD ["php-fpm"]
