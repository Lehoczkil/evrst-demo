# Changelog

## Versioning convention

Every new deploy = a new version. Bump rules:

- **Bug fix** → patch (`0.0.1`)
- **Small feature** → minor (`0.1.0`)
- **Big update** → major (`1.0.0`)

Before each deploy, bump the version in **`backend/config/app.php`**
(the `version` key — surfaced at the bottom of the Filament sidebar via
`config('app.version')`) and in **`frontend/package.json`** (the
`version` field — already gated by `.github/workflows/deploy.yaml`),
then add a one-line entry under the matching section below.

## 0.5.1 (unreleased)

### Schema
- **Discord identity split.** Added `discord_username` column to `team_members` (the @handle, e.g. `e.1415`, unique). The existing `discord_id` column is now reserved for the **numeric Discord snowflake** (17–20 digits, unique, currently null for everyone — pending collection over time). `discord_nick` continues to hold the server-display nickname. Migration: `2026_05_10_000000_add_discord_username_to_team_members.php`.

### Added
- **Discord bot scaffolding.** New `App\Services\DiscordBot` (wraps the Discord REST API) + `App\Jobs\SendDiscordDirectMessage` (queueable, takes `(snowflake, content, embed, reference)`). Both no-op until `DISCORD_BOT_TOKEN` is set in `.env` and silently skip when the snowflake doesn't match `^\d{17,20}$`. `config/services.php` gains `discord.bot_token` + `discord.api_base`; `.env.example` documents `DISCORD_BOT_TOKEN=` and an optional `DISCORD_API_BASE` override. **No dispatch sites use the DM job yet** — channel webhook (`SendDiscordWebhook`) remains the only live outbound path; this is groundwork for the eventual swap once a bot is registered and snowflakes are collected.
- **TeamMember roster reseeded** from `névjegyzék.xlsx` — 21 members (was 18). New `csapat-menedzser` ("Team manager") group seeded **first** in the org-chart sort order; Bihari Bertalan + Klabacsek Bálint occupy it.
- **Filament TeamMember admin form** split the old single "Discord" field into `discord_username` (plain text) and `discord_id` (text input with a `regex:/^\d{17,20}$/` validator + helper noting snowflakes are required for DM bot delivery + `<@id>` mentions). The TeamMembers table also gained two toggleable-hidden columns for username + snowflake.

### Changed
- **`DiscordPayloads::mention()` is snowflake-aware.** Only emits a real `<@id>` ping when `discord_id` matches `^\d{17,20}$`; otherwise falls back to plaintext `@discord_nick`, then `@discord_username`, then `user.name` — so the channel never shows broken `<@username>` markup. `wantsDiscordPing()` returns true when the user has any of nick / username / id.

### Test infrastructure
- **`phpunit.xml` forces `DISCORD_BOT_TOKEN=""`** (alongside the existing `DISCORD_WEBHOOK_URL=""` + `MAIL_MAILER=array` scrubs) so the test suite can never accidentally hit Discord. Feature tests are expected to call `Notification::fake()` / `Mail::fake()` / `Bus::fake()` / `Http::fake()` so no outbound notification, mail, or Discord webhook ever fires from `php artisan test`.

## 0.4.0 (unreleased)

Tracked on the `feature/0.4.0` branch.

### Schema

- **Team members + groups out of the CMS.** Promoted `TeamMember` and `TeamMemberGroup` from polymorphic `resources` rows to dedicated relational tables (`team_members`, `team_member_groups`) with a typed `team_member_team_member_group` pivot (own `id` PK, `is_primary`, per-assignment `title` override, `started_at` / `ended_at`, per-group `position`). New Eloquent models `App\Models\TeamMember`, `App\Models\TeamMemberGroup`, and `App\Models\TeamMemberAssignment` (typed `Pivot`); the old `App\Models\Cms\TeamMember` + `App\Models\Cms\TeamMemberGroup` classes are deleted. `TeamSeeder` writes directly to the new tables and `CollectionSeeder` no longer seeds the legacy `team-members` / `team-member-groups` collection UUIDs.
- **Discord field reshape.** Replaced the JSON `payload.discord` string with two columns: `team_members.discord_nick` (display name) and `team_members.discord_id` (snowflake, unique).
- **`email_private` column.** The old `payload.private_email` is now a real column on `team_members`.
- **FKs repointed.** `2026_05_07_000003_repoint_team_member_fks.php` swaps `member_applications.team_member_id` and `item_stocks.owner_team_member_id` from `foreignUuid → resources` to `bigint FK → team_members.id`.

