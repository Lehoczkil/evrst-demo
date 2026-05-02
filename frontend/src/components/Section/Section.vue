<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const props = defineProps<{
  id: string;
  index: number;
  title: string;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const number = computed(() => `0${props.index + 1}`.slice(-2));
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <section :id="id">
    <div
      class="flex flex-col items-start sm:flex-row sm:items-center sm:justify-between gap-16px mb-32px"
    >
      <div class="flex items-end gap-8px">
        <span
          class="section__number fs-[clamp(28px,6vw,40px)] lh-1 uppercase font-500"
        >
          {{ number }}
        </span>
        <h2
          class="m-0 fs-[clamp(28px,6vw,40px)] lh-1 uppercase font-[var(--font-family-headline)]"
        >
          {{ title }}
        </h2>
      </div>
      <div v-if="$slots.right">
        <slot name="right" />
      </div>
    </div>
    <div>
      <slot />
    </div>
  </section>
</template>

<style lang="scss" scoped>
.section__number {
  color: transparent;
  background: linear-gradient(to right, transparent, var(--color-primary));
  background-clip: text;
}
</style>
