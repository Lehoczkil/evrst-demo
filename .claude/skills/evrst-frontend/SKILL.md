---
name: evrst-frontend
description: Working on the EVRST Vue 3 + Vite SPA in frontend/. Use whenever you edit anything under frontend/src, frontend/public, frontend/vite.config.ts, frontend/package.json, or frontend/.env.
---

# EVRST frontend

Vue 3.5 + TypeScript (strict) + Vite SPA in `frontend/`. Talks to the Laravel backend at `VITE_API_URL` (default `http://localhost:8000/api`).

## Stack

- **Vue 3.5** Composition API + `<script setup lang="ts">`. TypeScript strict.
- **Vite 7** with `@vitejs/plugin-vue` + `vite-plugin-vue-devtools` + `unplugin-auto-import` + `unplugin-vue-components`. Output dir is `frontend/build/`.
- **Pinia** for shared client state (`configStore` for HTTP loading + 429 modal flag, `metaStore` for SEO/meta tags).
- **No component library.** PrimeVue and `@primevue/themes` were removed in 0.6.0 — 156 KB gz, and Aura's token cascade had to be fought at every step to reach the design. Their replacements are local: `components/Form/*` for the inputs, `components/Form/ToastHost.vue` + `composables/useToasts.ts` for submit feedback, `Chrome/LocaleToggle.vue` for the locale switch, and global `.btn` / `.pill` / `.input` classes in `styles/_controls.scss`.
- **vue-router** with `createWebHistory`. Routes lazy-loaded (`component: () => import(...)`). The `/:slug(.*)*` catch-all renders the dynamic CMS page.
- **vue-i18n v11** in non-legacy mode. Translation maps live under `src/translations/{en,hu}/index.ts`. The active locale is read from `localStorage('evrst:language')` on bootstrap and persisted there on change via `useLocale()`.
- **No form library.** `vue-formify` went with PrimeVue: the join-us page holds its own `answers` ref (a `Record<string, string | string[]>` keyed by field key) because the question set comes from the API, and that is three lines of Vue.
- **motion-v** for animations (Vue port of motion/Framer-motion).
- **No 3D.** Three.js was removed with the hero redesign. The hero is seven CSS/SVG parallax layers driven by `animation-timeline: scroll()` — see `components/Hero/`.
- **date-fns** for the events section's date formatting (HU/EN month names, ranges, the past/upcoming split).
- **UnoCSS** (presetWind3) for utility classes; theme colours all point at CSS custom properties so utilities and SCSS cannot drift. SCSS for component-scoped styles. Tokens in `src/styles/_tokens.scss` (NOT `_variables.scss`, which is gone), plus `_base.scss`, `_typography.scss`, `_controls.scss`, `_prose.scss` and the `_breakpoints.scss` mixins.
- **Sentry** initialised only when `VITE_ENV !== 'develop'` and `VITE_SENTRY_DSN` is set. Filters out common network noise.
- **@vueuse/core** is installed; `maska` was removed (zero call sites).

## Commands

```sh
cd frontend
bun install
bun run dev     # http://localhost:5173 (falls back to 5174/5175)
bun run build   # vue-tsc --noEmit --skipLibCheck && vite build, output frontend/build/
bun run lint    # eslint + vue-tsc
bun run lint:fix
bun run preview # serve frontend/build/
bun run test    # playwright (configured but no specs yet)
```

`bun run build` is the canonical "did I break it" check — it runs type-checking before building.

## Bootstrap (`src/main.ts`)

Plugin order: `router` → `pinia` → `i18n` → `PrimeVue` → `ToastService` → `MotionPlugin`. Sentry is initialised after the plugin chain only outside `develop`. Mount target is `#vue` in `index.html`.

## Routing (`src/router.ts` + `src/routes.ts`)

- `/` → `HomePage.vue` (Hero + Manifesto + Rocket + Programme + Events + Team + Sponsors + Marquee + JoinCta)
- `/csatlakozz` **and** `/join-us` → `JoinUsPage.vue` (vue-formify + PrimeVue form, **rendered from the API**: `MemberApplicationRequests.form(lang)` returns the sections and questions, and the page builds the inputs from them — the questions are edited in the admin panel, not in this file)
- `/:slug(.*)*` → `DynamicPage.vue` (catch-all for CMS pages; renders `payload.content` as `v-html`; falls back to a "Coming soon" placeholder when no match)

**Localized paths**: `src/translations/{hu,en}/routes.ts` map route NAMES to paths, and `routes.ts` registers one record per locale per name (`pathsFor()`). `composables/useLanguage.ts` owns the switch: `switchTo(lang)` sets the locale AND `router.replace`s to the other locale's spelling of the same route. `localeFromPath()` runs FIRST in `main.ts`'s resolution chain — path → stored → `navigator.language` → `hu` — because a shared `/csatlakozz` link was sent by someone who chose the language.

