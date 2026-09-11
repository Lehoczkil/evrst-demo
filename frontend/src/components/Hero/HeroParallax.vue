<script setup lang="ts">
import { motion } from 'motion-v';
import { CSS_SCROLL_DRIVEN } from '@/composables/useParallax';
import { rocketSway } from './anims';
import { GROUND_PROFILE, LAYERS, overscanFor, travelFor, type Layer } from './layers';

/*
  The hero's eight layers.

  Every one of them is CSS or inline SVG: the sky is a pair of radial
  gradients, the ridges are clip-path on a solid fill, the two star planes
  are one generated tile each and the rocket is a few hundred bytes of
  path data. **Zero image requests**, which is how the plan's ≤120 KB
  hero-art budget is met with room to spare.

  Geometry comes out of layers.ts and is stated once. See §5 of
  docs/frontend-redesign.md for why the stage is pinned rather than tall.

  ── What makes it read as speed ────────────────────────────────────────

  Travel alone did not. The first version moved seven layers vertically at
  seven rates and the effect was legible but flat, because the only cue
  was rate — and rate is exactly the cue a reader cannot judge without
  something to compare it against. Three things were added, and each is
  free because it rides the transform that was already there:

    · The two star planes ZOOM at different rates. A field that grows
      towards you is the one unambiguous read of moving through it.
    · They also STRETCH on Y. A dot scaled 3.4× vertically is a streak,
      so the field turns to lines as the speed builds, and it does it
      without a second asset or a repaint.
    · The signs all agree now. Ground descends, rocket climbs, and the
      closing speed between them is larger than either.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { progress = 0, pin = 1 } = defineProps<{
  /** 0-1 through the pin. Only read on the JS fallback path. */
  progress?: number;
  /** The pin's length in px, for the fallback's travel arithmetic. */
  pin?: number;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const isMobile = ref(false);
let mq: MediaQueryList | null = null;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const syncViewport = () => {
  isMobile.value = !!mq && !mq.matches;
};

/**
 * A layer's own custom properties.
 *
 * `--depth` is a bare number so it can multiply a length inside calc().
 * The two overscans are separate because they are directional: a layer
 * only needs extra art on the edge it is travelling towards.
 */
const varsFor = (layer: Layer) => ({
  '--depth': String(layer.depth),
  '--over-top': `calc(${overscanFor(layer.depth, 1).top} * var(--pin))`,
  '--over-bot': `calc(${overscanFor(layer.depth, 1).bottom} * var(--pin))`,
  '--drift-x': `${layer.driftX ?? 0}px`,
  '--zoom-x': String(layer.zoom ?? 1),
  '--zoom-y': String((layer.zoom ?? 1) * (layer.streak ?? 1)),
});

/**
 * The fallback's transform. Undefined on the CSS path, so nothing is
 * bound and the keyframe owns the transform outright — an inline style
 * here would win the fight and freeze the layer.
 */
