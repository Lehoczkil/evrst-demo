---
name: evrst-backend
description: Working on the EVRST Laravel backend in backend/ — REST API, Eloquent models, migrations, seeders, and the Filament admin panel. Use whenever you edit anything under backend/.
---

# EVRST backend

Laravel 12 + Sanctum + Filament v4 + SQLite. Lives in `backend/`. Serves the public REST API at `/api/*` and the team admin panel at `/admin`.

## Stack

- **PHP 8.4**, **Laravel 12** (`laravel/laravel`).
- **SQLite** by default (`backend/database/database.sqlite`). Connection: `DB_CONNECTION=sqlite`.
- **Laravel Sanctum** for API tokens (installed via `php artisan install:api`).
- **Filament v4** (`filament/filament:^4`) — admin panel mounted at `/admin`, Amber palette, default panel.
- **Composer** for dep management. `composer.lock` is committed.
- **`QUEUE_CONNECTION=database`** — every notification + Discord post goes through the queue. Run `php artisan queue:work` alongside `serve` in dev.
- **`MAIL_MAILER=log`** by default — temp passwords land in `storage/logs/laravel.log`.

## Commands

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate            # apply schema
php artisan db:seed            # idempotent — uses updateOrCreate
php artisan migrate:fresh --seed   # nuke + re-apply (only with user OK — wipes admin user)
php artisan serve              # http://localhost:8000
php artisan queue:work --tries=3   # required for notifications + Discord webhook
php artisan tinker             # REPL
php artisan route:list         # confirm routes; helpful greps below
```

The harness may block long-running web servers (`php artisan serve`, `queue:work`) until the user explicitly approves them. If a run is blocked, surface the blocker rather than retrying.

## Database tables

| Table | Purpose | Migration |
| ----- | ------- | --------- |
| `users` | Filament accounts (`role_id`, `password_changed_at` added later) | `0001_01_01_000000_create_users_table.php` + `2026_04_28_000001_add_role_and_password_changed_at_to_users_table.php` |
| `roles`, `permissions`, `permission_role` | RBAC (one role per user, many perms per role) | `2026_04_28_000000_create_roles_and_permissions_tables.php` |
| `notifications` | Filament bell + Laravel database channel | `2026_04_26_163354_create_notifications_table.php` |
| `collections`, `resources`, `object_files` | CMS payload-JSON store (events, sponsors, team, mentors, projects, goals, views) | `2026_04_25_230659_*` |
| `member_applications` | Join-us submissions under review | `2026_04_27_000000_create_member_applications_table.php` |
| `tasks`, `task_user`, `task_comments` | Trello-style task system | `2026_04_29_000000_create_tasks_tables.php` |
| Standard Laravel | `cache`, `cache_locks`, `sessions`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`, `personal_access_tokens` | `0001_01_01_*` + `2026_04_25_230643_create_personal_access_tokens_table.php` |

UUIDs are used by `Resource`, `Collection`, `ObjectFile`, and `MemberApplication`. Tasks + Users + Roles + Permissions use auto-increment IDs.

`Resource->title` accessor returns `payload.name`/`payload.title`/`id`. `ObjectFile->url` returns `Storage::disk($this->disk)->url($this->path)` — used by the API transformer.

## REST API

Mounted under `/api/*` (`routes/api.php`).

| Verb | Path | Controller | Notes |
| ---- | ---- | ---------- | ----- |
| GET  | `/api/resource` | `Api\ResourceController@index` | filters: `collectionId`, `where[payload][path][i]`+`where[payload][equals]`, `include=objects` |
| GET  | `/api/resource/{id}` | `Api\ResourceController@show` | optional `?include=objects` |
| POST | `/api/member-applications` | `Api\MemberApplicationController@store` | rate-limited `throttle:10,1`; fans out admin notification + queues Discord webhook |

The `SetLocale` middleware (registered globally for `api`) reads the locale from `?lang=`, the `X-Lang` header, or `Accept-Language`. The active locale is then read by `App\Http\Resources\ResourceResource` to flatten any `{en, hu}` translation map inside the payload before returning.

Output goes through `App\Http\Resources\ResourceResource` (the **JsonResource**, NOT the Filament resource). It sets `public static $wrap = null;` so responses are **NOT** wrapped in `{ data: … }`. Don't reintroduce a `data` envelope without updating the frontend.

