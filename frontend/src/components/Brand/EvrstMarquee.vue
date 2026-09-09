<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const words = 'Escape Velocity Rocketry Student Team ×'.split(' ');
const looped = [...words, ...words, ...words];
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
    class="relative overflow-hidden h-32px bg-primary text-bgDark uppercase select-none pointer-events-none"
  >
    <div class="marquee-track flex items-center gap-32px h-full whitespace-nowrap will-change-transform">
      <span
        v-for="(word, i) in looped"
        :key="`${word}-${i}`"
        class="font-[var(--font-family-headline)] fs-14px lh-1"
      >
        {{ word }}
      </span>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@keyframes marquee-scroll {
  from {
    transform: translateX(0);
  }

  to {
    transform: translateX(-33.3333%);
  }
}

.marquee-track {
  animation: marquee-scroll 24s linear infinite;
  padding-inline: 16px;
}

/*
  A marquee is motion with no resting state, so under reduced motion it
  stops — and stopping a `translateX(-50%)` loop mid-way would show the
  seam, so it holds at its start instead.
*/
@media (prefers-reduced-motion: reduce) {
  .marquee-track,
  :deep(.marquee-track) {
    animation: none;
    transform: none;
  }
}
</style>
