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
| `collections`, `resources`, `object_files` | CMS payload-JSON store (events, sponsors, mentors, projects, goals, views — team members are no longer here) | `2026_04_25_230659_*` |
| `team_members`, `team_member_groups`, `team_member_team_member_group` | Dedicated relational tables for the team. Pivot has its own `id` PK so the same role can be held across multiple time spans (`started_at` / `ended_at`); `is_primary` replaces the old `main_position_id`. Indexes on `(left_at, position)`. Discord identity is split across `discord_username` (the @handle, unique), `discord_id` (numeric snowflake 17–20 digits, unique, nullable — pending collection), and `discord_nick` (server-display nickname). | `2026_05_07_000001_create_team_members_table.php`, `…_000002_create_team_member_groups_table.php`, `…_000003_repoint_team_member_fks.php`, `2026_05_10_000000_add_discord_username_to_team_members.php` |
| `member_applications` | Join-us submissions under review (`team_member_id` is a `bigint` FK → `team_members.id` since `2026_05_07_000003`) | `2026_04_27_000000_create_member_applications_table.php` |
| `tasks`, `task_user`, `task_comments` | Trello-style task system | `2026_04_29_000000_create_tasks_tables.php` |
| `task_proofs` | Per-task documentation (image / file / link / note) — gates the TESTING + DONE transitions | `2026_05_03_000006_create_task_proofs_table.php` |
| `activity_logs` | Auto-logged create/update/delete + custom events (accepted, rejected) | `2026_05_02_000000_create_activity_logs_table.php` |
| `drawings` | Outputs of the in-panel drawing studio (PNG on the public disk) | `2026_05_03_000000_create_drawings_table.php` |
| `calendar_events` | Admin-only calendar entries (separate from CMS Events) | `2026_05_03_000001_create_calendar_events_table.php` |
| `onshape_models` | Onshape document pointers + cached GLB metadata (`glb_*` columns) | `2026_05_03_000005_*` + `2026_05_03_000008_add_glb_columns_to_onshape_models.php` |
| Standard Laravel | `cache`, `cache_locks`, `sessions`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`, `personal_access_tokens` | `0001_01_01_*` + `2026_04_25_230643_create_personal_access_tokens_table.php` |

The `users` table also gained `locale` (5-char nullable) for the EN/HU language preference, and `resources` gained `start_at`, `end_at`, `event_status` columns (promoted out of `payload` for Calendar / dashboard query speed; the JSON copy is dual-written for SPA compatibility).

Indexes worth knowing about: `tasks(status, position)`, `tasks(due_date)`, `tasks(supervisor_id)`, `member_applications(status, created_at)`, `activity_logs(subject_type, subject_id, created_at)`, `resources(collection_id, position)`, `resources(event_status, start_at)` — all installed by `2026_05_03_000003_add_performance_indexes.php` and the column-promotion migration.

UUIDs are used by `Resource`, `Collection`, `ObjectFile`, and `MemberApplication`. Tasks + Users + Roles + Permissions use auto-increment IDs.

`Resource->title` accessor returns `payload.name`/`payload.title`/`id`. `ObjectFile->url` returns `Storage::disk($this->disk)->url($this->path)` — used by the API transformer.

## REST API

Mounted under `/api/*` (`routes/api.php`).

| Verb | Path | Controller | Notes |
| ---- | ---- | ---------- | ----- |
| GET  | `/api/resource` | `Api\ResourceController@index` | filters: `collectionId`, `where[payload][path][i]`+`where[payload][equals]`, `include=objects` |
| GET  | `/api/resource/{id}` | `Api\ResourceController@show` | optional `?include=objects` |
| GET  | `/api/team/members` | `Api\TeamController@members` | returns `photo_path` (raw disk path, for `/api/img`) + `photo_url` (resolved public URL) |
| GET  | `/api/team/groups` | `Api\TeamController@groups` | |
| GET  | `/api/img` | `Api\ImageController@transform` | on-the-fly transform; params `path`, `w`, `h`, `f`, `q`, `dpr`, `fit`; cached on disk |
| GET  | `/api/img/meta` | `Api\ImageController@meta` | returns `{width, height, lqip}` (24px webp data URI) for placeholder rendering |
| POST | `/api/member-applications` | `Api\MemberApplicationController@store` | rate-limited `throttle:10,1`; fans out admin notification + queues Discord webhook |

The `SetLocale` middleware (registered globally for `api`) reads the locale from `?lang=`, the `X-Lang` header, or `Accept-Language`. The active locale is then read by `App\Http\Resources\ResourceResource` to flatten any `{en, hu}` translation map inside the payload before returning.

Output goes through `App\Http\Resources\ResourceResource` (the **JsonResource**, NOT the Filament resource). It sets `public static $wrap = null;` so responses are **NOT** wrapped in `{ data: … }`. Don't reintroduce a `data` envelope without updating the frontend.

Output shape (camelCase):
```json
{ "id": "...", "collectionId": "...", "payload": { ... }, "createdAt": "...", "updatedAt": "...", "objects": [ { "id": "...", "key": "...", "url": "..." } ] }
```

## Image transform pipeline

`Api\ImageController` powers `/api/img` (variant generator) and `/api/img/meta` (intrinsic dimensions + LQIP). Built on `intervention/image` v4 with the GD driver (Imagick is supported by the package but not required by the runtime). Path inputs are validated against traversal (`..`, absolute paths, NUL bytes) and resolved against the public disk; SVG and animated-GIF requests short-circuit to the original bytes.

Variants are written to `storage/app/public/cache/img/{prefix}/{hash}.{ext}` where `{prefix}` is the first 2 chars of `md5(path|serialize(params))` for filesystem hygiene. Subsequent hits stream the cached file directly with `Cache-Control: public, max-age=31536000, immutable` + ETag. The whole transform is wrapped in a try/catch — any encoder failure logs and falls back to streaming the original.

Meta lookups cache their JSON to `cache/img/{prefix}/{hash}.meta.json`. The LQIP is a 24px-wide webp at quality 30, base64-encoded into a data URI; the SPA renders it as a `background-image` on the `<img>` until `@load` fires.

The cache directory is gitignored (covered by the existing `storage/app/public/.gitignore`). `php artisan image-cache:prune --days=30` walks the tree and removes anything older; it's scheduled daily at 03:30 alongside `activity-log:prune`.

When a frontend consumer asks for the cleanest path → URL conversion: the value stored on the model (`team_members.photo_path`) is already the relative disk path, so it can be returned as-is. CMS payload values (`logo`, `photo`, `image`) are resolved to public URLs by `ResourceResource`, so the SPA strips the `/storage/` prefix client-side via `pathFromStorageUrl()` before feeding `/api/img`.

## Filament admin

Sidebar nav groups (`AdminPanelProvider::navigationGroups`): `Site`, `About`, `Team`, `Tasks`, `Membership`. Two raw-data resources (`Collections`, `Resources`) are admin-only and **hidden from the sidebar** via `shouldRegisterNavigation()=false` — visit `/admin/collections` or `/admin/resources` directly when debugging.

Resources live under `app/Filament/Resources/`:
- `Cms/Events`, `Cms/Sponsors`, `Cms/AboutGoals`, `Cms/AboutProjects`, `Cms/Mentors` — CMS content backed by the `resources` JSON-payload store (each binds to a fixed `collectionId`).
- `Cms/TeamMembers`, `Cms/TeamMemberGroups` — **no longer CMS-backed.** Bound to the dedicated `team_members` / `team_member_groups` tables and `App\Models\TeamMember` / `App\Models\TeamMemberGroup` via the new pivot. The `Cms/` namespace is a leftover so `/admin/cms/team-members` URLs keep working — fair game to lift out of `Cms\` later.
- `MemberApplications` — typed table; review/edit/accept/reject flow.
- `Tasks` — typed table; list grouped by status + custom Kanban page.
- `Users`, `Roles` — admin-only management of accounts and roles. Both gated through `canViewAny()` + `canAccess()` + `shouldRegisterNavigation()` returning `isAdmin()`.
- `ActivityLogs` — admin-only audit table.
- `Drawings` — gallery of in-panel canvas drawings.
- `OnshapeModels` — Onshape document pointers with optional cached GLB previews.
- `Collections`, `Resources` (under `app/Filament/Resources/{Collections,Resources}`) — raw-data inspectors, hidden from nav.

Custom Filament `Page`s in `app/Filament/Pages/`:
- `AboutContent` — singleton blob editor for the home About text.
- `Calendar` — admin-only month grid backed by the `calendar_events` table; click a day to spawn an event modal, click a card to edit. Tasks (by `due_date`) and AboutProjects (by `start_at`) overlay as read-only badges.
- `DatabaseInspector` — `/admin/database-inspector` (Advanced group), admin-only schema browser using `Schema::getTables/getColumns/getIndexes/getForeignKeys`.

Custom resource pages:
- `MemberApplications/Pages/AcceptMemberApplication` — provisions a User (Member role, temp password) + a TeamMember + emails the credentials.
- `Tasks/Pages/KanbanBoard` — Trello-style drag-and-drop board with SortableJS, persists position + status changes via Livewire `reorder()`. Validates each card's status transition against `Task::canTransitionTo()` before saving and snaps illegal moves back.
- `Drawings/Pages/Draw` — vanilla-JS canvas studio at `/admin/drawings/draw`. Pen / line / arrow / rect / ellipse / polygon / text / bucket fill / eyedropper / eraser, image insertion (file picker + `window:paste`), 40-step undo/redo, PNG/JPG export. Mobile breakpoint at 900 px collapses to single column with a fixed bottom toolbar; defaults to 1080×1350 portrait on first mobile mount. Save POSTs a base64 PNG data URL to a Livewire `save()` action.
- `OnshapeModels/Pages/EditOnshapeModel` — header action **Re-export GLB** runs `App\Jobs\ExportOnshapeModelToGlb` synchronously (so the demo works without a queue worker), then redirects to itself so the embed Section gets a fresh schema render. **Test connection** action on the list page hits `/users/sessioninfo` for a cheap pre-flight check.

RelationManagers:
- `Tasks/RelationManagers/CommentsRelationManager` — threaded comments per task.
- `Tasks/RelationManagers/ProofsRelationManager` — `task_proofs` documentation (kind: image | file | link | note). Required to move a task to TESTING or DONE.

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
2. A `TeamMember` row (`TeamMember::create([...])`) with the new column set (`name`, `email`, `email_private`, `discord_username`, `discord_nick`, `discord_id`, etc.) + `user_id` snapshot pointing at the new login. The chosen group is attached via `$member->groups()->sync([$groupId => ['is_primary' => true, 'started_at' => now()]])` and `$member->setPrimaryGroup($groupId)` keeps the `is_primary` flag canonical. **No more virtual-attribute setters or JSON `payload` writes.**
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
- `CollectionSeeder` — 7 fixed CMS collection UUIDs (don't change, the frontend hard-codes them). The old `team-members` (`00338d38-…`) and `team-member-groups` (`a4b4cb01-…`) UUIDs were removed when team members moved to dedicated tables.
- `ResourceSeeder` — about-view + projects + goals + mentors + sponsors. Uses `Ramsey\Uuid::uuid5()` for stable IDs (note: **don't** use `Str::uuid5()` — it doesn't exist on Laravel's helper).
- `TeamSeeder` — wipes + reseeds the dedicated `team_member_groups` + `team_members` + `team_member_team_member_group` tables from `névjegyzék.xlsx` (21 members; was 18). The new `csapat-menedzser` ("Team manager") group is seeded **first** in the org-chart sort order — Bihari Bertalan + Klabacsek Bálint occupy it. Includes emails (most stub `<slug>@evrst.test`; `Lehocki László` gets the real `evrstrocket@gmail.com`). Writes `discord_username` (the @handle) directly — no more `payload.discord` JSON; `discord_id` (the snowflake) is left null pending collection.
- `TaskSeeder` — four sample tasks across the kanban columns. Idempotent on title.
- `DatabaseSeeder` — `RoleSeeder → admin user → Collection/Resource/Team/Task seeders`.

Default admin: `admin@evrst.test` / `password`, role=Admin, `password_changed_at` pre-stamped.

## Team members + groups (dedicated tables)

Promoted out of the CMS into real relational tables. **Don't reach for the old `App\Models\Cms\TeamMember` / `Cms\TeamMemberGroup` — they're deleted.** Use the new models in `App\Models`:

- `App\Models\TeamMember` — columns: `id`, `user_id` (FK→users, nullable+unique), `name`, `email` (unique), `email_private`, `discord_username` (the @handle, e.g. `e.1415`, unique), `discord_id` (numeric snowflake 17–20 digits, unique, nullable — pending collection), `discord_nick` (server-display nickname), `degree` (json `{en,hu}`), `bio` (json), `photo_path`, `joined_at`, `left_at`, `is_public`, `position`, `meta` (json escape hatch), timestamps + soft deletes. Relations: `user()`, `groups()` (BelongsToMany via the `TeamMemberAssignment` pivot, withPivot `is_primary` / `title` / `started_at` / `ended_at` / `position`), `currentGroups()` (the `whereNull('ended_at')` subset), `primaryGroup()`. `setPrimaryGroup(?int $groupId)` flips `is_primary` atomically.
- `App\Models\TeamMemberGroup` — columns: `id`, `parent_id` (self FK, nullable — for org nesting), `slug` (unique 64), `name` (json `{en,hu}`), `description` (json), `kind` (`leadership` / `department` / `squad`), `position`, `is_public`, timestamps + soft deletes. Relations: `parent()`, `children()`, `members()`, `currentMembers()`. Static helper: `TeamMemberGroup::pickLocale($value, ?$lang = null)` — replaces the old `Cms\CollectionResource::pickLocale`.
- `App\Models\TeamMemberAssignment extends Pivot` — typed pivot for `team_member_team_member_group`. Has its own `id` PK so the same role can be held across multiple time spans. Casts `is_primary => bool`, `started_at` / `ended_at => datetime`, `title => array`.

Typical usage:
```php
TeamMember::with('groups')->get();
$member->groups()->sync([$groupId => ['is_primary' => false, 'started_at' => now()]]);
$member->setPrimaryGroup($groupId);
TeamMemberGroup::pickLocale($group->name);
```

Creating a TeamMember from `AcceptMemberApplication` is now `TeamMember::create([...])` + `$member->groups()->sync([...])` + `$member->setPrimaryGroup(...)`. **No more virtual-attribute setters or `payload` writes.** `payload.discord` is gone — Discord identity is split across three columns: `discord_username` (the @handle, e.g. `e.1415`), `discord_id` (numeric snowflake 17–20 digits, currently null pending collection), and `discord_nick` (server-display nickname). `payload.private_email` is now the `email_private` column.

The Filament TeamMember admin form exposes `discord_username` as a plain text input and `discord_id` as a text input with a `regex:/^\d{17,20}$/` validator (with a helper noting snowflakes are required for DM bot delivery + `<@id>` mentions). The TeamMembers table also gained two toggleable-hidden columns for username + snowflake.

`TeamSeeder` reseeds 21 members from `névjegyzék.xlsx` (was 18). A new `csapat-menedzser` ("Team manager") group is seeded **first** in the org-chart sort order — Bihari Bertalan + Klabacsek Bálint occupy it. The seeder writes spreadsheet @handles into `discord_username` directly; `discord_id` remains null.

## Discord delivery paths

There are two outbound channels and only one is live today:

- **Channel webhook (live).** `App\Jobs\SendDiscordWebhook` posts to `services.discord.webhook` (env `DISCORD_WEBHOOK_URL`). This is the **only** outbound path currently in use — fired for new member applications, new calendar events, and per-recipient task pings (assignment / status change / new comment from `CreateTask` / `EditTask` / `KanbanBoard` / `CommentsRelationManager`). The job no-ops when the URL is empty.
- **Bot DM (scaffolded, dormant).** `App\Services\DiscordBot` wraps the Discord REST API; `isConfigured()` is false until `DISCORD_BOT_TOKEN` is set. `App\Jobs\SendDiscordDirectMessage::dispatch($snowflake, $content, $embed, $reference)` exists and is queueable, but silently no-ops when the bot isn't configured **or** the snowflake doesn't match the regex. **None of the existing dispatch sites use the DM job yet.** Once the team registers a bot and collects snowflakes into `team_members.discord_id`, swapping the per-recipient `PostDiscordWebhook::dispatch(...)` calls for `SendDiscordDirectMessage::dispatch(...)` is the entire migration.

`App\Support\DiscordPayloads::mention()` is the helper both paths use to render an `@`-mention. It only emits `<@id>` (a real ping) when `discord_id` matches `^\d{17,20}$`; otherwise it falls back to plaintext `@discord_nick`, then `@discord_username`, then `user.name` — so the channel never shows a broken `<@username>` literal. `wantsDiscordPing()` returns true when the user has any of nick / username / id.

`config/services.php` has both `discord.webhook` and `discord.bot_token` + `discord.api_base`. `.env.example` documents `DISCORD_BOT_TOKEN=` (empty default) and an optional `DISCORD_API_BASE` override. `phpunit.xml` forces `DISCORD_BOT_TOKEN=""` so tests can never accidentally hit Discord.

## Test infrastructure

The contract is **"no outbound notification, mail, or Discord traffic ever fires from `php artisan test`"**. `phpunit.xml` already scrubs `DISCORD_WEBHOOK_URL`, `DISCORD_BOT_TOKEN`, and `MAIL_MAILER` to safe values, but every feature test should still fake the relevant facades:

```php
Notification::fake();
Mail::fake();
Bus::fake();   // catches SendDiscordWebhook + SendDiscordDirectMessage dispatches
Http::fake(); // catches anything that slipped through to the Discord REST API
```

FK migration `2026_05_07_000003_repoint_team_member_fks.php` repoints `member_applications.team_member_id` and `item_stocks.owner_team_member_id` from `foreignUuid → resources` to `bigint FK → team_members.id`. Anywhere code still typed those columns as `string` / `Uuid` should be updated to `int`.

The Filament admin still lives at `App\Filament\Resources\Cms\TeamMembers\` and `…\TeamMemberGroups\` so `/admin/cms/team-members` URLs keep working — the `Cms\` namespace is leftover and can be moved out as a cleanup.

## Recent admin features (cheat sheet)

- **Drawings** (`/admin/drawings`, `/admin/drawings/draw`): vanilla-JS canvas studio (no React, no build step). Saves PNG to `storage/app/public/drawings/{ulid}.png`. Mobile breakpoint at 900 px collapses to single column with a fixed bottom toolbar.
- **Calendar** (`/admin/calendar`): admin-only month grid backed by `calendar_events`. Click-day-to-create / click-event-to-edit modal. Tasks + Projects overlay as read-only badges.
- **Onshape models** (`/admin/onshape-models`): document pointers with cached GLB previews. `App\Services\Onshape\Client` wraps the REST API; `App\Jobs\ExportOnshapeModelToGlb` (dispatchSync — runs without a queue worker) translates the document, downloads the GLB, writes it to `storage/app/public/onshape/{ulid}.glb`. Viewer is vanilla Three.js v0.161 from unpkg via `<script type="importmap">`. **Onshape blocks third-party iframe embedding via CSP** so the GLB pipeline is the only viable path. Requires `ONSHAPE_ACCESS_KEY` + `ONSHAPE_SECRET_KEY`.
- **Task proofs / state machine**: `task_proofs` child table (kind: image | file | link | note) + `ProofsRelationManager`. `Task::canTransitionTo($user, $next)` is the gate; `Task::booted()` enforces it on every save. Assignees → TESTING (with proof), supervisors / admins → DONE (proof always required). Kanban drag validates per-card and snaps illegal moves back.
- **Activity log retention**: `php artisan activity-log:prune --days=90` runs nightly at 03:15 (`routes/console.php`). The trait now exposes `logActivity($event, $changes)` for hand-shaped entries (e.g. `'accepted'` / `'rejected'`) and `withoutActivityLog($cb)` to suppress the auto-log on a single save.
- **EN/HU locale switcher**: persistent on `users.locale`, mirrored in `session('locale')` so the login form remembers the previous pick. `App\Http\Middleware\SetLocale` resolves `?lang → X-Lang → user->locale → session('locale') → Accept-Language`. Translations in `lang/{en,hu}/admin.php` (~25 KB each).
- **Help system**: `?` icons next to every page heading + every sidebar nav item that has copy. Single global modal mounted via `BODY_END` render hook, dispatched through `window.evrstOpenHelp(key)`. Page copy is keyed by route name (with `filament.admin.` stripped). The keys are **literal dotted strings**, so use `trans('admin.help.pages')` + array-key lookup — `__('admin.help.pages.…')` would treat dots as nested-array traversal and fail silently.
- **Database inspector** (`/admin/database-inspector`): admin-only schema browser using portable `Schema::getTables/getColumns/getIndexes/getForeignKeys`. Same code works on SQLite / MySQL / Postgres.
- **Performance baseline**: dashboard widgets `Cache::remember(60s)` + `isLazy = true`. Reusable Select options (users / roles / team-member-groups / assignees) cached for 5 min with auto-invalidation in `AppServiceProvider`. Notification polling lifted to 2 min. Hot-path indexes on `tasks(status, position)`, `tasks(due_date)`, `tasks(supervisor_id)`, `member_applications(status, created_at)`, `activity_logs(subject_type, subject_id, created_at)`, `resources(collection_id, position)`, `resources(event_status, start_at)`. Event JSON keys (`start_at` / `end_at` / `event_status`) promoted to indexed columns with dual-write to `payload` for SPA back-compat.

## Shared traits (`app/Concerns/`)

- **`LogsActivity`** — auto-writes `activity_logs` rows on `created` / `updated` / `deleted`. `logActivity($event, $changes)` for custom events. `$skipActivityLog = true` (or `withoutActivityLog($cb)`) to suppress the auto-write for a single save. Used by `Resource` (so every CMS subclass logs), `Task`, `MemberApplication`, `Drawing`, `OnshapeModel`, `CalendarEvent`, `TaskProof`.
- **`HasFileUrl`** — `file_url` accessor + `deleteFile()` helper for any model that stores one file on a disk. Override `fileDiskAttribute()` / `filePathAttribute()` if your column names aren't `disk` / `path` (OnshapeModel uses `glb_*`, TaskProof uses `file_*`). Existing accessor aliases (`Drawing::url`, `OnshapeModel::glb_url`) defer to `file_url` so callers don't change. Use this trait on **every new model that owns a file** instead of re-implementing the disk + try/catch dance.

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
- Don't reintroduce the Advanced sidebar group — Collections + Resources are deliberately hidden via `shouldRegisterNavigation()=false`. The Database inspector + the All-resources / Collections raw inspectors all live under that group on purpose.
- **Don't add a new `Perm::*` key without a backfill migration.** `RoleSeeder` runs once at install; existing seeded Admin / Manager rows do not pick up new keys from `Perm::catalog()` automatically. Mirror `2026_05_03_000007_attach_models_permissions.php`: insert the row into `permissions` and the pivot row into `permission_role` for every role that should have it.
- **Don't queue the Onshape export with `dispatch()`.** It must be `dispatchSync()` so the action works without `php artisan queue:work` running (the local + Render dev setups don't run a worker). The Filament action then redirects to itself so the embed Section re-renders with the new `glb_url`.
- **Don't put the Three.js `<script type="module">` inside an `@if` branch.** Livewire DOM diffs do not re-execute module scripts, so `window.evrstMountOnshapeViewer` would never get defined when the `@if` flips. The viewer Blade renders the importmap + module unconditionally for that reason.
- **Don't call `__('admin.help.pages.<dotted-key>.title')`** — the lang file uses literal dotted strings as array keys (e.g. `'resources.cms.events.index' => […]`) and Laravel's `__()` would split on the dots. Use `trans('admin.help.pages')` and array-lookup.
- **Don't write a new file-handling model from scratch.** Add `use HasFileUrl;` and (if the columns aren't named `disk` / `path`) override `fileDiskAttribute()` / `filePathAttribute()`. Filament tables hook the cleanup via `DeleteAction::make()->before(fn ($r) => $r->deleteFile())`.
