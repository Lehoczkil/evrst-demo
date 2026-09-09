<script setup lang="ts">
import { animate } from 'motion-v';
import { surfaceModeAt, type SurfaceMode } from '@/composables/useSurfaceMode';
import { BURST_GROW, GOLD_BURST_DELAY, LOGO_BURST_LEAD, logoDrop, logoDropTransition } from './anims';

/*
  The floating wordmark. Ported from feat.agency's Header.vue.

  It is the whole header: centred, fixed, 8px from the top, and nothing
  else. That is what moves the locale switcher into the nav pill — there
  is no top-right corner left to put it in.

  It samples whatever is painted behind it and flips the mark's colours to
  stay legible, and it does its own sampling rather than using
  useSurfaceMode: it votes across three x-positions AND binary-searches
  for a section boundary crossing it vertically, which is past what the
  composable's API covers.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const route = useRoute();

const headerRef = ref<HTMLElement | null>(null);
const markRef = ref<HTMLElement | null>(null);

/** The mark's mode when no boundary crosses it. */
const mode = ref<SurfaceMode>('dark');

/** Set when one does: the ratio down the mark, and the mode either side. */
const split = ref<{ ratio: number; top: SurfaceMode; bottom: SurfaceMode } | null>(null);

/*
  Only split when the boundary falls meaningfully inside the mark. Outside
  30–70% one colour occupies a thin sliver, which reads as a rendering
  artefact rather than as an intended effect — so the dominant side wins
  instead.
*/
const SPLIT_MIN = 0.3;
const SPLIT_MAX = 0.7;

/** Binary search stops once the boundary is located to within this. */
const SPLIT_PRECISION = 2;

let ticking = false;

/*
  dropIn() can be triggered for the same page by more than one mechanism —
  mount and route change. feat.agency's fix for the logo replaying 2–3×
  per navigation: track the path we last dropped for and skip repeats.
*/
let lastDropPath = '';

/** The <a>, captured so the capture-phase listener can be removed. */
let logoLink: HTMLAnchorElement | null = null;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*
  Three horizontal samples at the vertical midpoint, majority vote: one
  off-centre element must not be able to flip the whole mark. `brand` wins
  outright rather than being outvoted — a wordmark half over gold is the
  exact failure the brand mode exists to fix.
*/
const voteAcross = (rect: DOMRect, y: number): SurfaceMode | null => {
  const votes = [rect.left + rect.width * 0.25, rect.left + rect.width / 2, rect.left + rect.width * 0.75]
    .map((x) => surfaceModeAt(Math.round(x), y, headerRef.value))
    .filter(Boolean) as SurfaceMode[];

  if (!votes.length) {
    return null;
  }
  if (votes.includes('brand')) {
    return 'brand';
  }

  return votes.filter((m) => m === 'light').length > votes.length / 2 ? 'light' : 'dark';
};

const detect = () => {
  if (typeof window === 'undefined' || !window.innerWidth || !headerRef.value) {
    return;
  }

  // The header's own box, not the mark's: it is static, so sampling stays
  // stable while the mark is mid drop-in.
  const rect = headerRef.value.getBoundingClientRect();
  if (rect.bottom <= 0 || rect.top >= window.innerHeight) {
    return;
  }

  const cx = Math.round(rect.left + rect.width / 2);
  const centerY = Math.round(rect.top + rect.height / 2);
  const topY = Math.round(rect.top + 4);
  const bottomY = Math.round(rect.bottom - 4);

  const majority = voteAcross(rect, centerY);
  const modeTop = surfaceModeAt(cx, topY, headerRef.value);
  const modeBottom = surfaceModeAt(cx, bottomY, headerRef.value);

  // A boundary crosses the mark: find where, to the pixel.
  if (modeTop && modeBottom && modeTop !== modeBottom) {
    let lo = topY;
    let hi = bottomY;
    while (hi - lo > SPLIT_PRECISION) {
      const mid = Math.round((lo + hi) / 2);
      if (surfaceModeAt(cx, mid, headerRef.value) === modeTop) {
        lo = mid;
      } else {
        hi = mid;
      }
    }
    const ratio = ((lo + hi) / 2 - rect.top) / rect.height;

    if (ratio >= SPLIT_MIN && ratio <= SPLIT_MAX) {
      split.value = { ratio, top: modeTop, bottom: modeBottom };

      return;
    }

    // Near an edge — take the side that actually covers the mark.
    split.value = null;
    mode.value = ratio < SPLIT_MIN ? modeBottom : modeTop;

    return;
  }

  split.value = null;
  if (majority) {
    mode.value = majority;
  }
};

