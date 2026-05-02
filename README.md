# EVRST

Monorepo for the **Escape Velocity Rocketry Student Team** site.

```
.
├── frontend/   Vue 3.5 + Vite SPA (uses bun)
└── backend/    Laravel 12 + Filament v4 admin (uses Composer + PHP 8.4)
```

## Frontend (`frontend/`)

Vue 3.5 (Composition API + `<script setup lang="ts">`), TypeScript strict,
Vite 7, PrimeVue 4 (Aura preset), Pinia, vue-router, vue-i18n, vue-formify,
UnoCSS, motion-v, Three.js (vanilla). Auto-imports + auto-component
registration via `unplugin-auto-import` + `unplugin-vue-components`.

```sh
cd frontend
bun install
bun run dev      # http://localhost:5173
bun run build    # outputs to frontend/build
bun run lint     # eslint + vue-tsc
```

`frontend/.env` (copy from `.env.example`) sets `VITE_API_URL`. By default
it points at the local Laravel backend at `http://localhost:8000/api`.
Language switching (EN/HU) lives in `src/translations/{en,hu}/index.ts`;
the active locale is sent on every API call via the `X-Lang` header
(`LanguageRequestInterceptor` in `src/lib/http/interceptors/`). Persistence
is via `localStorage('evrst:language')`.

## Backend (`backend/`)

Laravel 12 with Sanctum and a Filament v4 admin panel mounted at `/admin`.
SQLite is used out of the box (`backend/database/database.sqlite`).

```sh
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve         # http://localhost:8000
php artisan queue:work    # in a second terminal — required for in-app +
                          # mail + Discord notifications (database queue)
```

The admin user from the seed: **admin@evrst.test** / **password**.

### Admin panel

Mounted at `/admin`. Sidebar groups: **Site**, **About**, **Team**,
**Tasks**, **Membership**. Each group rolls up its Filament resources:

- **Site** — Events, Sponsors.
- **About** — Content (custom singleton page), Projects, Goals.
- **Team** — Team members, Positions, Mentors.
- **Tasks** — Tasks list view + drag-and-drop **Kanban board** at
  `/admin/tasks/kanban`.
- **Membership** — Member applications (review + accept/reject), Users.

#### Feature checklist

Single-line index of every admin capability currently shipped. Most items
have a dedicated subsection further down with the full story.

Auth & roles

- Three seeded roles (Admin / Manager / Member) backed by `permissions` + `permission_role` and `User::can()`.
- Runtime role + permission editor at Membership → Roles & permissions (no code deploy needed to rebalance access).
- First-login password gate: `RequirePasswordChange` middleware redirects new accounts to `/admin/profile`.
- Filament profile page with built-in password change (`ForceChangeProfile`).

Content

- CMS resources for Site (Events, Sponsors), About (Content singleton, Projects, Goals), Team (Members, Positions, Mentors).
- `Event` + `AboutProject` promote `start_at` / `end_at` / `event_status` to indexed columns (dual-written into JSON for the SPA).
- Sponsor logo upload accepts HEIC/HEIF up to 8 MB; PHP upload limits bumped via `backend/public/.user.ini`.
- Drawing studio at `/admin/drawings` (vanilla canvas, pen / shapes / fill / text / image paste, mobile-friendly toolbar).
- Onshape models at `/admin/onshape-models` with Three.js GLB viewer + REST translation re-export pipeline.
- Calendar at `/admin/calendar` (admin-only month grid, separate from public CMS Events; tasks + projects overlay as badges).

Tasks

- Tasks list + drag-and-drop kanban at `/admin/tasks/kanban` (SortableJS vendored locally, no CDN).
- Required-field gate on create (title, description ≥10 chars, supervisor, assignee, due date) enforced server-side.
- State machine in `Task::canTransitionTo()`: assignees can advance to TESTING with proof, only supervisor/admin can mark DONE.
- Per-task documentation/proofs (`task_proofs`: image / file / link / note) gating TESTING + DONE transitions.
- Filtering on the kanban disables drag-and-drop to avoid clobbering `position` over partial sets.

Tools

- Mission-console dashboard: dark-by-default glass-surface widgets (T-countdown, status tiles) lazy-loaded with 60s caches.
- Activity log at `/admin/activity-logs` (auto-logged on hot models; supports custom events; pruned after 90 days).
- Database inspector at `/admin/database-inspector` (portable schema browser — SQLite / MySQL / Postgres).
- Bug reports at `/admin/bug-reports` with topbar shortcut + four-way permission split (`bugs.report/view/triage/delete`).
- Help system: `?` icon on every page heading + sidebar nav, plus per-field tooltips, copy in `lang/{en,hu}/admin.php`.

