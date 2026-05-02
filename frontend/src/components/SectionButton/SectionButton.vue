<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
withDefaults(
  defineProps<{
    to?: string;
    href?: string;
    type?: 'button' | 'submit';
    disabled?: boolean;
    target?: string;
  }>(),
  { type: 'button' },
);
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <RouterLink v-if="to" :to="to" class="section-button">
    <slot name="left" />
    <span class="section-button__label"><slot /></span>
    <slot name="right" />
  </RouterLink>
  <a v-else-if="href" :href="href" :target="target" class="section-button">
    <slot name="left" />
    <span class="section-button__label"><slot /></span>
    <slot name="right" />
  </a>
  <button v-else :type="type" :disabled="disabled" class="section-button">
    <slot name="left" />
    <span class="section-button__label"><slot /></span>
    <slot name="right" />
  </button>
</template>

<style lang="scss" scoped>
.section-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 20px;
  height: 36px;
  background: transparent;
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
  border-radius: 999px;
  font-size: 14px;
  text-transform: uppercase;
  text-decoration: none;
  cursor: pointer;
  transition: background-color 150ms ease, color 150ms ease;

  &:hover:not(:disabled) {
    background-color: var(--color-primary);
    color: var(--bg-dark);
  }

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
}
</style>
