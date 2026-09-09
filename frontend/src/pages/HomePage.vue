<script setup lang="ts">
/*
  The home page, in the order of docs/frontend-redesign.md §3.

  One continuous ink ground with exactly one inverted paper band (Team +
  Mentors) and one raised band's worth of contrast in the Rocket section's
  starfield. The old diagonal dividers, the grid overlay and the global
  noise filter are gone: rhythm and hairlines carry the structure now, and
  three surfaces are enough.

  The marquee is the only loud element, placed as the transition into the
  join CTA — and it doubles as a live test of the chrome, since the pill
  crosses a full-bleed gold band there and has to invert.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
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
  <div>
    <Hero />
    <Manifesto />
    <Rocket />
    <Programme />
    <Events />
    <Team />
    <Sponsors />
    <EvrstMarquee />
    <JoinCta />
  </div>
</template>

<style lang="scss" scoped></style>
