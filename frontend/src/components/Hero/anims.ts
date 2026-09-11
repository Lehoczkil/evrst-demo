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
 * cascade on this page that plays *over* a geometry change — eight
 * parallax layers are settling on the same frames. So it is short and
 * near-critically damped: it lands rather than settling, and it is out of
 * the way before anything underneath it has finished moving.
 *
 * The fade is on its own tween. A spring cannot overshoot 1, so on
 * opacity it clamps and falls back under it on the way to rest — which on
 * type reads as a flicker a moment after it has landed. The delay is
 * repeated inside because a per-value transition inherits nothing it does
 * not state.
 *
 * `play` gates the whole build on the webfonts, in step with
 * <SplitText>'s own `play`. See Hero.vue: the copy is vertically centred,
 * so Panchang swapping in changes the block's height and moves every line
 * in it. Holding the build until the face has landed means the reader
 * sees the arrival once, in the right font, instead of a reflow.
 */
export const heroBuild = (delay = 0, play = true): MotionProps => ({
  initial: { opacity: 0, y: 10 },
  animate: play ? { opacity: 1, y: 0 } : { opacity: 0, y: 10 },
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

/*
  ── The build's clock ────────────────────────────────────────────────────

  Seconds from mount. Stated as a table rather than as five literals in
  the template, because the whole point of the sequence is that the two
  headline lines LAND TOGETHER: line one is fifteen characters and line
  two is eight, so line two has to start later by exactly the difference
  between their cascades, and that only stays true if both the delays and
  the per-character step live in one place.
*/
export const BUILD = {
  eyebrow: 0.1,
  title1: 0.28,
  title2: 0.58,
  lede: 0.9,
  cta: 1.25,
  countdown: 1.38,
} as const;

/** Seconds between one character and the next in a display line. */
export const CHAR_STEP = 0.03;

/** Body copy reveals by word — 90 boxes to say one sentence is a gimmick. */
export const WORD_STEP = 0.035;

/**
 * The rocket's idle sway.
 *
 * `repeatType: 'mirror'` rather than a loop: a rocket that snaps back to
 * its start every few seconds is a rocket on a rail. Mirroring makes the
 * motion continuous, which is what reads as hanging in air.
 *
 * Kept off the same element the scroll animations drive — the sway is on
 * the inner wrapper, the travel on the layer, the recede on the slot —
 * because two animations competing for one `transform` is a fight the
 * last writer wins.
 *
 * The travel is a `transform` STRING rather than independent `y` /
 * `rotate`, so motion hands it to the browser's own animation engine
 * instead of writing an inline style every frame for as long as the hero
 * is mounted. The technique is app-mcdonalds-hu's, in GenAlpha and in
 * Birthday's `cakeBob`.
 */
export const rocketSway: MotionProps = {
  animate: {
    transform: [
      'translateY(-9px) rotate(-1deg)',
      'translateY(9px) rotate(1deg)',
    ],
  },
  transition: {
    duration: 5.4,
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

  The windows OVERLAP by ~2% of the pin on purpose: one line is leaving as
  the next arrives, which is what makes it read as a sequence rather than
  as three separate fades. The first starts at 0.20 rather than 0 because
  that is where the headline has finished handing over, and the last holds
  to the very end of the pin so there is no empty stretch between it and
  the section that takes over.

  At STAGE_VH 3.0 the pin is 200dvh, so each of these is ~60dvh of scroll
  — roughly double what the same fractions bought at the old 2.15.
*/
export const SAY_WINDOWS: readonly (readonly [number, number])[] = [
  [0.2, 0.5],
  [0.48, 0.76],
  [0.74, 1],
] as const;

/** Where in its own window a line is fully arrived, and starts leaving. */
export const SAY_IN = 0.12;
export const SAY_OUT = 0.76;

/** How far a statement travels in and out, px — fallback path only. */
export const SAY_SHIFT = 18;

/** The copy block has handed over by this fraction of the pin. */
export const COPY_OUT = 0.2;
