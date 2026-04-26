#!/usr/bin/env bash
set -euo pipefail

# Optional persistent volume (Fly etc.). On Render free tier nothing is
# mounted at /persistent and the script falls through to the in-image
# storage/ + database/ paths — SQLite resets on each redeploy, but the
# bootstrap-if-empty block below re-creates the admin user automatically.
VOLUME=/persistent
if [ -d "$VOLUME" ]; then
  mkdir -p "$VOLUME/storage" "$VOLUME/database"
  if [ ! -f "$VOLUME/.bootstrapped" ]; then
    cp -an /var/www/html/storage/. "$VOLUME/storage/" || true
    [ -f /var/www/html/database/database.sqlite ] && cp -an /var/www/html/database/database.sqlite "$VOLUME/database/" || true
    touch "$VOLUME/.bootstrapped"
  fi
  [ -f "$VOLUME/database/database.sqlite" ] || touch "$VOLUME/database/database.sqlite"
  rm -rf /var/www/html/storage /var/www/html/database
  ln -s "$VOLUME/storage" /var/www/html/storage
  ln -s "$VOLUME/database" /var/www/html/database
  chown -R www-data:www-data "$VOLUME"
fi

cd /var/www/html

# Make sure the framework directories exist before any artisan call —
# `realpath(storage_path('framework/views'))` returns false otherwise
# and the view compiler throws "Please provide a valid cache path".
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    database

[ -f database/database.sqlite ] || touch database/database.sqlite
chown -R www-data:www-data storage bootstrap/cache database
chmod -R ug+rwX storage bootstrap/cache

# Build the package manifest now that real APP_KEY etc. are set in env.
# (Skipped at build time via composer --no-scripts to avoid the chicken-
# and-egg of needing APP_KEY before secrets are injected.)
php artisan package:discover --ansi --no-interaction || true

# Public storage symlink — no-op if it already exists.
php artisan storage:link --quiet 2>/dev/null || true

# Apply pending schema on every boot. Idempotent.
php artisan migrate --force --no-interaction || true

# Seed when the DB is empty (i.e. first boot OR after a free-tier reset).
# Every seeder uses updateOrCreate so re-running is also safe — but we
# only invoke it when there's no admin user yet to keep deploys quick.
USERS=$(php -r "require 'vendor/autoload.php'; \$a=require 'bootstrap/app.php'; \$a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo \\App\\Models\\User::count();" 2>/dev/null || echo 0)
if [ "$USERS" = "0" ]; then
  php artisan db:seed --force --no-interaction || true
fi

# Tighten caches in production.
if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
