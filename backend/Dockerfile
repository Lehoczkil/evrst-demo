# syntax=docker/dockerfile:1.7

# Single-image Laravel + Filament container. Ships an Apache for the web
# process and is also used (with a different command) for the queue
# worker process declared in fly.toml.
FROM php:8.4-apache AS base

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev zlib1g-dev libpng-dev \
        libonig-dev libxml2-dev libsqlite3-dev sqlite3 \
        ca-certificates curl gnupg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath intl pdo_sqlite mbstring xml zip exif pcntl gd \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Apache → Laravel public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Install PHP deps with cache mount
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# App source
COPY . .

# Finalise composer with --no-scripts so we don't run artisan at build
# time (it would need APP_KEY + DB to boot). The entrypoint runs
# `package:discover` at runtime, where real env vars from Render are set.
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && mkdir -p storage/framework/{cache/data,sessions,views,testing} storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwX storage bootstrap/cache

# A volume gets mounted at /persistent in fly.toml. The entrypoint moves
# storage/ + database/ onto it on first boot, then symlinks back so all
# uploads + the SQLite file survive deploys.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
EXPOSE 8080

# Apache listens on 8080 to match fly.toml's internal_port.
RUN sed -ri 's!Listen 80!Listen 8080!g' /etc/apache2/ports.conf \
    && sed -ri 's!:80!:8080!g' /etc/apache2/sites-available/000-default.conf

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