`scrollBehavior` resolves `to.hash` by waiting one tick then scrolling with an 80 px header offset; saved positions take priority on back/forward.

## Component layout

Every Vue SFC follows the section-comment convention:

```vue
<script lang="ts" setup>
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>
<template>…</template>
<style lang="scss" scoped>…</style>
```

**Keep all six landmarks in every SFC, always — never trim a block because
it's empty.** When scaffolding a new component, paste all six in the order
above before adding any code. When editing an existing SFC, never delete a
landmark even if you're removing the last item under it; leave the empty
block in place. Order is fixed: `PROPS & EMITS` → `VARIABLES` → `METHODS`
→ `COMPUTED` → `WATCHERS` → `HOOKS`. Trimmed or out-of-order blocks have
been flagged as a regression in the past — match the convention exactly.

## Auto-imports

`unplugin-auto-import` makes the following globally available — no `import` line needed:

- All of `vue` (`ref`, `computed`, `watch`, `onMounted`, `onBeforeUnmount`, etc.)
- `vue-router` (`useRoute`, `useRouter`, `RouterLink`)
- `vue-i18n` (`useI18n`)
- `pinia` (`defineStore`, `storeToRefs`)
- `vue-formify` (`useForm`, `useInput`)
- Anything exported under `src/composables/**`, `src/store/**/**`, `src/helpers/**`, `src/services/**/**` (this is how `useQuery`, `useLocale`, `useConfigStore`, `useMetaStore` etc. are reachable from any SFC without importing).

`unplugin-vue-components` auto-registers everything under `src/components/**/**`. This is why `<Header>`, `<Footer>`, `<Hero>`, `<About>`, `<Team>`, `<Sponsors>`, `<Mentors>`, `<Events>`, `<Outro>`, `<Section>`, `<SectionButton>`, `<Meta>`, `<HtmlTitle>`, `<EvrstLogo>`, `<RocketScene>`, `<BackToTop>`, `<ResponsiveImage>` work in templates with no `import` statement.

The auto-imports.d.ts and components.d.ts files are generated on first `vite build` (or `vite dev`) — they're git-ignored. After cloning, run `bun run build` once before `vue-tsc` will pass.

## Data fetching — `useQuery`

`src/composables/useQuery/useQuery.ts` is a tiny react-query-style composable with FRESH / STALE / REVALIDATE caching:

```ts
const { data, status, isLoading, fetch } = useQuery<TeamMember[]>({
  key: ['team-members', locale],
  request: () => TeamRequests.members(locale.value as string),
  cache: true,
  staleTime: 60,
  refetchTime: 600,
});
```

- `key` is a string or reactive array. When any reactive value in the array changes, `useQuery` refetches automatically.
- `enabled` defers execution (use a `computed` ref for dependent queries).
- `cache: true` keeps the response in `cacheStorage` after unmount; `staleTime` and `refetchTime` drive the FRESH/STALE/REVALIDATE state machine.
- `useCacheApi: true` additionally persists via the browser Cache API.

### HTTP layer (`src/lib/http/`)

- `FetchWrapper` is the custom fetch client with `get/post/put/delete`, axios-style `interceptors.request.use(...)` + `interceptors.response.use(...)` pipeline.
- `Api.ts` instantiates one `api` configured with `baseUrl: VITE_API_URL`, `Content-Type: application/json`, and these interceptors:
  - `ApiRequestInterceptor` — flips `configStore.loading = true`, increments `requestCount`.
  - `LanguageRequestInterceptor` — reads `localStorage('evrst:language')` and sets `X-Lang` on every request.
  - `ApiResponseInterceptor` — flips `loading` back off.
  - `ApiRequestError` / `ApiResponseError` — deduplicates errors, watches for 429 (sets `tooManyRequest = true`).

### Service layer (`src/services/requests/`)

One file per logical domain:

- `HomeRequests.page()` — single CMS resource (`/resource/{id}?include=objects`) for the rocket GLB scale + position metadata.
- `TeamRequests.members(lang)` / `TeamRequests.groups(lang)` — relational `/team/*` endpoints.
- `CmsRequests.{sponsors,mentors,events,aboutProjects,aboutGoals}` — collection fetches keyed off env IDs.
- `PageRequests.bySlug(slug)` — `/resource?collectionId=…&where[payload][path][0]=name&where[payload][equals]={slug}`.
- `MemberApplicationRequests.form(lang)` — GET `/application-form`, the question set for the join-us page. Types in `src/types/applicationForm.ts`.
- `MemberApplicationRequests.submit(payload)` — POST to `/member-applications`. The payload is `Record<string, string | string[]>` keyed by field key; empty answers are dropped before sending, since the API validates against the same questions.

When you add a new endpoint, put it in a new file under `services/requests/` (auto-import will pick it up — no barrel needed).

