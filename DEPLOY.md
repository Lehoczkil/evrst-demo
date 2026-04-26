# Deploy guide — free demo hosting

Two pieces, two free hosts:

| Piece | Host | URL it gets |
|---|---|---|
| Backend (Laravel + Filament admin + REST API) | **Fly.io** | `https://evrst-admin.fly.dev` |
| Frontend (Vite SPA) | **Vercel** | `https://evrst-demo.vercel.app` |

Both have a free tier sufficient for a demo. Total monthly bill: **$0** (Fly's free credit covers a 256 MB + 512 MB pair indefinitely for a quiet app).

---

## 0. Push the repo to GitHub

```sh
gh auth login
gh repo create evrst-demo --public --source . --remote origin --push
```

(Or do it through the GitHub UI, then `git remote add origin … && git push -u origin main`.)

---

## 1. Backend on Fly.io

Install once:

```sh
brew install flyctl
fly auth signup            # free, no card needed for the trial credit
```

Then from `backend/`:

```sh
cd backend

# Pick a unique app name (must be globally unique on fly.dev)
fly apps create evrst-admin

# Pick a region close to you — fra (Frankfurt), ams (Amsterdam),
# lhr (London), iad (US East), syd (Sydney) etc.
fly volumes create evrst_data --size 1 --region fra

# Grab a fresh APP_KEY (don't reuse your local dev key)
APP_KEY="base64:$(openssl rand -base64 32)"

# Set production secrets
fly secrets set \
    APP_KEY="$APP_KEY" \
    APP_URL=https://evrst-admin.fly.dev \
    ADMIN_URL=https://evrst-admin.fly.dev/admin \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    SESSION_DRIVER=database \
    SESSION_SECURE_COOKIE=true \
    SESSION_SAME_SITE=lax \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    MAIL_MAILER=resend \
    RESEND_API_KEY=YOUR_RESEND_KEY \
    MAIL_FROM_ADDRESS=admin@evrst.demo \
    MAIL_FROM_NAME='EVRST Admin' \
    DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...

# First deploy
fly deploy

# One-shot: seed the admin user, roles, sample data
fly ssh console -C "php artisan db:seed --force"

# Spin up the queue worker process
fly scale count app=1 worker=1

# Watch it boot
fly logs
```

Visit `https://evrst-admin.fly.dev/admin` → log in with `admin@evrst.test` / `password`. **Immediately change both** in Filament → Profile.

### Updating the backend

Anytime: `cd backend && fly deploy`. Migrations run on every boot via `docker/entrypoint.sh`.

---

## 2. Frontend on Vercel

```sh
brew install vercel-cli   # or `npm i -g vercel`
cd frontend
vercel login
vercel link               # accept defaults; pick your scope
```

Vercel reads `frontend/vercel.json` for build config. Two ways to set the API URL:

1. **Quick:** keep the value in `frontend/.env.production` — already pointing at `https://evrst-admin.fly.dev/api`. If your Fly app name differs, edit that file.
2. **Cleaner:** unset that file value and add the env var on the Vercel project page (Settings → Environment Variables → `VITE_API_URL`). Vercel envs win over the dotfile.

Then ship:

```sh
vercel --prod
```

You get `https://<project>.vercel.app`. Subsequent pushes to the GitHub repo's `main` branch auto-deploy.

### Pointing the SPA at the right backend

If the SPA URL ends up at, say, `https://evrst-demo.vercel.app`, also update Fly:

```sh
fly secrets set CORS_ALLOWED_ORIGINS=https://evrst-demo.vercel.app
```

(`config/cors.php` is wide-open already — this is just for if you tighten it later.)

---

## 3. Demo cleanup

Before sharing the URL with anyone:

```sh
# Rotate the seeded admin
fly ssh console
php artisan tinker
> User::where('email','admin@evrst.test')->update([
>     'email' => 'demo@your-address.dev',
>     'password' => Hash::make('a-strong-one'),
> ])
```

Otherwise the default credentials are publicly known.

---

## Costs / limits to watch

- **Fly free credit** (~$5/mo) easily covers `512 MB app + 256 MB worker + 1 GB volume` if the app sleeps when idle (`auto_stop_machines = "stop"` in `fly.toml`). First request after sleep takes ~1 s.
- **Vercel free** allows unlimited deploys + 100 GB bandwidth/mo + automatic HTTPS.
- **Resend free** is 3 000 emails/mo — overkill for demo.
- **SQLite** on a Fly volume is durable across deploys but **NOT** across `fly volumes destroy`. Take a backup before any destructive volume operation: `fly ssh console -C "sqlite3 /persistent/database/database.sqlite .dump" > backup.sql`.

---

## Common things that go wrong

| Symptom | Fix |
|---|---|
| Login form posts and bounces back to `/admin/login` | `SESSION_SECURE_COOKIE=true` is set but the URL is HTTP somehow. Check `APP_URL`. |
| Filament panel renders but logo broken | `php artisan config:cache` cached the wrong `APP_URL`. Re-run `fly deploy` after fixing the secret. |
| Discord webhook never fires | Worker process not running. `fly status` should show `worker` machine started. If not, `fly scale count worker=1`. |
| `/admin/profile` 500s after avatar upload | `php artisan storage:link` didn't run. The entrypoint runs it on every boot but the volume mount may need a one-shot: `fly ssh console -C "php artisan storage:link --force"`. |
| Sidebar items missing / wrong perms | Re-seed roles: `fly ssh console -C "php artisan db:seed --class=RoleSeeder --force"`. |
