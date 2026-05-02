<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const titleLines = ['Escape', 'Velocity', 'Rocketry', 'Student', 'Team'];

const rocketInfo = computed(() => [
  { labelKey: 'rocket.height', value: '95 cm' },
  { labelKey: 'rocket.diameter', value: '7.65 cm' },
  { labelKey: 'rocket.thrust', value: '700 N' },
  { labelKey: 'rocket.mass', value: '3.5 kg' },
]);
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="hero">
    <div class="container hero__inner">
      <div class="hero__title-col">
        <h1 class="hero__title">
          <span v-for="word in titleLines" :key="word" class="hero__title-line">
            {{ word }}
          </span>
        </h1>
      </div>
      <div class="hero__info-col">
        <div v-for="item in rocketInfo" :key="item.labelKey" class="hero__info-row">
          <hr class="hero__divider" />
          <div class="hero__info-card">
            <div class="hero__info-value">{{ item.value }}</div>
            <div class="hero__info-label">{{ t(item.labelKey) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.hero {
  width: 100%;
  height: 100vh;
  padding: 16px;

  @include media-up(sm) {
    padding: 32px;
  }

  &__inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 32px;
    height: 100%;
    flex-direction: column;
    padding-top: 80px;

    @include media-up(md) {
      flex-direction: row;
      align-items: center;
      padding-top: 0;
    }
  }

  &__title-col {
    width: 100%;

    @include media-up(md) {
      flex: 0 0 50%;
    }
  }

  &__title {
    display: flex;
    align-items: flex-start;
    margin: 0;
    color: var(--color-primary);
    font-size: clamp(1.125rem, 5.5vw, 3rem);
    line-height: 1.16;
    font-family: var(--font-family-headline);
    flex-direction: column;
    text-transform: uppercase;
  }

  &__title-line {
    padding-right: 16px;
  }

  &__info-col {
    display: none;
    width: 100%;
    max-width: 420px;
    flex-direction: column;
    gap: 12px;

    @include media-up(md) {
      display: flex;
      flex-basis: 40%;
    }
  }

  &__info-row {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  &__divider {
    flex-grow: 1;
    border: 0;
    border-top: 1px solid var(--card-border);
  }

  &__info-card {
    width: 160px;
    padding: 12px 16px;
    border: 1px solid var(--card-border);
    border-radius: 4px;
    background: var(--card-bg);
    text-align: center;
  }

  &__info-value {
    font-weight: 500;
    font-size: 16px;
  }

  &__info-label {
    color: var(--color-dimmed);
    font-size: 12px;
    text-transform: uppercase;
  }
}
</style>
