---
name: evrst-frontend
description: Working on the EVRST React/Vite SPA in frontend/. Use whenever you edit anything under frontend/src, frontend/public, frontend/vite.config.ts, frontend/package.json, or frontend/.env.
---

# EVRST frontend

React 19 + TypeScript + Vite SPA in `frontend/`. Talks to the Laravel backend at `VITE_API_URL` (default `http://localhost:8000/api`).

## Stack

- **React 19**, **TypeScript** (`tsconfig.app.json` + `tsconfig.node.json`).
- **Vite 7** with `@vitejs/plugin-react` (Babel fast refresh). Output dir is `frontend/build/`.
- **Mantine v9** (`@mantine/core` + `@mantine/hooks` + `@mantine/modals`) for UI; theme in `src/theme.ts` (primary `#f2ac3c`, default dark, headings font Panchang-Bold, body Space Grotesk).
- **react-router v7** with `getRouter(queryClient)` factory in `src/router.tsx`. Routes are lazy via `lazy: () => import(...)`.
- **@tanstack/react-query** for server state.
- **react-three-fiber** + `@react-three/drei` + `three` — only mounted on `/` via `React.lazy(() => import('./components/rocket-scene'))`.
- **motion** for animations (the package is `motion`, import from `motion/react`).
- **@mdx-js/mdx** for the dynamic-page MDX render. **Always dynamic-import** it (`await import('@mdx-js/mdx')`) so it stays in its own chunk.
- **react-icons** (only the `lu/` Lucide pack is used). Do not reintroduce `lucide-react`.
- **react-error-boundary**, **qs is removed** (use the local `URLSearchParams` helper in `src/api/api.ts`).

## Commands

```sh
cd frontend
bun install
bun run dev     # http://localhost:5173 (falls back to 5174/5175 if busy)
bun run build   # tsc -b && vite build, output frontend/build/
bun run lint    # eslint .
bun run preview # serve frontend/build/
```

`bun run build` is the canonical "did I break it" check — it runs `tsc -b` first so type errors fail the build.

## Repo conventions

- **Imports inside `src/components/**` and `src/pages/home/home.tsx` use direct subpaths** (e.g. `@/components/header`, not the barrel `@/components`). The barrel re-exports trigger Rollup circular-chunk warnings when the home chunk and the layout chunk both touch it. Other places (placeholder, dynamic-page, etc.) can use either.
- **Cross-page CTA buttons** use `SectionButton` from `@/components/section-button` so "View more", Back, Home, "Join us" etc. all match.
- **i18n.** Strings live in `src/i18n/translations.ts` (EN + HU) keyed under namespaces like `nav.*`, `section.*`, `button.*`, `outro.*`, `team.*`, `placeholder.*`, `about.*`, `join.*`. Components consume them via `useTranslation()` from `@/i18n`. New visible strings should go in `translations.ts` (both languages).
- **Routing.** `getRouter(queryClient)` lazy-loads `Home`, `DynamicPage`, `JoinUsPage`. Static placeholder routes for `/events`, `/about`, `/team`. The dynamic-page loader receives the `QueryClient` via `args.context.queryClient` injection inside the `lazy` block — keep this pattern when adding new lazy routes that need queryClient.
- **Hash navigation.** Header links use `<Anchor component={Link} to="/#section">`. `Home` listens to `useLocation().hash` and retries `getElementById` while lazy chunks hydrate. `[id]` elements globally get `scroll-margin-top: 80px` (in `globals.css`) to clear the fixed header. When adding new section anchors, just give them an `id` and they'll inherit the margin.
- **Lazy by default.** Anything that pulls a heavy dep (3D, MDX, large vendor chunk) goes behind `React.lazy` or a route `lazy:` block. The current bundle has three.js (~186 KB gz) and MDX (~132 KB gz) split out — keep them out of the initial chunk.

## Mantine quirks

