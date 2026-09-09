import type { MotionProps } from 'motion-v';
import { EASE_OUT, SPRING } from '@/components/Motion/springs';

/*
  The sections' motion.

  Everything here animates from a VISIBLE resting state on arrival, never
  from `opacity: 0` waiting on an observer: the first still frame of the
  page is what a thumbnail, a shared link and a reader who does not scroll
  all get, and content parked at zero opacity is content that is not
  there.

  So these are used with `while-in-view` + `:in-view-options="{ once }"`
  through <Reveal> / <StaggerGroup>, whose fallback when the observer
  never fires is the arrived state — not the initial one.
*/

/** A section's own arrival: a short rise, nothing more. */
export const sectionRise = (delay = 0): MotionProps => ({
  initial: { opacity: 0, y: 18 },
  whileInView: { opacity: 1, y: 0 },
  inViewOptions: { once: true, amount: 0.2 },
  transition: { duration: 0.5, delay, ease: EASE_OUT },
});

/** One card in a cascade. The index does the staggering. */
export const cardStagger = (index: number, step = 0.06): MotionProps => ({
  initial: { opacity: 0, y: 20 },
  whileInView: { opacity: 1, y: 0 },
  inViewOptions: { once: true, amount: 0.15 },
  transition: { ...SPRING, delay: index * step },
});

/**
 * The programme timeline's progress line drawing itself in.
 *
 * scaleX from its left edge rather than a width tween: width is layout,
 * scale is composited, and this line is 1px tall so the scale is
 * invisible on the cross axis.
 */
export const railFill = (delay = 0): MotionProps => ({
  initial: { scaleX: 0 },
  whileInView: { scaleX: 1 },
  inViewOptions: { once: true, amount: 0.4 },
  transition: { duration: 0.9, delay, ease: EASE_OUT },
});
