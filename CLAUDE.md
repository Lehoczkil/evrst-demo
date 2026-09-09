# EVRST — repo guide for Claude

Monorepo for the **Escape Velocity Rocketry Student Team** site.

```
.
├── frontend/   Vue 3.5 + PrimeVue + Pinia + Vite SPA, package manager: bun
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

**Current target: Hetzner, via `compose.yaml`.** One VM, one domain. Caddy (`deploy/Caddyfile`) serves the built SPA at `/` and reverse-proxies `/admin`, `/api`, `/livewire`, `/storage`, `/css|js/filament`, `/up` to the Laravel container. Four services: `backend` (owns the schema — `RUN_RELEASE_TASKS` defaults to 1 so it alone runs migrate+seed on boot), `queue` (`queue:work`), `scheduler` (ticks `schedule:run` every 60s), `web` (Caddy + TLS). SQLite + `storage/` live on the `app-data` volume. Env comes from `backend/.env.production` (gitignored; copy `backend/.env.production.example`) plus `SITE_ADDRESS` in the repo-root `.env`. Guides: `deploy/README.md`, `deploy/HETZNER.md`, `deploy/DNS.md`.

Two stale deploy paths are still in the tree — don't assume either works:
- `.github/workflows/deploy.yaml` pushes the SPA to Dokku on a `v*` tag. Its version check reads `./package.json`, which **does not exist** at the repo root, so the job fails before deploying. Either repoint it at `frontend/package.json` or delete it.
- `deploy/fly/fly.toml` is a parked Fly.io config. It predates the compose stack and, unlike `compose.yaml`, does **not** set `RUN_RELEASE_TASKS=0` on its `worker` process group — both process groups would run `migrate` against the same SQLite file. Fix that before ever using it.

## Things to know about this user

- Prefers **bun** for the frontend (never npm/yarn/pnpm; no `package-lock.json`).
- Wants tight, factual updates, not narration of every step.
- Likes `/loop` / `/ultrareview`-style automation but does **not** want me to launch `ultrareview` myself — they trigger it.
- Prefers proper investigation over destructive shortcuts.

## Common gotchas (carried over from past sessions)

- The Vite dev server may pick a non-default port (5174/5175) if 5173 is held — read the actual port from the server log before opening the browser.
- The harness blocks long-running web servers unless the user has approved them; if a `php artisan serve` / `bun run dev` background task is denied, surface the blocker rather than retrying.
- The Heroku buildpack we deploy with detects `bun.lock` only at recent versions — `.buildpacks` pins `@master` for that reason; do not pin to a specific commit without checking.
- The Heroku buildpack will run `npm run build` if it can't detect bun, so don't rely on `bun:` prefixed scripts in `frontend/package.json`.
- **Frontend is Vue 3, not React** (rewritten in 0.5.2). Build runs `vue-tsc --noEmit --skipLibCheck && vite build`. Auto-imports + auto-component registration are handled by `unplugin-auto-import` + `unplugin-vue-components`; the generated `.d.ts` files appear after the first `vite build`/`dev` run, so a freshly cloned tree must build once before `vue-tsc` succeeds.
- Vue rewrite removed Mantine, TanStack Query, react-icons, MDX, react-three-fiber, react-router. Replacements: PrimeVue (Aura preset) + custom `useQuery` composable + plain Three.js wrapped in `useRocketScene` + vue-router + `v-html` for CMS content.
- SCSS breakpoint mixin map starts at `xs: 0`. Don't pass `xs` to `media-down(...)` — Sass rejects `calc(0 - 1px)`. Use `media-down(sm)` for "below sm".
- `MAIL_MAILER=log` is the default in `.env.example`. The temp passwords sent by the Accept-application / Create-user / Resend-temp-password actions land in `backend/storage/logs/laravel.log` (queued through the database queue worker). Switch to Postmark/Resend (env keys are scaffolded in `config/services.php`) for real email delivery.
- **Team logins are org addresses.** Members sign in with `<given>.<surname>@evrst.hu` (`App\Support\OrgEmail`), stored in both `users.email` and `team_members.email`. Delivery is decoupled: `MAIL_DELIVER_TO_ORG=false` (default) sends to `team_members.email_private`. **Decision 2026-09-08: that stays false — `@evrst.hu` is a login only, permanently.** No mailboxes, aliases or catch-all at Websupport; mail *to* an org address bounces, and nothing in the app sends there. Don't print an org address as a contact address anywhere. Flipping to `true` requires creating mailboxes first. Always resolve the recipient via `User::deliveryEmail()`. Full guide + rollout checklist: `docs/org-email-logins.md`.
- **Onboarding the roster is a bulk action.** Users table → select rows → **Send temp password** (`UsersTable::sendTempPasswords()`). `TeamSeeder` mints passwords but deliberately does *not* email them (spam on every reseed) — it writes them to `storage/app/seeded-team-passwords.txt`. The bulk action **skips** anyone whose `deliveryEmail()` resolves to an `@evrst.hu` address while `MAIL_DELIVER_TO_ORG=false`, because rotating a password we can't deliver locks the member out. Nyári György + Som Nemere have no `email_private` and are exactly that case.
- **Adding a team member provisions their login.** The Team members create page (admin only) runs `App\Support\MemberLogin::provision()` in `afterCreate()`: derives the org address from the name when the field was left blank, creates a Member-role `User` with `password_changed_at = null`, links `team_members.user_id`, and `sendNow`s the temp password to `deliveryEmail()`. Skipped when the row already has a user or `left_at` is filled; a manager (who has `team.create` but not Users access) gets a "no login created" warning instead. The same service backs the **Create login** button on the member edit page and `php artisan team:provision-login {id|email|name}` (`--all`, or no argument to list rows with no login) for the deploy box. No mail is sent when the only address on file is the `@evrst.hu` login — the account is still created and reported. Not an observer: `TeamSeeder` writes straight to the table and would mail the whole roster on every reseed.
- **Temp passwords have exactly one exit.** `App\Actions\IssueTempPassword` — `rotate($user)` for an existing account, `deliver($user, $password)` for a fresh one. It resolves the destination via `deliveryEmail()`, refuses an undeliverable address *before* rotating, rolls the old hash back if the send throws, and returns a `TempPasswordResult`; `App\Filament\Support\TempPasswordReport::flash()` / `flashBatch()` turn that into the panel notification. All five call sites (Users row action + bulk, Create login, CreateUser, accept-application) go through it — don't hand-roll a sixth.
- **Tasks: `tasks.progress` vs `tasks.edit`.** `Task::canBeProgressedBy($user)` is the per-record gate behind `TaskResource::canEdit()`, `canTransitionTo()` and the kanban: `tasks.edit` opens any task, `tasks.progress` (the Member role) opens one you are assigned to or supervise. `TaskForm::definitionLocked()` disables the definition fields for the second group, so they can attach proof and move the status without rewriting the task. Added by `2026_09_09_000001_attach_tasks_progress_permission`.
- **Calendar is read-all, write-admin.** `Calendar::canManageEvents()` plus an `abort_unless` at the head of every mutating Livewire method — the Blade only hides the affordances, and a Livewire endpoint is reachable regardless of what was rendered.
- **Items stay open on purpose.** Everyone signed in can edit the catalog and the stock table; the trade is `/admin/item-log` (`App\Filament\Pages\ItemLog`), which surfaces the `Item` / `ItemStock` slice of `activity_logs` to the same audience. The full audit feed under Membership → Activity log stays admin-only.
- **`RequirePasswordChange` runs on the `web` group too** (`bootstrap/app.php`), not just the panel's `authMiddleware`: `/livewire/update` is a plain `web` route, so the gate never saw component updates. Its Livewire allow-list is by component *name*, resolved from the classes via Livewire's `ComponentRegistry` — profile + notification components only, so an unactivated user can't drive global search from the profile page's topbar.
- **The admin theme must be routed to the backend.** `deploy/Caddyfile`'s `@backend` matcher has to include `/css/admin-theme.css` and `/vendor/*` — they're `asset()`-linked from render hooks, and without them the SPA's `try_files` answers `index.html`, so the panel renders unstyled (plain text on black) and the kanban loses SortableJS. Anything else added under `backend/public/` needs the same treatment.

- **Notification mail is localized two ways.** `User` implements `HasLocalePreference` (→ `users.locale`), so notifications render in the recipient's chosen language. Body copy is `lang/{en,hu}/admin.php` under `mail.*`. Laravel's own chrome (`Regards,`, the trouble-clicking subcopy, the entire built-in password-reset email) are **JSON** keys, so they live in `lang/hu.json` — without that file HU members get Hungarian text in English framing. A member with `locale = null` renders in `APP_LOCALE` (`en` by default) because the queue worker has no session.
- **`APP_URL` must be right in production.** The task notifications are `ShouldQueue`, so `TaskResource::getUrl()` renders in the worker with no request in scope and falls back to `config('app.url')` — get it wrong and every "Open task" link in every email points at localhost.
- Filament's login screen has **Forgot password?** enabled (`->passwordReset()`), and a completed reset stamps `password_changed_at` (listener in `AppServiceProvider`) so it also clears the `RequirePasswordChange` gate.
- The seeded `admin@evrst.test` has `password_changed_at` pre-stamped so it never trips the first-login redirect. Every other user provisioned through the admin must set a new password before reaching any admin page.
- Switch to a real mailer to actually deliver temp passwords: in `backend/.env` set `MAIL_MAILER=resend` plus `RESEND_API_KEY=...` (or `MAIL_MAILER=postmark` + `POSTMARK_API_KEY=...` — both have config stubs in `config/services.php`). Local dev keeps `MAIL_MAILER=log` so the password lands in `storage/logs/laravel.log`.
- LAN-accessible dev: backend launches with `php artisan serve --host=0.0.0.0 --port=8000`; the SPA is bound to `0.0.0.0` via `frontend/package.json`'s `"dev": "vite --host 0.0.0.0"`. `frontend/.env` `VITE_API_URL` plus `backend/.env`'s `APP_URL` + `ADMIN_URL` need updating if your LAN IP changes.
- Activity log lives in `activity_logs` and is populated by `App\Concerns\LogsActivity` (mounted on `Resource`, `Task`, `MemberApplication`, `Drawing`, `OnshapeModel`, `CalendarEvent`, `TaskProof`). Visible to Admins under Membership → Activity log; not surfaced to Manager/Member.
- The trait now exposes `logActivity($event, $changes)` for hand-shaped entries (e.g. `'accepted'` / `'rejected'`) and `withoutActivityLog($cb)` for suppressing the auto-log inside a single transaction. Used by the application accept/reject flow and by the kanban bulk reorder so dozens of position changes don't dump dozens of "updated" rows.
- Activity log retention: a daily `php artisan activity-log:prune --days=90` job is scheduled in `routes/console.php` (03:15 every night, `onOneServer + withoutOverlapping`). Anything older than 90 days is removed.
- Public images go through `/api/img?path=…&w=…&f=webp` (intervention/image v4 + GD). Variants are cached under `storage/app/public/cache/img/{prefix}/{hash}.{ext}` (gitignored by the existing public-disk gitignore) and pruned by `image-cache:prune --days=30`, scheduled at 03:30 nightly. SVG + animated GIF requests pass through to the original bytes. The SPA's `<ResponsiveImage path=…>` + `lib/imgUrl.ts` (`imgUrl()`, `pathFromStorageUrl()`) consume both `/api/img` and `/api/img/meta` (which returns intrinsic dimensions + a 24px webp LQIP data URI).
- Roles + permissions are seeded via `Perm::catalog()` but admins can rebalance them at runtime in Filament: Membership → Roles & permissions. Code-side perm checks still drive visibility — flipping perms in the UI takes effect on the next request.
- **When you add new `Perm::*` keys**: write a one-shot migration that inserts the rows into `permissions` and attaches them to `roles` (via `permission_role`), like `2026_05_03_000007_attach_models_permissions.php`. Existing seeded roles do NOT pick up new keys from `Perm::catalog()` automatically because `RoleSeeder` runs once.
- The kanban (`/admin/tasks/kanban`) disables drag-and-drop while filters are active to avoid rewriting `position` over a partial set; the page shows a banner explaining why.
- SortableJS is vendored at `backend/public/vendor/sortable.min.js` so the kanban works without internet access.
- **Discord mentions are snowflake-gated.** `App\Support\DiscordPayloads::mention()` only emits a real `<@id>` ping when `discord_id` matches `^\d{17,20}$`. Otherwise it falls back to plaintext `@discord_nick`, then `@discord_username`, then `user.name` — so the channel never shows broken `<@username>` markup. `wantsDiscordPing()` returns true when the user has any of nick / username / id.
- **Discord bot DM scaffolding exists but is dormant.** `App\Services\DiscordBot` + `App\Jobs\SendDiscordDirectMessage` no-op until `DISCORD_BOT_TOKEN` is set in `.env` (and the snowflake is populated). Channel webhook (`SendDiscordWebhook`) remains the only live outbound path; `CreateTask` / `EditTask` / `KanbanBoard` / `CommentsRelationManager` still dispatch the webhook per recipient. Once a bot is registered + snowflakes collected, swap those dispatches for `SendDiscordDirectMessage::dispatch(...)`.
- **Tests must never produce real outbound traffic.** Use `Notification::fake()` / `Mail::fake()` / `Bus::fake()` / `Http::fake()` in every feature test. `phpunit.xml` already forces `DISCORD_WEBHOOK_URL=""`, `DISCORD_BOT_TOKEN=""`, and `MAIL_MAILER=array` as a safety net, but the fakes are still required so a stray queued job doesn't try to hit the network.

## Recent admin features (kept here so future sessions can find them)

- **Drawing studio** (`/admin/drawings`, `/admin/drawings/draw`): vanilla-JS canvas (no React) with pen / line / arrow / rect / ellipse / polygon / text / bucket fill / eyedropper / eraser, image insertion (file picker + clipboard paste), 40-step undo/redo, canvas-size presets, PNG/JPG export. Saves to `storage/app/public/drawings/{ulid}.png`. Mobile breakpoint at 900 px collapses to a single column with a fixed bottom toolbar; default canvas drops to 1080×1350 portrait on first mobile mount.
- **Calendar events** (`/admin/calendar`): admin-only month grid backed by the new `calendar_events` table — separate from the public-facing CMS `Event` collection. Click a day to spawn a create modal; click an event card to edit. Tasks (by `due_date`) and AboutProjects (by `start_at`) overlay as read-only badges.
- **Onshape models** (`/admin/onshape-models`): paste an Onshape document URL, the form auto-extracts `did/wid/eid`. Re-export GLB action runs `App\Jobs\ExportOnshapeModelToGlb` synchronously (because `dispatch_sync()` — no queue worker required) which calls Onshape's REST translation API and writes the binary GLB to `storage/app/public/onshape/{ulid}.glb`. The viewer is vanilla Three.js v0.161 loaded as ES modules from unpkg via `<script type="importmap">`. **Onshape blocks third-party iframe embedding via CSP `frame-ancestors`**, so direct cad.onshape.com iframes will never work — the GLB pipeline is the answer. Requires `ONSHAPE_ACCESS_KEY` + `ONSHAPE_SECRET_KEY` from dev-portal.onshape.com → API keys.
- **Tasks: documentation/proof system**: `task_proofs` child table (kind: image | file | link | note) + `ProofsRelationManager` on the task edit page. State machine in `Task::canTransitionTo()`: assignees can move to TESTING (with proof attached), only the supervisor (or admin) can mark DONE, DONE always requires at least one proof. `Task::booted()` enforces the gate at the model level so the rule applies to every entry point. Kanban drag validates per-card before save and snaps illegal moves back.
- **EN/HU locale switcher**: topbar pill control (next to user menu). Locale persists on `users.locale` (when authenticated) and in `session('locale')` so the login form remembers a previous pick. `App\Http\Middleware\SetLocale` resolves: `?lang` → `X-Lang` header → `user->locale` → `session('locale')` → `Accept-Language`. Translation keys live in `lang/en/admin.php` + `lang/hu/admin.php` (~20 KB each, cover every resource label, table column, form field, status badge, widget heading, help-modal copy).
- **Help system**: a small `?` icon next to every page heading + on every sidebar nav item that has copy. Single global modal in `BODY_END` render hook, dispatched via `window.evrstOpenHelp(key)`. Page copy is keyed by `Route::currentRouteName()` (with the `filament.admin.` prefix stripped) and looked up against `admin.help.pages.<key>` — those keys are literal dotted strings, so we use `trans('admin.help.pages')` + array-key lookup, not `__('admin.help.pages.…')`.
- **Force-desktop view toggle**: another topbar control. Sets a `<meta name="viewport" content="width=1280">` override and persists in localStorage so mobile users can pinch-zoom the desktop layout if they need to.
- **Database inspector** (`/admin/database-inspector`): admin-only read-only schema browser using Laravel's portable `Schema::getTables/getColumns/getIndexes/getForeignKeys` introspection. Works against SQLite + MySQL + Postgres without driver-specific code.
- **Performance baseline**: dashboard widgets are lazy-loaded + Cache::remember(60s); reusable Select options (users / roles / team-member-groups / assignees) cached for 5 min with auto-invalidation in `AppServiceProvider`. The applications nav-badge COUNT is also cached. Notification polling is 2 min (was 30 s). Hot-path indexes on `tasks(status, position)`, `tasks(due_date)`, `tasks(supervisor_id)`, `member_applications(status, created_at)`, `activity_logs(subject_type, subject_id, created_at)`, `resources(collection_id, position)`. CMS `Event` and `AboutProject` start_at / end_at / event_status are promoted to indexed columns (dual-write to JSON for backwards compatibility with the SPA).
- **Sponsor logo on mobile**: `SponsorForm` accepts HEIC/HEIF in addition to standard image MIMEs, `maxSize(8192)` (8 MB), `panelLayout('integrated')`. PHP upload limits are bumped via `backend/public/.user.ini` (`upload_max_filesize=16M`, `post_max_size=20M`).
- **Bug reports** (`/admin/bug-reports`): in-panel issue tracker. Anyone signed in (members included) can file a report; managers and admins triage. Resource lives in `app/Filament/Resources/Bugs/` with an explicit `protected static ?string $slug = 'bug-reports'` so the route names stay `filament.admin.resources.bug-reports.{index,create,edit}` despite the `Bugs/` subnamespace. Topbar shortcut (`resources/views/filament/hooks/report-bug-button.blade.php`, `USER_MENU_BEFORE` renderHook) puts a heroicon bug-ant button next to the user menu so a report is one click away from anywhere. Permission split: `bugs.report` / `bugs.view` (members) and `bugs.triage` / `bugs.delete` (manager + admin); members only see their own reports via a query scope on the resource.
- **Team members + groups promoted out of the CMS**: `team_members`, `team_member_groups`, and the `team_member_team_member_group` pivot are now real relational tables with `App\Models\TeamMember`, `App\Models\TeamMemberGroup`, and `App\Models\TeamMemberAssignment` (typed `Pivot`). `App\Models\Cms\TeamMember` + `App\Models\Cms\TeamMemberGroup` are deleted; `TeamSeeder` writes directly to the new tables and `CollectionSeeder` no longer seeds the `team-members` / `team-member-groups` UUIDs. The pivot has its own `id` PK so the same role can be held across multiple time spans (`started_at` / `ended_at`); `is_primary` replaces the old `main_position_id`. Gotchas:
  * `payload.discord` is gone. Discord identity is split across **three** columns: `discord_username` (the @handle, e.g. `e.1415`, unique), `discord_id` (the **numeric snowflake** 17–20 digits, unique, currently null for everyone — to be collected over time), `discord_nick` (server-display nickname). `payload.private_email` is now the `email_private` column.
  * Group localization helper moved from `App\Filament\Resources\Cms\CollectionResource::pickLocale` to `App\Models\TeamMemberGroup::pickLocale($value, ?$lang = null)` — update any callers that still reach for the old static.
  * `member_applications.team_member_id` and `item_stocks.owner_team_member_id` are now `bigint` FKs (not UUIDs) — the `2026_05_07_000003_repoint_team_member_fks.php` migration repoints them to `team_members.id`.
  * Filament admin now lives at `App\Filament\Resources\TeamMembers\` and `…\TeamMemberGroups\` (lifted out of `Cms\` — the models stopped being CMS-backed). Both resources pin `protected static ?string $slug = 'cms/team-members'` / `'cms/team-member-groups'`, so the URLs, the route names and the route-name-keyed help-modal entries are unchanged; only the PHP namespace moved.
  * The SPA still hits `GET /api/resource?collection_id=…` for team members against the (now non-existent) CMS collections — frontend cutover to a dedicated `/api/team-members` endpoint is a follow-up.
