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
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <footer class="bg-bgDark">
    <div
      class="container flex flex-col gap-16px py-16px border-t border-cardBorder sm:(flex-row items-center justify-between)"
    >
      <span class="text-[var(--color-dimmed)] fs-11px">
        © {{ year }} Escape Velocity Rocketry Student Team. Build: v{{ version }}
      </span>
    </div>
  </footer>
</template>

<style lang="scss" scoped></style>
