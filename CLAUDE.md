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
- Activity log lives in `activity_logs` and is populated by `App\Concerns\LogsActivity` (mounted on `Resource`, `Task`, `MemberApplication`, `Drawing`, `OnshapeModel`, `CalendarEvent`, `TaskProof`). Visible to Admins under Membership → Activity log; not surfaced to Manager/Member.
- The trait now exposes `logActivity($event, $changes)` for hand-shaped entries (e.g. `'accepted'` / `'rejected'`) and `withoutActivityLog($cb)` for suppressing the auto-log inside a single transaction. Used by the application accept/reject flow and by the kanban bulk reorder so dozens of position changes don't dump dozens of "updated" rows.
- Activity log retention: a daily `php artisan activity-log:prune --days=90` job is scheduled in `routes/console.php` (03:15 every night, `onOneServer + withoutOverlapping`). Anything older than 90 days is removed.
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
  * Filament admin still lives at `App\Filament\Resources\Cms\TeamMembers\` and `…\TeamMemberGroups\` so the `/admin/cms/team-members` URLs keep working — namespace is misleading now and can be moved out of `Cms\` later as a cleanup.
  * The SPA still hits `GET /api/resource?collection_id=…` for team members against the (now non-existent) CMS collections — frontend cutover to a dedicated `/api/team-members` endpoint is a follow-up.