## i18n + locale switcher

- Strings live under `src/translations/{en,hu}/index.ts`. Nested objects map naturally to `t('section.about')` lookups.
- The active locale resolves as **path → `localStorage('evrst:language')` → `navigator.language` → `hu`** (default HU, not EN), and is switched via the nav pill's `<LocaleToggle>` calling `useLanguage().switchTo()`.
- **Never put a literal `@` in a message value** — it is vue-i18n's linked-message sigil and throws at compile time. The team email is a constant in `src/lib/site.ts`.
- `useLocale().setLocale(next)` updates vue-i18n + writes to localStorage + sets `document.documentElement.lang` + dispatches a `locale-changed` window event.
- The `LanguageRequestInterceptor` re-reads `localStorage` on every API call, so a locale change automatically affects subsequent fetches. To force already-displayed `useQuery`-driven sections to refetch on locale change, include `locale` in the `key` array (Team does this).

## Image handling

`<ResponsiveImage>` has two modes:

- **API mode** — pass `path="team-members/abc.png"` (the bare disk path under the public disk). The component renders a `<picture>` with avif/webp/jpg `<source>`s, 1x/2x/3x DPR srcsets pointing at `/api/img`, and (when `lqip` is true, the default) a blurred LQIP `background-image` fetched from `/api/img/meta` on mount and cleared on `@load`. `width`/`height` set the CSS-px target (the component multiplies by DPR for srcset candidates) and seed the intrinsic dimensions for CLS. Optional props: `quality` (default 82), `fit` (cover/contain/inside), `formats` (default `['avif','webp','jpg']`), `sizes`.
- **Static mode** — pass `src="…"` for build-time-optimized assets in `src/assets/img/` (none ship right now). The component falls back to a plain lazy `<img>` wrapper.

`src/lib/imgUrl.ts` exports two helpers used together:

- `imgUrl(path, opts)` — build a `/api/img?path=…&w=…&dpr=…&f=…&q=…` URL. Used by Avatar consumers (Team / Sponsors / Mentors) where the surrounding component shape is already a circle/square and a single transformed URL is enough — no `<picture>` benefit.
- `pathFromStorageUrl(value)` — strip the `/storage/...` prefix off a backend-returned URL. CMS payload values (`logo`, `photo`, `image`) are resolved to public URLs by `ResourceResource`, so callers funnel them through this helper to recover the bare disk path before feeding `imgUrl` or `<ResponsiveImage path=…>`.

Team uses `member.photo_path` directly (the backend returns both `photo_path` and `photo_url`, so no string manipulation is needed). Sponsors / Mentors strip `/storage/` from `payload.logo` / `payload.photo`. SVG values short-circuit (the helpers detect `.svg` and the backend `/api/img` passes them through untouched).

## Styling

- **UnoCSS** for layout / spacing / responsive utilities. `container` shortcut is `w-full max-w-[1440px] mx-auto px-16px`.
- Custom rules: `fs-{size}` (font-size), `lh-{size}` (line-height), `ls-{value}` (letter-spacing).
- Breakpoints: `sm=576`, `md=768`, `lg=992`, `xl=1200`, `xxl=1366`, `xxxl=1500`. Match the SCSS map in `_breakpoints.scss`.
- **SCSS** in `<style scoped lang="scss">` per component. `vite.config.ts` adds `src/styles` to `loadPaths`, so `@import 'breakpoints'` works from anywhere.
- The breakpoint mixin map starts at `xs: 0`. Don't pass `xs` to `media-down(...)` — Sass rejects `calc(0 - 1px)`. Use `media-down(sm)` to mean "below the `sm` breakpoint."

### Styling conventions

Prefer UnoCSS utility classes in `<template>` for layout, spacing, colors, typography, flex/grid, and responsive variants. Reserve `<style scoped lang="scss">` for the cases utilities cannot express cleanly:

- `:deep(...)` selectors targeting child component or `v-html` internals.
- Pseudo-elements with non-trivial `content` (e.g. `::before { content: "→" }`).
- `@keyframes` definitions.
- CSS custom properties consumed by JS (e.g. `--rocket-scale`).
- Complex pseudo-class combinations like `:hover:not(:disabled)` or attribute selectors (`[data-checked='true']`).
- Linear-gradient backgrounds with `background-clip: text`.

Keep an empty `<style lang="scss" scoped></style>` block when everything migrated — never delete the block, since the SFC convention reserves all three (`<script>`, `<template>`, `<style>`) slots. UnoCSS theme tokens are exposed as `primary`, `bgDark`, `bgGray`, `cardBg`, `cardBorder`, `cardBorderAccent`; reach for `text-[var(--color-dimmed)]` etc. when a CSS variable isn't surfaced in `theme.colors`.

## 3D rocket

