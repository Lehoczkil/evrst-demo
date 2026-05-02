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
  <section :id="id" class="section">
    <div class="section__head">
      <div class="section__title-row">
        <span class="section__number">{{ number }}</span>
        <h2 class="section__title">{{ title }}</h2>
      </div>
      <div v-if="$slots.right" class="section__right">
        <slot name="right" />
      </div>
    </div>
    <div class="section__body">
      <slot />
    </div>
  </section>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.section {
  &__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    gap: 16px;

    @include media-down(sm) {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  &__title-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
  }

  &__number {
    color: transparent;
    background: linear-gradient(to right, transparent, var(--color-primary));
    font-weight: 500;
    font-size: clamp(28px, 6vw, 40px);
    line-height: 1;
    text-transform: uppercase;
    background-clip: text;
    background-clip: text;
  }

  &__title {
    margin: 0;
    font-size: clamp(28px, 6vw, 40px);
    line-height: 1;
    font-family: var(--font-family-headline);
    text-transform: uppercase;
  }
}
</style>
