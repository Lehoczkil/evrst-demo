<script setup lang="ts">
import { animate, stagger } from 'motion-v';
import { ROUTE_PATHS, localeFromPath, pathsFor } from '@/composables/useLanguage';
import { useSurfaceMode } from '@/composables/useSurfaceMode';
import type { Language } from '@/translations';
import {
  NAV_ITEM_STAGGER,
  NAV_REOPEN_DELAY,
  dotSeat,
  highlightFade,
  highlightGlide,
  pillOpen,
  pillOpenTransition,
  pillShut,
  pillShutTransition,
} from './anims';

/*
  The bottom nav pill. Ported from feat.agency's Nav.vue.

  A frosted translucent pill on EVERY surface: the blur smears whatever is
  behind it into a soft wash so the ink labels stay legible over the hero's
  large type. Only the framing adapts — over a light or a brand-gold
  surface it takes a hard ink outline, because a frosted pill has no edge
  of its own against either.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const route = useRoute();
const { locale } = useLocale();

const navRef = ref<HTMLElement | null>(null);
const pillRef = ref<HTMLElement | null>(null);
const highlightRef = ref<HTMLElement | null>(null);
const dotRef = ref<HTMLElement | null>(null);

/*
  Render the pill already closed so the first paint matches the entrance's
  start state instead of flashing the finished pill.
*/
const isArmed = ref(true);

/*
  The nav items.

  `compact` is what a 375px pill can hold: below lg only the compact ones
  render, above it all of them. One list, filtered — the same shape as
  feat.agency's `displayItems`.

  `rocket` is absent until the rocket section exists (phase 3 of the
  plan). A nav item pointing at nothing is worse than a shorter nav.
*/
const ITEMS = [
  { key: 'mission', hash: 'mission', compact: true },
  { key: 'team', hash: 'team', compact: true },
  { key: 'sponsors', hash: 'sponsors', compact: false },
  // A named route rather than a literal path: its spelling depends on the
  // locale (/csatlakozz vs /join-us).
  { key: 'joinUs', route: 'joinUs', compact: true },
] as const;

/* Hide the pill when it would clash with other UI. */
const LG_BREAKPOINT = 992;
/** px scrolled before the pill appears on mobile. */
const SHOW_AFTER = 120;

const isMobile = ref(false);
const scrolledEnough = ref(false);
const isAtFooter = ref(false);
let visTicking = false;

const hasHover = typeof window !== 'undefined' && window.matchMedia('(hover: hover)').matches;

/** The in-flight entrance, so a replay can cancel it before restarting. */
let entrance: { stop: () => void } | null = null;

/** Which item the dot is currently seated under. */
const activeKey = ref<string | null>(null);

let sectionObserver: IntersectionObserver | null = null;
let footerObserver: IntersectionObserver | null = null;

/*
  The pill floats over whatever section is scrolled behind it, so it
  samples that surface. Start 'dark': every route opens on the hero, and
  detect() corrects it the moment the pill settles.
*/
const { mode, detect } = useSurfaceMode(pillRef, { skipRef: navRef, initial: 'dark' });
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const itemEls = () => Array.from(pillRef.value?.querySelectorAll<HTMLElement>('.nav-item') ?? []);

/** A hash item resolves against home; a route item against the locale. */
const hrefFor = (item: (typeof ITEMS)[number]) => ('hash' in item && item.hash
  ? `/#${item.hash}`
  : ROUTE_PATHS[locale.value as Language][(item as { route: string }).route]);

