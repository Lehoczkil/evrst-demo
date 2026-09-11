/*
  The hero's parallax stack, declared once.

  `depth` is how fast a layer travels through the pin, as a multiple of
  the pin's own length, and the SIGN is the direction: POSITIVE descends,
  NEGATIVE climbs. The pane is sticky, so a layer at 0 is nailed to the
  viewport and reads as infinitely far.

  The stack is read as one camera move: the vehicle climbs, so everything
  on the ground descends — near fastest, far slowest — and the rocket is
  the one thing going the other way. The first pass mixed the signs (the
  far ridge descended while the near one rose) and the result had no
  single reading; nothing was moving *because* of anything.

  `overscan` is derived, never eyeballed, and it is DIRECTIONAL. A layer
  travelling down brings its own top edge into frame, so it needs that
  much extra art above and none below; a layer travelling up needs the
  mirror. Stating it per-edge rather than on both halves every layer's
  box — the near ridge at depth 1.18 would otherwise be a 340dvh
  composited box to pay for a 120px ridge line.
*/

export type LayerKey = 'stars' | 'dust' | 'nebula' | 'far' | 'mid' | 'rocket' | 'near' | 'copy';

export type Layer = {
  key: LayerKey;
  /** Travel through the pin, as a multiple of it. Positive descends. */
  depth: number;
  /** Scale at the end of the pin. 1 is no zoom. */
  zoom?: number;
  /** Vertical stretch at the end of the pin — what turns stars into streaks. */
  streak?: number;
  /** Sideways travel over the pin, px. Breaks the purely vertical read. */
  driftX?: number;
  /**
   * Rendered below `md`?
   *
   * Eight full-bleed composited layers is where a mid-range Android
   * starts dropping frames — fill-rate is the constraint on mobile GPUs,
   * not JS — so the purely atmospheric ones come out.
   */
  mobile: boolean;
};

export const LAYERS: readonly Layer[] = [
  /*
    Deep field. Barely travels; the zoom and the streak are what sell the
    speed, and both are free — the tile is scaled, not repainted.

    The zoom is deliberately restrained. At 1.42 (and 2.1 on the dust
    plane) it read as flying INTO the field rather than past it: the
    stars grew to the size of the type and the sky stopped being a
    backdrop. A little goes a long way here, because the eye reads
    relative rates, not absolute ones — 1.18 against the dust plane's
    1.36 is the same depth cue at a fifth of the visual disturbance.
  */
  { key: 'stars', depth: 0.08, zoom: 1.18, streak: 1.35, mobile: true },
  // A second, denser plane in front of it. The DIFFERENCE between the two
  // zoom rates is the depth cue, not the size of either.
  { key: 'dust', depth: 0.16, zoom: 1.36, streak: 2, driftX: -30, mobile: false },
  { key: 'nebula', depth: 0.24, zoom: 1.32, driftX: 55, mobile: false },
  /*
    Ground. Slower than the first pass by roughly a third, so the
    landscape is still in frame well past the halfway mark instead of
    clearing in the first quarter — the ridges are the only thing giving
    the climb a reference, and once they are gone the sky reads as still.
    The profile heights in GROUND_PROFILE went up to match: they start
    higher in the frame, so there is more of them to lose.
  */
  { key: 'far', depth: 0.3, mobile: true },
  { key: 'mid', depth: 0.48, mobile: true },
  { key: 'near', depth: 0.72, mobile: true },
  // The one thing climbing. Against ground that is going the other way,
  // the closing speed is the launch.
  { key: 'rocket', depth: -0.92, mobile: true },
  /*
    Depth 0 — the reader's own plane, and the reference everything else
    is fast or slow against.

    It was -0.16 for a moment, to let the headline lift as it handed
    over. That is the right idea on the wrong element: the statement
    sequence lives on this layer too, and a layer drifting 0.16 of the
    pin carries all three statements 300px off the centre they are
    supposed to hold. The headline's exit is its own keyframe in
    Hero.vue, which moves the copy block and leaves the layer still.
  */
  { key: 'copy', depth: 0, mobile: true },
] as const;

/**
 * How tall the stage is, as a multiple of the viewport.
 *
 * The pin duration is `STAGE_VH - 1` viewports, and that is what the
 * layers travel through and what the statement sequence is paced
 * against. It has been 2.6 (too long for the copy that was in it) and
 * 2.15 (too short once the copy became a per-letter build and three
 * statements that hold). At 3.0 each statement gets ~60dvh of scroll to
 * arrive, hold and leave, which is the measurement that set it.
 *
 * **Pin duration is a content measurement, not a taste setting** — it is
 * however long the sequence inside it takes, and no longer.
 */
export const STAGE_VH = 3.0;

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
 * Fixed rather than fluid because the `clip-path` polygons in
 * HeroParallax are expressed in percentages of their own box: a fluid
 * height would stretch the ridge line's proportions with the viewport.
 *
 * Raised from 220/170/120 so the horizon starts higher in the frame.
 * That is also what gives the slower descent something to spend: a 120px
 * ridge travelling at 0.72 of the pin is gone in the first eighth of it
 * however slowly it moves, because there was never much of it.
 */
export const GROUND_PROFILE = {
  far: 320,
  mid: 250,
  near: 180,
} as const;

/** A layer's travel over the full pin, in px. */
export const travelFor = (depth: number, pin: number) => depth * pin;

/**
 * The extra art a layer needs beyond the frame, per edge, in px.
 *
 * A descending layer needs it above and nothing below; a climbing layer
 * the mirror. Returning both as one pair keeps the two halves of the
 * derivation in one place.
 */
export const overscanFor = (depth: number, pin: number) => ({
  top: depth > 0 ? depth * pin : 0,
  bottom: depth < 0 ? -depth * pin : 0,
});