const schedule = () => {
  if (ticking) {
    return;
  }
  ticking = true;
  requestAnimationFrame(() => {
    ticking = false;
    detect();
  });
};

const dropIn = () => {
  if (!markRef.value || lastDropPath === route.path) {
    return;
  }
  lastDropPath = route.path;
  animate(markRef.value, logoDrop(), logoDropTransition);
};

/*
  Visual only: two discs bloom out of the mark to fill the viewport, then
  dissolve together — paper leads, gold starts later but grows faster so
  both land on the same frame, and being appended second it lands on top
  as the final brand colour before they fade.

  The nodes live on <body> with pointer-events:none and remove themselves,
  so they never block anything.
*/
const spawnBurst = () => {
  const box = markRef.value?.getBoundingClientRect();
  if (!box) {
    return;
  }

  const x = box.left + box.width / 2;
  const y = box.top + box.height / 2;

  // Reaches the farthest viewport corner from the origin, so each disc
  // covers the page however near an edge the mark sits.
  const radius = Math.hypot(
    Math.max(x, window.innerWidth - x),
    Math.max(y, window.innerHeight - y),
  );
  const size = Math.ceil(radius * 2);

  const disc = (color: string, grow: number, delay: number) => {
    const node = document.createElement('span');
    node.className = 'logo-burst';
    node.style.cssText = [
      `left:${x}px`,
      `top:${y}px`,
      `width:${size}px`,
      `height:${size}px`,
      // A soft rim makes the expanding edge read as a bloom, and lets the
      // leading paper disc show through the trailing gold one.
      `background:radial-gradient(circle, ${color} 60%, transparent 100%)`,
      'transform:translate(-50%,-50%) scale(0)',
    ].join(';');
    document.body.appendChild(node);

    animate(node, { transform: ['translate(-50%,-50%) scale(0)', 'translate(-50%,-50%) scale(1)'] }, {
      duration: grow,
      delay,
      ease: [0.4, 0, 1, 1],
    });
    animate(node, { opacity: [1, 0] }, { duration: 0.5, delay: BURST_GROW, ease: 'easeIn' })
      .then(() => node.remove(), () => node.remove());
  };

  disc('var(--paper)', BURST_GROW, 0);
  disc('var(--gold-500)', BURST_GROW - GOLD_BURST_DELAY, GOLD_BURST_DELAY);
};

/*
  The mark links home, and a click fires the burst on every route.

  Wired as a NATIVE capture-phase listener on the <a> rather than a Vue
  @click, so it reliably runs before router-link's own handler — the only
  way to keep the burst ahead of the navigation. On home there is nowhere
  to go, so we take the click over entirely and scroll ourselves after a
  short lead, which is what lets the burst be visibly underway before the
  view starts moving.
*/
const onLogoClick = (event: MouseEvent) => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!reduced) {
    spawnBurst();
    // Replay the pill's entrance in step with the burst.
    window.dispatchEvent(new Event('nav:replay'));
  }

  if (route.path !== '/') {
    // Let router-link navigate; the burst plays over the switch into home.
    return;
  }

  event.preventDefault();
  event.stopImmediatePropagation();
  window.setTimeout(() => {
    window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
  }, reduced ? 0 : LOGO_BURST_LEAD);
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watch(() => route.path, () => {
  nextTick(() => {
    detect();
    dropIn();
  });
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  detect();
  dropIn();

  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule, { passive: true });

  logoLink = headerRef.value?.querySelector('a') ?? null;
  logoLink?.addEventListener('click', onLogoClick, true);
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', schedule);
  window.removeEventListener('resize', schedule);
  logoLink?.removeEventListener('click', onLogoClick, true);
});
</script>

<template>
  <header
    ref="headerRef"
    class="fixed top-8px left-50% translate-x--50% z-[var(--z-header)]"
  >
    <RouterLink to="/" class="logo-link block leading-none">
      <span ref="markRef" class="block">
        <EvrstLogo :surface="mode" :split="split" height="40px" />
      </span>
    </RouterLink>
  </header>
</template>

<style lang="scss" scoped>
/*
  No armed-hidden state here, unlike the pill: the drop-in starts in
  onMounted, so the mark is at rest for at most a frame. Arming it in CSS
  would mean a permanently invisible logo if the animation never ran.
*/
.logo-link > span {
  will-change: transform, opacity;
}
</style>

<style lang="scss">
/*
  Global, not scoped: spawnBurst() appends these to <body>, where the
  component's data-v attribute would never reach them.
*/
.logo-burst {
  position: fixed; // width/height set inline, sized to cover the viewport
  border-radius: 50%;
  pointer-events: none;
  z-index: var(--z-burst); // below the header so the mark stays crisp
  will-change: transform, opacity;
}
</style>
