import type { MotionProps } from 'motion-v';
import { EASE_OUT, LAND } from '@/components/Motion/springs';

/*
  The hero's motion, as presets rather than object literals in templates.

  Shape follows app-mcdonalds-hu's Birthday/anims.ts: MotionProps
  factories taking a delay, so a cascade is declared at the call site and
  the timing curve is declared once, here.
*/

/**
 * The hero's build: eyebrow → headline → lede → CTA row → countdown.
 *
 * Its own preset rather than a shared fadeUp, because it is the one
 * cascade on this page that plays *over* a geometry change — seven
 * parallax layers are settling on the same frames. So it is short and
 * near-critically damped: it lands rather than settling, and it is out of
 * the way before anything underneath it has finished moving.
 *
 * The fade is on its own tween. A spring cannot overshoot 1, so on
 * opacity it clamps and falls back under it on the way to rest — which on
 * type reads as a flicker a moment after it has landed. The delay is
 * repeated inside because a per-value transition inherits nothing it does
 * not state.
 */
export const heroBuild = (delay = 0): MotionProps => ({
  initial: { opacity: 0, y: 10 },
  animate: { opacity: 1, y: 0 },
  transition: {
    ...LAND,
    delay,
    opacity: { duration: 0.18, ease: 'easeOut', delay },
  },
});

/** Fade + rise, for anything arriving into space that is already still. */
export const rise = (delay = 0): MotionProps => ({
  initial: { opacity: 0, y: 24 },
  animate: { opacity: 1, y: 0 },
  transition: { duration: 0.5, delay, ease: EASE_OUT },
});

/**
 * The rocket's idle sway.
 *
 * `repeatType: 'mirror'` rather than a loop: a rocket that snaps back to
 * its start every five seconds is a rocket on a rail. Mirroring makes the
 * motion continuous, which is what reads as hanging in air.
 *
 * Kept off the same element the scroll animation drives — the sway is on
 * the inner wrapper, the travel on the layer — because two animations
 * competing for one `transform` is a fight the last writer wins.
 */
export const rocketSway: MotionProps = {
  animate: { y: [-7, 7], rotate: [-0.6, 0.6] },
  transition: {
    duration: 6.5,
    repeat: Infinity,
    repeatType: 'mirror',
    ease: 'easeInOut',
  },
};

/*
  ── The statement sequence's windows ─────────────────────────────────────

  Fractions of the pin. The CSS path states these as `animation-range`
  slices and the JS fallback reads the same table, so the two cannot
  drift.

  The windows OVERLAP by ~4% of the pin on purpose: one line is leaving as
  the next arrives, which is what makes it read as a sequence rather than
  as three separate fades. The last one holds to the very end of the pin
  rather than clearing early, so there is no empty stretch between it and
  the section that takes over.
*/
export const SAY_WINDOWS: readonly (readonly [number, number])[] = [
  [0.15, 0.45],
  [0.41, 0.72],
  [0.68, 1],
] as const;

/** Where in its own window a line is fully arrived, and starts leaving. */
export const SAY_IN = 0.18;
export const SAY_OUT = 0.72;

/** How far a statement travels in and out, px. */
export const SAY_SHIFT = 18;

/** The copy block has handed over by this fraction of the pin. */
export const COPY_OUT = 0.15;