Output shape (camelCase):
```json
{ "id": "...", "collectionId": "...", "payload": { ... }, "createdAt": "...", "updatedAt": "...", "objects": [ { "id": "...", "key": "...", "url": "..." } ] }
```

## Filament admin

Sidebar nav groups (`AdminPanelProvider::navigationGroups`): `Site`, `About`, `Team`, `Tasks`, `Membership`. Two raw-data resources (`Collections`, `Resources`) are admin-only and **hidden from the sidebar** via `shouldRegisterNavigation()=false` — visit `/admin/collections` or `/admin/resources` directly when debugging.

Resources live under `app/Filament/Resources/`:
- `Cms/Events`, `Cms/Sponsors`, `Cms/AboutGoals`, `Cms/AboutProjects`, `Cms/Mentors`, `Cms/TeamMembers`, `Cms/TeamMemberGroups` — CMS content backed by the `resources` JSON-payload store (each binds to a fixed `collectionId`).
- `MemberApplications` — typed table; review/edit/accept/reject flow.
- `Tasks` — typed table; list grouped by status + custom Kanban page.
- `Users` — admin-only management of accounts and roles.
- `Collections`, `Resources` (under `app/Filament/Resources/{Collections,Resources}`) — raw-data inspectors, hidden from nav.

Custom Filament `Page`s in `app/Filament/Pages/`:
- `AboutContent` — singleton blob editor for the home About text.

Custom resource pages:
- `MemberApplications/Pages/AcceptMemberApplication` — provisions a User (Member role, temp password) + a TeamMember + emails the credentials.
- `Tasks/Pages/KanbanBoard` — Trello-style drag-and-drop board with SortableJS, persists position + status changes via Livewire `reorder()`.

## Roles + permissions

Single source of truth: `App\Auth\Perm`. Constants like `Perm::EVENTS_CREATE` are also seeded into the `permissions` table by `RoleSeeder`.

Three roles (also constants on `Perm`):
- **Admin** — every permission.
- **Manager** — every permission except sponsors, applications, notifications. Their sidebar therefore omits Sponsors + Applications entirely.
- **Member** — empty permission set; visible-but-read-only across all enabled resources; no notification bell.

`User::can('events.edit')` overrides Laravel's framework `can()` so any string with a dot is treated as a Perm key (delegates to `hasPermission()` against the role's permission collection). `User::isAdmin()` is the role-key shortcut.

Per-resource gating pattern (already wired everywhere):
```php
public static function canViewAny(): bool { return true; }   // Members can see
public static function canCreate(): bool  { return auth()->user()?->can(Perm::EVENTS_CREATE) ?? false; }
public static function canEdit($r): bool   { return auth()->user()?->can(Perm::EVENTS_EDIT) ?? false; }
public static function canDelete($r): bool { return auth()->user()?->can(Perm::EVENTS_DELETE) ?? false; }
```

For Sponsors + MemberApplications `canViewAny` is gated too — those resources are entirely hidden from Manager + Member.

## Login + first-password flow

`AdminPanelProvider` calls `->profile(ForceChangeProfile::class)` so `/admin/profile` is registered. `ForceChangeProfile` extends Filament's `EditProfile` and stamps `password_changed_at` on every successful password rotation. The `RequirePasswordChange` middleware (registered in `authMiddleware` after `Authenticate`) bounces any user with `password_changed_at = null` to `/admin/profile`. Login, logout, profile, and Livewire endpoints are allow-listed.

The seeded admin (`admin@evrst.test`) has `password_changed_at = now()` so it never trips the redirect.

## Member applications + Discord

`Api\MemberApplicationController@store`:
1. Validates and creates a `member_applications` row.
2. Sends `App\Notifications\NewMemberApplication` to `User::whereHas('role.permissions', key=notifications.see)` — i.e. Admins.
3. Dispatches `App\Jobs\SendDiscordWebhook` to ping a Discord channel using `services.discord.webhook` (env `DISCORD_WEBHOOK_URL`). The job no-ops silently when the URL is empty so dev doesn't fail.

Accept flow (`Filament/Resources/MemberApplications/Pages/AcceptMemberApplication`) creates:
1. A `User` with role=Member, random `Str::password(12)`, `password_changed_at = null`.
2. A `TeamMember` (CMS resource row) with positions chosen by the reviewer + `user_id` snapshot pointing at the new login.
3. A `TeamMemberAccountCreated` mail notification queued to the new user — includes the temp password and the Filament login URL.

