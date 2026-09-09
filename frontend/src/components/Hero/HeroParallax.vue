<script setup lang="ts">
import { motion } from 'motion-v';
import { CSS_SCROLL_DRIVEN } from '@/composables/useParallax';
import { rocketSway } from './anims';
import { GROUND_PROFILE, LAYERS, travelFor } from './layers';

/*
  The hero's seven layers.

  Every one of them is CSS or inline SVG: the sky is a pair of radial
  gradients, the ridges are clip-path on a solid fill, the tower is four
  boxes, the starfield is one generated tile and the rocket is a few
  hundred bytes of path data. **Zero image requests**, which is how the
  plan's ≤120 KB hero-art budget is met with room to spare — an earlier
  draft budgeted ~27 KB of SVG silhouettes for ridges that clip-path draws
  for nothing, with no file to fetch and no decode.

  Geometry comes out of layers.ts and is stated once. See §5 of
  docs/frontend-redesign.md for why the stage is pinned rather than tall.
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
 * A layer's own custom properties. `--depth` is a bare number so it can
 * multiply a length inside calc(); `--dabs` is its magnitude, so the
 * overscan formula does not have to care about the sign.
 */
const varsFor = (depth: number) => ({
  '--depth': String(depth),
  '--dabs': String(Math.abs(depth)),
});

/**
 * The fallback's transform. Undefined on the CSS path, so nothing is
 * bound and the keyframe owns the transform outright — an inline style
 * here would win the fight and freeze the layer.
 */
const travelFor_ = (depth: number) => {
  if (CSS_SCROLL_DRIVEN) {
    return undefined;
  }

  return { transform: `translate3d(0, ${travelFor(depth, pin * progress).toFixed(1)}px, 0)` };
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const visible = computed(() => LAYERS.filter((layer) => layer.mobile || !isMobile.value));

/** The trail's ramp: the rocket visibly lights up as it leaves. */
const trailStyle = computed(() => (CSS_SCROLL_DRIVEN
  ? undefined
  : {
      opacity: String(0.18 + progress * 0.77),
      transform: `scaleY(${(0.5 + progress * 0.5).toFixed(3)})`,
    }));

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
    <div class="layer" :style="{ ...varsFor(layer.depth), ...travelFor_(layer.depth) }">
      <!-- z1 — the starfield. -->
      <Starfield v-if="layer.key === 'stars'" />

      <!-- z2 — the nebula. Its softness is baked into the gradient stops:
           never a filter: blur() on a scrolling layer, which re-rasterises
           every frame (and WebKit rasterises filters at 1x). -->
      <div v-else-if="layer.key === 'nebula'" class="nebula absolute inset-0" aria-hidden="true" />

      <!-- z3/z4/z6 — ground. Two boxes: the clipped profile, and solid
           fill below it. One box with nothing underneath was the bug — a
           negative-depth layer travels up and brings its own bottom edge
           into frame as a hard band. -->
      <div
        v-else-if="layer.key === 'far'"
        class="ground ground--far"
        :style="{ '--prof': `${GROUND_PROFILE.far}px` }"
        aria-hidden="true"
      />

      <template v-else-if="layer.key === 'mid'">
        <div
          class="ground ground--mid"
          :style="{ '--prof': `${GROUND_PROFILE.mid}px` }"
          aria-hidden="true"
        />
        <!-- The launch tower, in boxes rather than art. It stands on the
             mid ridge so it travels with it and stays planted. -->
        <div class="tower" aria-hidden="true">
          <i class="tower__mast" />
          <i class="tower__mast tower__mast--back" />
          <i class="tower__arm tower__arm--1" />
          <i class="tower__arm tower__arm--2" />
          <i class="tower__arm tower__arm--3" />
          <i class="tower__lamp" />
        </div>
      </template>

      <div
        v-else-if="layer.key === 'near'"
        class="ground ground--near"
        :style="{ '--prof': `${GROUND_PROFILE.near}px` }"
        aria-hidden="true"
      />

      <!-- z5 — the rocket. Negative depth: it leaves faster than the page
           does, which is the launch. The sway is on the inner wrapper so
           it never competes with the travel for one transform. -->
      <div v-else-if="layer.key === 'rocket'" class="rocket-slot">
        <motion.div v-bind="rocketSway" class="rocket-sway">
          <RocketSvg :trail-style="trailStyle" />
        </motion.div>
      </div>

      <!-- z7 — the copy and the statement sequence. -->
      <div v-else-if="layer.key === 'copy'" class="copy-layer">
        <slot />
      </div>
    </div>
  </template>
</template>

<style lang="scss" scoped>
/*
  One rule for every layer. `--overscan` is derived from the layer's own
  depth and the pin, so the extra art beyond the frame is never eyeballed:
  a layer travelling depth × pin needs exactly that much, and gets it on
  both edges so the sign does not matter.
*/
.layer {
  --overscan: calc(var(--dabs) * var(--pin));

  position: absolute;
  inset: calc(-1 * var(--overscan)) 0;
  pointer-events: none;
  will-change: transform;
  backface-visibility: hidden;
}

@keyframes layer-travel {
  from {
    transform: translateY(0);
  }

  to {
    transform: translateY(calc(var(--depth) * var(--pin)));
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
    radial-gradient(58% 28% at 22% 60%, rgb(96 122 196 / 16%) 0%, rgb(96 122 196 / 0%) 70%),
    radial-gradient(46% 22% at 78% 50%, rgb(206 132 84 / 14%) 0%, rgb(206 132 84 / 0%) 72%);
}

// ── Ground ───────────────────────────────────────────────────────────────
// Height is exactly `overscan + profile`, which is what puts the profile's
// base on the pane's bottom edge at rest AND keeps opaque fill under it
// however far the layer travels, in either direction.
// Two pseudo-elements rather than one element plus one pseudo, because
// clip-path clips an element's pseudo-elements too: a fill declared on the
// clipped box's ::after would be clipped away with it.
.ground {
  position: absolute;
  inset: auto -2% 0;
  height: calc(var(--overscan) + var(--prof));

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

// ── Tower ────────────────────────────────────────────────────────────────
.tower {
  position: absolute;
  bottom: calc(var(--overscan) + 108px);
  left: clamp(6%, 12vw, 18%);
  width: 96px;
  height: clamp(150px, 26vh, 280px);

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
// Under md the copy owns the upper half, so the rocket sits low and
// centred beneath it. From md it moves out to the right margin, clear of
// the centred headline's widest line at every width — dead centre put it
// straight through the type.
.rocket-slot {
  position: absolute;
  bottom: calc(var(--overscan) + clamp(56px, 9vh, 110px));
  left: 50%;
  width: clamp(58px, 6.5vw, 96px);
  translate: -50% 0;

  @media (width >= 768px) {
    right: clamp(3%, 8vw, 12%);
    left: auto;
    translate: none;
  }
}

// ── Copy ─────────────────────────────────────────────────────────────────
.copy-layer {
  position: absolute;
  inset: var(--overscan) 0 auto 0;
  pointer-events: auto;
}

@media (prefers-reduced-motion: reduce) {
  .layer {
    animation: none;
    transform: none !important;
  }

  .tower__lamp {
    animation: none;
    opacity: 0.8;
  }
}
</style>
