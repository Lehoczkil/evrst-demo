# syntax=docker/dockerfile:1.7
#
# Front door for the single-domain deploy. Stage 1 builds the Vue SPA with the
# same bun toolchain used locally; stage 2 ships it inside Caddy, which serves
# the static SPA and reverse-proxies the Laravel/Filament paths to `backend`.
# Build context is the repo root (see compose.yaml + the root .dockerignore).

# ── 1. Build the SPA ──────────────────────────────────────────────────────
FROM oven/bun:1 AS spa
WORKDIR /app

# Deps first for layer caching. --ignore-scripts skips the package `postinstall`
# that runs `playwright install chromium` (a browser download we don't need here).
COPY frontend/package.json frontend/bun.lock ./
RUN bun install --frozen-lockfile --ignore-scripts

COPY frontend/ ./

# Same-origin deploy: the SPA calls the API at a relative /api, so this image
# stays domain-agnostic. `.env.production.local` is the highest-precedence Vite
# env file, so it overrides VITE_API_URL from the committed .env.production
# (which still points at the old standalone Fly backend) — for THIS build only.
ARG VITE_API_URL=/api
RUN printf 'VITE_API_URL=%s\n' "$VITE_API_URL" > .env.production.local
RUN bun run build      # vue-tsc + vite build → /app/build (outDir from vite.config.ts)

# ── 2. Serve with Caddy ───────────────────────────────────────────────────
FROM caddy:2
COPY --from=spa /app/build /srv/spa
# compose bind-mounts the real Caddyfile over this; the baked copy is a default
# so the image also works when run standalone.
COPY deploy/Caddyfile /etc/caddy/Caddyfile
