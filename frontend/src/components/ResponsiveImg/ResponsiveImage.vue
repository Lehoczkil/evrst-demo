<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { src, alt = '', loading = 'lazy', aspectRatio } = defineProps<{
  src: string;
  alt?: string;
  loading?: 'lazy' | 'eager';
  aspectRatio?: string;
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
const style = computed(() => (aspectRatio ? { aspectRatio } : undefined));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <img :src="src" :alt="alt" :loading="loading" :style="style" />
</template>

<style lang="scss" scoped>
img {
  display: block;
  width: 100%;
  height: auto;
}
</style>
