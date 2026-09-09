<script setup lang="ts">
/*
  A loading placeholder at the real row geometry.

  Not a spinner: a spinner says "something is happening" and then the page
  reflows around whatever arrives. A skeleton at the actual row height and
  count says what is coming and reserves its space, so nothing moves when
  the data lands.

  Never a full-page loader either — the hero, every heading and all static
  copy are already painted by the time any query resolves.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { rows = 3, height = '48px', gap = '1px' } = defineProps<{
  /** How many rows the real content will have. */
  rows?: number;
  height?: string;
  gap?: string;
}>();
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
  <div class="skeleton" :style="{ gap }" aria-hidden="true">
    <span v-for="i in rows" :key="i" class="skeleton__row" :style="{ height }" />
  </div>
</template>

<style lang="scss" scoped>
.skeleton {
  display: grid;
}

.skeleton__row {
  display: block;
  background: var(--ink-2);
  animation: skeleton-breathe 1.6s ease-in-out infinite;

  .paper & {
    background: var(--paper-2);
  }
}

// A slow breath rather than a sweeping shimmer: a shimmer animates a
// gradient's position, which repaints the row every frame.
@keyframes skeleton-breathe {
  0%,
  100% {
    opacity: 0.5;
  }

  50% {
    opacity: 1;
  }
}

@media (prefers-reduced-motion: reduce) {
  .skeleton__row {
    animation: none;
    opacity: 0.7;
  }
}
</style>
