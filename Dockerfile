# Tahap 1: Build frontend Laravel
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build


# Tahap 2: Menjalankan Laravel dengan PHP dan Apache
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libicu-dev \
    libonig-dev libpng-dev libjpeg62-turbo-dev \
    libfreetype6-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
       pdo_pgsql pdo_sqlite mbstring zip intl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Konfigurasi Apache untuk Laravel
RUN a2enmod rewrite \
    && sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' \
       /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' \
       > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# Salin kode Laravel
COPY . .

# Salin hasil build frontend
COPY --from=frontend /app/public/build ./public/build

# Install dependency Laravel dan siapkan folder
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev --prefer-dist --no-interaction \
    --no-progress --optimize-autoloader --no-scripts \
    && mkdir -p storage/app/public \
       storage/framework/cache/data \
       storage/framework/sessions \
       storage/framework/views \
              storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -s /var/www/html/storage/app/public \
       /var/www/html/public/storage

EXPOSE 80

CMD ["apache2-foreground"]