Notifications

- In-app database-notification bell (Filament) for application + task events; polling at 2 min.
- Discord webhook posts on new member applications and on new calendar events (`SendDiscordWebhook` job, no-op without URL).
- Email delivery for provisioned accounts (temp passwords) — `MAIL_MAILER=log` in dev, Postmark/Resend stubs in prod.

Quality of life

- EN/HU locale switcher pill in the topbar, persisted on `users.locale` + `session('locale')`.
- Force-desktop view toggle in the topbar (overrides viewport meta, persists in localStorage) for mobile pinch-zoom.
- Performance baseline: lazy widgets, 60s cache on dashboard panes, 5-min cache on shared Select options, hot-path indexes on `tasks`, `member_applications`, `activity_logs`, `resources`.
- Public-facing CMS resources opted out of the global search to keep results focused on operational data.
- `withoutActivityLog()` helper used in bulk reorders so dozens of position updates don't flood the activity log.

### Roles and permissions

Three seeded roles (`database/seeders/RoleSeeder.php`):

- **Admin** — every permission.
- **Manager** — every permission except sponsors, member applications, and
  notifications. Sponsor + Application resources are hidden from their
  sidebar entirely.
- **Member** — read-only on every visible resource; no notification bell;
  cannot accept/refuse/edit applications.

Permission keys are listed in `App\Auth\Perm` and seeded into the
`permissions` + `roles` + `permission_role` tables. `User::can('events.edit')`
walks that join.

### Login + first-password flow

Filament's built-in profile page is enabled via `->profile(ForceChangeProfile::class)`.
A user provisioned through the admin (`UserResource`, the application Accept
flow, or the "Create login" action on a TeamMember row) is created with
`password_changed_at = null`. The `RequirePasswordChange` middleware
redirects them to `/admin/profile` until they choose a new password.

### Member applications (Join us flow)

Public form at `/join-us` (`frontend/src/pages/join-us/`) POSTs to
`/api/member-applications`. The controller:

1. Stores the row in `member_applications` (status `PENDING`).
2. Sends an in-app `NewMemberApplication` notification to every user with
   the `notifications.see` permission (Admins).
3. Dispatches `App\Jobs\SendDiscordWebhook` to ping a Discord channel
   (no-op when `DISCORD_WEBHOOK_URL` is empty).

Reviewers open the application in `/admin/member-applications`, edit if
needed, then **Accept** (creates a Member-role User + a `team_members`
row, attaches it to the chosen group via the
`team_member_team_member_group` pivot with `is_primary=true`, links the
user, emails the temp password) or **Reject**.

### Tasks + kanban

`tasks` + `task_user` (assignees pivot) + `task_comments`. Each task has
status (`TODO | IN_PROGRESS | TESTING | DONE`), supervisor, due date,
multiple assignees, and threaded comments. Notifications fire on:

- New assignee → assignee gets `TaskAssigned`.
- Status change → all watchers (assignees + supervisor minus the actor)
  get `TaskStatusChanged`.
- New comment → all watchers minus the comment author get `TaskCommented`.

The kanban board (`/admin/tasks/kanban`) is a Trello-style 4-column
drag-and-drop view backed by SortableJS, with avatar chips for
supervisor + assignees.

#### Required fields + state machine

A task can no longer be created without a title, a description (≥10
characters), a supervisor, at least one assignee, and a due date —
all enforced server-side. The status workflow is a real state
machine (`Task::canTransitionTo()`):

- An assignee can move a card as far as **Testing**, but only if at
  least one **Documentation** entry has been attached.
- Only the **supervisor** (or an admin) can mark a task **Done**;
  Done always requires at least one proof.
- The kanban board validates each card before the save, and snaps
  illegal moves back to the source column with a localised toast.

#### Documentation / proofs

`task_proofs` is a child table on every task. Each entry is one of:

- **Image** — a screenshot or photo (max 8 MB, HEIC accepted).
- **File** — a 3D model (GLB / STL / STEP), PDF, or zip (max 20 MB).
- **Link** — an external URL (GitHub, Onshape, Drive, …).
- **Note** — a written summary, no attachment.

Authors / supervisors / admins can post or edit proofs; bulk delete
is admin-only. Posting a proof is the gate that lets an assignee
move the task to **Testing**, and the supervisor sees it before
ticking it off as Done.

