<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
withDefaults(
  defineProps<{
    height?: string;
  }>(),
  { height: '80px' },
);
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
  <RouterLink to="/" class="evrst-logo">
    <img src="/evrst_logo.svg" :style="{ height }" alt="EVRST" />
  </RouterLink>
</template>

<style lang="scss" scoped>
.evrst-logo {
  display: inline-flex;

  img {
    width: auto;
    transition: height 200ms ease, margin 200ms ease;
  }
}
</style>
