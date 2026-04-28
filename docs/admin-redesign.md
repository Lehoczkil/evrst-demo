# EVRST admin — design brief

A refined, opinionated redesign of the Filament admin. The intent: make
the panel feel like the **inside of a rocket-team mission console** — quiet
graphite surfaces, a single warm signal colour, hairline blueprint
geometry — without sacrificing any of Filament's table / form mechanics.

This is the plan to **verify before any code is written**. Approve, push
back, or `ship it` — once green-lit I implement Option A in one sweep.

---

## 0. Aesthetic direction (the part that's not generic)

The current panel is "Filament default + amber". It works, but it could
be any SaaS admin. We commit to a specific identity:

> **Mission console at night.** Graphite chrome, a single amber signal
> colour reserved for action and status, faint blueprint grid behind
> hero surfaces, hairline borders doing the visual lifting. Type set
> with a touch of monospace where data lives. Quiet, technical, on-brand
> for an engineering team that builds rockets at 2 AM.

This rules out three things designers reach for by default:
1. **No purple/blue gradient backgrounds.** They're the "AI dashboard"
   tell. We use a flat near-black graphite, accented only by amber.
2. **No glassmorphism everywhere.** Glass is a *moment*, used on five
   surfaces (topbar, modals, hero stat tiles, drawing tool rail, Onshape
   chrome). Everything else is solid graphite with a 1px border.
3. **No bouncy micro-interactions.** Motion is short, linear, and
   functional. A console doesn't bounce.

What we *do* lean into:

- **Hairline geometry.** Every card edge is a 1px border in
  `rgba(255,255,255,.07)`. The whole panel reads like blueprint paper.
- **Amber as a signal, not a flourish.** Amber is reserved for: primary
  CTA, focus rings, the active sidebar item's left bar, the "today"
  marker on the calendar, the priority dot for `urgent`, status pill
  for in-progress states, and the launch-countdown digits on the
  dashboard. Nothing else gets amber. This is what makes a single
  colour feel intentional instead of decorative.
- **Mono where data lives.** IDs, timestamps, kanban counts, calendar
  day numbers, telemetry-style readouts on the dashboard. Body text
  stays in Inter; mono is a callout.
- **A blueprint grid.** A 32px-on-32px subtle grid in
  `rgba(255,255,255,.025)` lives behind the page background — invisible
  in screenshots at thumbnail size, present and grounding once you're
  on the page. Ties every screen together without demanding attention.

---

## 1. Design tokens

### 1.1 Palette (dark default)

| Token              | Value                         | Where                                                    |
| ------------------ | ----------------------------- | -------------------------------------------------------- |
| `--bg`             | `#0a0c10`                     | Page background. Near-black graphite, not pure black.    |
| `--bg-grid`        | `rgba(255,255,255,.025)`      | Blueprint grid lines on `--bg`.                          |
| `--surface`        | `#11141a`                     | Solid card / panel base.                                 |
| `--surface-raised` | `#161a22`                     | Hover row, dropdown, popover.                            |
| `--glass`          | `rgba(20,24,33,.55)`          | Translucent surface, paired with `blur(18px)`.           |
| `--border`         | `rgba(255,255,255,.07)`       | Hairline border on every card edge.                      |
| `--border-strong`  | `rgba(255,255,255,.14)`       | Hover / focus state.                                     |
| `--border-amber`   | `rgba(251,191,36,.45)`        | Active focus ring, today marker, urgent priority.        |
| `--text`           | `#e8ebf2`                     | Primary text. Slightly warm against the cool graphite.   |
| `--text-muted`     | `#9aa3b3`                     | Captions, table meta, help text.                         |
| `--text-dim`       | `#6b7383`                     | Disabled, watermark, "no data" prompts.                  |
| `--accent`         | `#fbbf24`                     | Amber. The only signal colour.                           |
| `--accent-text`    | `#1a1306`                     | Text on amber fills.                                     |
| `--accent-soft`    | `rgba(251,191,36,.12)`        | Amber tint for active rows, focus glow.                  |
| `--danger`         | `#f87171`                     | Destructive actions, blocked state.                      |
| `--success`        | `#34d399`                     | Done state, success toast.                               |
| `--info`           | `#38bdf8`                     | "Testing" status, neutral info chip.                     |
| `--mono`           | `ui-monospace, "JetBrains Mono", monospace` | Numbers, IDs, timestamps.                  |

Light mode stays available behind a manual toggle (single `:root.light`
override), but **dark is the default**. The brand reads better dark, the
glass language is dark-first, and most students touch the panel at night.

