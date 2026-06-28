<script setup lang="ts">
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
  <div
    aria-hidden="true"
    class="fixed inset-0 z-[1000] pointer-events-none mix-blend-overlay opacity-50"
  >
    <svg width="100%" height="100%">
      <filter id="evrst-noise-filter">
        <feTurbulence type="fractalNoise" baseFrequency="1" numOctaves="4" stitchTiles="stitch" />
        <feColorMatrix type="saturate" values="0" />
        <feComponentTransfer>
          <feFuncR type="linear" slope="0.46" />
          <feFuncG type="linear" slope="0.46" />
          <feFuncB type="linear" slope="0.46" />
          <feFuncA type="linear" slope="0.08" />
        </feComponentTransfer>
        <feComponentTransfer>
          <feFuncR type="linear" slope="1.47" intercept="-0.23" />
          <feFuncG type="linear" slope="1.47" intercept="-0.23" />
          <feFuncB type="linear" slope="1.47" intercept="-0.23" />
        </feComponentTransfer>
      </filter>
      <rect width="100%" height="100%" filter="url(#evrst-noise-filter)" />
    </svg>
  </div>
</template>

<style lang="scss" scoped></style>
