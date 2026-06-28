# Single-domain deploy (Caddy + Laravel/Filament + Vue SPA)

One origin serves everything: the Vue SPA at `/`, Filament at `/admin`, the API
at `/api`. Caddy is the front door — it serves the built SPA and reverse-proxies
the Laravel paths to the backend container. SQLite + uploads live on a Docker
volume so they survive redeploys.

```
web (Caddy :80/:443)
 ├─ /admin /api /livewire /storage /css|js/filament /up ─▶ backend (php:8.4-apache :8080)
 └─ everything else ────────────────────────────────────▶ /srv/spa (Vite build, SPA fallback)
backend ── app-data volume (/persistent: SQLite + storage/) ──◀ queue, scheduler
```

## One-time setup

1. **Server:** a Hetzner Cloud VM (CX22 / CAX11, ~€4/mo), Ubuntu 24.04, Docker installed. Open ports **80 + 443**.
2. **DNS:** point your domain's A (and AAAA) records at the VM IP.
3. **Backend env:** `cp backend/.env.production.example backend/.env.production`, then set at least `APP_KEY` (`php -r 'echo "base64:".base64_encode(random_bytes(32));'`), `APP_URL`, and a real mailer key. Gitignored.
4. **Domain for Caddy:** `cp .env.example .env` and set `SITE_ADDRESS=yourdomain.hu`.

## Deploy

```sh
docker compose up -d --build
```

The backend image **runs the PHP test suite during build** — a red suite fails
the build, so bad code never replaces a running prod. Caddy fetches a Let's
Encrypt cert automatically once DNS resolves and 80/443 are reachable.

Update later: `git pull && docker compose up -d --build`.

## Notes / caveats

- **First boot seeds from the committed dev SQLite** (it ships inside the backend image) plus the seeders. For a clean prod DB, add `database/*.sqlite` to `backend/.dockerignore`, or wipe the volume once: `docker compose down && docker volume rm evrst_app-data`.
- **SQLite is single-writer.** Fine for admin-panel traffic; the worker + scheduler skip migrations (`RUN_RELEASE_TASKS=0`) to avoid lock contention. To scale up, set `DB_CONNECTION=pgsql` (the image already bundles `pdo_pgsql`) and add a Postgres service.
- **Same-origin = no CORS.** The SPA is built with `VITE_API_URL=/api` (relative); the separate Dokku/Fly deploy is untouched (its `frontend/.env.production` still points at Fly).
- **Local smoke test:** leave `SITE_ADDRESS=localhost`, run `docker compose up --build`, open <https://localhost> (accept Caddy's local cert).