### 1.2 Geometry

- **Radius**: `0.75rem` (12px) on cards, panels, inputs, buttons. Pills
  fully rounded. No more 6/8/10px mix.
- **Borders**: 1px solid `--border` everywhere; on hover or focus the
  border darkens to `--border-strong`. Focus moves to `--border-amber`
  with a `0 0 0 3px var(--accent-soft)` glow.
- **Shadows**: none on cards. Modals get one heavy drop:
  `0 24px 80px rgba(0,0,0,.6)` so they read as floating over chrome.
- **Spacing**: one step looser than current. `gap-3 → gap-4` between
  cards, `p-4 → p-5` inside cards, sidebar item height 32 → 36px.

### 1.3 Glass language

Glass is reserved. These six surfaces are glass; everything else stays
solid `--surface`:

1. The Filament topbar (sticky, blurred-on-scroll).
2. Modals (help, calendar event, drawing image-stamp).
3. Dashboard hero stat tiles (top row only).
4. Drawing studio tool rail + properties panel.
5. Onshape viewer chrome (open-in-Onshape chip + status badge).
6. Kanban filter row.

Pattern:

```css
background: var(--glass);
border: 1px solid var(--border);
backdrop-filter: blur(18px) saturate(120%);
```

Mobile (<720px) falls back to solid `--surface` with the same border —
`backdrop-filter` is GPU-heavy on cheap Android phones.

### 1.4 Typography

- **Display / page heading**: Inter 700, 1.5rem / 1.2.
- **Section heading (card title)**: Inter 600, 0.95rem / 1.3.
- **Body**: Inter 400, 0.875rem / 1.55, `--text`.
- **Meta**: Inter 500, 0.75rem / 1.4, `--text-muted`.
- **Mono callout**: 0.75rem `--mono`, `letter-spacing: .02em`. Used for
  IDs, due-date stamps, telemetry digits on dashboard.

Drop the default body weight from Filament's 500 → 400 — it tightens
the visual rhythm and lets headings actually feel like headings.

### 1.5 Motion

Linear, short, never bouncy.

- Hover transitions: 120ms ease (`background-color`, `border-color`,
  `transform`, `opacity`).
- Modal / dropdown enter: 160ms `cubic-bezier(.16,1,.3,1)`.
- Sidebar active-bar slide: 180ms.
- Kanban drag: untouched (SortableJS already correct).
- **The one exception**: the dashboard countdown digits flip with a 220ms
  vertical translate on tick. That's the signature flourish, see §3.

---

## 2. The blueprint grid

Behind every page background, a 32px square grid:

```css
body {
    background:
        linear-gradient(var(--bg-grid) 1px, transparent 1px) 0 0 / 32px 32px,
        linear-gradient(90deg, var(--bg-grid) 1px, transparent 1px) 0 0 / 32px 32px,
        var(--bg);
}
```

It's barely visible — just enough to anchor surfaces and read as
"engineering paper" rather than "flat dark theme". This is the cheapest,
most distinctive thing we can do.

A second, denser grid (16px, slightly stronger) sits behind the
dashboard stat-tile row only — a "telemetry panel" feel for the screen
the team sees most.

---

## 3. Signature moment per page

Each page gets one element that makes the redesign feel deliberate
instead of cosmetic.

### 3.1 Dashboard — `T-` countdown to next launch / event

Top of the page, full-width, 96px tall glass strip. Reads:

```
T- 23 : 14 : 06 : 41        APOGEE-IV  ·  STATIC FIRE
   D    H    M    S         next event from Calendar
```

- Mono digits in `--accent`, separators in `--text-muted`.
- Pulled from the next upcoming event in the calendar (already exists —
  `Event` model with date).
- If no upcoming event: shows `STANDBY` in `--text-dim` with a softer
  tone. Doesn't disappear; the slot is structural.
- The seconds digit ticks every second with the 220ms flip motion
  defined in §1.5.
- Click → calendar.

Below the countdown, the existing five tiles in a 2-column grid (per
the previous plan).

### 3.2 Tasks list / Kanban — priority dot legend

Top-right of the table toolbar, a compact legend:

```
●  urgent      ●  high      ●  normal      ●  low
amber          red          blue           dim
```

Each is a 8px dot + a label. Hovering filters the table by that
priority (already supported via Filament filter). Tiny, but it tells you
what the colours mean without a tooltip and reinforces that **amber =
attention**.

### 3.3 Tasks edit — sticky save bar with state pill

Bottom of the page, glass strip, full-width, 64px tall:

