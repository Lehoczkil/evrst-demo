/*
  The hero's parallax stack, declared once.

  `depth` is how pinned to the viewport a layer is: 0 moves with the page,
  1 is infinitely far and appears static, and NEGATIVE is nearer than the
  page — it rushes past. The rocket is negative on purpose; that is the
  launch.

  `overscan` is derived, never eyeballed. A layer travelling `depth × H`
  needs that much extra art beyond the frame in the direction of travel,
  or its own edge slides into view mid-scroll. Stating it here rather than
  in each layer's CSS is the lesson from app-mcdonalds-hu's
  GenAlpha/backdrop.ts, where percentages of the viewport and percentages
  of the art were mixed and only agreed on the one screen the design was
  drawn at.
*/

export type LayerKey = 'stars' | 'nebula' | 'far' | 'mid' | 'rocket' | 'near' | 'copy';

export type Layer = {
  key: LayerKey;
  depth: number;
  /**
   * Rendered below `md`?
   *
   * Seven full-bleed composited layers is where a mid-range Android
   * starts dropping frames — fill-rate is the constraint on mobile GPUs,
   * not JS — so the two purely atmospheric ones come out.
   */
  mobile: boolean;
};

export const LAYERS: readonly Layer[] = [
  { key: 'stars', depth: 0.92, mobile: true },
  { key: 'nebula', depth: 0.78, mobile: false },
  { key: 'far', depth: 0.58, mobile: true },
  { key: 'mid', depth: 0.32, mobile: true },
  { key: 'rocket', depth: -0.42, mobile: true },
  { key: 'near', depth: -0.2, mobile: false },
  { key: 'copy', depth: 0.1, mobile: true },
] as const;

/**
 * How tall the stage is, as a multiple of the viewport.
 *
 * The pin duration is `STAGE_VH - 1` viewports (≈115dvh at 2.15), and
 * that is what the layers travel through. It was 2.6 in the first pass:
 * the statement sequence in <HeroSays> does not need that much scroll,
 * and the surplus showed up as the hero standing there with nothing in it
 * once the last line had cleared.
 *
 * **Pin duration is a content measurement, not a taste setting** — it is
 * however long the sequence inside it takes, and no longer.
 */
export const STAGE_VH = 2.15;

/**
 * The pin's length as a dvh figure, derived from the stage.
 *
 * This is what `--pin` is set to, in CSS units rather than measured
 * pixels: the pin is `stage − viewport`, both of which are viewport
 * multiples, so it needs no measurement and is right on the very first
 * paint. Binding a measured px value meant `--pin: 1px` until onMounted
 * ran, which put every scroll-driven animation at its end state for the
 * first frame.
 */
export const PIN_VH = (STAGE_VH - 1) * 100;

/**
 * The ground layers' profile heights, in px.
 *
 * Fixed rather than fluid because the `clip-path` polygons below are
 * expressed in percentages of their own box: a fluid height would stretch
 * the ridge line's proportions with the viewport.
 */
export const GROUND_PROFILE = {
  far: 220,
  mid: 170,
  near: 120,
} as const;

/** A layer's travel over the full pin, in px. */
export const travelFor = (depth: number, pin: number) => depth * pin;

/**
 * The extra art a layer needs beyond the frame, in px — always positive,
 * and applied to both edges so the sign of `depth` does not matter.
 */
export const overscanFor = (depth: number, pin: number) => Math.abs(depth) * pin;
