import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/*
  "What colour is the surface under this fixed element?"

  Ported from ~/Sites/feat.agency/frontend/src/composables/useSurfaceMode.ts.
  Both pieces of floating chrome — the centred wordmark at the top and the
  nav pill at the bottom — hang over whatever section happens to be
  scrolled behind them, so each has to flip its own colours to stay
  legible. This composable owns the sampling so the two stay in lock-step.

  It samples the element actually painted under a point via
  `elementsFromPoint` (which already skips pointer-events:none overlays),
  skips our own UI, then walks up for the first opaque background colour.

  ── The one structural change from feat.agency: a third mode ───────────

  feat.agency folds its brand green into `dark`, because its wordmark sits
  on a white *card*: over green the card goes white and the letters stay
  green, so green behaves like any other dark surface.

  EVRST's mark has no card — the gold IS the lettering. Folding gold into
  `dark` keeps a gold wordmark, and over a full-bleed --gold-500 band (the
  sponsor pitch panel, the marquee) that is gold on gold: the wordmark
  disappears and only the paper swoosh survives, which reads as a broken
  logo rather than a colour clash. So brand gold gets its own mode, and
  over it the whole wordmark goes ink.
*/

/** The logo's own #F1AB3C. */
const BRAND_GOLD = { r: 241, g: 171, b: 60 };

/** How far from BRAND_GOLD still counts as the brand surface, per channel. */
const GOLD_TOLERANCE = 26;

/**
 * Only a clearly LIGHT surface (≈ the paper sections) counts as 'light'.
 * Everything darker is 'dark'; brand gold is neither and is caught first.
 */
const LIGHT_LUMINANCE = 0.6;

export type SurfaceMode = 'light' | 'dark' | 'brand';

const parseRgb = (value: string) => {
  const match = value.match(/rgba?\(([^)]+)\)/i);
  if (!match) {
    return null;
  }
  const parts = match[1].split(/[\s,/]+/).filter(Boolean).map(Number);
  if (parts.length < 3 || parts.slice(0, 3).some((n) => Number.isNaN(n))) {
    return null;
  }

  return { r: parts[0], g: parts[1], b: parts[2], a: parts.length >= 4 ? parts[3] : 1 };
};

const relativeLuminance = (r: number, g: number, b: number) => {
  const linear = (channel: number) => {
    const c = channel / 255;

    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
  };

  return 0.2126 * linear(r) + 0.7152 * linear(g) + 0.0722 * linear(b);
};

/**
 * Elements carrying this class are excluded from sampling, along with
 * everything inside them. Put it on decorative overlays whose background
 * must not decide the chrome's colour — the hero's gradient layers are
 * the reason it exists. The walk then falls through to `body`, whose own
 * --ink-0 is the right answer anyway.
 */
export const SURFACE_IGNORE = 'surface-ignore';

/**
 * The mode of the surface painted under one screen-space point.
 *
 * Exported because <SiteHeader> does its own sampling: it votes across
 * three x-positions AND binary-searches for a section boundary crossing
 * the wordmark, which is more than one call can express. The pill uses
 * `useSurfaceMode` below.
 */
export const surfaceModeAt = (x: number, y: number, skip?: HTMLElement | null): SurfaceMode | null => {
  const hit = document
    .elementsFromPoint(x, y)
    .find((el) => !skip?.contains(el) && el !== skip && !el.closest(`.${SURFACE_IGNORE}`));
  if (!hit) {
    return null;
  }

  // First opaque background colour up the ancestor chain.
  let surface: ReturnType<typeof parseRgb> = null;
  let node: Element | null = hit;
  while (node && node !== document.documentElement) {
    const color = parseRgb(getComputedStyle(node).backgroundColor);
    if (color && color.a > 0.5) {
      surface = color;
      break;
    }
    node = node.parentElement;
  }

  // Nothing opaque anywhere up the chain. Every surface on this site
  // paints explicitly, so this means a transparent stack over the page
  // ground — which is dark unless a paper section is behind it.
  if (!surface) {
    return hit.closest('.paper') ? 'light' : 'dark';
  }

  const isBrandGold =
    Math.abs(surface.r - BRAND_GOLD.r) < GOLD_TOLERANCE &&
    Math.abs(surface.g - BRAND_GOLD.g) < GOLD_TOLERANCE &&
    Math.abs(surface.b - BRAND_GOLD.b) < GOLD_TOLERANCE;

  if (isBrandGold) {
    return 'brand';
  }

  return relativeLuminance(surface.r, surface.g, surface.b) >= LIGHT_LUMINANCE ? 'light' : 'dark';
};

type UseSurfaceModeOptions = {
  /**
   * Element to exclude when sampling so we never hit our own floating UI.
   * Defaults to the target element itself.
   */
  skipRef?: Ref<HTMLElement | null>;
  /** Attach scroll/resize listeners that re-detect. Default true. */
  auto?: boolean;
  /** Mode before the first detect resolves. */
  initial?: SurfaceMode;
  /**
   * Sample three x-positions across the target and take the majority,
   * instead of one sample at its centre. The wordmark wants this: one
   * off-centre element must not be able to flip the whole mark. `brand`
   * still wins outright — a wordmark half over gold is the failure this
   * mode exists to fix, so it is not outvoted.
   */
  vote?: boolean;
};

export const useSurfaceMode = (
  targetRef: Ref<HTMLElement | SVGElement | null>,
  options: UseSurfaceModeOptions = {},
) => {
  const mode = ref<SurfaceMode>(options.initial ?? 'dark');
  let ticking = false;

  const detect = () => {
    if (typeof window === 'undefined' || !window.innerWidth || !targetRef.value) {
      return;
    }

    const rect = targetRef.value.getBoundingClientRect();
    // Skip while the element is parked off-screen (e.g. lifted by its
    // entrance animation) — there is nothing painted under it to sample.
    if (rect.bottom <= 0 || rect.top >= window.innerHeight) {
      return;
    }

    const skip = (options.skipRef?.value ?? (targetRef.value as unknown as HTMLElement));
    const cy = Math.round(rect.top + rect.height / 2);

    let next: SurfaceMode | null;

    if (options.vote) {
      const votes = [rect.left + rect.width * 0.25, rect.left + rect.width / 2, rect.left + rect.width * 0.75]
        .map((x) => surfaceModeAt(Math.round(x), cy, skip))
        .filter(Boolean) as SurfaceMode[];
      if (!votes.length) {
        return;
      }
      const light = votes.filter((m) => m === 'light').length;
      next = votes.includes('brand') ? 'brand' : light > votes.length / 2 ? 'light' : 'dark';
    } else {
      next = surfaceModeAt(Math.round(rect.left + rect.width / 2), cy, skip);
    }

    if (next && next !== mode.value) {
      mode.value = next;
    }
  };

  // One detection per frame at most — cheap enough for scroll, no thrash.
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

  onMounted(() => {
    detect();
    if (options.auto !== false) {
      window.addEventListener('scroll', schedule, { passive: true });
      window.addEventListener('resize', schedule, { passive: true });
    }
  });

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', schedule);
    window.removeEventListener('resize', schedule);
  });

  return { mode, detect, schedule };
};