### Known follow-ups

- The SPA still fetches team members via the generic `/api/resource?collection_id=…` endpoint, which now returns nothing because the seeded CMS collections are gone. Frontend cutover to a dedicated team-members endpoint + relational response shape is pending.
- Filament admin resources for team members + groups still live under `App\Filament\Resources\Cms\TeamMembers\` so the `/admin/cms/team-members` URLs keep working — namespace cleanup deferred.

### Tooling
- **Package manager → bun.** `package-lock.json` removed, `bun.lock` committed. `README` updated to use `bun install` / `bun run build` / `bun run dev`. The Heroku Node.js buildpack on Dokku detects `bun.lock` from v300+ (we pin `@master`, so the deploy installs Bun automatically and runs `bun install` + `bun run build`).
- **Vite chunking & deps.** Dropped unused `lucide-react`. Replaced `qs` with a small `URLSearchParams` helper in `src/api/api.ts` (still supports the nested `where` query the dynamic-page loader uses). Bumped `chunkSizeWarningLimit` to 800.

### Performance
- **Route-level code splitting.** `router.tsx` uses `lazy: () => import(...)` for the home, dynamic-page, and join-us routes. Non-home routes no longer load three.js, drei, or MDX.
- **Lazy rocket Canvas.** Pulled `<Canvas>`/three/drei into `pages/home/components/rocket-scene.tsx` and `React.lazy`'d it from `home.tsx`. Three.js (~186 KB gz) and the rocket scene chunk (~80 KB gz) load after first paint.
- **Lazy MDX evaluate.** `about.tsx` now `await import('@mdx-js/mdx')` inside the queryFn so the 132 KB gz MDX chunk is fetched only after the API request resolves.
- Initial JS for non-home routes dropped from ~288 KB gz to ~140 KB gz. Home initial drops from ~288 KB gz to ~248 KB gz with three / scene streamed in afterwards.

### Features
- **`/join-us` page.** Replicates the existing Google Form with Mantine components and submits to Google Forms via `…/formResponse` (`mode: 'no-cors'`) using the entry IDs extracted from the form. Three sectioned cards (About you / Availability / Department & contribution) with pill-style radio + checkbox options, success card on submit, error fallback to the original form. Translations added in EN + HU.
- **"Join us" in header nav.** New `nav.joinUs` translation key; the header (and the mobile drawer) now show a Join us link that points to `/join-us`. The team-members CTA also routes there instead of opening Google Forms in a new tab.
- **Header on inner pages.** `Layout` no longer passes `withNavigation={false}`, so the navigation shows on every route.
- **SPA-aware hash navigation.** Header links use `<Link to="/#section">`. `Home` listens to `useLocation().hash` and scrolls the matching `id` into view (with a retry while the lazy chunks finish hydrating). `[id]` elements get `scroll-margin-top: 80px` so the fixed header doesn't cover the section anchor.

### Design / responsive
- Smaller fonts, tighter spacing and padding on mobile across `Section`, `Hero`, `Outro`, `Footer`, dynamic & placeholder pages. Hero gets `pt: 80px` on mobile to clear the rocket.
- Cards are full-width per row below the `xs` breakpoint (team-members, mentors). New `*.module.css` files override the `motion.div` flex item width on small screens.
- Rocket Canvas is pinned to the bottom 60 vh on mobile (≤ md) so it doesn't overlap the title.
- Footer (Outro menu groups) stacks to one column on mobile.
- Back / Home buttons across pages now use `SectionButton` so they match "View more". `SectionButton` lifted from `pages/home/components` to `src/components/section-button.tsx`.
- Header logo grows to 80 px (56 px on mobile) when un-scrolled with a negative `margin-bottom` so it overflows the header, and shrinks back to 36/28 px when scrolled.
- Imports inside `src/components/**` and `pages/home/home.tsx` use direct subpaths (e.g. `@/components/header`) to avoid a Rollup circular-chunk warning when the layout chunk and the home chunk both touched the barrel.

### Deploy
- `.github/workflows/deploy.yaml` is unchanged — the tag-match guard still runs and the Dokku push still triggers a buildpack rebuild. Verified that `.buildpacks` (`heroku-buildpack-nodejs` `@master`, then `heroku-buildpack-nginx`) handles a `bun.lock` lockfile.

### Files added
- `src/components/section-button.tsx`
- `src/components/back-to-top/`, `src/components/container/`
- `src/components/header/header.module.css`
- `src/i18n/` (extracted from inline strings — adds EN + HU translations and a `useTranslation` hook)
- `src/pages/home/components/{rocket-scene,reveal,diagonal-divider,hero.module.css,home.module.css,team-members.module.css,mentors.module.css}`
- `src/pages/join-us/{join-us.tsx,join-us.module.css,index.ts}`
- `src/pages/placeholder/`

### Files removed
- `package-lock.json` (replaced by `bun.lock`)
- `lucide-react`, `qs`, `@types/qs` from `package.json`

### Backend (new — under `backend/`)
The repo is now a small monorepo: the React SPA lives in `frontend/` and a fresh Laravel 12 backend was scaffolded in `backend/` to replace the spacelab CMS we were calling.

- **Stack.** Laravel 12 + Sanctum + Filament v4 + SQLite (default `backend/database/database.sqlite`).
- **Domain.** Three UUID tables: `collections` (id, name, slug, description), `resources` (id, collection_id, payload JSON, position) and `object_files` (id, resource_id, key, disk, path, mime, size). The `Collection`, `Resource` and `ObjectFile` Eloquent models use `HasUuids`; `Resource` exposes a derived `title` accessor pulled from `payload.name|payload.title`, and `ObjectFile` exposes a `url` accessor via the configured Storage disk.
- **API.** `GET /api/resource` and `GET /api/resource/{id}` (in `App\Http\Controllers\Api\ResourceController`) return the same camelCase `{ id, collectionId, payload, createdAt, updatedAt, objects? }` envelope the frontend was already consuming. `?include=objects` eager-loads the file children; `?collectionId=…` filters; the Payload-style `?where[payload][path][0]=name&where[payload][equals]=…` clause that the dynamic-page loader uses gets translated into a `payload->key = value` JSON predicate.
- **Filament admin.** Mounted at `/admin` (Amber palette, default panel). Two resources:
  - **Collections** — name + auto-generated slug + description, with a `resources_count` column.
  - **Resources** — collection select, position, payload edited as pretty-printed JSON with a JSON-validity rule. Table search runs raw `json_extract(payload, '$.name|$.title')` queries; collection filter included.
- **Seed.** `CollectionSeeder` recreates the nine collections the frontend hard-codes UUIDs for (pages, events, team-members, team-member-groups, mentors, sponsors, about-projects, about-goals, views) using the same UUIDs so existing constants keep resolving. `ResourceSeeder` populates the home page rocket placement, the about view body, three projects, three goals, four team-member groups, seven team members, two mentors and two sponsors. `DatabaseSeeder` also creates an admin user `admin@evrst.test` / `password` for Filament.
- **CORS.** `config/cors.php` ships permissive defaults for `api/*` so the Vite dev server can call `http://localhost:8000/api/*` cross-origin.

### Frontend wired to the new backend
- `frontend/.env` defaults `VITE_API_URL=http://localhost:8000/api` and now also sets the previously-empty `VITE_ABOUT_PROJECTS_COLLECTION_ID` / `VITE_ABOUT_GOALS_COLLECTION_ID` to the seeded collection IDs so the About section's projects/goals cards come from the API instead of the i18n fallback.
- Added `frontend/.env.example` documenting these variables.