`src/composables/useRocketScene.ts` owns the Three.js setup: scene + perspective camera at z=4 + ambient + directional light + GLB loader + ResizeObserver-driven canvas resize + auto-rotate (`rocket.rotation.y += 0.005`) on every frame. Cleanup is automatic via `onBeforeUnmount`. The `<RocketScene>` component is just a `<canvas>` plus the composable. HomePage fetches the home resource via `useQuery + HomeRequests.page()`, finds `objects.find(o => o.key === 'rocket')`, and passes its url + payload `data.rocket.scale` + `data.rocket.position` to RocketScene.

## Dynamic CMS page

`DynamicPage.vue` reads `route.params.slug` (treats array params as joined path), queries `PageRequests.bySlug(slug)`, then renders `payload.title` and `payload.content` (HTML via `v-html`). When the query returns successfully but no resource matches, it shows a "Coming soon" placeholder. When a hard 404 / network error happens, the component leaves the placeholder/title slot empty (status stays `FAILED`).

## Common gotchas

- The first `bun run build` after cloning generates `src/auto-imports.d.ts` and `src/components.d.ts`. Without those files vue-tsc complains about unresolved `useMetaStore`, `useConfigStore`, `useQuery`, `useI18n` etc. — re-run `bun run build` and the errors go away.
- Vue 3.5 is strict about `defineProps` defaults — `vue/require-default-prop` warns when an optional prop has no `default`. Use `withDefaults(defineProps<...>(), { ... })` for any non-required props.
- `vue-formify`'s `useForm` returns `values` as a `Ref<Partial<T>>`. Spread cautiously into typed payloads (always coerce `??` defaults).
- The Heroku buildpack still detects `bun.lock` only at recent versions — `.buildpacks` pins `@master` for that reason; do not pin to a specific commit without checking.
- `php artisan serve` proxy: dev images come from the backend `/storage/...` URL space. Vite proxies `/storage` to `VITE_DEV_BACKEND_URL` so the SPA can render them without CORS preflight noise.

## Talking to the backend

- All requests go through the `api` instance in `src/lib/http/Api.ts`.
- The bracket-serialiser inside `FetchWrapper` flattens `params` recursively, so nested filters like `where: { payload: { path: ['name'], equals: slug } }` produce `where[payload][path][0]=name&where[payload][equals]=slug` in the URL.
- POST bodies go via `api.post('/path', { body: data })` — the wrapper JSON-serialises automatically.
- Resource shape returned by the API:
  ```ts
  { id, payload, createdAt, updatedAt, objects?: [{ id, key, url }] }
  ```
  Defined in `src/types/api.ts` (`Resource<T>`, `PageResource`, `ResourceWithObjects`). Don't drop the `objects` optionality — only `?include=objects` queries return them.
- The backend recursively flattens any `{en, hu}` translation map inside `payload` to a single string for the active locale, so the frontend reads e.g. `event.payload.title` as a plain string.

## Join-us form

`JoinUsPage.vue` renders whatever `/api/application-form` returns: sections
become the numbered cards, and each field's `type` picks the input (`text` /
`email` → InputText, `textarea` → Textarea, `select` → Select, `radio` /
`checkbox` → the option pills). Adding a question is an admin-panel edit, not a
frontend change.

Two things to keep in mind when touching it:

- The schema query is keyed on `locale`, so a language switch refetches the
  translated labels. `syncValues()` therefore *merges* — it seeds missing keys
  and leaves typed-in answers alone, so switching language mid-form does not
  wipe what the applicant has written.
- The surrounding copy (tagline, intro, the "it's a plus if" list, success and
  error messages) is still i18n in `src/translations/**`. Only the questions
  come from the API.

## Don'ts

- Don't reintroduce React, Mantine, TanStack Query, react-icons, MDX, react-three-fiber or react-router — the 0.5.2 rewrite removed those. Don't reintroduce PrimeVue, `@primevue/themes`, Three.js, `vue-formify` or `maska` either — the 0.6.0 redesign removed those, each with a reason recorded in `docs/frontend-redesign.md`.
- Don't put `overflow-x: hidden` on `body`, and don't give `html`/`body` `height: 100%`: either makes body the scroll container and silently kills every scroll-driven animation on the site. `_base.scss` uses `overflow-x: clip` on `html`.
- Don't animate `top`, `left`, `width` or `height` where a transform will do, and never `filter: blur()` on a scrolling layer.
- Don't import a Vue component manually if it lives under `src/components/**` — `unplugin-vue-components` will register it automatically.
- Don't pass `xs` to the SCSS `media-down(...)` mixin (the breakpoint map has `xs: 0`).
- Don't add a barrel `index.ts` for components — auto-registration covers it.
- Don't delete the `objects?` optionality on the `Resource<T>` type; some endpoints return resources without `?include=objects`.