/*
  The entrance: the pill materialises from its own centre via a clip-path
  reveal — so the frosted blur grows outward with it — while the items
  fade in from the centre out.
*/
const playEntrance = () => {
  const nav = navRef.value;
  const pill = pillRef.value;
  if (!nav || !pill) {
    return;
  }

  // Hand the elements to motion-v; the arming class is no longer needed.
  isArmed.value = false;
  entrance?.stop();

  const items = itemEls();
  if (!items.length) {
    return;
  }

  entrance = animate([
    [nav, { opacity: [0, 1] }, { duration: 0.4, ease: 'easeOut', at: 0 }],
    [pill, pillOpen(), { ...pillOpenTransition, at: 0 }],
    [items, { opacity: [0, 1] }, {
      duration: 0.4,
      delay: stagger(NAV_ITEM_STAGGER, { from: 'center' }),
      ease: 'easeOut',
      at: 0.2,
    }],
  ]);

  (entrance as unknown as Promise<void>).then?.(() => {
    // A clipped pill samples the wrong centre pixel, so only re-detect
    // once it has fully opened in place.
    pill.style.clipPath = '';
    detect();
  });
};

/*
  The header-mark replay, choreographed with the logo burst: the pill
  shrinks to zero width as the discs bloom, holds while they peak, then
  re-expands from the centre as they are almost gone.
*/
const replayEntrance = () => {
  const pill = pillRef.value;
  if (!pill) {
    return;
  }

  isArmed.value = false;
  entrance?.stop();

  const items = itemEls();
  if (!items.length) {
    return;
  }

  entrance = animate([
    [pill, pillShut(), { ...pillShutTransition, at: 0 }],
    [items, { opacity: 0 }, { duration: 0.2, ease: 'easeIn', at: 0 }],
    [pill, pillOpen(), { ...pillOpenTransition, at: NAV_REOPEN_DELAY }],
    [items, { opacity: 1 }, {
      duration: 0.4,
      delay: stagger(NAV_ITEM_STAGGER, { from: 'center' }),
      ease: 'easeOut',
      at: NAV_REOPEN_DELAY + 0.2,
    }],
  ]);

  (entrance as unknown as Promise<void>).then?.(() => {
    pill.style.clipPath = '';
    detect();
  });
};

/*
  The hover state as ONE element gliding between items, rather than a
  background per item — so it reads as a single thing following the
  cursor. Touch has no hover and gets :active feedback in CSS instead.
*/
const moveHighlight = (event: MouseEvent) => {
  const hl = highlightRef.value;
  const item = event.currentTarget as HTMLElement | null;
  if (!hl || !item || !hasHover) {
    return;
  }

  const target = {
    width: `${item.offsetWidth}px`,
    height: `${item.offsetHeight}px`,
    transform: `translate(${item.offsetLeft}px, ${item.offsetTop}px)`,
  };

  const visible = Number(getComputedStyle(hl).opacity) > 0.05;
  if (visible) {
    // Opacity back to 1 too: a quick re-enter can land mid fade-out, and
    // without this the highlight stays half-faded.
    animate(hl, { ...target, opacity: 1 }, highlightGlide);
  } else {
    Object.assign(hl.style, target);
    animate(hl, { opacity: 1 }, highlightFade);
  }
};

const hideHighlight = () => {
  if (highlightRef.value) {
    animate(highlightRef.value, { opacity: 0 }, { duration: 0.25, ease: 'easeIn' });
  }
};

/*
  The gold dot marks the section the reader is actually in — it echoes the
  brand mark and is the one place the accent appears in the chrome.
*/
const seatDot = (animated = true) => {
  const pill = pillRef.value;
  const dot = dotRef.value;
  if (!pill || !dot) {
    return;
  }

  const index = displayItems.value.findIndex((item) => item.key === activeKey.value);
  const el = itemEls()[index];
  if (index < 0 || !el) {
    animate(dot, { opacity: 0 }, highlightFade);

    return;
  }

  const x = el.offsetLeft + el.offsetWidth / 2 - 2;
  if (animated && Number(getComputedStyle(dot).opacity) > 0.05) {
    animate(dot, { transform: `translateX(${x}px)` }, dotSeat);
  } else {
    dot.style.transform = `translateX(${x}px)`;
    dot.style.opacity = '1';
  }
};

