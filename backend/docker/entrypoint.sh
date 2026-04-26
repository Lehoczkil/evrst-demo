#!/usr/bin/env bash
set -euo pipefail

# Persistent volume root (mounted via fly.toml). Falls back to in-image
# paths when run locally without a mount.
VOLUME=/persistent

if [ -d "$VOLUME" ]; then
  mkdir -p "$VOLUME/storage" "$VOLUME/database"

  # Seed the volume from the image on first boot.
  if [ ! -f "$VOLUME/.bootstrapped" ]; then
    cp -an /var/www/html/storage/. "$VOLUME/storage/" || true
    [ -f /var/www/html/database/database.sqlite ] && cp -an /var/www/html/database/database.sqlite "$VOLUME/database/" || true
    touch "$VOLUME/.bootstrapped"
  fi

  # Make sure the SQLite file exists.
  [ -f "$VOLUME/database/database.sqlite" ] || touch "$VOLUME/database/database.sqlite"

  # Symlink so storage/ + database/ inside the app point at the volume.
  rm -rf /var/www/html/storage /var/www/html/database
  ln -s "$VOLUME/storage" /var/www/html/storage
  ln -s "$VOLUME/database" /var/www/html/database

  chown -R www-data:www-data "$VOLUME"
fi

cd /var/www/html

# Public storage symlink — no-op if it already exists.
php artisan storage:link --relative --quiet 2>/dev/null || true

# Apply pending schema on every boot. Idempotent.
php artisan migrate --force --no-interaction || true

# Tighten caches in production.
if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