### Drawing studio (`/admin/drawings`)

In-panel canvas with pen, line, arrow, rectangle, ellipse, n-sided
polygon, text, bucket fill, eyedropper, and eraser. Image insertion
via file picker or `⌘/Ctrl+V` paste — drag to position, corner handle
to resize, Enter to commit. 40-step undo/redo, canvas-size presets
(square / HD / story / banner / A4), PNG/JPG export, "Continue
editing" clones an existing drawing as the starting layer.

The studio is plain Blade + vanilla JS (no React, no build step).
Outputs are PNGs on the public disk; mobile breakpoint at 900 px
collapses to a single column with a fixed bottom toolbar.

### 3D models (`/admin/onshape-models`)

Onshape document pointers with cached GLB previews rendered in-panel
by Three.js. Paste any Onshape URL — the form auto-extracts the
document / workspace / element IDs. Click **Re-export GLB** and the
admin calls Onshape's REST translation API, downloads the binary
GLB to `storage/app/public/onshape/`, and the embed component renders
it via `@react-three/drei`-style orbit controls (vanilla, loaded as
ES modules from unpkg).

Onshape blocks third-party iframe embedding via CSP, so direct
cad.onshape.com iframes will never work — the GLB pipeline is the
right path. Editing happens on cad.onshape.com (one click via the
**Open in Onshape** action). Requires `ONSHAPE_ACCESS_KEY` +
`ONSHAPE_SECRET_KEY` from `dev-portal.onshape.com`.

### Calendar (`/admin/calendar`)

Admin-only month grid backed by the new `calendar_events` table —
deliberately separate from the public-facing CMS `Event` collection
so admin scheduling doesn't leak to the API. Click any day to spawn
a create modal; click an event card to edit. Tasks (by `due_date`)
and Projects (by `start_at`) overlay as read-only badges.

### Activity log

Every create / update / delete on the high-traffic models writes a
row to `activity_logs` (via the `LogsActivity` trait on `Resource`,
`Task`, `MemberApplication`, `Drawing`, `OnshapeModel`,
`CalendarEvent`, `TaskProof`). Application accept / reject also
write a dedicated event type. Admin-only, visible at
`/admin/activity-logs`. Entries older than 90 days are pruned each
night by `php artisan activity-log:prune --days=90` (scheduled in
`routes/console.php` at 03:15).

### Database inspector (`/admin/database-inspector`)

Admin-only schema browser using Laravel's portable
`Schema::getTables/getColumns/getIndexes/getForeignKeys`. Two-pane
layout: every table on the left with row counts, the selected
table's columns / indexes / foreign keys / driver / row-count badge
on the right. Same code works against SQLite, MySQL, and Postgres.

### Bug reports (`/admin/bug-reports`)

Lightweight in-panel issue tracker. Anyone signed in can file a
report (members included) — title, description, severity, page URL,
optional screenshot. Managers and admins triage: status, assignee,
admin notes, and the open-bug count surfaces as a sidebar badge. A
heroicon bug-ant shortcut lives next to the user menu so a report
is always one click away from wherever the bug was hit. Permissions
split four ways: `bugs.report` / `bugs.view` / `bugs.triage` /
`bugs.delete`.

### EN / HU language switcher

Compact pill toggle in the topbar (next to the user avatar). Stores
the choice on `users.locale` and mirrors it into `session('locale')`
so the login form remembers the previous pick. Translation keys
cover every resource label, table column, form field, status badge,
widget heading, help-modal copy, and notification body — Filament's
own panel chrome (search, pagination, "Are you sure?" confirms,
login form, table filters) is already shipped in `hu` by the vendor
packages, so flipping the locale flips everything in one go.

### Help system

A small `?` icon next to every page heading and every sidebar nav
item that has copy. Click → modal explaining the page's purpose,
the actions available, and any non-obvious gotchas. Inline `?`
icons on form fields show a hover tooltip with field-specific
guidance. Copy lives in `lang/{en,hu}/admin.php` under
`admin.help.pages.*` (page modals) and `admin.help.fields.*` (field
hints) — adding help for a new page is a one-line lang edit.

### Public REST API

| Method | Path                        | Purpose                                    |
| ------ | --------------------------- | ------------------------------------------ |
| GET    | `/api/resource`             | List records (filter by `collectionId`, `where`, `include`) |
| GET    | `/api/resource/{id}`        | Single record (with optional `?include=objects`) |
| POST   | `/api/member-applications`  | Submit a Join-us application (rate-limited 10/min) |

