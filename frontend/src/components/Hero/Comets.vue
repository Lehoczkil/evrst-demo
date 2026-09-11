<script setup lang="ts">
/*
  Three streaks crossing the sky, on a long loop.

  The one thing in the hero that happens on its own clock rather than on
  the reader's scroll — which is the point of it. A parallax stack only
  moves while someone is scrolling, so a reader who lands and does not
  touch anything sees a still image; the tower beacon and these are what
  keep the frame alive in that first few seconds.

  Cheap by construction: each comet is ONE element, a gradient with no
  image behind it, animating `transform` and `opacity` only. The duty
  cycle does the rest — a comet is visible for ~14% of its period, so the
  three of them together are painting for about half a second in every
  nine, and never more than one at a time.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*
  Start position, travel and clock per comet. Spread across the sky and
  deliberately not on a common divisor, so the three never fall into a
  pattern the eye can pick up.
*/
const COMETS = [
  { top: '11%', left: '4%', travel: '58vw', period: 13, delay: 2.4, len: 150, tilt: 22 },
  { top: '27%', left: '46%', travel: '44vw', period: 19, delay: 8.5, len: 110, tilt: 17 },
  { top: '6%', left: '62%', travel: '36vw', period: 24, delay: 15, len: 90, tilt: 28 },
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="comets absolute inset-0" aria-hidden="true">
    <i
      v-for="(comet, i) in COMETS"
      :key="i"
      class="comet"
      :style="{
        top: comet.top,
        left: comet.left,
        width: `${comet.len}px`,
        '--travel': comet.travel,
        '--tilt': `${comet.tilt}deg`,
        animationDuration: `${comet.period}s`,
        animationDelay: `${comet.delay}s`,
      }"
    />
  </div>
</template>

<style lang="scss" scoped>
.comets {
  pointer-events: none;
}

/*
  The head is the bright end. A linear-gradient rather than a dot plus a
  tail: one paint, one element, and the taper is exactly what a gradient
  is for.
*/
.comet {
  position: absolute;
  height: 1.5px;
  border-radius: 2px;
  background: linear-gradient(90deg, rgb(255 255 255 / 0%) 0%, rgb(247 195 104 / 75%) 72%, #fff 100%);
  animation-name: comet-run;
  animation-timing-function: cubic-bezier(0.3, 0, 0.1, 1);
  animation-iteration-count: infinite;
  opacity: 0;
}

@keyframes comet-run {
  0% {
    opacity: 0;
    transform: rotate(var(--tilt)) translate3d(0, 0, 0) scaleX(0.2);
  }

  3% {
    opacity: 0.9;
  }

  12% {
    opacity: 0.9;
    transform: rotate(var(--tilt)) translate3d(var(--travel), 0, 0) scaleX(1);
  }

  /* Off before the next one is due; the rest of the period is dark sky. */
  16%,
  100% {
    opacity: 0;
    transform: rotate(var(--tilt)) translate3d(var(--travel), 0, 0) scaleX(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .comet {
    animation: none;
  }
}
</style>