```
[ Status: IN PROGRESS ]            Cancel    Save
   amber pill, mono                 ghost     amber-fill
```

The status pill on the left mirrors the Task's current state and
updates live as the user changes it in the form. Makes the state machine
visible without scrolling back up.

### 3.4 Calendar — "today" as an amber inline ring, no fill

Day cells are flat `--surface` tiles. Today is marked by a 2px amber
inset ring (`box-shadow: inset 0 0 0 2px var(--border-amber)`), no
background fill. Event chips inside cells are coloured **3px left
border + 1 line of text**, no fill — keeps a busy month from looking
like confetti.

The toolbar (month nav + filters) is glass, sticky, 56px tall.

### 3.5 Drawing studio — tool rail in mono labels

Tool labels under each icon in `--mono`, uppercase, 0.65rem,
`letter-spacing: .08em`:

```
PEN   LINE   RECT   TEXT   STAMP
```

Active tool: amber icon + amber underline 2px. Idle: muted icon, no
border. Tightens the rail and ties it to the "engineering tool" feel.

### 3.6 Onshape viewer — telemetry status line

Bottom-left of the viewer, mono, `--text-muted`:

```
GLB · 4.2 MB · cached 3h ago
```

Replaces the current button-shaped status badge. Open-in-Onshape chip
top-right stays glass, small chevron, no fill.

### 3.7 Member applications — applicant chip with status dot

Each row leads with an avatar circle (initial-fallback) + name in body
weight + a status dot in the priority colour. Email and applied-on date
in `--mono` muted. Reads as a contact card, not a database row.

### 3.8 Activity log — timeline rail

Left-edge timeline (1px border, dots at events) instead of a flat
table. Each entry: time in mono, actor name, action verb in muted body,
target as a link. Feels like `git log`. Doesn't change the underlying
table — just the row template.

### 3.9 Database inspector — schema map header

Above the table list: a 1-line summary in mono — `54 tables · 312
columns · sqlite 3.46`. Tiny, but instantly says "you are looking at
the engine room".

### 3.10 Login — single amber signal

Centred card, glass, 1px border. Logo top, two inputs, single amber
"Sign in". Background: the blueprint grid + a soft radial gradient from
`rgba(251,191,36,.06)` at 30% 20%. The only screen where amber appears
in the background — gives the panel a distinct entry moment.

---

## 4. Micro-interactions worth keeping (and adding)

**Keep**:
- Kanban drag-drop with SortableJS — works, don't touch.
- The help-modal `?` pills next to page headings and sidebar items —
  already aligned with the new ghost-button language.
- The drawing studio's tool tooltips.

**Add or refine**:
- **Sidebar active row**: 2px amber left bar slides into position
  (180ms) when you navigate. The bar is the only animation on the
  sidebar — no row backgrounds, no icon scaling.
- **Topbar on scroll**: starts transparent (over the blueprint grid),
  picks up `--glass` + `border-bottom` after 8px of scroll. 200ms
  transition. Anchors the chrome.
- **Status pill flip**: when a Task transitions states, the pill text
  swaps with a 180ms vertical translate (same motion as the dashboard
  countdown). Consistent gesture for "a value just changed".
- **Focus glow**: 0 0 0 3px `--accent-soft` on inputs. The amber doesn't
  fill — it bleeds out. Reads as "this field is hot" without being
  loud.
- **Empty states**: the heroicon stays, but is rendered at 64px in
  `--text-dim` over a 1px-dashed `--border` rectangle. Looks like a
  schematic placeholder, not a stock illustration.

**Cut**:
- Filament's default subtle-drop-shadow on cards. Replaced everywhere
  with hairline border.
- The amber accent bar on the existing "Upcoming this week" widget —
  redundant with the new dashboard signature.

---

## 5. Per-page treatment (deltas from §3)

Most pages just inherit the tokens, sticky save bar, and topbar
treatment. Specifics:

- **Topbar**: glass-on-scroll (see §4). Search input → pill, ghost
  border, focus → amber glow. Locale switcher: smaller pill, ghost
  outline, amber fill on the active locale only.
- **Sidebar**: solid `--surface`, no blur. Group headings in
  uppercase 0.65rem `--text-muted`. Items 36px, no icon backgrounds.
  Active state is the sliding amber bar described in §4.
- **Tables**: no zebra. Header row: muted-text uppercase 0.65rem, bottom
  border only. Hover row: `--surface-raised`. Action buttons collapse
  to icons with hover-label tooltips (today they're text + icon and
  crowd the line).