`UserResource::Pages\CreateUser` does the same (auto-generates the temp password when admin leaves the field blank). `Tables\UsersTable` exposes a `Resend temp password` row action that rotates and re-mails.

## Tasks + notifications

Three notification classes, all on the `database` channel:
- `TaskAssigned` — fired in `CreateTask::afterCreate` and `EditTask::afterSave` for newly added assignees.
- `TaskStatusChanged` — fired from `EditTask::afterSave` and `KanbanBoard::reorder` whenever a task moves columns.
- `TaskCommented` — fired by the create-comment hook on `CommentsRelationManager`.

`Task::watchers($excludeUserId)` returns the dedup'd assignees + supervisor minus a given user — used to fan out everything except `TaskAssigned` (which targets only the new assignees).

The kanban Blade view at `resources/views/filament/resources/tasks/pages/kanban-board.blade.php` lazy-loads SortableJS from a CDN, calls `$wire.reorder({TODO:[…], IN_PROGRESS:[…], …})` on drop, persists position + status atomically, and only fires status notifications for cards that actually changed columns.

## Seed data

- `RoleSeeder` — every permission + 3 roles + their pivot rows. Idempotent on `key`.
- `CollectionSeeder` — 9 fixed CMS collection UUIDs (don't change, the frontend hard-codes them).
- `ResourceSeeder` — about-view + projects + goals + mentors + sponsors. Uses `Ramsey\Uuid::uuid5()` for stable IDs (note: **don't** use `Str::uuid5()` — it doesn't exist on Laravel's helper).
- `TeamSeeder` — wipes + reseeds team-member-groups + team-members from the spreadsheet, including emails (most stub `<slug>@evrst.test`; `Lehocki László` gets the real `evrstrocket@gmail.com`).
- `TaskSeeder` — four sample tasks across the kanban columns. Idempotent on title.
- `DatabaseSeeder` — `RoleSeeder → admin user → Collection/Resource/Team/Task seeders`.

Default admin: `admin@evrst.test` / `password`, role=Admin, `password_changed_at` pre-stamped.

## Conventions / quirks

- `app/Models/Resource.php` collides with PHP's reserved `resource` pseudo-type only as a name — Laravel still accepts it. Always use `App\Models\Resource as ResourceModel` (or fully-qualified) in controllers/Filament to avoid shadowing.
- `Resource` and `Collection` model classes both clash with PHP/Laravel built-ins (`Illuminate\Support\Collection`). Avoid `use Collection;` style shorthand without an alias.
- Two classes share the name `ResourceResource`: the **Filament** admin resource (`App\Filament\Resources\Resources\ResourceResource`) and the **REST** transformer (`App\Http\Resources\ResourceResource`). Disambiguate imports.
- Migrations use `foreignUuid('collection_id')->constrained('collections')->cascadeOnDelete()`. SQLite is tolerant of FK-to-not-yet-existing tables, so timestamp ordering is forgiving — but if you regenerate names, keep the implicit order: collections → resources → object_files.
- CORS config is published at `config/cors.php` — defaults are wide-open for `api/*`, intentional so the Vite dev server can hit `/api`.
- `php artisan storage:link` has been run; uploaded objects are served from `/storage/...` URLs.
- Filament's `EditProfile` is enabled — so don't add a separate password-change page.
- `databaseNotifications()` is conditional on `auth()->user()?->can(Perm::NOTIFICATIONS_SEE)`. Don't call it unconditionally or Manager + Member sessions will see an empty bell.

## Don'ts

- Don't add a `data` wrapper to API responses — the frontend reads arrays/objects directly.
- Don't change the camelCase keys on `ResourceResource::toArray` without updating the frontend's `Resource<T>` types in `frontend/src/types.ts`.
- Don't seed UUIDs at random — the frontend has hard-coded collection UUIDs that come from `CollectionSeeder::COLLECTIONS`.
- Don't drop the SQLite file via `migrate:fresh` without confirming with the user — the seeded admin user, applications, tasks, and any manually edited content will be wiped.
- Don't run `composer require` for unscoped Filament plugins without checking compat with Filament v4 (the API differs from v3).
- Don't enable `auth:sanctum` on `/api/resource` — that endpoint is public-read for the SPA. Same for `POST /api/member-applications` — the public Join-us form must reach it.
- Don't reintroduce the Advanced sidebar group — Collections + Resources are deliberately hidden via `shouldRegisterNavigation()=false`.
