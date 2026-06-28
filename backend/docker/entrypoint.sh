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

# Refuse to boot without APP_KEY in the right shape. Catches the case
# where Render's `generateValue: true` produced raw bytes instead of
# the `base64:<base64>` prefix Laravel needs.
if [ -z "${APP_KEY:-}" ]; then
    echo "FATAL: APP_KEY is empty. Set it in Render → Environment to base64:\$(openssl rand -base64 32)"
    exit 1
fi
case "$APP_KEY" in
    base64:*|*:* ) ;;  # Laravel-shaped or driver:key
    *) echo "FATAL: APP_KEY must start with 'base64:' followed by base64 of 32 random bytes."
       echo "       Generate one with: php -r 'echo \"base64:\".base64_encode(random_bytes(32));'"
       exit 1;;
esac

# Bind Apache to whatever port the host injected ($PORT). Render sets
# 10000 on free tier, Fly uses 8080. Default 8080 covers local docker run.
PORT="${PORT:-8080}"
sed -ri "s!^Listen [0-9]+!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!:[0-9]+>!:${PORT}>!g" /etc/apache2/sites-available/000-default.conf

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

# Schema + seed are release tasks: run them in exactly one container. The queue
# worker and scheduler share this image and the same /persistent SQLite file, so
# if they ran migrate/seed too you'd get three processes writing the database on
# boot (lock contention), and the seeders would re-run on every worker restart —
# clobbering runtime permission changes admins made in Filament. compose.yaml
# sets RUN_RELEASE_TASKS=0 on those. Defaults to 1 so single-container hosts
# (Fly/Render) are unaffected.
if [ "${RUN_RELEASE_TASKS:-1}" = "1" ]; then
  # Apply pending schema on every boot. Idempotent.
  php artisan migrate --force --no-interaction

  # Seed every boot. All seeders use `updateOrCreate` / `firstOrCreate`,
  # so this is safe to re-run and guarantees the admin user exists even
  # if a previous deploy's seed step failed silently. Stderr is NOT
  # suppressed so any seeder failure surfaces in the logs.
  php artisan db:seed --force --no-interaction
fi

# Tighten caches in production.
if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
