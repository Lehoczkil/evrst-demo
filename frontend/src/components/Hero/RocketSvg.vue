<script setup lang="ts">
/*
  The hero's rocket, inline so its parts are addressable.

  Inline rather than an <img> for the same reason the wordmark is: the
  exhaust flickers, the trail's opacity ramps with scroll, and neither is
  reachable through a file reference. It is also the cheapest art on the
  page — a few hundred bytes of path data against a raster of any size.

  The parts, by id:
    #trail    long gold gradient, opacity + scaleY driven by scroll
    #exhaust  the flame, CSS keyframes on scaleY/opacity
  and by class, the airframe, its brand band, the porthole and the fins.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { trailStyle = undefined } = defineProps<{
  /**
   * The plume's scroll ramp, from the JS fallback path. Undefined where
   * the browser drives it from CSS — binding a style there would win the
   * fight against the keyframe and freeze the trail.
   */
  trailStyle?: Record<string, string>;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*
  Unique per instance: two rockets on one page would otherwise share
  gradient ids, and the second would paint with the first's stops.
*/
const uid = useId();
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
  <svg
    class="rocket-svg block w-full h-auto"
    viewBox="0 0 120 400"
    role="img"
    :aria-label="$t('hero.rocketAlt')"
  >
    <defs>
      <linearGradient :id="`body-${uid}`" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" stop-color="#cfcdc7" />
        <stop offset=".42" stop-color="#f8f7f4" />
        <stop offset="1" stop-color="#9c9a95" />
      </linearGradient>
      <linearGradient :id="`trail-${uid}`" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#f7c368" stop-opacity=".85" />
        <stop offset="1" stop-color="#f1ab3c" stop-opacity="0" />
      </linearGradient>
    </defs>

    <!-- The plume, lit by scroll progress. -->
    <path
      id="trail"
      d="M52 258 L68 258 L62 400 L58 400 Z"
      :fill="`url(#trail-${uid})`"
      :style="trailStyle"
    />

    <g id="exhaust">
      <path d="M60 260 C50 288 45 306 60 326 C75 306 70 288 60 260 Z" fill="#f7c368" />
      <path d="M60 264 C54 286 51 300 60 314 C69 300 66 286 60 264 Z" fill="#fff6e2" />
    </g>

    <!-- Fins, the far one darker so the airframe reads as round. -->
    <path d="M36 198 L12 254 L36 240 Z" fill="#8e8c87" />
    <path d="M84 198 L108 254 L84 240 Z" fill="#b6b4ae" />

    <path d="M42 234 L78 234 L84 258 L36 258 Z" fill="#4a4d55" />

    <path
      d="M60 6 C74 40 84 78 84 120 L84 236 L36 236 L36 120 C36 78 46 40 60 6 Z"
      :fill="`url(#body-${uid})`"
    />

    <!-- The brand band on the airframe: the one place the accent appears
         on the vehicle itself. -->
    <rect x="36" y="152" width="48" height="15" fill="#f1ab3c" />
    <rect x="36" y="176" width="48" height="3" fill="#c6871f" />

    <circle cx="60" cy="128" r="12" fill="#0b0c0e" />
    <circle cx="60" cy="128" r="12" fill="none" stroke="#f1ab3c" stroke-width="2.5" />
    <circle cx="56" cy="124" r="3.4" fill="#3c4048" />
  </svg>
</template>

<style lang="scss" scoped>
/*
  The flame, on CSS keyframes rather than motion-v: it has to keep
  flickering while the scroll animation owns the parent's transform, and
  it is GPU-only work on an element that never changes size.

  `transform-box: fill-box` makes the origin resolve against the group's
  own box instead of the SVG's viewBox.
*/
#exhaust {
  transform-box: fill-box;
  transform-origin: top center;
  animation: rocket-flame 0.16s steps(2, end) infinite alternate;
}

@keyframes rocket-flame {
  from {
    opacity: 0.92;
    transform: scaleY(0.86) scaleX(1.06);
  }

  to {
    opacity: 1;
    transform: scaleY(1.12) scaleX(0.94);
  }
}

#trail {
  transform-box: fill-box;
  transform-origin: top center;
  opacity: 0.22;
}

/*
  The plume lights up as the rocket climbs. Scroll-driven like the layers
  themselves, over the same 0 → pin window.
*/
@supports (animation-timeline: scroll()) {
  #trail {
    animation: rocket-trail linear both;
    animation-timeline: scroll(root block);
    animation-range-start: 0;
    animation-range-end: var(--pin);
  }
}

@keyframes rocket-trail {
  from {
    opacity: 0.18;
    transform: scaleY(0.5);
  }

  to {
    opacity: 0.95;
    transform: scaleY(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  #exhaust,
  #trail {
    animation: none;
  }

  #trail {
    opacity: 0.5;
  }
}
</style>