Records are returned in the shape
`{ id, collectionId, payload, createdAt, updatedAt, objects? }` —
unwrapped (no `{ data: … }` envelope) so the frontend can consume them
directly. The `payload` is recursively localized into the `X-Lang`
header's locale before being returned (see
`App\Http\Resources\ResourceResource`).

### Tests

```sh
cd backend
php artisan test
```

Feature suite lives under `backend/tests/Feature/`:

- **`AdminPagesTest`** — every admin page (resources, kanban, calendar, drawings, database inspector, bug reports, activity log) renders without 5xx for the seeded admin.
- **`AdminFunctionalityTest`** — exercises the activity-log trait, the task state machine + proof gating, and the kanban reorder path.
- **`PermissionsTest`** — Admin / Manager / Member visibility on each resource + action (sponsors and applications hidden from Manager, members read-only, etc.).
- **`PublicApiTest`** — public REST contract for `/api/resource`, `/api/resource/{id}`, and the rate-limited `/api/member-applications` POST.

## Database tables

All schema lives in `backend/database/migrations/` and ships seeded.

| Table | Purpose |
| ----- | ------- |
| `users` | Filament admin accounts (now with `role_id` + `password_changed_at`). |
| `roles`, `permissions`, `permission_role` | Role-based access control. |
| `notifications` | Filament/Laravel database notification bell rows. |
| `collections`, `resources`, `object_files` | CMS content store (events / sponsors / mentors / projects / goals / views). |
| `team_members`, `team_member_groups`, `team_member_team_member_group` | Dedicated relational tables for team members + their groups + a typed pivot (with `is_primary`, `title` override, `started_at` / `ended_at`, per-group `position`). Promoted out of the CMS. Discord identity is split across `discord_username` (the @handle), `discord_id` (numeric snowflake, currently null pending collection), and `discord_nick` (server-display name). |
| `member_applications` | Join-us submissions awaiting review (`team_member_id` is now a bigint FK → `team_members.id`). |
| `tasks`, `task_user`, `task_comments` | Trello-style task management. |
| `task_proofs` | Per-task documentation (image / file / link / note) — gates the TESTING / DONE transitions. |
| `activity_logs` | Auto-logged create / update / delete + custom events (accepted / rejected). |
| `drawings` | Outputs of the in-panel drawing studio (PNG on the public disk). |
| `calendar_events` | Admin-only calendar entries (separate from the public CMS Events). |
| `onshape_models` | Onshape document pointers + cached GLB metadata. |
| `cache`, `cache_locks`, `sessions`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`, `personal_access_tokens` | Standard Laravel scaffolding. |

## Configuration / env

Key settings in `backend/.env.example`:

- `QUEUE_CONNECTION=database` — needed for in-app notifications + Discord
  posts. Run `php artisan queue:work` in a separate terminal during dev.
- `MAIL_MAILER=log` — temporary passwords sent on user provisioning land
  in `backend/storage/logs/laravel.log`. Switch to Postmark/Resend (env
  keys are scaffolded in `config/services.php`) for real delivery.
- `DISCORD_WEBHOOK_URL` — Discord channel webhook for new-application
  pings, new calendar events, and per-recipient task pings. Leave blank
  in dev to skip.
- `DISCORD_BOT_TOKEN` *(optional, future use)* — bot token for
  per-person DM delivery via `App\Services\DiscordBot` +
  `App\Jobs\SendDiscordDirectMessage`. The scaffolding ships dormant —
  it no-ops until both this token is set **and** the recipient's
  `team_members.discord_id` (snowflake) is populated. The channel
  webhook above remains the only live outbound path until the swap
  happens. `DISCORD_API_BASE` overrides the REST base URL if you proxy
  it.
- `ADMIN_URL` — public URL of the Filament admin, used inside Discord
  webhook payloads.
- `ONSHAPE_ACCESS_KEY`, `ONSHAPE_SECRET_KEY` — generate at
  `dev-portal.onshape.com → API keys`. Required for the
  `/admin/onshape-models` re-export pipeline. The "Test connection"
  action on the model list verifies they work without spending a
  translation request.

## Deploy

`.github/workflows/deploy.yaml` pushes to Dokku on tag pushes that match
the frontend `package.json` version. The Dokku side is currently set up
for the SPA only; once we deploy the backend separately we'll want a
second app/buildpack pipeline for the Laravel side.
