<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const year = new Date().getFullYear();
const version = __APP_VERSION__;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <footer>
    <div
      class="container flex flex-col sm:flex-row sm:items-center sm:justify-between gap-16px py-16px border-t border-cardBorder"
    >
      <span class="text-[var(--color-dimmed)] fs-11px">
        © {{ year }} Escape Velocity Rocketry Student Team. Build: v{{ version }}
      </span>
    </div>
  </footer>
</template>

<style lang="scss" scoped></style>