- `Stack` / `Group` `gap` does **not** accept responsive object syntax (`{ base, sm, md }`); use a single token like `'md'` or `'24px'`. Other props like `w`, `h`, `mih`, `miw`, `p`, `pt`, `pb`, `pl`, `pr`, `mb`, `mt` etc. accept the responsive object syntax.
- `Anchor` with `component={Link}` requires `to=` not `href=` — Vite has no router on initial load otherwise (full reload).
- Mantine's `Image` height: pass `h={number|string}`, not `height=` — older code used `height` and CSS-target image rules.
- `Button` `loading` + `disabled` is the right combo for in-flight submits; `SectionButton` accepts the same props (it's a prop-spread `Button` wrapper).

## React 19 quirks

- `React.FormEvent`, `React.FormEventHandler`, etc. are deprecated. Either inline-type form handlers `(event) => { event.preventDefault(); … }`, or just type the `event` parameter via inference.
- The IDE may flag deprecation warnings (`[6385]`) on those names — they still compile, but prefer the inline pattern in new code.

## Responsive design rules of thumb

- Mantine breakpoints: `xs=36em`, `sm=48em`, `md=62em`, `lg=75em`. We treat `md` as the desktop break.
- Cards become full-width per row below `xs`. Where the wrapping `motion.div` shrinks to its content, an adjacent `*.module.css` file forces `flex: 1 1 100%` on base and reverts to `auto` from `xs` (see `team-members.module.css`, `mentors.module.css`).
- Hero on mobile pins the rocket Canvas to the bottom 60 vh so it doesn't overlap the title (`home.module.css` `.canvasWrap`).
- Section titles use `clamp(28px, 6vw, 40px)` patterns — don't hardcode large fixed font sizes for primary section headers.
- Footer (`Outro` menu groups) stacks to one column below `sm` via `outro.module.css` `.menuGroups`.

## Build / chunk targets

- `vite.config.ts` already manual-chunks `three` and `@mdx-js/mdx` and sets `chunkSizeWarningLimit: 800`.
- After significant changes, run `bun run build` and check that:
  - Initial main bundle stays under ~150 KB gzip.
  - Three.js / MDX / rocket-scene chunks remain split (don't get inlined).
  - No "circular chunk" warnings — those come from importing `@/components` barrel inside another `src/components/**` file.

## TODO: team-member endpoint cutover

Team members + groups have moved out of the CMS into dedicated relational tables (`team_members`, `team_member_groups`, `team_member_team_member_group`) on the backend. The SPA still fetches them from the generic `GET /api/resource?collection_id=…` endpoint, so the team section currently breaks against a freshly-seeded backend (the old `team-members` and `team-member-groups` collection UUIDs are no longer seeded). When the backend ships a dedicated `/api/team-members` (or similar) endpoint, swap the team-section queries over and drop the hard-coded `VITE_TEAM_MEMBERS_COLLECTION_ID` / `VITE_TEAM_MEMBER_GROUPS_COLLECTION_ID` env vars. The expected response shape will be relational (`{ id, name, discordUsername, discordNick, discordId, groups: [{ id, slug, name, isPrimary }] }`) — not the wrapped `{ payload: {…} }` envelope the rest of the CMS uses. Note the Discord identity split: `discordUsername` is the @handle, `discordId` is the numeric snowflake (currently null for everyone — pending collection), and `discordNick` is the server-display nickname.

## Talking to the backend

- All requests go through `src/api/api.ts`. The helper supports a recursive bracket serializer (`where[payload][path][0]=name&where[payload][equals]=foo`) so the dynamic-page `where` clause keeps working.
- The same client supports `method: 'post'` + `body: any` (used by the Join-us form to hit `POST /api/member-applications`). It auto-serialises JSON, sets `Content-Type: application/json`, and throws on non-2xx.
- Every request sets `X-Lang` from the current i18n language (`api.setLanguage(lang)` is called by `<LocaleSync />` mounted in `main.tsx`). On language change the `LocaleSync` effect also calls `queryClient.invalidateQueries()` so all live `useQuery`s refetch in the new locale.
- Resource shape returned by the API:
  ```ts
  { id, collectionId, payload, createdAt, updatedAt, objects?: [{ id, key, url }] }
  ```
  The frontend's `Resource<T>` type and the `objects.find(o => o.key === '…')` pattern depend on this shape — don't change it unilaterally on the backend without updating the type.
- The backend recursively flattens any `{en, hu}` translation map inside `payload` to a single string for the active locale, so the frontend reads e.g. `event.payload.title` as a plain string. Names that are intentionally not translated (sponsors, mentor names, member names, the brand "Escape Velocity Rocketry Student Team") stay as plain strings in seed data.

## Don'ts

- Don't reintroduce `lucide-react`, `qs`, `@types/qs`, `leva` — they were dropped on purpose.
- Don't write `Stack gap={{base,sm}}` style; gap won't take a responsive object.
- Don't import three / drei / fiber from `home.tsx` directly — keep them inside `rocket-scene.tsx` so the lazy split holds.
- Don't add a barrel re-export for `Header`/`Footer`/`Layout` consumers in `frontend/src/components` and then import that barrel from inside `src/components/**` — Rollup will warn about circular chunks.
- Don't delete the `objects` array fallback in API resource consumers; some queries return resources without `?include=objects` and the frontend treats `objects` as optional.