- **Forms**: every Section becomes a solid `--surface` panel (not glass
  — too noisy across long forms). Inputs flat: no side borders, 1px
  bottom border in `--border`, focus → amber bottom border + glow.
  Save / Cancel sticky at page bottom (see §3.3).
- **Modals**: glass, the heavy `0 24px 80px rgba(0,0,0,.6)` drop, max
  width 560px, vertically centred via `position: fixed; top:50%;
  left:50%; transform: translate(-50%,-50%)` (the workaround for
  Filament's transformed ancestor is already in place).
- **Kanban**: columns are solid `--surface` tiles with hairline border
  (not glass — they need to feel like a fixed lane). Cards keep their
  current structure; drop the small SVG accent bar (priority dot does
  that job now). Filter row at top is glass and sticky.

---

## 6. Implementation strategy

**Recommended: Option A** — single override stylesheet mounted via
`Filament\PanelsRenderHook::HEAD_END`. Rough size: ~350 lines of CSS
overriding `.fi-*` selectors with the new tokens. No Tailwind
recompilation, no Vite step in deploy, fast to iterate on. The custom
Blades (drawing studio, calendar, kanban, Onshape viewer) already use
scoped class names — they pick up the new tokens automatically once
`:root` is themed.

Option B (proper Filament theme via `make:filament-theme admin` +
Tailwind) stays available later if we ever want bespoke utilities.
Don't need it for this pass.

### File deltas

- **New** `resources/css/filament/admin/theme.css` — tokens, blueprint
  grid, all `.fi-*` overrides, the per-page signature classes
  (`.evrst-countdown`, `.evrst-priority-dot`, `.evrst-status-pill`,
  `.evrst-timeline`).
- **New** `resources/views/filament/admin/dashboard/countdown.blade.php`
  — 30-line Blade for the §3.1 strip. Pure HTML + a tiny Alpine
  countdown timer (no JS framework — same pattern as the help modal).
- **Edit** `app/Providers/Filament/AdminPanelProvider.php` — add the
  HEAD_END hook for the stylesheet, add a PAGE_HEADER_WIDGETS_BEFORE
  hook for the dashboard countdown.
- **Edit** the dashboard widget Blades — switch backgrounds to glass
  tokens, drop accent bars, scale numbers to 1.6rem.
- **No PHP refactors.** Filament's PHP stays untouched.

---

## 7. Verification — what you'll see

When I implement, I'll capture the same 10 before/after screenshots and
drop them in `docs/admin-redesign-screenshots/{before,after}/`:

1. Login (signature radial + grid).
2. Dashboard with countdown strip + tile grid.
3. Tasks list (priority legend + cleaned table chrome).
4. Tasks edit (sectioned form + sticky save bar with status pill).
5. Kanban board.
6. Calendar (month + event modal, today marker).
7. Drawing studio (desktop + mobile breakpoint).
8. Onshape model edit (with viewer + telemetry status line).
9. Help modal (sanity check the new tokens).
10. Activity log (timeline rail).

---

## 8. Decisions (locked after first review)

1. **More glass, less radius, keep all chrome** — every card / panel /
   table / kanban column / sidebar is glass (`--blur` + `--glass`);
   solid surfaces only on inputs and small chips for legibility.
   Radius drops to `4 / 6 / 8 px` (was 12 px). All existing
   functionality stays: help modal, page-heading `?` pill, sidebar
   per-item `?` pill, language switcher, theme toggle, force-desktop
   toggle, user menu, search.
2. **Countdown fallback** — when no upcoming event in the calendar,
   strip switches to **`LAST EVENT +X d`** counting *up* from the most
   recent past event (e.g. `LAST FIRE +12d`). Same digit-flip motion,
   muted colour for the digits so the strip reads "standby" rather
   than "imminent".
3. **Mono font** — JetBrains Mono, loaded via Google Fonts.
4. **Amber strictness** — kept tight (the seven uses listed in §0).
5. **Force dark default + manual light toggle** — `:root` is dark,
   `:root.light` overrides every token. Toggle in topbar persists in
   `users.locale`-style preference + localStorage fallback.
6. **Mobile glass fallback** — `@media (max-width: 720px)`
   `backdrop-filter` is replaced with a solid 92 %-opaque `--glass`
   variant.
7. **Container** — content area capped at 1440 px (`--container`),
   centered. Sidebar pinned to the left edge of the viewport (full
   height, 220 px wide). Topbar spans the full viewport width with the
   real EVRST logo at the left.

Once you've reviewed the updated mockup at
`docs/admin-redesign-mockup.html` reply with `ship it` (or further
deltas) and I implement Option A in one commit and post the
before/after grid.
