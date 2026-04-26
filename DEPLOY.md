# Deploy guide — free demo hosting (no credit card)

Two pieces, two free hosts:

| Piece | Host | URL it gets | Card required? |
|---|---|---|---|
| Backend (Laravel + Filament admin + REST API) | **Render.com** | `https://evrst-admin.onrender.com` | ❌ No |
| Frontend (Vite SPA) | **Vercel** | `https://evrst-demo.vercel.app` | ❌ No |

Both have a free tier sufficient for a demo. **Total bill: $0**.

> Note: this used to point at Fly.io; Fly removed their no-card free trial in 2024 and now requires a credit card upfront. Switched to Render. The old `fly.toml` is parked under `deploy/fly/` for reference if you ever want to switch back.

## Render free-tier limitations to know about

The free plan trades persistence and uptime for $0:

- **15-min sleep** when no traffic; ~30s cold start on the next request. Fine for a demo, annoying for production.
- **No persistent disk.** SQLite resets on each redeploy. The Docker entrypoint re-seeds the admin user automatically when the DB is empty, so the demo always works — but uploaded avatars and any custom data added through the admin will disappear on the next deploy. (For permanent data, wire up Neon Postgres — see "Upgrade paths" at the end.)
- **One service only**, no background workers. We work around this by setting `QUEUE_CONNECTION=sync` so Discord/email jobs fire inline with the request.
- **750 instance hours/month** total — easily covered by one always-sleeping app.

---

## 0. Push the repo to GitHub

You can't deploy without a Git remote.

```sh
cd /Users/feat/Documents/projects/evrst-demo

# If you have the GitHub CLI installed:
gh auth login          # if you haven't already
gh repo create evrst-demo --public --source=. --remote=origin --push

# OR manually:
# (create the repo on github.com, then)
# git remote add origin git@github.com:<you>/evrst-demo.git
# git push -u origin main
```

Confirm at `https://github.com/<you>/evrst-demo` — should show 3 commits.

---

## 1. Backend on Render

### a. Sign up

Open https://dashboard.render.com/register → sign in with GitHub. No card needed.

### b. Create the service from the Blueprint

1. **Dashboard → New → Blueprint**
2. Connect the GitHub repo (`evrst-demo`)
3. Render reads `render.yaml` at the repo root → it'll show one service called **evrst-admin** under the **Free** plan
4. Click **Apply**

Render will:
- Pull the Dockerfile from `backend/Dockerfile`
- Build it (3-5 min the first time)
- Start the container, which runs `entrypoint.sh` → migrates → seeds → serves on `:8080`

The first build streams in the dashboard. When it goes green, your URL is `https://evrst-admin.onrender.com` (or whatever name Render auto-appended if `evrst-admin` was taken).

### c. Set the secrets that aren't in render.yaml

In **Dashboard → evrst-admin → Environment**:

```
DISCORD_WEBHOOK_URL = https://discord.com/api/webhooks/1498024652931334316/o-6n54R1TDol89TLWZ4dNFV15UU6J4nolvlzHY0XbKqb_ADN6luOMZ9cW1BffoiURC7-
RESEND_API_KEY      = re_REPLACE_ME      (or leave MAIL_MAILER=log if you don't care about email yet)
```

Click **Save Changes** — Render redeploys automatically.

### d. ⚠️ Fix the URLs if your service name differs

If Render assigned `evrst-admin-x7q1` instead of `evrst-admin`, edit `render.yaml`:

```yaml
- key: APP_URL
  value: https://evrst-admin-x7q1.onrender.com
- key: ADMIN_URL
  value: https://evrst-admin-x7q1.onrender.com/admin
```

Push the change → Render redeploys → cookies + Filament URLs work correctly.

### e. Smoke test

Open `https://<your-service>.onrender.com/admin/login`. The first hit takes ~30 s while the dyno wakes. You should see the EVRST logo + login form.

Log in with **`admin@evrst.test` / `password`** (auto-seeded by the entrypoint).

