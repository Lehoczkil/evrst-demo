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
  <footer class="footer">
    <div class="container footer__inner">
      <span class="footer__copy">
        © {{ year }} Escape Velocity Rocketry Student Team. Build: v{{ version }}
      </span>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.footer {
  &__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 16px;
    padding-bottom: 16px;
    border-top: 1px solid var(--card-border);

    @include media-down(sm) {
      flex-direction: column;
    }
  }

  &__copy {
    color: var(--color-dimmed);
    font-size: 11px;
  }
}
</style>
