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
const baseClass = [
  'inline-flex items-center gap-8px h-36px px-20px',
  'border border-primary rounded-full',
  'text-primary bg-transparent fs-14px uppercase no-underline cursor-pointer',
  'transition-[background-color,color,transform] duration-150',
  'hover:not-disabled:(text-bgDark bg-primary)',
  'disabled:(opacity-50 cursor-not-allowed)',
].join(' ');
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
  <RouterLink v-if="to" :to="to" :class="baseClass">
    <slot name="left" />
    <span><slot /></span>
    <slot name="right" />
  </RouterLink>
  <a v-else-if="href" :href="href" :target="target" :class="baseClass">
    <slot name="left" />
    <span><slot /></span>
    <slot name="right" />
  </a>
  <button v-else :type="type" :disabled="disabled" :class="baseClass">
    <slot name="left" />
    <span><slot /></span>
    <slot name="right" />
  </button>
</template>

<style lang="scss" scoped></style>
