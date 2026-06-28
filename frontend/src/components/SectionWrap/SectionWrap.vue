<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  bg = 'dark',
  withGridOverlay = false,
} = defineProps<{
  bg?: 'dark' | 'gray';
  withGridOverlay?: boolean;
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
const bgClass = computed(() => (bg === 'dark' ? 'bg-bgDark' : 'bg-bgGray'));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div
    class="relative py-40px"
    :class="[bgClass, withGridOverlay ? 'overflow-visible' : 'overflow-hidden']"
  >
    <div
      v-if="withGridOverlay"
      aria-hidden="true"
      class="absolute left-0 right-0 top-1/2 h-[calc(50%+80px)] bg-no-repeat bg-cover bg-center pointer-events-none opacity-40 z-0"
      :style="{ backgroundImage: 'url(/grid.svg)' }"
    />
    <div class="relative z-1 container">
      <slot />
    </div>
  </div>
</template>

<style lang="scss" scoped></style>
