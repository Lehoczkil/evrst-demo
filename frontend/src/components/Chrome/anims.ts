import { EASE_INOUT, EASE_OUT, EASE_POP } from '@/components/Motion/springs';

/*
  The chrome's motion, as presets rather than as object literals in
  templates — that is how two components end up with two slightly
  different fade-ups.

  Ported from feat.agency's Header.vue / Nav.vue, which drive these with
  GSAP timelines. motion-v's imperative `animate()` covers the same
  ground, and keeping one engine means `MotionConfig reduced-motion="user"`
  reaches every animation on the site instead of most of them.
*/

/** How long the pill takes to open, and how long a replay holds shut. */
export const PILL_OPEN = 0.75;
export const PILL_SHUT = 0.35;

/**
 * Seconds before a replay re-expands the pill.
 *
 * Timed against the logo burst: the discs grow for 0.8s and have faded by
 * ~1.3s, so the pill comes back as the burst is almost gone rather than
 * competing with it at its peak.
 */
export const NAV_REOPEN_DELAY = 0.85;

/** Head-start (ms) the burst gets before the page starts scrolling home. */
export const LOGO_BURST_LEAD = 200;

/** How long each burst disc takes to cover the viewport. */
export const BURST_GROW = 0.8;

/** How much later the gold disc starts, so it lands with the paper one. */
export const GOLD_BURST_DELAY = 0.35;

/**
 * The wordmark's drop-in.
 *
 * Not a spring: it arrives over an empty header with nothing else moving,
 * so a long eased fall reads as weight rather than as bounce.
 *
 * Explicit from/to keyframes because this is played imperatively — a
 * declarative `{ opacity: 1, y: 0 }` target would animate from the mark's
 * current state, which is already exactly that, and nothing would move.
 */
export const logoDrop = () => ({
  opacity: [0, 1],
  transform: ['translateY(-120px)', 'translateY(0px)'],
});

export const logoDropTransition = { duration: 1, ease: EASE_OUT };

/**
 * The pill's clip-path entrance: it materialises from its own centre, so
 * the frosted blur grows outward with it rather than being revealed.
 *
 * `inset()` on both ends, in percentages, is load-bearing. A relative
 * animation reads the current clip-path back in px and interpolates from
 * the left edge instead of the centre.
 */
const PILL_WIDE = 'inset(0% 0% 0% 0% round 4px)';
const PILL_NARROW = 'inset(0% 50% 0% 50% round 4px)';

export const pillOpen = () => ({ clipPath: [PILL_NARROW, PILL_WIDE] });
export const pillShut = () => ({ clipPath: [PILL_WIDE, PILL_NARROW] });

export const pillOpenTransition = { duration: PILL_OPEN, ease: EASE_INOUT };
export const pillShutTransition = { duration: PILL_SHUT, ease: 'easeIn' as const };

/** Items fade in from the centre out, so the pill reads as one object. */
export const NAV_ITEM_STAGGER = 0.07;

/**
 * The gliding hover highlight. One element tweened between items, so the
 * hover state reads as a single thing following the cursor rather than as
 * a per-item background.
 */
export const highlightGlide = { duration: 0.4, ease: EASE_OUT };
export const highlightFade = { duration: 0.2, ease: 'easeOut' as const };

/** The active-route dot seating itself, with a little overshoot. */
export const dotSeat = { duration: 0.5, ease: EASE_POP };