/*
  Recompute when the pill should be out of the way. The footer is
  re-queried each tick because it mounts late and is re-created on every
  navigation.
*/
const updateVisibility = () => {
  isMobile.value = window.innerWidth < LG_BREAKPOINT;
  scrolledEnough.value = window.scrollY > SHOW_AFTER;
};

const scheduleVisibility = () => {
  if (visTicking) {
    return;
  }
  visTicking = true;
  requestAnimationFrame(() => {
    visTicking = false;
    updateVisibility();
  });
};

/** Watch the home page's sections so the dot follows the scroll. */
const observeSections = () => {
  sectionObserver?.disconnect();
  sectionObserver = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          activeKey.value = entry.target.id;
        }
      }
    },
    // Only the band through the middle of the viewport counts, so the dot
    // marks what is being read rather than what is merely on screen.
    { rootMargin: '-45% 0px -45% 0px' },
  );

  for (const item of ITEMS) {
    const el = 'hash' in item && item.hash ? document.getElementById(item.hash) : null;
    if (el) {
      sectionObserver.observe(el);
    }
  }
};

const observeFooter = () => {
  footerObserver?.disconnect();
  const footer = document.querySelector('footer');
  if (!footer) {
    return;
  }
  footerObserver = new IntersectionObserver(
    ([entry]) => {
      isAtFooter.value = entry.isIntersecting;
    },
    { rootMargin: '0px 0px -40% 0px' },
  );
  footerObserver.observe(footer);
};