const travelStyle = (layer: Layer) => {
  if (CSS_SCROLL_DRIVEN) {
    return undefined;
  }

  const y = travelFor(layer.depth, pin * progress).toFixed(1);
  const x = ((layer.driftX ?? 0) * progress).toFixed(1);
  const zx = (1 + ((layer.zoom ?? 1) - 1) * progress).toFixed(3);
  const zy = (1 + ((layer.zoom ?? 1) * (layer.streak ?? 1) - 1) * progress).toFixed(3);

  return { transform: `translate3d(${x}px, ${y}px, 0) scale(${zx}, ${zy})` };
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const visible = computed(() => LAYERS.filter((layer) => layer.mobile || !isMobile.value));

/** The trail's ramp: the rocket visibly lights up as it leaves. */
const trailStyle = computed(() => (CSS_SCROLL_DRIVEN
  ? undefined
  : {
      opacity: String(0.18 + progress * 0.82),
      transform: `scaleY(${(0.5 + progress * 1.9).toFixed(3)}) scaleX(${(1 - progress * 0.28).toFixed(3)})`,
    }));

/** The rocket's own recede, on the fallback path. */
const rocketStyle = computed(() => (CSS_SCROLL_DRIVEN
  ? undefined
  : { transform: `scale(${(1 - progress * 0.55).toFixed(3)})` }));

/** The pad's ignition glow: up fast, then left behind. */
const flareStyle = computed(() => {
  if (CSS_SCROLL_DRIVEN) {
    return undefined;
  }
  const ramp = progress < 0.22 ? progress / 0.22 : Math.max(0, 1 - (progress - 0.22) / 0.5);

  return { opacity: String(0.18 + ramp * 0.82) };
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  mq = window.matchMedia('(min-width: 768px)');
  syncViewport();
  mq.addEventListener('change', syncViewport);
});

onBeforeUnmount(() => {
  mq?.removeEventListener('change', syncViewport);
});
</script>

<template>
  <template v-for="layer in visible" :key="layer.key">
    <div
      class="layer"
      :class="`layer--${layer.key}`"
      :style="{ ...varsFor(layer), ...travelStyle(layer) }"
    >
      <!-- z1 — the deep field, plus the only thing that moves on its own
           clock. The comets ride the far plane so they inherit its zoom
           and never look pasted on top. -->
      <template v-if="layer.key === 'stars'">
        <Starfield :size="340" :count="52" :max-radius="0.85" />
        <Comets />
      </template>

      <!-- z2 — a second, denser plane in front of it. Finer dust, no gold
           (the far plane owns that), and a much harder zoom: the
           difference between the two rates IS the depth cue. -->
      <Starfield
        v-else-if="layer.key === 'dust'"
        :size="190"
        :count="30"
        :gold="0"
        :max-radius="0.7"
        :opacity="0.75"
      />

      <!-- z3 — the nebula. Its softness is baked into the gradient stops:
           never a filter: blur() on a scrolling layer, which re-rasterises
           every frame (and WebKit rasterises filters at 1x). -->
      <div v-else-if="layer.key === 'nebula'" class="nebula absolute inset-0" aria-hidden="true" />

      <!-- z4/z5/z6 — ground, descending. Nearest fastest, which is what
           makes the three of them read as one landscape rather than as
           three ridges at three speeds. -->
      <div
        v-else-if="layer.key === 'far'"
        class="ground ground--far"
        :style="{ '--prof': `${GROUND_PROFILE.far}px` }"
        aria-hidden="true"
      />

      <template v-else-if="layer.key === 'mid'">
        <!--
          The launch tower, in boxes rather than art, and painted BEFORE
          the ridge on purpose.

          It stood on a number — 108px up from the pane's bottom — and the
          ridge line under it is not at 108px. It is wherever the clip-path
          polygon happens to be at the tower's x, which at this width is
          about 60px, so the mast hung in mid-air with daylight under its
          legs. Ordering it behind the ridge instead means the ridge's own
          fill buries the feet at whatever height the profile actually is,
          at every viewport width, with no number to keep in sync.
        -->
        <div class="tower" aria-hidden="true">
          <i class="tower__mast" />
          <i class="tower__mast tower__mast--back" />
          <i class="tower__arm tower__arm--1" />
          <i class="tower__arm tower__arm--2" />
          <i class="tower__arm tower__arm--3" />
          <i class="tower__lamp" />
        </div>

        <div
          class="ground ground--mid"
          :style="{ '--prof': `${GROUND_PROFILE.mid}px` }"
          aria-hidden="true"
        />
      </template>

      <template v-else-if="layer.key === 'near'">
        <div
          class="ground ground--near"
          :style="{ '--prof': `${GROUND_PROFILE.near}px` }"
          aria-hidden="true"
        />
        <!-- Ignition. On the nearest ground layer so it leaves with the
             pad rather than hanging in the sky after the vehicle has
             gone — which is what it did when it lived on the sky itself. -->
        <div class="flare" :style="flareStyle" aria-hidden="true" />
      </template>

      <!-- z7 — the rocket. Negative depth: it climbs while everything
           else descends, and the closing speed is the launch. The sway is
           on the inner wrapper and the recede on the slot, so no two
           animations ever compete for one transform. -->
      <div v-else-if="layer.key === 'rocket'" class="rocket-slot" :style="rocketStyle">
        <motion.div v-bind="rocketSway" class="rocket-sway">
          <RocketSvg :trail-style="trailStyle" />
        </motion.div>
      </div>

      <!-- z8 — the copy and the statement sequence, on the pane's own
           vertical centre. -->
      <div v-else-if="layer.key === 'copy'" class="copy-layer">
        <slot />
      </div>
    </div>
  </template>
</template>

<style lang="scss" scoped>
/*
  One rule for every layer. The overscan is derived from the layer's own
  depth and the pin and applied to ONE edge — the one it travels towards —
  so a layer is never larger than the art it actually needs. The near
  ridge at depth 1.18 was a 340dvh composited box when both edges got it;
  it is 236dvh now, and none of that is wasted below the frame.
*/
.layer {
  position: absolute;
  inset: calc(-1 * var(--over-top)) 0 calc(-1 * var(--over-bot));
  pointer-events: none;
  will-change: transform;
  backface-visibility: hidden;
}

@keyframes layer-travel {
  from {
    transform: translate3d(0, 0, 0) scale(1, 1);
  }

  to {
    transform:
      translate3d(var(--drift-x), calc(var(--depth) * var(--pin)), 0)
      scale(var(--zoom-x), var(--zoom-y));
  }
}

/*
  PRIMARY PATH. The browser drives this off the scroll position: no JS, no
  listener, no per-frame measurement, and it holds its rate while the main
  thread is busy. 0 → the pin's length is exactly the window the JS
  fallback reads, so the two agree frame for frame.
*/
@supports (animation-timeline: scroll()) {
  .layer {
    animation: layer-travel linear both;
    animation-timeline: scroll(root block);
    animation-range-start: 0;
    animation-range-end: var(--pin);
  }
}

.nebula {
  background:
    radial-gradient(58% 28% at 22% 60%, rgb(96 122 196 / 20%) 0%, rgb(96 122 196 / 0%) 70%),
    radial-gradient(46% 22% at 78% 50%, rgb(206 132 84 / 18%) 0%, rgb(206 132 84 / 0%) 72%),
    radial-gradient(34% 18% at 54% 22%, rgb(148 108 196 / 14%) 0%, rgb(148 108 196 / 0%) 74%);
}

// ── Ground ───────────────────────────────────────────────────────────────
// The three ground layers all descend, so the box needs no room below the
// profile: it leaves the frame downwards and never brings its own bottom
// edge back in. `--over-bot` is 0 for all three, and the expression is
// kept anyway so a layer whose sign is ever flipped gets its fill back.
// Two pseudo-elements rather than one element plus one pseudo, because
// clip-path clips an element's pseudo-elements too: a fill declared on the
// clipped box's ::after would be clipped away with it.
.ground {
  position: absolute;
  inset: auto -2% 0;
  height: calc(var(--over-bot) + var(--prof));

  &::before,
  &::after {
    content: '';
    position: absolute;
    right: 0;
    left: 0;
    background: var(--fill);
  }

  &::before {
    top: 0;
    height: var(--prof);
    clip-path: var(--profile);
  }

  &::after {
    top: var(--prof);
    bottom: 0;
  }
}

.ground--far {
  --fill: #0d1017;
  --profile: polygon(
    0 100%, 0 62%, 7% 48%, 14% 58%, 22% 34%, 31% 52%, 39% 40%, 48% 56%,
    57% 30%, 66% 50%, 74% 38%, 83% 54%, 91% 44%, 100% 60%, 100% 100%
  );
}

.ground--mid {
  --fill: #090b10;
  --profile: polygon(
    0 100%, 0 74%, 9% 60%, 19% 72%, 28% 50%, 38% 68%, 47% 58%, 56% 74%,
    64% 54%, 73% 70%, 82% 60%, 92% 76%, 100% 66%, 100% 100%
  );
}

.ground--near {
  --fill: #020203;
  --profile: polygon(
    0 100%, 0 82%, 12% 70%, 24% 84%, 35% 66%, 47% 80%, 58% 68%, 70% 84%,
    81% 72%, 92% 86%, 100% 76%, 100% 100%
  );
}

// ── Ignition ─────────────────────────────────────────────────────────────
// A wash on the pad rather than a light with a shape: the vehicle is 60px
// wide at this size and a rendered flame would be four pixels of detail
// nobody can see. What reads is the ground being lit from below.
.flare {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 46vh;
  background:
    radial-gradient(42% 100% at 50% 100%, rgb(255 214 140 / 34%) 0%, rgb(241 171 60 / 0%) 70%),
    radial-gradient(78% 62% at 50% 100%, rgb(241 171 60 / 20%) 0%, rgb(241 171 60 / 0%) 72%);
  opacity: 0.18;

  @media (width >= 768px) {
    right: 0;
    left: 34%;
  }
}

@supports (animation-timeline: scroll()) {
  .flare {
    animation: flare-up linear both;
    animation-timeline: scroll(root block);
    animation-range-start: 0;
    animation-range-end: var(--pin);
  }
}

@keyframes flare-up {
  0% {
    opacity: 0.18;
  }

  /* Ignition is quick; being left behind is not. */
  22% {
    opacity: 1;
  }

  72%,
  100% {
    opacity: 0;
  }
}

// ── Tower ────────────────────────────────────────────────────────────────
// Deep enough that the ridge in front of it always covers the feet — the
// profile dips to roughly 40px above the pane's bottom at its lowest, so
// anything at or below that is buried at every width.
.tower {
  position: absolute;
  bottom: calc(var(--over-bot) + 14px);
  left: clamp(6%, 12vw, 18%);
  width: 96px;
  height: clamp(210px, 32vh, 360px);

  i {
    display: block;
    position: absolute;
    background: #14171f;
  }
}

.tower__mast {
  top: 0;
  left: 30px;
  width: 7px;
  height: 100%;
}

.tower__mast--back {
  top: 8%;
  left: 56px;
  width: 5px;
  height: 92%;
}

.tower__arm {
  left: 22px;
  width: 52px;
  height: 4px;
}

.tower__arm--1 {
  top: 20%;
}

.tower__arm--2 {
  top: 44%;
}

.tower__arm--3 {
  top: 68%;
}

// The one live thing in the landscape, and the only gold in it.
.tower__lamp {
  top: -6px;
  left: 28px;
  width: 11px;
  height: 11px;
  border-radius: 50%;
  background: var(--gold-500);
  box-shadow: 0 0 14px 3px var(--gold-dim);
  animation: tower-beacon 2.6s var(--ease-inout) infinite;
}

@keyframes tower-beacon {
  0%,
  100% {
    opacity: 0.35;
  }

  50% {
    opacity: 1;
  }
}

// ── Rocket ───────────────────────────────────────────────────────────────

/*
  Off the copy's axis at every width, and low enough to be on the pad.

  Under md it used to be dead centre, which was fine while the copy was
  anchored to the top of the pane and stopped being fine the moment the
  copy moved to the middle: on a 390px screen the CTA row landed straight
  across the airframe. It now sits in the right margin on mobile too —
  the copy is centred text, so its right edge is the emptiest part of the
  frame — and rides lower, where the ridges read as the ground it is
  standing on rather than scenery behind it.
*/
.rocket-slot {
  position: absolute;
  right: 5%;
  bottom: calc(var(--over-bot) + clamp(30px, 5vh, 110px));
  width: clamp(46px, 13vw, 96px);
  transform-origin: 50% 100%;

  @media (width >= 768px) {
    right: clamp(3%, 8vw, 12%);
    bottom: calc(var(--over-bot) + clamp(56px, 9vh, 110px));
    width: clamp(58px, 6.5vw, 96px);
  }
}

/*
  The recede, on the slot rather than on the layer.

  Distance is the second half of the launch: without it the vehicle simply
  slides off the top at constant size, which reads as a sprite on a rail.
  It is a separate element from the travelling layer and from the swaying
  wrapper, so all three transforms compose instead of overwriting each
  other.
*/
@supports (animation-timeline: scroll()) {
  .rocket-slot {
    animation: rocket-recede linear both;
    animation-timeline: scroll(root block);
    animation-range-start: 0;
    animation-range-end: var(--pin);
  }
}

@keyframes rocket-recede {
  from {
    scale: 1;
  }

  to {
    scale: 0.45;
  }
}

// ── Copy ─────────────────────────────────────────────────────────────────
// The pane's own vertical centre, not a measured drop from the top: the
// headline's height changes with the type scale and the viewport's middle
// does not, so the block lands in the same place at every width — and in
// the same place the statement sequence takes over from it.
.copy-layer {
  display: grid;
  position: absolute;
  inset: var(--over-top) 0 var(--over-bot);
  align-content: center;
  pointer-events: auto;
}

@media (prefers-reduced-motion: reduce) {
  .layer {
    animation: none;
    transform: none !important;
  }

  .rocket-slot {
    animation: none;
    scale: 1;
  }

  .flare {
    animation: none;
    opacity: 0.5;
  }

  .tower__lamp {
    animation: none;
    opacity: 0.8;
  }
}
</style>
