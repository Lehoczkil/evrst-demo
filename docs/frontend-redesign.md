# EVRST frontend — redesign plan

A ground-up redesign of the public SPA (`frontend/`) on the existing
Vue 3.5 + Vite stack. The stack stays; the dependency list, the design
system, the chrome and the hero are replaced.

**Verify this before any code is written.** Every numbered decision below
is a decision, not a survey — push back on the ones you disagree with and
I implement the rest in the phase order of §13.

Reference material read for this plan:

| Source | What was taken from it |
| --- | --- |
| `~/Sites/feat.agency/frontend` | `Header.vue` + `Nav.vue` + `useSurfaceMode.ts` — the floating centred wordmark, the bottom nav pill, the background-following colour inversion, localized route paths in `useLanguage.ts` |
| `~/Sites/app-mcdonalds-hu/frontend` | The motion discipline: per-feature `anims.ts` exporting `MotionProps` factories, `MotionConfig reduced-motion="user"`, geometry derived from one constants table (`GenAlpha/backdrop.ts`) |
| `~/Sites/petrolszolg/frontend` | The build pipeline: `vite-plugin-webfont-dl`, `vite-convert-images`, `vite-plugin-image-optimizer`, `generateImageType.ts` |
| `https://feat.agency/` | Chrome layout, entrance choreography, pill interactions |
| `https://fireship.dev/` | Near-black base, one loud accent, mono type as a second voice, playful copy |
| Kevin Powell, [*True parallax with CSS-only is now possible*](https://www.youtube.com/watch?v=Qj0Qx8HpNUo) | `animation-timeline: scroll()` — now the hero's **primary** path (§5.4) |
| Fireship, [*The Parallax Effect*](https://www.youtube.com/watch?v=UgIwjLg4ONk) | `perspective` + `translateZ`; rejected as the site's scroll model, its depth→speed maths kept (§5.4) |
| `backend/routes/api.php` + the Filament admin | What data actually exists (§10) |

---

## 0. Decisions that need your sign-off

These are the ones with real trade-offs. Everything else in this document
follows from them.

| # | Decision | Why | Cost if you disagree |
| --- | --- | --- | --- |
| D1 | **Drop PrimeVue + `@primevue/themes`** | Its only consumers are the join-us inputs, a Drawer, a SelectButton and a Toast — all of which the new design restyles from scratch anyway. Aura's runtime + theme engine is the single largest dependency after Three.js. | Keep it *only* for the form (`InputText`/`Textarea`/`Select`) and hand-roll the rest. Costs ~100 KB gz and a permanent fight with Aura's token cascade. |
| D2 | **Drop `three` + `@types/three` from the SPA** | The hero becomes the SVG parallax you asked for. Nothing else on the public site is 3D. The admin's Onshape GLB viewer is unaffected — it loads Three.js from an importmap inside `backend/`. | Keep it behind a lazy route chunk (`/rocket-3d`) so it never enters the initial graph. Never in `manualChunks`. |
| D3 | **No GSAP.** Port feat.agency's Header/Nav timelines to motion-v's imperative `animate()` | You asked for motion-v used the mcdonalds way. Two animation engines means two reduced-motion stories, and feat.agency itself notes GSAP can't see `MotionConfig`. Everything those two components do (`fromTo`, staggered opacity, clip-path timeline, scrollTo) is expressible in motion-v. | Add `gsap` (~70 KB gz for core + ScrollToPlugin) and port the files verbatim — faster to write, permanently two engines. |
| D4 | **Default locale flips to `hu`** | Hungarian student team, Hungarian audience. Today it hard-defaults to `en`. Resolution order becomes stored → `navigator.language` → `hu`. | Keep `en` as the default. |
| D5 | **Localized route paths** (`/join-us` ⇄ `/csatlakozz`) | feat.agency's `useLanguage` pattern; real HU URLs, shareable and indexable. | Single path set, `?lang=` only. Simpler, worse SEO. |
| D6 | **The home page keeps at least one light (paper) section** | The whole point of the ported header/nav is that they invert over the surface behind them. On an all-black page the inversion never fires and the port is dead code. | An all-dark page — then drop `useSurfaceMode` and hard-code the chrome colours. |
| D7 | **The locale switcher moves into the bottom nav pill** | The ported header is *only* a centred wordmark — there is no top-right corner left to put it in. | A separate fixed top-right pill with its own surface sampling. |

---

## 1. Aesthetic direction

> **Launch pad at 04:00.** Near-black ground, one gold signal colour taken
> straight out of the logo, hairline geometry doing the structural work,
> and exactly one inverted paper section so the page has a horizon. Type
> does the shouting; nothing else does.

Three rules that keep it "letisztult" rather than "a lot of effects":

1. **Three surfaces, not seven.** The current page has `bg-dark`,
   `bg-gray`, two `DiagonalDivider`s, a grid overlay and a global
   `NoiseFilter`. The redesign has `--ink-0` (page), `--ink-1` (one raised
   band) and `--paper` (one inverted section). Diagonal dividers are
   deleted; hairline rules and vertical rhythm replace them.
2. **One accent, reserved.** Gold means *action or live status* — buttons,
   the active nav dot, an upcoming-event badge, the rocket's exhaust. It is
   never decoration. (This is the same discipline as
   `docs/admin-redesign.md`, so the panel and the public site finally read
   as one brand.)
3. **Motion is structural.** Nothing animates to be lively. Things animate
   because they arrive, because they are being tracked, or because the page
   is being scrolled. Everything obeys `prefers-reduced-motion`.

---

## 2. Design system

### 2.1 Colour

Sampled from `public/evrst_logo.svg`, whose six paths carry four
near-identical golds (`#EFAA3B`, `#EEA93B`, `#EFAA3B`, `#F1AB3C`) and two
off-whites (`#EFEFEF`, `#F8F7F4`). The four golds collapse to one token —
they are export noise, not intent.

```scss
// src/styles/_tokens.scss
@layer tokens {
  :root {
    /* ── Ground ─────────────────────────────────────────────
       Not pure #000: a black page with a gold radial glow over it bands
       visibly on 6-bit mobile panels, and #050506 gives the gradient
       somewhere to go. */
    --ink-0:   #050506;   /* page */
    --ink-1:   #0b0c0e;   /* one raised band */
    --ink-2:   #14161a;   /* cards on ink */
    --line:    #22252b;   /* hairlines on ink */
    --line-hi: #32363e;   /* hairline, hovered */

    /* ── Signal ─────────────────────────────────────────────
       --gold-500 is the logo's own #F1AB3C. 10.3:1 against --ink-0, so
       it is a legitimate text colour on dark. It is 1.87:1 against
       --paper, so on a paper surface it is a FILL ONLY — see §2.2. */
    --gold-400: #f7c368;  /* hover, gradient top */
    --gold-500: #f1ab3c;  /* brand */
    --gold-600: #c6871f;  /* pressed, gold-on-paper */
    --gold-dim: rgb(241 171 60 / 18%);   /* glows, focus rings */

    /* ── Paper (inverted surface) ───────────────────────────*/
    --paper:      #f8f7f4;   /* the logo's own off-white */
    --paper-line: #dedcd6;

    /* ── Type ───────────────────────────────────────────────*/
    --text-hi:  #f4f4f5;
    --text-mid: #a2a6ad;
    --text-low: #6b7078;
    --text-on-paper:     var(--ink-0);
    --text-on-paper-mid: #55595f;

    /* ── Status (events, application state) ─────────────────*/
    --live:  #43d68a;
    --warn:  var(--gold-500);
    --error: #f2685c;
  }
}
```

**The contrast rule that must not be broken.** Gold is a *dark-surface
text colour* (10.3:1 on ink) and a *light-surface fill colour* (1.87:1 on
paper — unreadable as text). So on the paper section: body copy is
`--ink-0`, headings are `--ink-0`, and gold appears only as button fills,
rules and graphic marks. Never as paper-surface text.

### 2.2 Typography

The faces are already right; only the loading is wrong (§8.3). Keep all
three and give each one job:

| Role | Face | Use |
| --- | --- | --- |
| Display | `Panchang-Bold` (self-hosted, `public/fonts/`) | H1/H2, the marquee, big numbers |
| Body | `Space Grotesk` 300–700 | Everything readable |
| Data | `IBM Plex Mono` 400–600 | Section eyebrows (`01 — MISSION`), specs, countdown, dates, form labels |

Fluid scale, one clamp per step, no per-breakpoint overrides:

```scss
--fs-display: clamp(3rem,   9vw,   9.5rem);   /* hero H1, marquee */
--fs-h1:      clamp(2.25rem, 5.5vw, 4.5rem);
--fs-h2:      clamp(1.75rem, 3.5vw, 2.75rem);
--fs-h3:      clamp(1.25rem, 2vw,   1.625rem);
--fs-body-lg: clamp(1.0625rem, 1.4vw, 1.25rem);
--fs-body:    1rem;
--fs-eyebrow: 0.75rem;    /* mono, uppercase, ls 0.16em */
--fs-meta:    0.8125rem;  /* mono */
```

Display type is set tight and negative-tracked (`lh 0.92`, `ls -0.03em`);
body is `lh 1.6`, measure capped at `62ch`.

### 2.3 Space, shape, elevation

```scss
--radius:     4px;    /* matches feat.agency's rd-4px — the pill, cards, buttons */
--radius-lg:  8px;    /* media only */
--gutter:     16px;   /* < md */  --gutter-md: 24px;  --gutter-lg: 32px;
--container:  1440px;
--section-y:  clamp(96px, 12vw, 200px);
--z-nav:      100;    /* bottom pill  */
--z-header:    99;    /* wordmark     */
--z-burst:     98;    /* logo burst   */
```

No shadows on ink — they are invisible and cost a paint layer. Elevation
on dark is expressed as a hairline (`--line` → `--line-hi` on hover) plus
a 1 px `translateY(-1px)`. Shadows exist only on the paper section.

### 2.4 Motion tokens

One easing set, shared by CSS and motion-v, so hand-written transitions and
motion presets cannot drift:

```scss
--ease-out:   cubic-bezier(0.22, 1, 0.36, 1);     /* arrivals */
--ease-inout: cubic-bezier(0.65, 0, 0.35, 1);     /* moves, pill show/hide */
--ease-pop:   cubic-bezier(0.34, 1.7, 0.4, 1);    /* dots, badges */
--dur-fast:   0.18s;  --dur:  0.35s;  --dur-slow: 0.75s;
```

The motion-v mirror lives in `src/components/Motion/springs.ts` (§11.4).

### 2.5 UnoCSS theme

```ts
// uno.config.ts — theme.colors (breakpoints + fs/lh/ls rules stay as they are)
colors: {
  ink:   { 0: 'var(--ink-0)', 1: 'var(--ink-1)', 2: 'var(--ink-2)' },
  gold:  { 400: 'var(--gold-400)', 500: 'var(--gold-500)', 600: 'var(--gold-600)' },
  paper: 'var(--paper)',
  line:  { DEFAULT: 'var(--line)', hi: 'var(--line-hi)', paper: 'var(--paper-line)' },
  text:  { hi: 'var(--text-hi)', mid: 'var(--text-mid)', low: 'var(--text-low)' },
},
```

Keep the existing `fs-` / `lh-` / `ls-` custom rules and the `container`
shortcut; add `shortcuts.rule` (`h-1px w-full bg-line`) and
`shortcuts.eyebrow` (`font-mono fs-12px uppercase ls-[0.16em] text-gold-500`).

---

## 3. Page architecture

Home stays a one-pager with anchors (that is what the content shape wants
— five short collections), plus the two real routes and the CMS catch-all.

```
/                     HomePage      hero + 8 sections, anchor-navigated
/join-us  /csatlakozz JoinUsPage    the API-driven application form
/:slug(.*)*           DynamicPage   CMS pages (payload.content via v-html)
*                     NotFoundPage
```

### Home sections, in order

| Section | Component | Surface | Data source (§10) |
| --- | --- | --- | --- |
| Hero | `Hero/Hero.vue` | ink-0 | local art + next event for the countdown |
| Mission | `Home/Mission.vue` | ink-0 | `AboutGoal` cards + the `Views` about body |
| Rocket | `Home/Rocket.vue` + `RocketSpecs.vue` | ink-1 | i18n spec table + `AboutProject` |
| Programme | `Home/Projects.vue` | ink-0 | `AboutProject` (start_at/end_at) |
| Events | `Home/Events.vue` | ink-0 | `Event` split upcoming / past |
| Team | `Home/Team.vue` | **paper** | `/api/team/members` + `/api/team/groups` |
| Mentors | `Home/Mentors.vue` | paper | `Mentor` collection |
| Sponsors | `Home/Sponsors.vue` | ink-0 | `Sponsor` collection, embla ribbon |
| Marquee | `Brand/EvrstMarquee.vue` | gold | — |
| Join us | `Home/JoinCta.vue` | ink-0 | link to `/join-us` |
| Footer | `Chrome/SiteFooter.vue` | ink-0 | contact + Óbuda logo |

Team + Mentors on paper is what satisfies **D6**: the wordmark and the
pill visibly invert twice per scroll-through, which is the entire reason
for porting `useSurfaceMode`.

### Section shell

Every section is one component wrapping `<SectionShell>`, which owns the
eyebrow / heading / rule / right-slot geometry so nothing is re-declared:

```
┌ container ─────────────────────────────────────────────┐
│ 3 RAKÉTA · 1 REPÜLT              [optional right slot] │  mono eyebrow
│ Amit építünk                                           │  Panchang H2
│ ───────────────────────────────────────────────────────│  hairline rule
│                                                        │
│ (slot)                                                 │
└────────────────────────────────────────────────────────┘
```

**The eyebrow carries a count, not a number.** An earlier draft had
`01 —`, `02 —`, `03 —` here. That was decoration pretending to be
structure: the home page is not a sequence, so ordinals encode nothing a
reader needs, and they are one of the more recognisable generated-design
tells. Each eyebrow instead states something true and derived from the
data it introduces — `20 TAG · 9 CSOPORT`, `3 RAKÉTA · 1 REPÜLT`,
`KÖVETKEZŐ: 2026. MÁRCIUS`, `9 TÁMOGATÓ` — which is also the mission-console
vernacular doing real work rather than being a costume.

`SectionShell` takes `eyebrow`, `title`, `surface` (`ink-0 | ink-1 | paper`)
and `id`, and sets `data-surface` on its root. `useSurfaceMode` walks up
for the first opaque `background-color`, so a section painting its surface
via a token class is all the sampling needs — but `data-surface` is kept as
the explicit fallback marker (feat.agency uses `.dark` for the same
purpose on WebGL heroes where no background colour exists).

### 3.3 Every section's treatment, and what decides it

The rule for this whole section: **the layout device comes out of the
content's own shape, not out of a template.** A page where nine sections
are nine variations on "heading, rule, three cards" is not a design, it is
a stylesheet. So each one below names its device and what in the data
picked it.

**Manifesto** — *the substance behind the hero's statements.*
The About section's **statement** moved into the hero (§5.8): three lines
playing in the band the headline leaves behind, which is where a reader
already is when they want to know what this is. What stays here is
everything the statement does not carry — a quiet mono eyebrow, a
two-column drop to the `Views/about` paragraph, a single hairline strip of
figures (`Alapítva 2024 · 19 · 9 · 3 · 1 repült`), then the three
`AboutGoal` rows.

Keeping the pull-quote in both places was the first attempt and it made
the same claim twice, 200px apart. One statement, made once, in the place
with the most attention on it.

Air is spent below rather than above: `padding-block: clamp(52px, 5.5vw,
96px) clamp(100px, 13vw, 200px)`, and the gaps between the blocks are
larger than the blocks' own line heights. The top padding started
symmetric at up to 260px and was cut — with the statement now made in the
hero, opening this section with a grand pause put a long empty band
immediately after the sequence, which is where the "too much empty space"
report landed. The goals lost their vertical dividers in this round — they
are three facets of one statement, not three objects, so **no cards and no
rules**, just space. Each keeps a mono over-line naming its concrete
instrument (`CAD → próbapad → kilövés`, `EuRoC · Spaceport America Cup`,
`Nyílt dokumentáció`), which is what stops it being generic mission copy.
Figures are mono at ~1.15rem, deliberately *not* number tiles: they are
context for the statement, not the point of the page.

**Rocket** — *a dimensioned drawing sheet, and the starfield comes back.*
The section is `ink-0` with its own **parallaxed starfield** — the same
generated tile as the hero, on an `animation-timeline: view()` drift of
±9% as the section crosses the viewport, plus a faint gold floor glow. It
is the one section besides the hero that is set in space, and it should
look like it.

The layout is **symmetric on purpose**: three specs right-aligned in the
left column, the blueprint on the centre axis, three specs left-aligned in
the right column — the object dimensioned from both sides, the way it
would be on a real sheet. The previous single-sided arrangement (table
left, everything else right) read as thrown together because it was.
The blueprint is the hero's rocket redrawn in 1.5px `--line-hi` strokes,
no fills, a gold dashed centre line, over a 34px grid masked to a soft
radial so it fades rather than ending.
Under it, the one table on the site that joins the two halves of the
database: **subsystem → owning team group**, with the member count.
Avionika → Elektronika · 4, Fedélzeti szoftver → Szoftver · 1, and so on.
That is real relational content (`team_member_groups`), and it is what
makes the rocket section about the team rather than about a spec sheet.

**Programme** — *a timeline, because this content genuinely is a sequence.*
Atlas-1 flew, Helios is in build, Voyager is on paper: `start_at`/`end_at`
order them and the order carries information. A horizontal rail with a
gradient fill (live-green → gold) marking how far the programme has got,
year labels above it, a node per vehicle whose fill encodes state (solid
green = flown, gold with a `--gold-dim` halo = in build, hollow = design),
then the title, the description and the apogee figure. Below `lg` the rail
rotates into a left border and the nodes hang off it.
**This is the only place ordinal structure earns its keep on the site** —
which is precisely why the section eyebrows elsewhere are counts (§3.2).

**Events** — *split by tense, because the API splits them that way.*
`GET /api/resource?…&past=true` is a different query, so it gets a
different form. Upcoming (left, 1.5fr): a bordered day/month date block in
gold display type, the title, one line of description, the venue in mono.
Past (right, 1fr): a condensed **log** — one hairline row per entry, title
left, date right, both dimmed. A reader scanning for "what's next" and a
reader scanning for "what have they actually done" want different objects,
and this gives them two.

**Team** — *the roster, on the page's one inverted surface, and not a
table.* Paper. The first attempt was a 1px-gutter grid of member cells,
which read as a spreadsheet — the contrast was right and the object was
wrong. A roster is a list of people under the thing they do, so that is
what it is: **nine borderless group blocks** (`repeat(auto-fill,
minmax(216px, 1fr))` with ~50px gaps), each a mono group label with its
real count in gold, then the names as plain 16px type at `gap: 9px`. No
cells, no rules, no monogram circles. Where a member has a `degree` it
sits under the name in mono.
Photos join the names as a small round `<ResponsiveImage path>` once
`photo_path` is populated, which the layout already has room for.
**Mentors** is a sub-band of the same section, divided by a rule rather
than given a section of its own: two people do not need a heading, an
eyebrow and 200px of padding.

**Sponsors** — *the design follows the data volume.* There are two sponsors
on file. A ribbon of two logos is a ribbon with a hole in it, so the
section's real job is recruitment: a full-width gold pitch panel takes the
lead (`Egy hallgatói rakétacsapat nem építkezik magától` + what a sponsor
gets + the CTA), and the two partners sit under it as equal, quiet tiles
with their tier and year in mono. **Past ~8 sponsors this inverts** into
the embla auto-scroll ribbon of §8.2 with the pitch demoted to the head
slot — one `v-if` on `sponsors.length`, specified now so it is not a
redesign later.

**Marquee** — the only loud element on the page: a gold band, ink display
type, `translateX(-50%)` on a doubled track. It is the transition into the
CTA, and it is the one place the accent covers a whole surface. It is also
a live test of the chrome: the pill crosses it and must flip, which is why
`useSurfaceMode` classes brand gold as a *dark* surface.

**Join us** — *two things an applicant needs.* Left: the "you don't have to
be an engineer" argument plus a **where-we-need-people** table. Right: the
form itself, in place. See §3.4.

**Footer** — four columns (identity + address, Site, Team, Contact), a
single top hairline, links in `--text-mid` going gold on hover, and 130px
of bottom padding so the fixed pill never sits on the last row. The pill
hides here anyway (it detects the footer), but the padding is what makes
that graceful rather than necessary.

### 3.4 `/join-us` — the form page

The one page on the site that is a **tool, not a document**, so the craft
shifts from typography to input design.

- **The page never hard-codes a field.** Every question comes from
  `GET /api/application-form`; the page renders sections as cards and each
  field's `type` picks the control. `name` and `email` are `is_system`, so
  they always render and always first — their help text says why
  (`is_system — átnevezhető, de nem törölhető: ebből készül a belépés`).
- **A progress rail**, not a wizard: sections stay on one page (the schema
  is short and an applicant should see the whole ask), and a thin
  three-segment rail at the top fills as sections are completed. No
  multi-step navigation to lose answers in.
- **Controls** (§11.5): mono uppercase label with a gold `*` for required,
  `--ink-0` input on the `--ink-1` card, `--line` border going `--gold-500`
  with a 3px `--gold-dim` ring on focus, help text in mono under the field,
  error text in `--error` with `role="alert"` and the field's
  `aria-describedby` pointed at it.
- **`radio` / `checkbox` render as option pills**, not as radios: the
  seeded questions are short single-choice sets, and a pill row reads and
  taps better than a stack of dots. Multi-select keeps the same pills with
  `aria-pressed` on each.
- **Submit** is full-width on the card, and the response is a `ToastHost`
  message plus an in-page success state that replaces the form — never a
  redirect, because a throttled 429 (`throttle:10,1`) has to be
  recoverable without retyping.
- **Language switch mid-form must not lose answers.** The schema query is
  keyed on `locale`, so a switch refetches translated labels;
  `syncValues()` *merges* — seeds missing keys, leaves typed answers alone.
  This already works today and must survive the rewrite.

### 3.5 `/:slug` — the CMS prose scale

`DynamicPage.vue` renders `payload.content` through `v-html`. That means
**every CMS page looks unstyled unless the prose scale is specified**, and
it currently is not. This is the spec:

```scss
.prose {
  max-width: 66ch;

  h3 { font-size: clamp(1.3rem, 2.1vw, 1.7rem); margin: 34px 0 12px; }
  h3:first-child { margin-top: 0; }
  p  { color: var(--text-mid); margin-bottom: 16px; }

  /* Bullets are a gold 8px rule, not a disc: the page has no other round
     marks on it, and a disc list is the one place a CMS page reverts to
     looking like a default stylesheet. */
  ul { list-style: none; padding-left: 0; }
  li { position: relative; padding-left: 22px; }
  li::before { content: ''; position: absolute; left: 0; top: .62em;
               width: 8px; height: 1px; background: var(--gold-500); }

  blockquote { padding-left: 20px; border-left: 2px solid var(--gold-500);
               color: var(--text-hi); font-size: 1.08em; }
  code { font-family: var(--font-mono); font-size: .88em;
         background: var(--ink-2); border: 1px solid var(--line);
         border-radius: 3px; padding: 2px 6px; }
  a { color: var(--gold-500); border-bottom: 1px solid var(--gold-600);
      text-decoration: none; }
  img { border-radius: var(--radius); }
  table { /* wrapped in an overflow-x:auto container — CMS tables are
             authored at whatever width the author felt like */ }
}
```

Styled with `:deep()` from the page component, since the markup arrives
from `v-html` and scoped attributes never reach it.

### 3.6 `/404` and the catch-all's miss state

`DynamicPage` today shows a "Coming soon" placeholder when the query
succeeds but nothing matches — which is wrong for a mistyped URL and right
for a page the team has not written yet, and it cannot tell them apart.
The redesign splits them:

- **No matching resource** → the 404 treatment: the hero's sky gradient at
  40dvh, `404` in display type, `Ez az oldal nincs meg` and two ghost
  buttons (home, join). No parallax — a 404 does not deserve seven layers.
- **Query failed** (network, 5xx) → an inline error block with a retry
  button, not a 404: telling someone their URL is wrong when the server is
  down is a lie.

### 3.7 Empty, loading and error states

The current site has none of these specified, and with a CMS behind it that
is the difference between "unfinished" and "finished but empty".

| State | Treatment |
| --- | --- |
| **Loading** (`useQuery` `isLoading`, first fetch) | Hairline skeletons at the real row geometry — a roster skeleton is 19 grid cells, not a spinner — so nothing reflows when data lands. Never a full-page loader: the hero and all static copy are already painted. |
| **Empty collection** (Events with no rows) | The section **renders its heading and a one-line statement** (`Most nincs meghirdetett esemény — a naplóban látod, mi volt`), never an empty grid and never nothing. A section that vanishes makes the page look broken; a section that says why looks maintained. |
| **Empty and unrecoverable** (no future event) | The hero countdown removes itself entirely. Some blocks are better absent than explained — a `T− —:—:—` is worse than no clock. |
| **Missing image** (`photo_path`/`logo` null) | The monogram circle / the name as display type. Specified as a *design*, not a fallback, so it does not read as a hole. |
| **Failed fetch** | An inline hairline block with the section heading intact and a retry button. The rest of the page keeps working; one dead collection must not take the page with it. |
| **429** (`configStore.tooManyRequest`) | The existing modal flag, restyled to the new tokens. |

### 3.8 Component inventory

The full list the phases in §13 build, so nothing is discovered late:

| Group | Components |
| --- | --- |
| Chrome | `SiteHeader` · `SiteNav` · `LocaleToggle` · `SiteFooter` · `BackToTop` (§5.8) |
| Brand | `EvrstLogo` (inline SVG, two bound fills) · `ObudaLogo` · `EvrstMarquee` |
| Hero | `Hero` · `HeroParallax` · `HeroSays` (§5.9) · `RocketSvg` · `RocketGlyph` · `Starfield` · `Countdown` |
| Frame | `SectionShell` · `Rule` · `StatRail` · `Pill` (status) · `MonoLabel` |
| Home | `Manifesto` · `FigureStrip` · `Rocket` · `RocketSheet` · `RocketBlueprint` · `SubsystemTable` · `Programme` (timeline) · `Events` · `EventCard` · `EventLog` · `Team` · `GroupBlock` · `Mentors` · `Sponsors` · `SponsorPitch` · `JoinCta` · `NeedsTable` |
| Form | `FormField` · `TextInput` · `TextArea` · `SelectInput` · `OptionPills` · `SubmitButton` · `ToastHost` · `FormProgress` |
| States | `Skeleton` · `EmptyNote` · `FetchError` |
| Media | `ResponsiveImage` (kept as-is) |
| Motion | `Reveal` · `StaggerGroup` · `springs.ts` |
| Meta | `Meta` · `HtmlTitle` |

### 3.9 The prototype

A working prototype of everything in §2 and §3 — the parallax on the CSS
path with the JS fallback, the surface-following chrome on the real logo
SVG, the HU/EN switch, all nine home sections, the form page and the prose
scale — is published at
**<https://claude.ai/code/artifact/120885c5-7bc0-4c60-ae2b-e738dba774bc>**.

It carries no framework and no image requests at all: the sky is a
gradient, the ridges are `clip-path`, the tower is four `<i>`s, the rocket
is inline SVG and the starfield is one generated 320px tile. That is the
existence proof for the ≤120 KB hero-art budget in §12.

What it stands in for rather than implements is listed on the page itself:
Panchang-Bold (Archivo in its place, since Panchang is a self-hosted woff2
in the repo), the rocket figures, the Events rows, the group filter, and
photos/logos.

---

## 4. The chrome — ported from feat.agency

Three files, ported as a unit. `useSurfaceMode` is already generic and
ports almost verbatim; the two components are re-timed onto motion-v (D3).

### 4.1 `composables/useSurfaceMode.ts`

Copy `~/Sites/feat.agency/frontend/src/composables/useSurfaceMode.ts`,
with **one structural change: a third mode.**

```ts
-export type SurfaceMode = 'light' | 'dark';
+export type SurfaceMode = 'light' | 'dark' | 'brand';
-const BRAND_GREEN = { r: 0, g: 215, b: 150 };
+const BRAND_GOLD = { r: 241, g: 171, b: 60 };
```

```ts
   if (isBrandGold) {
-    next = 'dark';          // feat.agency's answer
+    next = 'brand';         // ours
   }
```

**Why it cannot be two modes.** feat.agency folds its brand green into
`dark` because its wordmark sits on a white *card* — over green, the card
goes white and the letters stay green, so green behaves like any dark
surface. EVRST's mark has no card: the gold **is** the lettering. Folding
gold into `dark` keeps a gold wordmark, and over the sponsor pitch panel
(a full-bleed `--gold-500` band) that is gold on gold — the wordmark
disappears, which is exactly what happened in the prototype's first
round. Only the paper swoosh stayed visible, so it read as a broken logo
rather than a colour clash.

So there are three surfaces and each one gets a mapping:

| Surface | gold group (paths 1–4) | paper group (paths 5–6) | pill |
| --- | --- | --- | --- |
| `dark` | `--gold-500` | `--paper` | frosted, no border |
| `light` | `--ink-0` | `--gold-600` | frosted + **1px `--ink-0` border** |
| `brand` | `--ink-0` | `--ink-0` | frosted + **1px `--ink-0` border** |

Two rules that fall out of it:

- **`brand` wins the vote outright.** The three-sample majority (§4.2) is
  there to stop one off-centre element flipping the wordmark, but a
  wordmark *half* over gold is the failure being fixed — so any sample
  seeing brand gold decides the whole mark, rather than being outvoted.
- **The pill takes the ink outline on `light` and `brand` both.** A
  frosted translucent pill has no edge of its own against a light or a
  gold page; on dark it does not need one.

Everything else — `elementsFromPoint`, the opaque-ancestor walk, the
`LIGHT_LUMINANCE = 0.6` threshold, the one-detect-per-frame `schedule()`,
the `.dark` fallback — stays. Keep the `logo-ignore` opt-out class from
feat.agency's `Header.vue` and hoist it into the composable so both the
wordmark and the pill honour it: the hero's gradient overlays must not vote
on the surface colour. Put it on the hero root, and the walk falls through
to `body`'s own `--ink-0` — which is the right answer anyway.

### 4.2 `components/Chrome/SiteHeader.vue`

Ported behaviour, all of it:

- Fixed, centred, `top: 8px`, `z-index: var(--z-header)`. Wordmark only.
- **Three-sample majority vote** at the wordmark's vertical midpoint, plus
  a **top/bottom split check** with the binary search for the boundary Y,
  and the 30–70 % guard band before it commits to a split render. This is
  the part that makes the inversion look deliberate rather than glitchy
  when a section edge crosses the logo — keep the whole algorithm.
- Two clipped `<g>` copies of the wordmark (`#evrst-top-clip` /
  `#evrst-bottom-clip`) driven by `splitClipY`.
- Drop-in entrance on mount and on route change, once per path
  (`lastDropPath` guard — feat.agency's fix for the logo replaying 2–3×
  per navigation).
- Capture-phase native click listener on the `<a>` so the burst runs
  before `router-link`; on `/` it takes over the click entirely
  (`preventDefault` + `stopImmediatePropagation`), fires the burst, waits
  `LOGO_BURST_LEAD = 200 ms`, then smooth-scrolls to top.
- Two-disc burst from the logo's own mark: paper leads, gold starts
  `0.35 s` later and grows faster so both land together, then they fade as
  one. Nodes on `<body>`, `pointer-events: none`, self-removing.
- `window.dispatchEvent(new Event('nav:replay'))` so the pill replays in
  step.

**Prerequisite: `EvrstLogo.vue` must become inline SVG.** Today it is
`<img src="/evrst_logo.svg">`, which cannot be recoloured. Convert the six
paths into the component and bind two fills:

| Surface | gold group (paths 1–4) | paper group (paths 5–6) |
| --- | --- | --- |
| dark | `--gold-500` | `--paper` |
| light | `--ink-0` | `--gold-600` |

Normalize the four export-noise golds to one bound value while converting.
Run the file through `svgo` first; 6.2 KB should come down to ~4 KB and it
is inlined, so it costs no request.

Also port the hover micro-interaction, retargeted: feat.agency slides its
two wordmark halves in and pops the dot. EVRST's mark has no dot — the
equivalent is the gold group sliding up into the paper group with a short
overshoot. Pointer devices only (`@media (hover: hover)`), CSS keyframes,
no JS.

### 4.3 `components/Chrome/SiteNav.vue`

The bottom pill, ported with its full interaction set:

- Fixed, centred, `bottom: 2vh`, `z-index: var(--z-nav)`. Frosted
  translucent backing (`rgb(255 255 255 / 75%)` + `backdrop-filter:
  blur(7px)`) on **every** surface — feat.agency's note is that a fully
  transparent pill lets hero type bleed through and become unreadable.
  Only the framing adapts: `surface-light` keeps a crisp outline,
  `surface-dark` goes solid.
- **Clip-path entrance**: `inset(0% 50% 0% 50%)` → `inset(0)` over
  `0.75 s` on `--ease-inout`, items fading in `stagger: { from: 'center' }`.
  Rendered pre-armed via an `is-armed` class so the first paint matches the
  animation's start state instead of flashing the finished pill.
- **Gliding hover highlight**: one absolutely-positioned element that
  tweens `x/y/width/height` between items so the hover state reads as one
  object following the cursor. Pointer devices only; touch gets `:active`;
  keyboard focus paints the full state on the item itself.
- **Active dot** in `--gold-500`, seated in the label's bottom padding,
  sliding with `--ease-pop` on route change, re-seated after
  `document.fonts.ready` (the first measurement uses fallback metrics and
  the labels shift when Panchang/Space Grotesk swap in).
- **Hide rules**: slide out (`translateY(150%)`) once the footer enters the
  viewport; on mobile, stay tucked until `scrollY > 120`.
- `nav:replay` listener: shrink to zero width as the burst blooms, hold,
  re-expand at `NAV_REOPEN_DELAY = 0.85 s`.

Port note for D3: the two GSAP timelines become one motion-v sequence each.

```ts
import { animate, stagger } from 'motion-v';

const playEntrance = async () => {
  isArmed.value = false;                       // hand over to motion-v
  entrance?.stop();
  entrance = animate([
    [nav.value!,  { opacity: [0, 1] }, { duration: 0.4, ease: EASE_OUT, at: 0 }],
    [pill.value!, { clipPath: ['inset(0% 50% 0% 50% round 4px)',
                               'inset(0% 0%  0% 0%  round 4px)'] },
                  { duration: 0.75, ease: EASE_INOUT, at: 0 }],
    [items,       { opacity: [0, 1] },
                  { duration: 0.4, delay: stagger(0.07, { from: 'center' }),
                    ease: EASE_OUT, at: 0.2 }],
  ]);
  await entrance;
  // A clipped pill samples the wrong centre pixel, so only detect once it
  // has fully opened in place.
  pill.value!.style.clipPath = '';
  detect();
};
```

The `scrollTo` case (`gsap.to(window, { scrollTo })` with
`autoKill: false`) becomes a plain
`window.scrollTo({ top: 0, behavior: 'smooth' })`. feat.agency needed
`autoKill: false` because ScrollTrigger pins could hijack the scroll
mid-flight; there are no ScrollTriggers here, so native is correct.

### 4.4 Nav items + the locale control (D7)

```ts
const ITEMS = [
  { key: 'mission',  hash: '#mission',  compact: true  },
  { key: 'rocket',   hash: '#rocket',   compact: false },
  { key: 'team',     hash: '#team',     compact: true  },
  { key: 'sponsors', hash: '#sponsors', compact: false },
  { key: 'join',     to:   'joinUs',    compact: true  },  // named, localized
] as const;
```

`compact` is what a 375 px pill can hold: below `lg` the pill renders the
three compact items plus the locale toggle, above it renders all five. Same
shape as feat.agency's `displayItems` computed — one list, filtered.

`Chrome/LocaleToggle.vue` is a two-cell `HU | EN` segmented control seated
in the pill after a hairline divider. It inherits the pill's
`surface-{mode}` class, so it inverts with everything else. It is a
`role="group"` of two `<button>`s (not a `<select>`), each with
`aria-pressed`; the active cell carries a gold underline rather than a
fill, so it never competes with the active-route dot.

---

## 5. The hero — multi-layer parallax with an SVG rocket

The centrepiece, and the part with a real performance budget. Seven
layers, all CSS or inline SVG, driven by **one** scroll timeline.

### 5.1 The stage — why the hero is not a tall box

The hero is a **100dvh sticky pane inside a 260dvh stage**:

```html
<div class="hero-stage">          <!-- height: 260dvh          -->
  <section class="hero"> … </section>   <!-- position: sticky; top: 0; height: 100dvh -->
</div>
```

`--hero-h` is therefore the **pin duration** (`stage − viewport`, ≈115dvh),
not the box height, and it is what both the keyframe's travel and
`animation-range-end` read.

The stage was 260dvh in the first pass and is 215dvh now: the sequence in
§5.8 did not need that much scroll, and the surplus showed up as the hero
standing there with nothing in it once the last statement had cleared.
**Pin duration is a content measurement, not a taste setting** — it is
however long the sequence inside it takes, and no longer.

Three problems this solves at once, all of them found by building it the
other way first:

1. **Not enough parallax.** With a 100dvh hero the layers had 100dvh of
   scroll to move through and the effect was barely legible. 160dvh of pin
   is what makes depth readable — travel is `depth × --hero-h`, so the
   runway *is* the effect.
2. **The composition stayed in one screen.** Simply making the hero
   `160dvh` tall was the obvious fix and the wrong one: everything
   positioned from the hero's bottom edge (the ridges, the tower, the
   rocket) fell below the fold, and the first frame showed sky. A pinned
   pane is always exactly the viewport, so bottom-anchored geometry is
   always in view.
3. **The band at the hero's bottom edge.** An oversized `overflow: hidden`
   box has a bottom edge for a travelling layer to slide into. A pinned
   pane does not — it is the viewport.

### 5.2 Layer stack

```
                                        depth   art
z0  sky            CSS radial gradients    —    0 KB  (no request)
z1  starfield      generated 320px tile   0.92  ~1 KB (canvas → data URI)
z2  nebula band    CSS radial gradients   0.78   0 KB
z3  far ground     clip-path on a fill    0.58   0 KB
z4  mid ground     clip-path on a fill    0.32   0 KB
    + tower        four <i> boxes                0 KB
z5  ROCKET         inline SVG            -0.42  ~3 KB
z6  near ground    clip-path on a fill   -0.20   0 KB
z7  copy / HUD     DOM                    0.10    —
```

**The whole hero is 0 image requests.** The plan's earlier draft budgeted
~27 KB of SVG silhouettes; `clip-path` on a solid fill draws the same
profile with no file, no decode and exact control, and it is one
composited layer either way. That is the ≤120 KB hero-art budget met with
a couple of KB to spare, and it is what the prototype ships.

The rocket's depth went from `-0.35` to `-0.42` when the runway grew: with
160dvh to climb through it can afford to leave faster.

### 5.3 Ground — the bug worth writing down

A ridge was one `clip-path`ed box sitting at the hero's bottom edge with
**nothing underneath it**. A negative-depth layer travels *up*, and what
came into frame was the box's own bottom edge: a hard horizontal band with
the layers behind it showing through, stuck across the page for the whole
scroll. Reported, reproduced, fixed.

A ground is now two boxes — the clipped profile, and solid fill below it:

```scss
.ground {
  position: absolute;
  inset: auto -2% 0 -2%;
  /* Exactly the height that puts the profile's base on the viewport's
     bottom edge at rest AND keeps opaque fill under it however far this
     layer travels, in either direction. */
  height: calc(var(--os) + var(--prof));
}
.ground::before,               /* the profile */
.ground::after                 /* the fill below it */
  { content: ''; position: absolute; inset-inline: 0; background: var(--fill); }
.ground::before { top: 0; height: var(--prof); clip-path: var(--profile); }
.ground::after  { top: var(--prof); bottom: 0; }
```

Two boxes rather than one element plus a pseudo-element, because
`clip-path` clips an element's pseudo-elements too — a fill declared on
`::after` of the clipped box would be clipped away with it. `--prof` is a
fixed px height per ground, which is also what keeps the profile's
percentage `clip-path` coordinates meaningful.

`depth` is "how pinned to the viewport this layer is": `0` moves with the
page, `1` is infinitely far and appears static, and **negative** is nearer
than the page — it rushes past. The rocket at `-0.35` is the launch: as
the hero scrolls out, the rocket leaves faster than everything around it.

### 5.4 The technique, and why

**CSS scroll-driven animations first, motion-v as the fallback.** Both
paths read the same `depth` out of `layers.ts` (§11.1), so there is one
source of truth for the geometry and two ways of playing it.

This revises an earlier draft of this plan, which put motion-v's
`useScroll` first and dismissed the CSS route on the grounds that
`MotionConfig reduced-motion="user"` could not reach it. That objection
was weak: reduced motion on a CSS animation is a `@media
(prefers-reduced-motion: reduce)` block, which is neither worse nor harder
than the `MotionConfig` switch. What is left once the objection goes is a
one-sided comparison — see the table.

**Primary path — `animation-timeline: scroll()`** (Kevin Powell, *True
parallax with CSS-only is now possible*):

```css
@keyframes layer-travel { from { transform: translateY(0); }
                          to   { transform: translateY(var(--travel)); } }

.hero__layer {
  --travel: calc(var(--depth) * var(--hero-h));
  animation: layer-travel linear both;
  animation-timeline: scroll(root block);
  /* Lengths, not named ranges: scroll() has no entry/cover/exit. 0 → the
     hero's own height is exactly the `['start start', 'end start']` window
     the JS path uses, so the two agree frame for frame. */
  animation-range: 0 var(--hero-h);
}
@media (prefers-reduced-motion: reduce) { .hero__layer { animation: none; } }
```

Zero JS, no scroll listener, no per-frame measurement, and it keeps running
at full rate while the main thread is busy. `--depth` is set per layer as
an inline style from `LAYERS`; one `@keyframes` serves all seven.

**Fallback path — motion-v**, mounted only when the feature is absent, so
the subscription is never created on browsers that do not need it:

```ts
const cssDriven = CSS.supports('animation-timeline', 'scroll()');
```

`useParallax()` (§11.2) is called behind that flag; when `cssDriven` is
true the layers carry no `:style` binding at all and the CSS owns them.

**The comparison, so nobody re-litigates it:**

| Approach | Verdict |
| --- | --- |
| `animation-timeline: scroll()` | **Primary.** Off-main-thread, no JS, reduced motion via media query. Chromium 115+ and Safari 26+; Firefox trails, which is what the fallback is for. |
| motion-v `useScroll` + `useTransform` | **Fallback.** Motion can hand this to `ScrollTimeline` where it exists and measures where it does not — so it is the correct *second* path, and it is code we want anyway for the rocket's `#trail` opacity ramp, which needs a value JS can read. |
| `perspective` + `translateZ` on a scroll container (Fireship, *The Parallax Effect*) | **Rejected, but its maths is borrowed.** It is the cheapest parallax there is — pure 3D compositing, no timeline at all — and it is the right answer for a page that is *entirely* parallax, which is what that tutorial builds. It is wrong here because it requires the scrolling element to be an inner `div` carrying `perspective`, and that would cost the whole site: `position: fixed` chrome, hash smooth-scroll, `100dvh`, and mobile URL-bar behaviour all assume the document scrolls. Its depth→speed relationship (`scale(1 + z / perspective)`) is nevertheless the model `layers.ts` encodes, and its `perspective` trick is kept for **one** self-contained use: an optional pointer-driven tilt inside the rocket group, which is its own 3D context and touches no scroll container. |
| rAF + `translate3d` by hand | Rejected. Main-thread bound and re-implements what both paths above already do. |
| `background-attachment: fixed` | Rejected. Janks on iOS, cannot express per-layer speed. |

### 5.5 The scroll container — the bug that killed everything

Found by building it: **the whole parallax was dead, silently.**

`_layout.scss` carried `overflow-x: hidden` on `body`, together with
`height: 100%` on `html, body, #vue`. That combination makes **body** the
scroll container instead of the document — setting one axis to a
non-visible overflow forces the other to compute as `auto`. Everything
downstream then fails without an error anywhere:

- `animation-timeline: scroll(root block)` tracks the **root** scroller,
  which now never scrolls. Every scroll-driven animation on the site sits
  frozen at its start state.
- `window.scrollY` stays 0, so the JS fallback path is dead too — both
  paths, from one line of CSS.
- The chrome's surface sampling, the back-to-top's threshold and the
  pill's mobile reveal all read `scrollY`, so none of them ever fire.

The fix is `overflow-x: **clip**` on `html`, and no `height: 100%`
anywhere:

```scss
html {
  overflow-x: clip;   // suppresses overflow WITHOUT becoming a scroller
}

body, #vue {
  display: flex;
  flex-direction: column;
  min-height: 100dvh; // this is what holds the footer down, not height: 100%
}
```

`clip` differs from `hidden` in exactly the way that matters here: it
does not create a scroll container. The marquee and the hero's oversized
layers still cannot give the page a sideways scrollbar, and the document
keeps scrolling.

**The general rule, worth carrying to any scroll-driven page:** a root
timeline is only as reliable as the assumption that the root scrolls, and
that assumption is one `overflow` declaration away from being false. When
a scroll-driven animation appears not to run at all, check what is
scrolling before checking the animation.

### 5.6 The rules that make it fast

1. **Only `transform` animates.** Never `top`, `height`, `filter`,
   `background-position`. Every layer gets `will-change: transform` and
   nothing else.
2. **No `filter: blur()` on a scrolling layer.** It rasterises every frame,
   and WebKit rasterises filters at 1× — the exact cost documented in
   `app-mcdonalds-hu`'s cake flame. The nebula's softness is *baked into
   the SVG gradient*, not applied at runtime.
3. **Overscan is derived, not eyeballed.** A layer translated by up to
   `H · |depth|` must carry that much extra art in the direction of travel
   or its own edge slides into frame. So each layer is
   `position: absolute; inset: -{overscan}px 0;` with
   `overscan = ceil(H_max · |depth|)`, `H_max = 100dvh` capped at 1100 px,
   and SVG art uses `preserveAspectRatio="xMidYMax slice"`. This is stated
   once in `layers.ts` and read by the template — the discipline from
   `GenAlpha/backdrop.ts`, where percentages of the viewport and
   percentages of the art were mixed and broke on every screen but one.
4. **Mobile drops to four layers.** Below `md`, `z2` and `z6` are not
   rendered (`v-if`) — fill-rate, not JS, is the constraint on mobile GPUs,
   and seven full-bleed composited layers is where a mid-range Android
   starts dropping frames.
5. **Total hero art ≤ 120 KB.** All vector, all `svgo`'d. The sky is a CSS
   gradient (zero requests); the starfield is one tiled 2 KB SVG at two
   scales rather than two files.
6. **Reduced motion**: layers freeze at `y = 0`, the rocket keeps only its
   opacity fade, the exhaust stops. `MotionConfig reduced-motion="user"`
   covers the `<motion.*>` layers; the CSS exhaust keyframes need their own
   `@media (prefers-reduced-motion: reduce)` block, because CSS cannot see
   `MotionConfig` (same gap feat.agency documents for GSAP).

### 5.7 The rocket SVG

`Hero/RocketSvg.vue` — inline, so its parts are addressable:

```
#rocket
├─ #trail      long thin gold gradient, opacity driven by scroll progress
├─ #exhaust    flame group — CSS keyframes on scaleY + opacity
├─ #body       fuselage, --paper fill
├─ #stripe     gold band (the brand mark on the airframe)
├─ #window     ink porthole with a gold rim
└─ #fins
```

Two animations, deliberately cheap:

- **Idle sway** — motion-v `repeat: Infinity, repeatType: 'mirror'` on
  `y: [-6, 6]` and `rotate: [-0.6, 0.6]`, ~5 s, on the group. A `bob()`
  factory in `anims.ts`, exactly the mcdonalds `cakeBob` shape.
- **Exhaust flicker** — CSS `@keyframes` on `#exhaust`'s `scaleY`/opacity
  with `transform-box: fill-box; transform-origin: top center`. GPU-only,
  no canvas, no filter, and it keeps running while the scroll animation
  owns the parent's `y`.

On scroll, `#trail` opacity and `#exhaust` scale ramp with
`scrollYProgress` — the rocket visibly *lights up* as it leaves. One
`useTransform` each.

### 5.8 Back to top — the rocket as a control, not an indicator

`Chrome/BackToTop.vue`: a 46px gold disc in the **bottom right** holding
the mini rocket glyph, nose up so it reads as an arrow. Appears once the
hero has handed over (`scrollY > 90vh`), scrolls to top on click, and
nudges the glyph up 4px on hover — the one thing a rocket button should
do.

This replaces a right-margin scroll-progress rail that an earlier pass
put here (a hairline with the rocket riding it and the line filling in
behind). The rail was reviewed out, and the reason is worth keeping: it
occupied a permanent slot on every screen and **did nothing**, where the
same mark as a control is useful on every one of them. A decorative
indicator has to earn its persistence against the thing it could have
been instead.

Bottom right specifically, because the nav pill owns bottom-centre — the
two can never collide — and unlike the pill it stays visible over the
footer, which is exactly where it is wanted.

The mini-rocket glyph is a single `<symbol>` reused three times: here, at
the head of the programme timeline's progress line (§3.3), and nowhere
else. One silhouette, three jobs.

### 5.9 Hero content — the copy, then the statement sequence

The pinned stage carries two phases, not one.

**Phase 1 (0 → 15% of the pin)** is the copy block: eyebrow, headline,
lede, CTA row, countdown. It hands over early rather than fading across
half the pin, because what follows needs the room.

**Phase 2 (15% → 100%)** is what the About section used to be. Three
statements arrive one after another in the band the headline leaves
behind, each rising in, holding, and rising out:

```scss
/* Centred on the viewport's middle rather than measured down from the
   headline: the headline's height changes with the type scale, the
   viewport's middle does not, so the band lands in the same place at
   every width. */
.hero-says { position: absolute; inset: calc(var(--os) + 50dvh) 0 auto 0; translate: 0 -50%; }
.hero-say  { position: absolute; inset-inline: 0; max-width: 26ch; opacity: 0; }

@supports (animation-timeline: scroll()) {
  .hero-say { animation: say linear both; animation-timeline: scroll(root block); }
  .hero-say:nth-of-type(1) { animation-range: calc(var(--hero-h) * .15) calc(var(--hero-h) * .45); }
  .hero-say:nth-of-type(2) { animation-range: calc(var(--hero-h) * .41) calc(var(--hero-h) * .72); }
  /* The last line holds to the end of the pin rather than clearing early:
     it is still on screen as the section below takes over, so there is no
     empty stretch at the hand-over. */
  .hero-say:nth-of-type(3) { animation-range: calc(var(--hero-h) * .68) var(--hero-h); }
}
@keyframes say {
  0%   { opacity: 0; transform: translateY(18px); }
  18%  { opacity: 1; transform: translateY(0); }
  72%  { opacity: 1; transform: translateY(0); }
  100% { opacity: 0; transform: translateY(-18px); }
}
```

**The windows overlap by ~4% of the pin on purpose** — one line is leaving
as the next arrives, which is what makes it read as a sequence rather than
as three separate fades.

In the Vue build this is `<AnimatePresence>` over one `say()` preset in
`Hero/anims.ts`, driven by a `useTransform` on the pin progress; the
windows live in one exported table so the CSS ranges and the fallback path
cannot drift. Under `prefers-reduced-motion` the three lines stop being a
sequence and stand as a static stack — the content is the point, the
sequencing is not.

### 5.10 Phase 1, laid out

Centred, `z7`, `depth 0.10`, faded out by 18% of the pin (§5.8):

```
                    [ wordmark — the fixed header's, already there ]

              ESCAPE VELOCITY                     Panchang, --fs-display
              ROCKETRY                            gold gradient on line 2

              Óbudai Egyetem · Budapest           mono eyebrow, above

        [ Csatlakozz ]  [ A rakéta ]              gold fill / hairline

        16 nap 17ó 38p 20s                        mono, gold, ticking
        KÖVETKEZŐ: STATIKUS HAJTÓMŰTESZT          mono, --text-low
```

The countdown is `useCountdown(nextEvent.start_at)` against the soonest
future `Event.start_at` (§10) — real data, no fake urgency. When there is
no future event it renders nothing rather than a placeholder.

**No `T−` prefix.** It was there in the first draft and it read as
mission-control cosplay over a student team's next bench test. The units
carry the meaning; the affectation only borrowed someone else's gravity.

**There is no scroll cue.** It went through two versions and then out
altogether, and both steps are worth keeping.

The first was a 1px vertical hairline with a gold segment running down it
— read, on first look, as a progress bar. That is a defect rather than a
misreading, and it generalises: **on a page carrying real scroll-driven
mechanics, decorative furniture must not borrow the vocabulary of the
functional kind.** Bars, rails, dots on tracks and filling lines all read
as state here, because elsewhere on this page they are. (The right-margin
progress rail cut in §5.7 was the same mistake from the other end: it
looked functional and wasn't.)

The second was a bobbing chevron, which fixed the misreading and then
failed a simpler test: a hero pinned for two viewports does not need to
be told it can be scrolled. Removed. If a cue is ever wanted back, it has
to justify a permanent slot in the first frame against saying nothing at
all.

---

## 6. Motion system

The mcdonalds pattern, adopted wholesale: **presets live in an `anims.ts`
next to the components that use them, and export `MotionProps` factories.**
No inline `:initial`/`:animate` object literals in templates — that is how
two components end up with two slightly different fade-ups.

```
src/components/Hero/anims.ts        heroBuild, rocketBob, exhaustRamp, cueBounce
src/components/Chrome/anims.ts      pillEntrance, pillReplay, logoDrop, burstDisc
src/components/Home/anims.ts        sectionRise, cardStagger, ribbonDrift
src/components/Motion/springs.ts    the shared spring/ease constants
```

Shape, verbatim from `Birthday/anims.ts`:

```ts
import type { MotionProps } from 'motion-v';

const spring = { type: 'spring', stiffness: 220, damping: 16 } as const;

/**
 * The hero's build: eyebrow → headline → CTA row → countdown, arriving
 * over the parallax layers as they settle.
 *
 * Near-critically damped rather than bouncy, because it plays *over* a
 * geometry change — seven layers are moving on the same frames — so it
 * has to land rather than settle.
 *
 * The fade is on its own tween: a spring cannot overshoot 1, so on
 * opacity it clamps and falls back under it on the way to rest, which on
 * type reads as a flicker a moment after it has landed. The delay is
 * repeated inside because a per-value transition inherits nothing it does
 * not state.
 */
export const heroBuild = (delay = 0): MotionProps => ({
  initial: { opacity: 0, y: 10 },
  animate: { opacity: 1, y: 0 },
  transition: {
    type: 'spring', stiffness: 420, damping: 32, delay,
    opacity: { duration: 0.18, ease: 'easeOut', delay },
  },
});
```

`App.vue` wraps everything in `<MotionConfig reduced-motion="user">` — the
one switch that makes every preset and every `v-motion` directive honour
the OS setting.

`Motion/Reveal.vue` (already in the tree, already correct) stays as the
scroll-arrival primitive; `Motion/StaggerGroup.vue` is added so card grids
declare their cascade once instead of computing per-index delays inline.

---

## 7. i18n

Keep vue-i18n v11 non-legacy and the `src/translations/{en,hu}/` maps.
Three changes:

**7.1 Split the maps by concern** — `index.ts` currently holds everything.
Becomes `{ routes, nav, home, join, meta, common }` per locale, merged in
the locale's `index.ts`. The files are about to triple in size.

**7.2 Localized route paths (D5).** Port feat.agency's `useLanguage`:

```ts
// translations/hu/routes.ts
export default { home: '/', joinUs: '/csatlakozz' };
// translations/en/routes.ts
export default { home: '/', joinUs: '/join-us' };
```

`routes.ts` builds one record per named route per locale, so both paths
resolve and the catch-all never sees them (named routes are registered
first — already the case). Switching locale calls
`changePathIfPossible(old, next)`, which looks the current path up by name
in the old map and `router.replace`s the new map's path. A first load
resolves the locale *from* the path (`getLanguageByPath`) so a shared
`/csatlakozz` link opens in Hungarian regardless of what is in
`localStorage`.

**7.3 Locale resolution order (D4).**

```
?lang= → path (getLanguageByPath) → localStorage('evrst:language')
       → navigator.language.startsWith('hu') → 'hu'
```

Unchanged: the `X-Lang` request interceptor, `document.documentElement.lang`,
and the rule that any `useQuery` fetching localized CMS content must carry
`locale` in its `key` array (the backend localizes by `X-Lang`, so a
language switch has to refetch — `Team` already does this; every new
section must too).

Added: `<link rel="alternate" hreflang="hu|en|x-default">` and `og:locale`
in `Meta.vue`.

---

## 8. Tech stack

### 8.1 Keep

`vue` 3.5 · `vue-router` 5 · `pinia` · `vue-i18n` 11 · `motion-v` ·
`unocss` (presetWind3) + `sass` · `vue-formify` · `@sentry/vue` ·
`unplugin-auto-import` + `unplugin-vue-components` · `vite` · `vue-tsc` ·
the eslint/stylelint/prettier set · `@playwright/test` ·
`@rollup/plugin-strip` · `rollup-plugin-visualizer` · `rimraf`.

`@vueuse/core` stays **and finally gets used** — `useMediaQuery` (the
mobile layer drop, the compact pill), `useEventListener` (auto-cleanup on
the scroll/resize listeners the chrome adds), `useIntersectionObserver`
(the footer/pill collision test). It is currently at zero call sites,
which is why it looked droppable.

### 8.2 Add (from `app-mcdonalds-hu` / `petrolszolg`)

| Package | Why it earns its place |
| --- | --- |
| `vite-plugin-webfont-dl` | **Fixes a live bug.** `_typography.scss` pulls Space Grotesk and IBM Plex Mono with CSS `@import` from `fonts.googleapis.com` — a render-blocking serial request chain inside a stylesheet, plus a third-party font request from an EU-facing site. This plugin self-hosts them into the build. |
| `vite-convert-images` + `vite-plugin-image-optimizer` + `vite/generateImageType.ts` | The hero art, the ridges and any local raster are build-time assets, so they never touch the backend's `/api/img` pipeline. This is petrolszolg's answer: emit avif+webp, generate the `ImageSources` type, and `svgo` every SVG (the rocket, the ridges, the logo). |
| `embla-carousel` + `embla-carousel-auto-scroll` | The sponsor ribbon (auto-drifting, pausable, pointer-draggable) and the mobile events rail. Both reference projects use it; a hand-rolled marquee that is also draggable and also respects reduced motion is more code than the dependency. |
| `date-fns` (+ `hu` locale) | Events carry `start_at`/`end_at` and need HU/EN range formatting ("2026. március 3–5." vs "3–5 March 2026") plus the past/upcoming split and the hero countdown. Tree-shaken, ~3 KB for what we import. |

Optional, phase 4 only if we want it: `@number-flow/vue` for the rocket
spec counters (mcdonalds uses it). Not in the base plan.

### 8.3 Drop

| Package | Reason |
| --- | --- |
| `primevue`, `@primevue/themes` | **D1.** Replaced by six local components (§9). |
| `three`, `@types/three` | **D2.** The hero is SVG now. `useRocketScene.ts` and `RocketScene.vue` are deleted; the CMS `rocket` GLB object stays in the payload, unused by the SPA. |
| `maska` | Zero call sites, no masked inputs in the form schema. |
| `vanilla-lazyload` | Both reference projects carry it, but `ResponsiveImage` already emits native `loading="lazy"` + LQIP, which is strictly better than a JS observer. Deliberate divergence from the reference deps. |
| `@unocss/transformer-directives` | Only needed for `@apply` in SCSS; the styling convention (utilities in template, SCSS for the rest) means we never write `@apply`. Drop unless a case appears. |

**Do not adopt** the reference projects' `clean` / `move` npm scripts. They
move `dist/` into `../public/` for a Laravel-served frontend; EVRST's
`deploy/web.Dockerfile` copies `frontend/build/` into Caddy's `/srv/spa`.
`outDir: 'build'` must stay exactly as it is.

### 8.4 `vite.config.ts` changes

```diff
     rollupOptions: {
       output: {
         manualChunks: {
-          three: ['three'],
           'vue-router': ['vue-router'],
           pinia: ['pinia'],
-          primevue: ['primevue/config'],
-          '@primevue/themes': ['@primevue/themes', '@primevue/themes/aura'],
           'vue-formify': ['vue-formify'],
           'motion-v': ['motion-v'],
+          embla: ['embla-carousel', 'embla-carousel-auto-scroll'],
         },
       },
     },
```

`build.target` moves `es2015` → `es2020`: `es2015` forces regenerator
transforms on every `async` function in the app for browsers that have not
mattered for years, and `backdrop-filter` (the pill) already requires far
newer than ES2015 anyway.

---

## 9. File structure

```
frontend/src/
├── app/
│   ├── App.vue                 MotionConfig · Meta · SiteHeader · main · SiteNav · SiteFooter
│   └── App.scss
├── styles/
│   ├── _tokens.scss            §2.1 + §2.3 + §2.4  (replaces _variables.scss)
│   ├── _typography.scss        @font-face only — the Google @imports go away
│   ├── _breakpoints.scss       unchanged (xs:0 — never pass xs to media-down)
│   ├── _reset.scss
│   └── _base.scss
├── components/
│   ├── Chrome/
│   │   ├── SiteHeader.vue      §4.2   surface-following wordmark + burst
│   │   ├── SiteNav.vue         §4.3   bottom pill
│   │   ├── LocaleToggle.vue    §4.4
│   │   ├── SiteFooter.vue
│   │   ├── BackToTop.vue
│   │   └── anims.ts
│   ├── Brand/
│   │   ├── EvrstLogo.vue       inline SVG, two bound fills, split clips
│   │   ├── ObudaLogo.vue
│   │   └── EvrstMarquee.vue
│   ├── Hero/
│   │   ├── Hero.vue            copy + CTA + countdown
│   │   ├── HeroParallax.vue    §5.2   the layer stack
│   │   ├── RocketSvg.vue       §5.7
│   │   ├── Starfield.vue
│   │   ├── layers.ts           §5.2   the one constants table
│   │   └── anims.ts
│   ├── Sections/
│   │   ├── SectionShell.vue    §3     eyebrow · heading · rule · right slot
│   │   └── Rule.vue
│   ├── Home/
│   │   ├── Mission.vue  Rocket.vue  RocketSpecs.vue  Projects.vue
│   │   ├── Events.vue   EventCard.vue
│   │   ├── Team.vue     TeamCard.vue   Mentors.vue
│   │   ├── Sponsors.vue JoinCta.vue
│   │   └── anims.ts
│   ├── Form/
│   │   ├── FormField.vue  TextInput.vue  TextArea.vue
│   │   ├── SelectInput.vue OptionPills.vue
│   │   ├── SubmitButton.vue ToastHost.vue      ← the PrimeVue replacements
│   ├── Media/ResponsiveImage.vue                keep as-is
│   ├── Motion/
│   │   ├── Reveal.vue  StaggerGroup.vue  springs.ts
│   └── Meta/Meta.vue  HtmlTitle.vue
├── composables/
│   ├── useSurfaceMode.ts       §4.1  ported
│   ├── useParallax.ts          §11.2  (fallback path only)
│   ├── useLanguage.ts          §7.2  ported
│   ├── useCountdown.ts
│   ├── useQuery/               unchanged
│   └── useLocale.ts            folded into useLanguage, then deleted
├── pages/  HomePage · JoinUsPage · DynamicPage · NotFoundPage
├── services/requests/          + EventRequests.ts (upcoming / past split)
├── store/  configStore · metaStore
├── translations/{en,hu}/{index,routes,nav,home,join,meta,common}.ts
└── types/  api.ts · applicationForm.ts · imageSources.ts
```

Deleted: `RocketScene/`, `composables/useRocketScene.ts`, `NoiseFilter/`,
`DiagonalDivider/`, `Section/`, `SectionWrap/`, `SectionButton/`,
`Header/`, `Footer/`, `About/`, `Team/`, `Events/`, `Mentors/`,
`Sponsors/`, `Outro/`, `styles/_variables.scss`, `styles/_layout.scss`,
`styles/_animations.scss`.

Every new SFC carries all six section landmarks —
`PROPS & EMITS → VARIABLES → METHODS → COMPUTED → WATCHERS → HOOKS` —
in order, empty blocks included.

---

## 10. Data map — what the backend already serves

Everything the redesign needs exists. **No backend changes are required.**

| Section | Endpoint | Shape |
| --- | --- | --- |
| Mission | `/api/resource?collectionId={ABOUT_GOALS}` | `payload.{title,description}`, locale-flattened |
| Mission body | `/api/resource?collectionId={VIEWS}` + `where[payload][path][0]=name` | `payload.content` (HTML) |
| Rocket / Programme | `/api/resource?collectionId={ABOUT_PROJECTS}` | `payload.{title,description}`, `start_at`, `end_at` (promoted, indexed columns) |
| Events | `/api/resource?collectionId={EVENTS}` (+ `?past=true`) | `payload.{title,content,image,date}`, `start_at`, `end_at`, `event_status` |
| Team | `/api/team/members?lang=` | `{id,name,degree,photo_path,photo_url,groups[{slug,name,is_primary}],position}` — relational, already localized |
| Team groups | `/api/team/groups?lang=` | `{id,slug,name,kind,position}` |
| Mentors | `/api/resource?collectionId={MENTORS}` | `payload.{name,email,photo}` |
| Sponsors | `/api/resource?collectionId={SPONSORS}` | `payload.{name,description,year,url,logo}` |
| Join-us form | `/api/application-form` | `sections[].fields[]` — the question set is an **admin edit**, never a frontend change |
| Submit | `POST /api/member-applications` | `Record<fieldKey, string \| string[]>`, throttled 10/min |
| Images | `/api/img?path=&w=&dpr=&f=&q=` + `/api/img/meta` | avif/webp/jpg variants + intrinsic size + 24 px LQIP |

Two notes carried from `CLAUDE.md`:

- The backend flattens `{en, hu}` maps inside `payload` to a single string
  for the active locale, so sections read `event.payload.title` as a plain
  string — and must key their `useQuery` on `locale` to refetch on switch.
- `payload.{logo,image,photo}` come back as `/storage/...` URLs; funnel
  them through `pathFromStorageUrl()` before `<ResponsiveImage path=…>`.

The hero countdown reads the soonest `Event` with `start_at > now`; that
is a client-side pick over the existing collection fetch, not a new
endpoint.

---

## 11. Code plan

### 11.1 `Hero/layers.ts` — the one constants table

```ts
/**
 * The hero's parallax stack, declared once.
 *
 * `depth` is how pinned to the viewport a layer is: 0 moves with the page,
 * 1 is infinitely far and appears static, and NEGATIVE is nearer than the
 * page — it rushes past. The rocket is negative on purpose; that is the
 * launch.
 *
 * `overscan` is derived, never eyeballed. A layer translated by up to
 * `H · |depth|` needs that much extra art in the direction of travel or its
 * own edge slides into frame mid-scroll. Stating it here rather than in each
 * layer's CSS is the lesson from GenAlpha/backdrop.ts, where percentages of
 * the viewport and percentages of the art were mixed and only agreed on the
 * one screen the design was drawn at.
 */
export type Layer = {
  key: string;
  depth: number;
  /** Rendered below `md`? Seven composited full-bleed layers is where a
   *  mid-range Android starts dropping frames — fill-rate, not JS. */
  mobile: boolean;
};

/** The tallest hero we will ever lay out, for the overscan arithmetic. */
const H_MAX = 1100;

export const LAYERS = [
  { key: 'stars',  depth:  0.92, mobile: true  },
  { key: 'nebula', depth:  0.78, mobile: false },
  { key: 'far',    depth:  0.55, mobile: true  },
  { key: 'tower',  depth:  0.30, mobile: true  },
  { key: 'rocket', depth: -0.35, mobile: true  },
  { key: 'near',   depth: -0.18, mobile: false },
  { key: 'copy',   depth:  0.12, mobile: true  },
] as const satisfies readonly Layer[];

export const overscan = (depth: number) => Math.ceil(H_MAX * Math.abs(depth));
```

### 11.2 `composables/useParallax.ts` — the fallback path only

```ts
import { useScroll, useTransform, type MotionValue } from 'motion-v';
import type { Ref } from 'vue';

/** Does this browser drive animations off the scroll position on its own? */
export const CSS_SCROLL_DRIVEN =
  typeof CSS !== 'undefined' && CSS.supports('animation-timeline', 'scroll()');

/**
 * The hero's scroll progress as a motion value, for browsers without
 * `animation-timeline: scroll()`. Where the CSS path is available this is
 * never called, so the subscription is not merely unused — it is not created.
 *
 * One `useScroll` for all seven layers, not one each: Motion can hand a single
 * subscription to the browser's ScrollTimeline where that exists, and seven
 * would each pay for their own measurement where it does not.
 *
 * The offsets say "0 while the hero is at rest against the top of the viewport,
 * 1 once it has fully left" — the same window the CSS `animation-range` states
 * as `0 → var(--hero-h)`, so the two paths agree frame for frame.
 */
export const useParallax = (heroRef: Ref<HTMLElement | null>) => {
  const { scrollYProgress } = useScroll({
    target: heroRef,
    offset: ['start start', 'end start'],
  });

  /** A layer's travel, in px, over the hero's exit. */
  const layerY = (depth: number, height: number): MotionValue<number> =>
    useTransform(scrollYProgress, [0, 1], [0, height * depth]);

  /** Copy and the scroll cue are gone well before the hero is. */
  const fade = (until = 0.55) => useTransform(scrollYProgress, [0, until], [1, 0]);

  return { progress: scrollYProgress, layerY, fade };
};
```

The rocket's `#trail` opacity and `#exhaust` scale ramp is the one thing
that stays on `useScroll` in **both** paths: it is a value other code reads,
not just a transform, so it is cheaper to keep one small subscription than
to mirror it in CSS custom properties.

### 11.3 `Hero/HeroParallax.vue` — the shape

```vue
<template>
  <div
    ref="heroRef"
    class="hero relative h-100dvh overflow-hidden logo-ignore"
    :style="{ '--hero-h': `${heroH}px` }">
    <!-- z0: no element. The sky is a CSS radial gradient on .hero itself —
         zero requests, and nothing to composite. -->
    <component
      :is="CSS_SCROLL_DRIVEN ? 'div' : motion.div"
      v-for="layer in visibleLayers"
      :key="layer.key"
      class="hero__layer"
      :style="{
        '--depth': layer.depth,
        inset: `${-overscan(layer.depth)}px 0`,
        /* Only the fallback binds a transform; under the CSS path the
           keyframe owns it and a binding here would fight it. */
        ...(CSS_SCROLL_DRIVEN ? {} : { y: y[layer.key] }),
      }">
      <component :is="ART[layer.key]" />
    </component>
  </div>
</template>
```

`logo-ignore` on the hero root is what stops its gradient overlays from
voting in `useSurfaceMode`'s ancestor walk.

```scss
.hero__layer {
  position: absolute;
  will-change: transform;
  pointer-events: none;
  backface-visibility: hidden;   /* keeps it on its own composited layer */
}

/* The CSS path. One keyframe for all seven layers — `--depth` and
   `--hero-h` are what differ, and both are set inline. */
@supports (animation-timeline: scroll()) {
  @keyframes layer-travel {
    from { transform: translateY(0); }
    to   { transform: translateY(calc(var(--depth) * var(--hero-h))); }
  }

  .hero__layer {
    animation: layer-travel linear both;
    animation-timeline: scroll(root block);
    animation-range: 0 var(--hero-h);
  }

  @media (prefers-reduced-motion: reduce) {
    .hero__layer { animation: none; }
  }
}
```

### 11.4 `Motion/springs.ts`

```ts
/** The CSS easing tokens from _tokens.scss, as motion-v can read them.
 *  One definition each, so a hand-written transition and a motion preset
 *  cannot drift apart. */
export const EASE_OUT   = [0.22, 1, 0.36, 1] as const;
export const EASE_INOUT = [0.65, 0, 0.35, 1] as const;
export const EASE_POP   = [0.34, 1.7, 0.4, 1] as const;

export const SPRING = { type: 'spring', stiffness: 220, damping: 16 } as const;
export const LAND   = { type: 'spring', stiffness: 420, damping: 32 } as const;
export const POP    = { type: 'spring', stiffness: 320, damping: 13 } as const;
```

### 11.5 The PrimeVue replacements (D1)

Six components, ~350 lines total, all driven by the API-shaped field
descriptor already in `types/applicationForm.ts`:

```vue
<!-- Form/FormField.vue — label, help, error, required marker; one wrapper
     so every input inherits identical geometry and focus treatment. -->
<template>
  <div class="field" :data-invalid="!!error">
    <label :for="id" class="eyebrow block mb-8px">
      {{ label }}<span v-if="required" class="text-gold-500" aria-hidden="true"> *</span>
    </label>
    <slot :id="id" :described-by="describedBy" />
    <p v-if="help"  :id="`${id}-help`"  class="fs-13px text-text-low mt-6px">{{ help }}</p>
    <p v-if="error" :id="`${id}-error`" class="fs-13px text-[var(--error)] mt-6px" role="alert">{{ error }}</p>
  </div>
</template>
```

`JoinUsPage.vue`'s type switch changes only in which component it renders:

| field.type | today | after |
| --- | --- | --- |
| `text`, `email` | `InputText` | `TextInput` |
| `textarea` | `Textarea` | `TextArea` |
| `select` | `Select` | `SelectInput` (native `<select>`, styled) |
| `radio`, `checkbox` | `RadioButton` / `Checkbox` | `OptionPills` |
| submit feedback | `Toast` + `ToastService` | `ToastHost` (one `aria-live` region, 3 s auto-dismiss) |

`vue-formify`'s `useForm` and the merge-not-replace `syncValues()` behaviour
stay exactly as they are — that is what keeps typed-in answers alive across
a language switch.

### 11.6 `routes.ts` with localized paths (D5)

```ts
import hu from '@/translations/hu/routes';
import en from '@/translations/en/routes';

/** One record per named route per locale, so both paths resolve and the
 *  catch-all never sees them. Order matters: named routes first. */
const localized = (name: keyof typeof hu, component: () => Promise<unknown>) =>
  [...new Set([hu[name], en[name]])].map(path => ({ path, name, component }));

const routes: RouteRecordRaw[] = [
  { path: '/', name: 'home', component: () => import('@/pages/HomePage.vue') },
  ...localized('joinUs', () => import('@/pages/JoinUsPage.vue')),
  { path: '/:slug(.*)*', component: () => import('@/pages/DynamicPage.vue') },
];
```

`scrollBehavior` keeps its hash handling, with the offset moved from a
hard-coded `80` to `var(--header-height)` read off the computed style — the
header is 8 px + wordmark now, not 96 px.

---

## 12. Performance budget

| Metric | Budget | How it is checked |
| --- | --- | --- |
| Initial JS (gz) | **≤ 120 KB** | `rollup-plugin-visualizer` → `build/_stats.html`. Today's graph carries Three.js + PrimeVue + Aura; dropping both is what makes this reachable. |
| Initial CSS (gz) | ≤ 20 KB | UnoCSS emits only used utilities |
| Hero art, total | ≤ 120 KB | all SVG, `svgo`'d, sky is a gradient |
| Fonts | 3 faces, self-hosted, `font-display: swap`, preloaded | `vite-plugin-webfont-dl` (§8.2) |
| LCP | < 2.0 s, 4G / mid-range Android | Lighthouse mobile |
| CLS | < 0.02 | `ResponsiveImage` seeds intrinsic size; the pill and wordmark are `position: fixed` and out of flow |
| INP | < 100 ms | 4× CPU throttle, scroll the hero and the paper boundary |
| Hero scroll | **composite only** — no layout, no paint | DevTools Performance: the frame chart must show transform-only work while the hero is on screen |

The last row is the one that actually decides whether the parallax feels
right, and it is the one a Lighthouse score will not catch. It gets its own
manual check on a real mid-range Android before the phase is called done.
Optionally, `motion-reviewer` can audit the animation code for MotionScore
tiers once §5 and §6 are in.

---

## 13. Phasing

Small, single-responsibility commits per `CLAUDE.md`, on
`feature/0.6.0`.

| Phase | Commits | Deliverable |
| --- | --- | --- |
| **0. Foundation** | `chore(frontend): drop three, primevue and maska` · `chore(frontend): self-host webfonts` · `feat(frontend): new design tokens` | Deps pruned, `_tokens.scss` in, Google-Fonts `@import` gone, `bun run build` green |
| **1. Chrome** | `refactor(frontend): inline the evrst wordmark` · `feat(frontend): surface-following header` · `feat(frontend): bottom nav pill` · `feat(frontend): locale toggle in the nav pill` | §4 complete: the wordmark and pill invert over a test page with one paper band |
| **2. Hero** | `feat(frontend): parallax layer stack` · `feat(frontend): svg rocket` · `feat(frontend): hero copy and countdown` | §5 complete, budget verified on a real device |
| **3. Sections** | `SectionShell` + `Rule` + `StatRail` first, then one commit per section in the §3.3 order | §3.3 complete on the new tokens; old section components deleted as each replacement lands |
| **3b. States** | `feat(frontend): skeleton, empty and error states` | §3.7 complete — every section survives an empty or failed collection |
| **4. Form** | `feat(frontend): primevue-free form controls` · `feat(frontend): toast host` · `feat(frontend): form progress rail` | §3.4 + §11.5 complete, form still renders from the API |
| **4b. Prose + 404** | `feat(frontend): cms prose scale` · `feat(frontend): split 404 from coming-soon` | §3.5 + §3.6 complete — CMS pages stop looking unstyled |
| **5. i18n** | `feat(frontend): localized route paths` · `feat(frontend): hungarian default locale` | §7 complete, `/csatlakozz` resolves and shares correctly |
| **6. Motion + polish** | `feat(frontend): motion presets` · `perf(frontend): hero scroll budget` | `anims.ts` per feature, `MotionConfig` wired, reduced-motion verified |
| **7. Docs** | `docs: frontend redesign` | `CLAUDE.md` gotchas + `.claude/skills/evrst-frontend/SKILL.md` rewritten to match |

Phases 1 and 2 are independent of 3–5 and can ship behind nothing — the old
sections keep rendering under the new chrome while they are replaced one at
a time.

---

## 14. Open questions

1. **Hero art.** The seven layers are original artwork (ridge silhouettes,
   launch tower, rocket). Do we draw them here, or is there existing team
   art / a Figma frame to work from? This is the only item on the critical
   path that is not code.
2. **Rocket specs.** `RocketSpecs.vue` wants real numbers (height,
   diameter, thrust, mass, apogee). Today's `translations/*/index.ts` has
   the labels but no values. Are these fixed copy (i18n) or should they be
   a CMS `Views` resource so the team can update them?
3. **Paper section choice (D6).** I put Team + Mentors on paper because
   faces read better on light. Sponsors on paper would suit logos better —
   but then the marquee has nothing to sit against. Your call.
4. **`/join-us` as a page or a section?** It is a real route today and the
   form is long enough to deserve one. Keeping it.
5. **Óbuda University branding.** `ObudaLogo.vue` exists; is there a
   required placement / clear-space rule for the university mark in the
   footer?
6. **Open positions (§3.3, Join us).** The "where we need people" table
   wants to know which groups are recruiting. Nothing in the schema says
   so — it would be a new boolean (plus a headcount) on
   `team_member_groups`, editable in the panel. Worth a migration, or
   should that block just be static i18n copy?
7. **Events copy.** The `Event` collection is empty in the seed, so the
   section is designed against its column shape rather than against real
   rows. Is there a list of the team's actual past events to seed it with?
   Without one, Events ships as an empty state on day one (§3.7), which is
   correct but not much of a launch.
