# syntax=docker/dockerfile:1.7

# Single-image Laravel + Filament container. Ships an Apache for the web
# process and is also used (with a different command) for the queue
# worker process declared in fly.toml.
FROM php:8.4-apache AS base

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev zlib1g-dev libpng-dev \
        libonig-dev libxml2-dev libsqlite3-dev sqlite3 \
        libpq-dev \
        libwebp-dev libjpeg-dev libfreetype6-dev libavif-dev \
        ca-certificates curl gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath intl pdo_sqlite pdo_pgsql pgsql mbstring xml zip exif pcntl gd opcache \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Enable OPcache aggressively for production. The base php:8.4-apache
# image installs the extension but ships it disabled — without this
# block every request re-parses every PHP file. Roughly halves request
# latency on Filament pages.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.save_comments=1'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini \
    && { \
        echo 'memory_limit=256M'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/zz-runtime.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Apache → Laravel public/
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Install PHP deps with cache mount. Dev dependencies (phpunit etc.)
# are installed first so the test stage below can run the suite; they
# get pruned right after via `composer install --no-dev`.
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-interaction --no-scripts --prefer-dist

# App source
COPY . .

# Run the test suite as part of the build. phpunit.xml configures
# DB_CONNECTION=sqlite + DB_DATABASE=:memory: so no external services
# are needed. A red suite fails the Docker build, which fails the
# Render deploy — bad code never replaces a known-good production.
RUN composer dump-autoload --no-scripts \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
    && APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')" \
       APP_ENV=testing \
       php artisan test --stop-on-failure

# Strip dev dependencies and re-optimise the autoloader for production.
# `bootstrap/cache/*.php` is wiped at the end because the test stage above
# booted Laravel with dev packages installed, which caused
# `Illuminate\Foundation\PackageManifest` to write a packages.php that
# references `Laravel\Pail\PailServiceProvider`. After --no-dev that
# class is gone but the cached manifest still points at it, and any
# subsequent artisan call (entrypoint runtime) fails before
# `package:discover` gets a chance to regenerate the cache.
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize --no-dev --no-scripts \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
             bootstrap/cache/config.php bootstrap/cache/routes-*.php \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwX storage bootstrap/cache

# A volume gets mounted at /persistent in fly.toml. The entrypoint moves
# storage/ + database/ onto it on first boot, then symlinks back so all
# uploads + the SQLite file survive deploys.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
# PORT is whatever the host injects at runtime — Render sets 10000 by
# default for free-tier web services; Fly uses 8080 (its `internal_port`).
# The entrypoint substitutes it into Apache's config on boot, so this
# image works on either platform without rebuilding. EXPOSE is purely
# documentation; container hosts ignore it.
EXPOSE 8080
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
