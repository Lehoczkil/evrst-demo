/*
  The motion-v half of the easing pair. The CSS half is in
  styles/_tokens.scss (--ease-out, --ease-inout, --ease-pop, --dur*), and
  the two must not drift: a hand-written CSS transition and a motion
  preset animating the same kind of thing should be indistinguishable.

  Presets themselves do not live here — they live in an `anims.ts` next to
  the components that use them, exporting MotionProps factories. This file
  holds only the constants those presets share.
*/

/** Arrivals. Fast out of the gate, long settle — the site's default. */
export const EASE_OUT = [0.22, 1, 0.36, 1] as const;

/** Moves and reversible state changes: pill open/close, show/hide. */
export const EASE_INOUT = [0.65, 0, 0.35, 1] as const;

/** Small marks that should feel seated rather than placed: dots, badges. */
export const EASE_POP = [0.34, 1.7, 0.4, 1] as const;

/** General-purpose spring for elements arriving into empty space. */
export const SPRING = { type: 'spring', stiffness: 220, damping: 16 } as const;

/**
 * Near-critically damped: it lands rather than settling.
 *
 * For anything animating *over* a geometry change — the hero's copy
 * arriving while seven parallax layers are moving on the same frames. A
 * bouncier spring reads as unsteadiness when the ground under it is also
 * in motion.
 */
export const LAND = { type: 'spring', stiffness: 420, damping: 32 } as const;

/** Bouncy scale-in, for marks small enough that overshoot reads as life. */
export const POP = { type: 'spring', stiffness: 320, damping: 13 } as const;

/** Seconds. Mirrors --dur-fast / --dur / --dur-slow. */
export const DUR_FAST = 0.18;
export const DUR = 0.35;
export const DUR_SLOW = 0.75;
