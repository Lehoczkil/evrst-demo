# EVRST — repo guide for Claude

Monorepo for the **Escape Velocity Rocketry Student Team** site.

```
.
├── frontend/   React 19 + Vite SPA, package manager: bun
├── backend/    Laravel 12 + Filament v4 admin, SQLite
├── .github/    Dokku deploy workflow (tag-driven)
├── .buildpacks heroku-buildpack-nodejs @ master + dokku/heroku-buildpack-nginx
├── CHANGELOG.md
└── .claude/skills/{evrst-frontend,evrst-backend}/SKILL.md
```

When working on the SPA, follow `.claude/skills/evrst-frontend/SKILL.md`.
When working on the API/admin, follow `.claude/skills/evrst-backend/SKILL.md`.
The README at the repo root has the public-facing tour of features
(roles, applications flow, tasks, kanban, mailer/queue env).

## Run locally

```sh
# Backend (port 8000)
cd backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed && php artisan serve

# Backend queue worker (separate terminal — needed for member-application
# admin notifications + Discord webhook posts; QUEUE_CONNECTION=database)
cd backend && php artisan queue:work --tries=3

# Frontend (port 5173 — falls back to 5174/5175 if busy)
cd frontend && bun install && bun run dev
```

Admin login: `admin@evrst.test` / `password` at `http://localhost:8000/admin`.
Frontend `.env` already points `VITE_API_URL` at `http://localhost:8000/api`.

## Branching + commits

- Feature work goes on `feature/<version>` branches (currently `feature/0.4.0`).
- **Small, single-responsibility commits** with conventional prefixes: `feat:`, `fix:`, `chore:`, `refactor:`, `perf:`, `docs:`, `style:`, optional scopes like `feat(home):`, `chore(backend):`.
- Never push to `main` directly. Never `git reset --hard` or `git push --force` without explicit permission.
- Never `--no-verify` / `--no-gpg-sign` etc. Investigate hook failures, don't bypass them.
- Don't commit without an explicit ask.

## Deploy

`.github/workflows/deploy.yaml` validates the pushed tag matches `frontend/package.json` version, then pushes to Dokku. The Dokku side currently builds the SPA only (heroku-buildpack-nodejs detects `bun.lock` and runs `bun install` + `bun run build`). The Laravel backend is not yet wired into the deploy.

## Things to know about this user

- Prefers **bun** for the frontend (never npm/yarn/pnpm; no `package-lock.json`).
- Wants tight, factual updates, not narration of every step.
- Likes `/loop` / `/ultrareview`-style automation but does **not** want me to launch `ultrareview` myself — they trigger it.
- Prefers proper investigation over destructive shortcuts.

## Common gotchas (carried over from past sessions)

- The Vite dev server may pick a non-default port (5174/5175) if 5173 is held — read the actual port from the server log before opening the browser.
- The harness blocks long-running web servers unless the user has approved them; if a `php artisan serve` / `bun run dev` background task is denied, surface the blocker rather than retrying.
- The Heroku buildpack we deploy with detects `bun.lock` only at recent versions — `.buildpacks` pins `@master` for that reason; do not pin to a specific commit without checking.
- Mantine **does not** accept responsive-object syntax for `gap` on `Stack` / `Group` (only spacing-token strings). It does accept it for `w`, `h`, `mih`, `miw`, `pt`, `p` etc.
- A subset of `react/jsx-runtime` types are deprecated under `React.FormEvent` in React 19 — type form handlers as inline `(event) => …` or use the `FormEventHandler` alias only if necessary.
- The Heroku buildpack will run `npm run build` if it can't detect bun, so don't rely on `bun:` prefixed scripts in `frontend/package.json`.
- `MAIL_MAILER=log` is the default in `.env.example`. The temp passwords sent by the Accept-application / Create-user / Resend-temp-password actions land in `backend/storage/logs/laravel.log` (queued through the database queue worker). Switch to Postmark/Resend (env keys are scaffolded in `config/services.php`) for real email delivery.
- The seeded `admin@evrst.test` has `password_changed_at` pre-stamped so it never trips the first-login redirect. Every other user provisioned through the admin must set a new password before reaching any admin page.
- Switch to a real mailer to actually deliver temp passwords: in `backend/.env` set `MAIL_MAILER=resend` plus `RESEND_API_KEY=...` (or `MAIL_MAILER=postmark` + `POSTMARK_API_KEY=...` — both have config stubs in `config/services.php`). Local dev keeps `MAIL_MAILER=log` so the password lands in `storage/logs/laravel.log`.
- LAN-accessible dev: backend launches with `php artisan serve --host=0.0.0.0 --port=8000`; the SPA is bound to `0.0.0.0` via `frontend/package.json`'s `"dev": "vite --host 0.0.0.0"`. `frontend/.env` `VITE_API_URL` plus `backend/.env`'s `APP_URL` + `ADMIN_URL` need updating if your LAN IP changes.
- Activity log lives in `activity_logs` and is populated by `App\Concerns\LogsActivity` (mounted on `Resource`, `Task`, `MemberApplication`). Visible to Admins under Membership → Activity log; not surfaced to Manager/Member.
- Roles + permissions are seeded via `Perm::catalog()` but admins can rebalance them at runtime in Filament: Membership → Roles & permissions. Code-side perm checks still drive visibility — flipping perms in the UI takes effect on the next request.
- The kanban (`/admin/tasks/kanban`) disables drag-and-drop while filters are active to avoid rewriting `position` over a partial set; the page shows a banner explaining why.
- SortableJS is vendored at `backend/public/vendor/sortable.min.js` so the kanban works without internet access.