const onResize = () => {
  scheduleVisibility();
  seatDot(false);
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const displayItems = computed(() => (
  isMobile.value ? ITEMS.filter((item) => item.compact) : [...ITEMS]
));

/*
  Slide the pill away over the footer, which carries its own navigation.
  On mobile keep it tucked until the reader has scrolled a little — the
  hero's own CTAs are the primary route out of the first screen there.
*/
const isHidden = computed(() => (
  isAtFooter.value || (isMobile.value && !scrolledEnough.value)
));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watch(() => route.path, async () => {
  activeKey.value = localeFromPath(route.path) || pathsFor('joinUs').includes(route.path)
    ? (pathsFor('joinUs').includes(route.path) ? 'joinUs' : null)
    : null;
  await nextTick();
  observeSections();
  observeFooter();
  seatDot(true);
});

// The item list changes with the breakpoint, so the dot has to re-seat.
watch(displayItems, async () => {
  await nextTick();
  seatDot(false);
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  updateVisibility();
  activeKey.value = pathsFor('joinUs').includes(route.path) ? 'joinUs' : null;

  playEntrance();

  window.addEventListener('nav:replay', replayEntrance);
  window.addEventListener('scroll', scheduleVisibility, { passive: true });
  window.addEventListener('resize', onResize, { passive: true });

  nextTick(() => {
    observeSections();
    observeFooter();
    seatDot(false);
  });

  /*
    Re-seat once the webfonts resolve: the first measurement uses fallback
    metrics, and the labels shift a few px when Space Grotesk swaps in.
  */
  document.fonts?.ready.then(() => seatDot(false));
});

onUnmounted(() => {
  window.removeEventListener('nav:replay', replayEntrance);
  window.removeEventListener('scroll', scheduleVisibility);
  window.removeEventListener('resize', onResize);
  entrance?.stop();
  sectionObserver?.disconnect();
  footerObserver?.disconnect();
});
</script>

<template>
  <nav
    ref="navRef"
    class="nav fixed inset-x-0 bottom-2vh z-[var(--z-nav)] flex items-center justify-center"
    :class="{ 'is-armed': isArmed, 'is-hidden': isHidden }"
    aria-label="Fő navigáció"
  >
    <div
      ref="pillRef"
      class="nav-pill flex items-center gap-2px rd-[var(--radius)] p-2px"
      :class="`surface-${mode}`"
      @mouseleave="hideHighlight"
    >
      <span ref="highlightRef" class="nav-highlight" aria-hidden="true" />
      <span ref="dotRef" class="nav-active-dot" aria-hidden="true" />

      <RouterLink
        v-for="item in displayItems"
        :key="item.key"
        :to="hrefFor(item)"
        class="nav-item rd-[var(--radius)] px-13px pt-7px pb-9px fs-13.5px font-500 ls-[-0.012em] whitespace-nowrap no-underline"
        @mouseenter="moveHighlight"
      >
        {{ t(`nav.${item.key}`) }}
      </RouterLink>

      <LocaleToggle />
    </div>
  </nav>
</template>

<style lang="scss" scoped>
// ── Show / hide ─────────────────────────────────────────────────────────
// Transform only, so it never fights the entrance's opacity + clip-path.
.nav {
  transition: transform 0.45s var(--ease-inout);
}

.nav.is-hidden {
  transform: translateY(190%);
  pointer-events: none;
}

// ── The pill's surface ──────────────────────────────────────────────────
// Frosted on every surface: a fully transparent pill let the hero's large
// type bleed straight through and stop being readable. Only the framing
// adapts. The mode classes are namespaced `surface-*` so they can never
// collide with a section's own `.paper`.
.nav-pill {
  position: relative; // anchors the highlight and the dot
  border: 1px solid transparent;
  background: rgb(248 247 244 / 78%);
  backdrop-filter: blur(8px);
  transition: background-color 0.3s ease, border-color 0.3s ease;

  // A light OR a brand-gold surface takes the frosted pill's edge away,
  // so it gets a hard outline instead.
  &.surface-light,
  &.surface-brand {
    border-color: var(--ink-0);
    background: rgb(248 247 244 / 62%);
  }
}

// ── Items ───────────────────────────────────────────────────────────────
.nav-item {
  position: relative;
  color: var(--ink-0);
  transition: color 0.22s ease, transform 0.16s var(--ease-out);
  z-index: 1;

  @media (hover: hover) {
    &:hover {
      color: var(--gold-500);
    }

    &:active {
      transform: scale(0.94);
    }
  }

  // Touch has no hover, so it gets the full state as tap feedback.
  @media (hover: none) {
    &:active {
      color: var(--gold-500);
      background-color: var(--ink-0);
    }
  }

  // Keyboard parity: tabbing cannot glide the highlight, so a focused
  // item paints the whole state on itself.
  &:focus-visible {
    color: var(--gold-500);
    background-color: var(--ink-0);
    outline: none;
  }
}

// The hover state as one element; motion-v glides this between items.
.nav-highlight {
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 0;
  border-radius: var(--radius);
  background-color: var(--ink-0);
  opacity: 0;
  pointer-events: none;
}

// The brand dot marking the active section, seated in the label's bottom
// padding. motion-v drives the wrapper's opacity and x; the dot paints on
// ::before with a soft pulse, so the pulse multiplies with that opacity
// instead of fighting it for the same inline style.
.nav-active-dot {
  position: absolute;
  bottom: 5px;
  left: 0;
  width: 4px;
  height: 4px;
  opacity: 0;
  pointer-events: none;
  z-index: 2;

  &::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background-color: var(--gold-500);
    animation: nav-dot-pulse 2.4s ease-in-out infinite;
  }
}

// Subdued resting presence with a gentle breath — never fully opaque.
@keyframes nav-dot-pulse {
  0%,
  100% {
    opacity: 0.4;
  }

  50% {
    opacity: 0.85;
  }
}

// ── Armed state (pre-animation, no FOUC) ────────────────────────────────
.nav.is-armed {
  opacity: 0;

  .nav-pill {
    clip-path: inset(0% 50% 0% 50% round 4px);
  }
}

/*
  The dot's breath is the only CSS animation in the pill — the entrance
  and the glide are motion-v and already covered by MotionConfig.
*/
@media (prefers-reduced-motion: reduce) {
  .nav-active-dot::before {
    animation: none;
    opacity: 0.7;
  }
}
</style>
