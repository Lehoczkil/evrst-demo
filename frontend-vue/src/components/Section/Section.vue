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
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 32px;

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
    font-size: clamp(28px, 6vw, 40px);
    line-height: 1;
    text-transform: uppercase;
    font-weight: 500;
    background: linear-gradient(to right, transparent, var(--color-primary));
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
  }

  &__title {
    font-size: clamp(28px, 6vw, 40px);
    text-transform: uppercase;
    line-height: 1;
    margin: 0;
    font-family: var(--font-family-headline);
  }
}
</style>
