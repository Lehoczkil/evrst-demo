# EVRST

Monorepo for the **Escape Velocity Rocketry Student Team** site.

```
.
├── frontend/   React 19 + Vite SPA (uses bun)
└── backend/    Laravel 12 + Filament v4 admin (uses Composer + PHP 8.4)
```

## Frontend (`frontend/`)

React 19, TypeScript, Vite, Mantine v9, react-router v7, react-three-fiber.

```sh
cd frontend
bun install
bun run dev      # http://localhost:5173
bun run build    # outputs to frontend/build
```

`frontend/.env` (copy from `.env.example`) sets `VITE_API_URL`. By default
it points at the local Laravel backend at `http://localhost:8000/api`.
Language switching (EN/HU) lives in `src/i18n/`; the active locale is sent
on every API call via the `X-Lang` header (see `src/api/api.ts` +
`src/i18n/locale-sync.tsx`).

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
needed, then **Accept** (creates a Member-role User + a TeamMember row,
links them, emails the temp password) or **Reject**.

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

## Database tables

All schema lives in `backend/database/migrations/` and ships seeded.

| Table | Purpose |
| ----- | ------- |
| `users` | Filament admin accounts (now with `role_id` + `password_changed_at`). |
| `roles`, `permissions`, `permission_role` | Role-based access control. |
| `notifications` | Filament/Laravel database notification bell rows. |
| `collections`, `resources`, `object_files` | CMS content store (events / sponsors / team / mentors / projects / goals / views). |
| `member_applications` | Join-us submissions awaiting review. |
| `tasks`, `task_user`, `task_comments` | Trello-style task management. |
| `cache`, `cache_locks`, `sessions`, `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`, `personal_access_tokens` | Standard Laravel scaffolding. |

## Configuration / env

Key settings in `backend/.env.example`:

- `QUEUE_CONNECTION=database` — needed for in-app notifications + Discord
  posts. Run `php artisan queue:work` in a separate terminal during dev.
- `MAIL_MAILER=log` — temporary passwords sent on user provisioning land
  in `backend/storage/logs/laravel.log`. Switch to Postmark/Resend (env
  keys are scaffolded in `config/services.php`) for real delivery.
- `DISCORD_WEBHOOK_URL` — Discord channel webhook for new-application
  pings. Leave blank in dev to skip.
- `ADMIN_URL` — public URL of the Filament admin, used inside Discord
  webhook payloads.

## Deploy

`.github/workflows/deploy.yaml` pushes to Dokku on tag pushes that match
the frontend `package.json` version. The Dokku side is currently set up
for the SPA only; once we deploy the backend separately we'll want a
second app/buildpack pipeline for the Laravel side.