### f. ⚠️ Rotate the seeded credentials immediately

While logged in:

1. Click your avatar (top-right) → **Profile**
2. Change email + password
3. Save

Or via Render shell:

```sh
# Render → service → Shell tab → opens an interactive terminal
php artisan tinker
> User::where('email','admin@evrst.test')->update(['email'=>'demo@your.dev','password'=>Hash::make('a-strong-one')]);
> exit
```

---

## 2. Frontend on Vercel

```sh
brew install vercel-cli   # or npm i -g vercel
cd /Users/feat/Documents/projects/evrst-demo/frontend
vercel login              # sign in with GitHub
vercel link               # → new project → name "evrst-demo"
```

Vercel auto-detects Vite from `vercel.json`. If your Render URL differs from `evrst-admin.onrender.com`, set the API URL on Vercel:

```sh
vercel env add VITE_API_URL production
# paste: https://<your-render-service>.onrender.com/api
```

Or edit `frontend/.env.production` directly and commit.

Ship it:

```sh
vercel --prod
```

You get `https://<project>.vercel.app`. From here on, every push to `main` auto-deploys.

---

## 3. End-to-end smoke test

1. `https://<your-service>.onrender.com/admin/login` → log in (with the new credentials).
2. Walk through dashboard, calendar, kanban, applications, profile.
3. Open the SPA at `https://<project>.vercel.app/` → click **Join us** → submit the form.
4. Within ~5 s:
   - A row appears in `/admin/member-applications`
   - The Discord channel gets a 📬 New member application embed
   - The bell on the admin shows a new notification

If 1-3 work, you're live.

---

## 4. Updating the deployed app

```sh
cd /Users/feat/Documents/projects/evrst-demo
git add . && git commit -m "feat: …" && git push
```

Render and Vercel both auto-deploy on push to `main`. Usually 2-4 min total.

---

## 5. Useful Render commands

Render doesn't have a CLI for free-tier inspection — everything's via the dashboard:

- **Logs** → service → Logs tab (live tail)
- **Shell** → service → Shell tab (free tier limited; available when service is awake)
- **Manual deploy** → service → Manual Deploy → Deploy latest commit
- **Env vars** → service → Environment → Edit then save → triggers redeploy

---

## 6. Common things that go wrong

| Symptom | Fix |
|---|---|
| Cold start takes >30s | Normal on free tier. The page eventually loads. |
| `php artisan migrate` errors in build logs | Almost always a missing PHP extension. Add it in `backend/Dockerfile` under `docker-php-ext-install`. |
| Login posts and bounces back to `/admin/login` | `APP_URL` doesn't match the actual scheme. `https://...onrender.com` (no trailing slash). |
| Avatar uploads vanish after a redeploy | Expected on free tier — no persistent disk. Move to Cloudflare R2 or a paid Render disk if it bothers you. |
| Discord webhook silent | `QUEUE_CONNECTION=sync` should make jobs fire in-band. Double-check the env var. |
| 500 with "no application encryption key" | `APP_KEY` missing. `render.yaml` uses `generateValue: true` so it should be auto. Check Environment tab. |

---

## 7. Upgrade paths once the demo proves itself

- **Persistent SQLite** → switch to Render's $7/mo Starter plan; adds a 1 GB disk that survives deploys. Or move to Postgres on Neon (free, no card) — change `DB_CONNECTION=pgsql` and four env vars.
- **No more sleep** → Starter ($7/mo) keeps the service warm.
- **Background worker** → enable a Render Worker service (paid) and switch `QUEUE_CONNECTION` back to `database`.
- **Custom domain** → free on both Render and Vercel; just point a CNAME and they handle TLS.

---

## TL;DR (after sign-up + GitHub push)

1. Render Dashboard → New → Blueprint → pick the repo → Apply.
2. Add `DISCORD_WEBHOOK_URL` (and optional `RESEND_API_KEY`) in the Environment tab.
3. `cd frontend && vercel link && vercel --prod`.
4. Open the Render URL, log in, change the password.
5. Done.
